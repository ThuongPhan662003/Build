#!/usr/bin/env bash
set -euo pipefail
##
PKG="tel4vn-postgresql12-config"
VER="${1:-1.0-1}"
ARCH="$(dpkg --print-architecture)"
OUTDIR="${PWD}/out"
WORK="$(mktemp -d)"

mkdir -p "$OUTDIR" \
  "$WORK/DEBIAN" \
  "$WORK/usr/local/sbin" \
  "$WORK/etc/tel4vn" \
  "$WORK/etc/systemd/system" \
  "$WORK/var/lib/tel4vn"

trap 'rm -rf "$WORK"' EXIT

# ----------------------------
# control
# ----------------------------
cat > "$WORK/DEBIAN/control" <<EOF
Package: ${PKG}
Version: ${VER}
Section: database
Priority: optional
Architecture: ${ARCH}
Maintainer: Tel4VN <support@example.com>
Depends: bash, coreutils, sudo, systemd, postgresql-12, postgresql-client-12
Description: First-boot PostgreSQL 12 setup for FusionPBX/FreeSWITCH (db/role/grant + patch pg_hba IPv4 localhost to trust)
EOF

cat > "$WORK/DEBIAN/conffiles" <<EOF
/etc/tel4vn/postgresql12.env
EOF

# ----------------------------
# Runtime env (editable)
# ----------------------------
cat > "$WORK/etc/tel4vn/postgresql12.env" <<'EOF'
PGVER="12"
CLUSTER="main"

DB_FUSIONPBX="fusionpbx"
DB_FREESWITCH="freeswitch"

ROLE_FUSIONPBX="fusionpbx"
ROLE_FREESWITCH="freeswitch"

ROLE_PASSWORD="Tel4vn.com##2023"
ROLE_FUSIONPBX_PASSWORD=""
ROLE_FREESWITCH_PASSWORD=""

# Only patch this exact line in pg_hba.conf:
# host all all 127.0.0.1/32 <METHOD>   -> trust
PATCH_PG_HBA_IPV4_TRUST="1"

# Backup then remove FusionPBX config if you want
REMOVE_FUSIONPBX_CONFIG="1"
EOF
chmod 0644 "$WORK/etc/tel4vn/postgresql12.env"

# ----------------------------
# systemd oneshot (runs after boot)
# ----------------------------
cat > "$WORK/etc/systemd/system/tel4vn-postgresql12-setup.service" <<'EOF'
[Unit]
Description=Tel4VN: setup PostgreSQL12 (first boot)
After=postgresql.service
Wants=postgresql.service
ConditionPathExists=!/var/lib/tel4vn/postgresql12-setup.done

[Service]
Type=oneshot
ExecStart=/usr/local/sbin/tel4vn-setup-postgresql12
Restart=on-failure
RestartSec=3
StartLimitIntervalSec=60
StartLimitBurst=20

[Install]
WantedBy=multi-user.target
EOF
chmod 0644 "$WORK/etc/systemd/system/tel4vn-postgresql12-setup.service"

# ----------------------------
# Helper script (runs AFTER boot)
# ----------------------------
cat > "$WORK/usr/local/sbin/tel4vn-setup-postgresql12" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

LOG="/var/log/tel4vn-postgresql12-config.log"
ENV_FILE="/etc/tel4vn/postgresql12.env"
DONE_FLAG="/var/lib/tel4vn/postgresql12-setup.done"

mkdir -p /var/lib/tel4vn || true
touch "$LOG" 2>/dev/null || true

log() { echo "[$(date -Is)] $*" | tee -a "$LOG" >/dev/null; }

sql_escape() { local s="${1//\'/\'\'}"; printf "%s" "$s"; }
valid_ident() { [[ "$1" =~ ^[A-Za-z0-9_]+$ ]]; }

# TCP to avoid socket timing issues
psql_pg() { sudo -u postgres psql -h 127.0.0.1 -p 5432 -v ON_ERROR_STOP=1 -qAtc "$1"; }

