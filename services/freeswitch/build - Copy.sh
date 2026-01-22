#!/usr/bin/env bash
set -euo pipefail

FS_PREFIX="${FS_PREFIX:-/usr/local/freeswitch}"
PKGNAME="tel4vn-freeswitch-local"
VERSION="${VERSION:-1.10.12-1}"
ARCH="amd64"

OUTDIR="$PWD/out"
WORKDIR="$PWD/build-fs-local"
STAGE="$WORKDIR/stage"
DEBIAN_DIR="$STAGE/DEBIAN"

SERVICE_SRC="/etc/systemd/system/freeswitch.service"
DEFAULT_SRC="/etc/default/freeswitch"

# FIX: correct SONAME is libtpl.so.0 (not libtpt1.so.0)
# Only bundle truly "local" libs if needed. Usually libspandsp should be installed via Depends.
EXTRA_SONAMES=(
  "libtpl.so.0"
)

[[ -d "$FS_PREFIX" ]] || { echo "ERROR: missing $FS_PREFIX"; exit 1; }

# rsync + dpkg-dev only; no need to apt-get update every time
sudo apt-get install -y rsync dpkg-dev >/dev/null

rm -rf "$WORKDIR" "$OUTDIR"
mkdir -p "$DEBIAN_DIR" "$OUTDIR"

# 1) Copy full /usr/local/freeswitch
mkdir -p "$STAGE$FS_PREFIX"
sudo rsync -aHAX --numeric-ids "$FS_PREFIX/" "$STAGE$FS_PREFIX/"

# 2) Bundle extra libs into /usr/local/lib (ONLY if found)
copy_soname() {
  local soname="$1"
  local found=0

  for base in /usr/local/lib /usr/local/lib64 /usr/lib /usr/lib64 /lib /lib64; do
    [[ -d "$base" ]] || continue

    # copy real files
    while IFS= read -r f; do
      found=1
      mkdir -p "$STAGE/usr/local/lib"
      sudo rsync -aHAX --numeric-ids "$f" "$STAGE/usr/local/lib/"
    done < <(find "$base" -maxdepth 2 -type f -name "${soname}*" 2>/dev/null || true)

    # copy symlinks as symlinks
    while IFS= read -r l; do
      found=1
      mkdir -p "$STAGE/usr/local/lib"
      rel="$(readlink "$l" || true)"
      if [[ -n "$rel" ]]; then
        ln -sf "$rel" "$STAGE/usr/local/lib/$(basename "$l")"
      fi
    done < <(find "$base" -maxdepth 2 -type l -name "${soname}*" 2>/dev/null || true)
  done

  if [[ "$found" -eq 0 ]]; then
    echo "WARNING: could not find ${soname} on build machine to ship into package" >&2
  else
    echo "INFO: shipped ${soname} into package"
  fi
}

for s in "${EXTRA_SONAMES[@]}"; do
  copy_soname "$s"
done
bundle_one_lib() {
  local src="$1"          # full path to real file
  local soname="$2"       # e.g. libspandsp.so.3
  local destdir="$3"      # e.g. /usr/lib/x86_64-linux-gnu

  mkdir -p "$STAGE$destdir"
  sudo rsync -aHAX --numeric-ids "$src" "$STAGE$destdir/"
  # ensure SONAME symlink exists inside package
  ( cd "$STAGE$destdir" && ln -sf "$(basename "$src")" "$soname" )
}

# Bundle spandsp from build machine into correct system dir:
REAL_SPANDSP="$(readlink -f /lib/x86_64-linux-gnu/libspandsp.so.3)"
bundle_one_lib "$REAL_SPANDSP" "libspandsp.so.3" "/usr/lib/x86_64-linux-gnu"
echo "INFO: bundled libspandsp.so.3 into package (system path)"
# Ensure loader knows /usr/local/lib + freeswitch lib dir
mkdir -p "$STAGE/etc/ld.so.conf.d"
cat > "$STAGE/etc/ld.so.conf.d/tel4vn-local.conf" <<EOF
/usr/local/lib
/usr/local/lib64
EOF

