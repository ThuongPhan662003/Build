# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/nginx-ssl/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/nginx-ssl/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/nginx-ssl/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/nginx-ssl/DEBIAN/postrm   2>/dev/null || true
###
dpkg-deb --build services/nginx-ssl