wait_ready() {
  systemctl enable postgresql >/dev/null 2>&1 || true
  systemctl start postgresql  >/dev/null 2>&1 || true
  if command -v pg_ctlcluster >/dev/null 2>&1; then
    pg_ctlcluster "$PGVER" "$CLUSTER" start >/dev/null 2>&1 || true
  fi

  for _ in $(seq 1 30); do
    if sudo -u postgres pg_isready -h 127.0.0.1 -p 5432 >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  log "[ERROR] Postgres not ready on 127.0.0.1:5432"
  return 1
}

patch_pg_hba_ipv4_trust() {
  [[ "${PATCH_PG_HBA_IPV4_TRUST:-0}" == "1" ]] || return 0

  local CONF_DIR="/etc/postgresql/${PGVER}/${CLUSTER}"
  local PG_HBA="${CONF_DIR}/pg_hba.conf"

  if [[ ! -f "$PG_HBA" ]]; then
    log "[WARN] $PG_HBA not found; skip patch"
    return 0
  fi

  cp -a "$PG_HBA" "${PG_HBA}.bak.$(date +%F_%H%M%S)" || true

  # Replace ONLY the method on the 127.0.0.1/32 line:
  # host all all 127.0.0.1/32 <anything>  -> trust
  if grep -qE '^[[:space:]]*host[[:space:]]+all[[:space:]]+all[[:space:]]+127\.0\.0\.1/32[[:space:]]+' "$PG_HBA"; then
    sed -i -E \
      's@^([[:space:]]*host[[:space:]]+all[[:space:]]+all[[:space:]]+127\.0\.0\.1/32[[:space:]]+).*$@\1trust@' \
      "$PG_HBA"
    log "[INFO] patched method to trust for 127.0.0.1/32 in $PG_HBA"
  else
    # If the line doesn't exist, append it (still only affects that one rule)
    echo 'host    all             all             127.0.0.1/32            trust' >> "$PG_HBA"
    log "[INFO] appended trust line for 127.0.0.1/32 to $PG_HBA"
  fi

  # reload/restart to apply
  if command -v pg_ctlcluster >/dev/null 2>&1; then
    pg_ctlcluster "$PGVER" "$CLUSTER" reload >/dev/null 2>&1 || pg_ctlcluster "$PGVER" "$CLUSTER" restart >/dev/null 2>&1 || true
  else
    systemctl reload postgresql >/dev/null 2>&1 || systemctl restart postgresql >/dev/null 2>&1 || true
  fi
}

create_db_roles() {
  for v in DB_FUSIONPBX DB_FREESWITCH ROLE_FUSIONPBX ROLE_FREESWITCH; do
    local val="${!v}"
    if ! valid_ident "$val"; then
      log "[ERROR] invalid identifier $v='$val' (allowed: A-Za-z0-9_)"
      return 1
    fi
  done

  local pw_f="${ROLE_FUSIONPBX_PASSWORD:-}"
  local pw_s="${ROLE_FREESWITCH_PASSWORD:-}"
  [[ -n "$pw_f" ]] || pw_f="${ROLE_PASSWORD:-ChangeMeStrong#2026}"
  [[ -n "$pw_s" ]] || pw_s="${ROLE_PASSWORD:-ChangeMeStrong#2026}"

  local pw_f_esc pw_s_esc
  pw_f_esc="$(sql_escape "$pw_f")"
  pw_s_esc="$(sql_escape "$pw_s")"

  role_exists() { psql_pg "SELECT 1 FROM pg_roles WHERE rolname='${1}'" | grep -q 1; }
  db_exists()   { psql_pg "SELECT 1 FROM pg_database WHERE datname='${1}'" | grep -q 1; }

  if ! role_exists "$ROLE_FUSIONPBX"; then
    log "[INFO] create role $ROLE_FUSIONPBX"
    psql_pg "CREATE ROLE ${ROLE_FUSIONPBX} WITH LOGIN PASSWORD '${pw_f_esc}';"
  else
    log "[INFO] update password role $ROLE_FUSIONPBX"
    psql_pg "ALTER ROLE ${ROLE_FUSIONPBX} WITH PASSWORD '${pw_f_esc}';"
  fi

  if ! role_exists "$ROLE_FREESWITCH"; then
    log "[INFO] create role $ROLE_FREESWITCH"
    psql_pg "CREATE ROLE ${ROLE_FREESWITCH} WITH LOGIN PASSWORD '${pw_s_esc}';"
  else
    log "[INFO] update password role $ROLE_FREESWITCH"
    psql_pg "ALTER ROLE ${ROLE_FREESWITCH} WITH PASSWORD '${pw_s_esc}';"
  fi

  if ! db_exists "$DB_FUSIONPBX"; then
    log "[INFO] create database $DB_FUSIONPBX owner $ROLE_FUSIONPBX"
    psql_pg "CREATE DATABASE ${DB_FUSIONPBX} OWNER ${ROLE_FUSIONPBX};"
  else
    psql_pg "ALTER DATABASE ${DB_FUSIONPBX} OWNER TO ${ROLE_FUSIONPBX};" || true
  fi

  if ! db_exists "$DB_FREESWITCH"; then
    log "[INFO] create database $DB_FREESWITCH owner $ROLE_FREESWITCH"
    psql_pg "CREATE DATABASE ${DB_FREESWITCH} OWNER ${ROLE_FREESWITCH};"
  else
    psql_pg "ALTER DATABASE ${DB_FREESWITCH} OWNER TO ${ROLE_FREESWITCH};" || true
  fi

  psql_pg "GRANT ALL PRIVILEGES ON DATABASE ${DB_FUSIONPBX} TO ${ROLE_FUSIONPBX};" || true
  psql_pg "GRANT ALL PRIVILEGES ON DATABASE ${DB_FREESWITCH} TO ${ROLE_FREESWITCH};" || true

  log "[INFO] db/roles/grants ready"
}

