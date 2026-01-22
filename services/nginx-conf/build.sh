# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/nginx-conf/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/nginx-conf/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/nginx-conf/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/nginx-conf/DEBIAN/postrm   2>/dev/null || true
###
dpkg-deb --build services/nginx-conf