#!/usr/bin/env bash
set -euo pipefail

PKG="tel4vn-nginx-config"
VER="${1:-1.0-1}"
ARCH="amd64"                       # config package -> all
OUTDIR="${PWD}/out"
SRC_DIR="${PWD}/nginx-config"

# required inputs
[ -f "$SRC_DIR/nginx.conf" ] || { echo "ERROR: thiáº¿u $SRC_DIR/nginx.conf"; exit 1; }
[ -f "$SRC_DIR/conf.d/fusionpbx.conf" ] || { echo "ERROR: thiáº¿u $SRC_DIR/conf.d/fusionpbx.conf"; exit 1; }

WORK="$(mktemp -d)"
mkdir -p "$OUTDIR" "$WORK/DEBIAN"

# Ship templates vÃ o /usr/share Ä‘á»ƒ trÃ¡nh conflict vá»›i nginx-common
mkdir -p "$WORK/usr/share/$PKG/etc/nginx/conf.d"
install -m 0644 "$SRC_DIR/nginx.conf" "$WORK/usr/share/$PKG/etc/nginx/nginx.conf"
install -m 0644 "$SRC_DIR/conf.d/fusionpbx.conf" "$WORK/usr/share/$PKG/etc/nginx/conf.d/fusionpbx.conf"

# Control: Section + Depends rÃµ rÃ ng (Ä‘á»ƒ build ISO khÃ´ng bá»‹ miss/uninstallable)
cat > "$WORK/DEBIAN/control" <<EOF
Package: $PKG
Version: $VER
Section: web
Priority: optional
Architecture: $ARCH
Maintainer: You <you@example.com>
Depends: nginx
Description: Tel4VN Nginx config (force overwrite nginx.conf and fusionpbx.conf)
EOF

# postinst: force overwrite vÃ o /etc/nginx + test + reload
cat > "$WORK/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e

PKG="tel4vn-nginx-config"
SRC="/usr/share/$PKG/etc/nginx"

mkdir -p /etc/nginx/conf.d

# Force overwrite configs
install -m 0644 "$SRC/nginx.conf" /etc/nginx/nginx.conf
install -m 0644 "$SRC/conf.d/fusionpbx.conf" /etc/nginx/conf.d/fusionpbx.conf

# Test & reload nginx
if command -v nginx >/dev/null 2>&1; then
  if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx >/dev/null 2>&1 || true
  else
    echo "WARNING: nginx -t failed; not reloading nginx" >&2
  fi
fi

exit 0
EOF
chmod 0755 "$WORK/DEBIAN/postinst"

# Optional: prerm Ä‘á»ƒ reload náº¿u remove (khÃ´ng báº¯t buá»™c)
cat > "$WORK/DEBIAN/prerm" <<'EOF'
#!/bin/sh
set -e
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/prerm"

dpkg-deb --build "$WORK" "$OUTDIR/${PKG}_${VER}_${ARCH}.deb"

echo "OK: $OUTDIR/${PKG}_${VER}_${ARCH}.deb"
echo "Check content:"
dpkg-deb -c "$OUTDIR/${PKG}_${VER}_${ARCH}.deb" | grep -E "usr/share/$PKG/etc/nginx"