remove_fusionpbx_config() {
  [[ "${REMOVE_FUSIONPBX_CONFIG:-0}" == "1" ]] || return 0

  if [[ -f /etc/fusionpbx/config.php ]]; then
    local bk="/etc/fusionpbx/config.php.bak.$(date +%F_%H%M%S)"
    mv /etc/fusionpbx/config.php "$bk" || true
    log "[INFO] moved /etc/fusionpbx/config.php -> $bk"
  fi

  if [[ -e /var/www/fusionpbx/resources/config.php ]]; then
    rm -f /var/www/fusionpbx/resources/config.php || true
    log "[INFO] removed /var/www/fusionpbx/resources/config.php"
  fi
}

main() {
  if [[ -f "$DONE_FLAG" ]]; then
    log "[INFO] already done"; exit 0
  fi

  if [[ -f "$ENV_FILE" ]]; then
    # shellcheck disable=SC1090
    source "$ENV_FILE"
  fi

  log "[INFO] start setup (PGVER=${PGVER}, CLUSTER=${CLUSTER})"

  # Ensure DB is up first (so later SQL works)
  wait_ready

  # Patch ONLY the 127.0.0.1/32 method to trust
  patch_pg_hba_ipv4_trust

  # Ensure ready again (in case reload/restart happened)
  wait_ready

  # Create DB/roles
  create_db_roles

  # Optional remove FusionPBX config
  remove_fusionpbx_config

  touch "$DONE_FLAG"
  systemctl disable --now tel4vn-postgresql12-setup.service >/dev/null 2>&1 || true

  log "[INFO] done"
}

main "$@"
EOF
chmod 0755 "$WORK/usr/local/sbin/tel4vn-setup-postgresql12"

# ----------------------------
# postinst/postrm
# ----------------------------
cat > "$WORK/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e
systemctl daemon-reload >/dev/null 2>&1 || true
systemctl enable tel4vn-postgresql12-setup.service >/dev/null 2>&1 || true
# Try start now (non-blocking-ish; service has retry)
systemctl start tel4vn-postgresql12-setup.service >/dev/null 2>&1 || true
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/postinst"

cat > "$WORK/DEBIAN/postrm" <<'EOF'
#!/bin/sh
set -e
systemctl disable --now tel4vn-postgresql12-setup.service >/dev/null 2>&1 || true
systemctl daemon-reload >/dev/null 2>&1 || true
exit 0
EOF
chmod 0755 "$WORK/DEBIAN/postrm"

# ----------------------------
# Build deb
# ----------------------------
dpkg-deb --build "$WORK" "$OUTDIR/${PKG}_${VER}_${ARCH}.deb"
echo "OK: $OUTDIR/${PKG}_${VER}_${ARCH}.deb"
