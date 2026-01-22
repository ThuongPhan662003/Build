# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/php-conf/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/php-conf/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/php-conf/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/php-conf/DEBIAN/postrm   2>/dev/null || true
#
dpkg-deb --build services/php-conf
