#!/usr/bin/env bash
set -euo pipefail

FS_PREFIX="${FS_PREFIX:-/usr/local/freeswitch}"
PKGNAME="tel4vn-freeswitch-config"
VERSION="${VERSION:-1.0-1}"
ARCH="amd64"

OUTDIR="$PWD/out"
WORKDIR="$PWD/build-fs-config"
STAGE="$WORKDIR/stage"
DEBIAN_DIR="$STAGE/DEBIAN"

[[ -d "$FS_PREFIX" ]] || { echo "ERROR: missing $FS_PREFIX"; exit 1; }

rm -rf "$WORKDIR"
mkdir -p "$DEBIAN_DIR" "$OUTDIR"

# Chỉ copy conf + scripts (đúng “config package”)
mkdir -p "$STAGE/usr/local/freeswitch"
if [[ -d "$FS_PREFIX/conf" ]]; then
  mkdir -p "$STAGE$FS_PREFIX/conf"
  sudo rsync -aHAX --numeric-ids "$FS_PREFIX/conf/" "$STAGE$FS_PREFIX/conf/"
fi
if [[ -d "$FS_PREFIX/scripts" ]]; then
  mkdir -p "$STAGE$FS_PREFIX/scripts"
  sudo rsync -aHAX --numeric-ids "$FS_PREFIX/scripts/" "$STAGE$FS_PREFIX/scripts/"
fi

# Nếu bạn đang dùng /var/log/freeswitch và logfile.conf.xml đã trỏ về đó, giữ nguyên
mkdir -p "$STAGE/var/log/freeswitch"

# Đánh dấu conffiles để apt hỏi khi ghi đè
mkdir -p "$DEBIAN_DIR"
CONFF="$DEBIAN_DIR/conffiles"
: > "$CONFF"
if [[ -d "$STAGE$FS_PREFIX/conf" ]]; then
  find "$STAGE$FS_PREFIX/conf" -type f | sed "s#^$STAGE##" >> "$CONFF"
fi

cat > "$DEBIAN_DIR/control" <<EOF
Package: ${PKGNAME}
Version: ${VERSION}
Section: admin
Priority: optional
Architecture: ${ARCH}
Maintainer: Tel4VN <admin@local>
Depends: tel4vn-freeswitch-local
Description: FreeSWITCH configuration (conf + scripts) packaged from a running VM
EOF

cat > "$DEBIAN_DIR/postinst" <<'POST'
#!/bin/sh
set -e
# set owner/perms giống best practice khi chạy FS bằng www-data
chown -R www-data:www-data /usr/local/freeswitch 2>/dev/null || true
find /usr/local/freeswitch -type d -exec chmod 2770 {} \; 2>/dev/null || true
mkdir -p /var/log/freeswitch || true
chown -R www-data:www-data /var/log/freeswitch 2>/dev/null || true

mkdir -p /var/cache/fusionpbx || true
chown -R www-data:www-data /var/cache/fusionpbx 2>/dev/null || true

systemctl daemon-reload || true
systemctl restart freeswitch.service >/dev/null 2>&1 || true
exit 0
POST
chmod 0755 "$DEBIAN_DIR/postinst"

dpkg-deb --build --root-owner-group "$STAGE" "$OUTDIR/${PKGNAME}_${VERSION}_${ARCH}.deb"
echo "OK: $OUTDIR/${PKGNAME}_${VERSION}_${ARCH}.deb"
