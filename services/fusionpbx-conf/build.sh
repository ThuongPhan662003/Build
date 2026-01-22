#!/usr/bin/env bash
set -euo pipefail

PKG="tel4vn-fusionpbx-dbimport"
VER="${1:-1.0-1}"
ARCH="all"
OUTDIR="${PWD}/out"

WORK="$(mktemp -d)"
mkdir -p "$OUTDIR" \
  "$WORK/DEBIAN" \
  "$WORK/usr/local/sbin" \
  "$WORK/etc/tel4vn"

# ===== control =====
cat > "$WORK/DEBIAN/control" <<'EOF'
Package: tel4vn-fusionpbx-dbimport
Version: __VER__
Section: admin
Priority: optional
Architecture: all
Maintainer: Tel4VN <support@example.com>
Depends: bash, sed, coreutils, php-cli | php7.2-cli | php8.2-cli, php-pgsql | php7.2-pgsql | php8.2-pgsql, postgresql-client | postgresql-client-12 | postgresql-client-15
Description: Tel4VN FusionPBX DB import + write /etc/fusionpbx/config.php
EOF
sed -i "s/__VER__/${VER}/" "$WORK/DEBIAN/control"

# ===== conffile =====
cat > "$WORK/etc/tel4vn/fusionpbx-db.conf" <<'EOF'
DB_TYPE="pgsql"
DB_HOST="127.0.0.1"
DB_PORT="5432"
DB_NAME="fusionpbx"
DB_USER="fusionpbx"
DB_PASS="ChangeMeStrong#2026"
FUSION_DIR="/var/www/fusionpbx"
RUN_SCHEMA="1"
RUN_DOMAINS="1"
EOF

cat > "$WORK/DEBIAN/conffiles" <<'EOF'
/etc/tel4vn/fusionpbx-db.conf
EOF

# ===== runner =====
cat > "$WORK/usr/local/sbin/tel4vn-fusionpbx-dbimport" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

CONF="/etc/tel4vn/fusionpbx-db.conf"
LOG="/var/log/tel4vn-fusionpbx-dbimport.log"

usage() {
  cat <<USAGE
Usage:
  tel4vn-fusionpbx-dbimport [--write-config] [--import] [--all]
Edit:
  ${CONF}
Logs:
  ${LOG}
USAGE
}

write_config() {
  # shellcheck disable=SC1090
  source "$CONF"

  mkdir -p /etc/fusionpbx
  chown -R www-data:www-data /etc/fusionpbx || true

  cat > /etc/fusionpbx/config.php <<PHP
<?php
\$database = new stdClass;
\$database->type = '${DB_TYPE}';
\$database->host = '${DB_HOST}';
\$database->port = '${DB_PORT}';
\$database->name = '${DB_NAME}';
\$database->username = '${DB_USER}';
\$database->password = '${DB_PASS}';
\$database->options = '';
?>
PHP

  chmod 0640 /etc/fusionpbx/config.php || true
  chown www-data:www-data /etc/fusionpbx/config.php || true
}

do_import() {
  # shellcheck disable=SC1090
  source "$CONF"

  if [[ ! -d "${FUSION_DIR}" ]]; then
    echo "[ERR] FusionPBX dir not found: ${FUSION_DIR}" | tee -a "$LOG"
    return 2
  fi

  if [[ "${RUN_SCHEMA:-1}" == "1" ]]; then
    echo "[INFO] Running upgrade_schema.php" | tee -a "$LOG"
    (cd "${FUSION_DIR}" && php "${FUSION_DIR}/core/upgrade/upgrade_schema.php") >>"$LOG" 2>&1
  fi

  if [[ "${RUN_DOMAINS:-1}" == "1" ]]; then
    echo "[INFO] Running upgrade_domains.php" | tee -a "$LOG"
    (cd "${FUSION_DIR}" && php "${FUSION_DIR}/core/upgrade/upgrade_domains.php") >>"$LOG" 2>&1
  fi
}

main() {
  [[ -f "$CONF" ]] || { echo "[ERR] Missing $CONF"; exit 1; }

  if [[ $# -eq 0 ]]; then usage; exit 0; fi

  local do_write=0 do_imp=0
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --write-config) do_write=1 ;;
      --import)       do_imp=1 ;;
      --all)          do_write=1; do_imp=1 ;;
      -h|--help)      usage; exit 0 ;;
      *) echo "Unknown arg: $1"; usage; exit 1 ;;
    esac
    shift
  done

  touch "$LOG" 2>/dev/null || true
  if [[ $do_write -eq 1 ]]; then write_config; fi
  if [[ $do_imp -eq 1 ]]; then do_import; fi
}

main "$@"
EOF
chmod 0755 "$WORK/usr/local/sbin/tel4vn-fusionpbx-dbimport"

# ===== postinst =====
cat > "$WORK/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e

LOG="/var/log/tel4vn-fusionpbx-dbimport.log"
touch "$LOG" 2>/dev/null || true

/usr/local/sbin/tel4vn-fusionpbx-dbimport --write-config >>"$LOG" 2>&1 || true
/usr/local/sbin/tel4vn-fusionpbx-dbimport --import >>"$LOG" 2>&1 || true

exit 0
EOF
chmod 0755 "$WORK/DEBIAN/postinst"

# ===== prerm =====
cat > "$WORK/DEBIAN/prerm" <<'EOF'
#!/bin/sh
set -e
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/prerm"

dpkg-deb --build "$WORK" "$OUTDIR/${PKG}_${VER}_${ARCH}.deb"

echo "OK: $OUTDIR/${PKG}_${VER}_${ARCH}.deb"
echo "Install:"
echo "  sudo dpkg -i $OUTDIR/${PKG}_${VER}_${ARCH}.deb"
echo "Edit conf:"
echo "  sudo nano /etc/tel4vn/fusionpbx-db.conf"
echo "Run:"
echo "  sudo tel4vn-fusionpbx-dbimport --all"
