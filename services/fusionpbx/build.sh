APP=/tmp/debpkg/fusionpbx-web
rm -rf "$APP"
mkdir -p "$APP/DEBIAN" "$APP/var/www"

# lấy source đúng như bạn dùng (tel4vn/fusionpbx branch 4.4)
rm -rf /tmp/fusionpbx-src
git clone -b 4.4 https://github.com/tel4vn/fusionpbx /tmp/fusionpbx-src

cp -a /tmp/fusionpbx-src "$APP/var/www/fusionpbx"

cat > "$APP/DEBIAN/postinst" <<'EOF'
#!/bin/sh
set -e
chown -R www-data:www-data /var/www/fusionpbx
chmod -R 755 /var/www/fusionpbx/secure || true
EOF
chmod 0755 "$APP/DEBIAN/postinst"

cat > "$APP/DEBIAN/control" <<'EOF'
Package: fusionpbx-web
Version: 4.4-1
Section: web
Priority: optional
Architecture: amd64
Depends: php7.2-fpm, php7.2-cli, nginx
Maintainer: tel4vn <devnull@local>
Description: FusionPBX 4.4 web application installed to /var/www/fusionpbx
EOF

dpkg-deb --build "$APP" "/tmp/fusionpbx-web_4.4-1_all.deb"