cat > "$STAGE/etc/ld.so.conf.d/freeswitch.conf" <<EOF
${FS_PREFIX}/lib
EOF

# 3) Convenience symlinks
mkdir -p "$STAGE/usr/bin"
ln -sf "$FS_PREFIX/bin/fs_cli" "$STAGE/usr/bin/fs_cli"
ln -sf "$FS_PREFIX/bin/freeswitch" "$STAGE/usr/bin/freeswitch"

# 4) systemd unit
mkdir -p "$STAGE/etc/systemd/system"
if [[ -f "$SERVICE_SRC" ]]; then
  sudo cp -a "$SERVICE_SRC" "$STAGE/etc/systemd/system/freeswitch.service"
else
  cat > "$STAGE/etc/systemd/system/freeswitch.service" <<'UNIT'
[Unit]
Description=freeswitch
After=syslog.target network.target local-fs.target postgresql.service haveged.service

[Service]
Type=forking
PIDFile=/usr/local/freeswitch/run/freeswitch.pid
Environment="DAEMON_OPTS=-nonat"
EnvironmentFile=-/etc/default/freeswitch
ExecStart=/usr/local/freeswitch/bin/freeswitch -u www-data -g www-data -ncwait $DAEMON_OPTS
TimeoutSec=45s
Restart=always
User=root
Group=daemon
UMask=0007

[Install]
WantedBy=multi-user.target
UNIT
fi

# 5) /etc/default/freeswitch
mkdir -p "$STAGE/etc/default"
if [[ -f "$DEFAULT_SRC" ]]; then
  sudo cp -a "$DEFAULT_SRC" "$STAGE/etc/default/freeswitch"
else
  cat > "$STAGE/etc/default/freeswitch" <<'DEF'
FS_USER="www-data"
FS_GROUP="www-data"
DAEMON_OPTS="-nonat -ncwait -u www-data -g www-data"
DEF
fi

# 6) control
# IMPORTANT: add libtpl0 + libspandsp3 as real Debian runtime deps
cat > "$DEBIAN_DIR/control" <<EOF
Package: ${PKGNAME}
Version: ${VERSION}
Section: admin
Priority: optional
Architecture: ${ARCH}
Maintainer: Tel4VN <admin@local>
Depends: systemd, adduser, libtpl0,  libtiff6, libjpeg62-turbo, libodbc2, unixodbc, libspeex1, libspeexdsp1, libsofia-sip-ua0
Description: FreeSWITCH runtime under ${FS_PREFIX} + systemd unit (offline install; bundles libtpl if needed)
EOF

# 7) postinst: permissions + ldconfig + enable service (NO restart)
cat > "$DEBIAN_DIR/postinst" <<'POST'
#!/bin/sh
set -e

mkdir -p /var/log/freeswitch || true
mkdir -p /usr/local/freeswitch/run || true

chown -R www-data:www-data /usr/local/freeswitch /var/log/freeswitch 2>/dev/null || true
find /usr/local/freeswitch -type d -exec chmod 2770 {} \; 2>/dev/null || true

ldconfig >/dev/null 2>&1 || true

systemctl daemon-reload || true
systemctl unmask freeswitch.service || true
systemctl enable freeswitch.service || true

exit 0
POST
chmod 0755 "$DEBIAN_DIR/postinst"

cat > "$DEBIAN_DIR/prerm" <<'PRERM'
#!/bin/sh
set -e
systemctl stop freeswitch.service >/dev/null 2>&1 || true
exit 0
PRERM
chmod 0755 "$DEBIAN_DIR/prerm"

dpkg-deb --build --root-owner-group "$STAGE" "$OUTDIR/${PKGNAME}_${VERSION}_${ARCH}.deb"
echo "OK: $OUTDIR/${PKGNAME}_${VERSION}_${ARCH}.deb"
