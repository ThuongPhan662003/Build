#!/usr/bin/env bash
set -euo pipefail

PKG="tel4vn-nginx-ssl"
VER="${1:-1.0-1}"
ARCH="all"
OUTDIR="${PWD}/out"

WORK="$(mktemp -d)"
mkdir -p "$OUTDIR" "$WORK/DEBIAN" "$WORK/usr/local/sbin"

# control (KHONG comment '#')
cat > "$WORK/DEBIAN/control" <<EOF
Package: ${PKG}
Version: ${VER}
Section: web
Priority: optional
Architecture: ${ARCH}
Maintainer: Tel4VN <support@example.com>
Depends: bash, coreutils, ssl-cert
Description: Provide /etc/ssl/certs/nginx.crt and /etc/ssl/private/nginx.key using snakeoil cert
EOF

# helper script
cat > "$WORK/usr/local/sbin/tel4vn-setup-nginx-ssl" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

LOG="/var/log/tel4vn-nginx-ssl.log"

NGINX_CRT="/etc/ssl/certs/nginx.crt"
NGINX_KEY="/etc/ssl/private/nginx.key"

SNAKE_CRT="/etc/ssl/certs/ssl-cert-snakeoil.pem"
SNAKE_KEY="/etc/ssl/private/ssl-cert-snakeoil.key"

backup_if_real_file() {
  local p="$1"
  if [[ -e "$p" && ! -L "$p" ]]; then
    mv -f "$p" "${p}.bak.$(date +%Y%m%d%H%M%S)" || true
  fi
}

main() {
  touch "$LOG" 2>/dev/null || true

  {
    echo "[INFO] tel4vn nginx ssl setup start"

    if [[ ! -f "$SNAKE_CRT" || ! -f "$SNAKE_KEY" ]]; then
      echo "[WARN] snakeoil cert/key not found:"
      echo "       $SNAKE_CRT"
      echo "       $SNAKE_KEY"
      echo "       (ssl-cert may not be installed yet)."
      exit 0
    fi

    backup_if_real_file "$NGINX_CRT"
    backup_if_real_file "$NGINX_KEY"

    ln -sf "$SNAKE_KEY" "$NGINX_KEY"
    ln -sf "$SNAKE_CRT" "$NGINX_CRT"

    # permissions: keep default; nginx master (root) can read key on startup
    chmod 644 "$NGINX_CRT" 2>/dev/null || true

    echo "[INFO] Linked:"
    echo "       $NGINX_KEY -> $SNAKE_KEY"
    echo "       $NGINX_CRT -> $SNAKE_CRT"
    echo "[INFO] done"
  } >>"$LOG" 2>&1 || true

  exit 0
}

main "$@"
EOF
chmod 0755 "$WORK/usr/local/sbin/tel4vn-setup-nginx-ssl"

# postinst: KHONG lam fail ISO
cat > "$WORK/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e
/usr/local/sbin/tel4vn-setup-nginx-ssl || true
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/postinst"

cat > "$WORK/DEBIAN/prerm" <<'EOF'
#!/bin/sh
set -e
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/prerm"

dpkg-deb --build "$WORK" "$OUTDIR/${PKG}_${VER}_${ARCH}.deb"
echo "OK: $OUTDIR/${PKG}_${VER}_${ARCH}.deb"
