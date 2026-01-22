# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/fusionpbx/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/fusionpbx/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/fusionpbx/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/fusionpbx/DEBIAN/postrm   2>/dev/null || true
##
dpkg-deb --build services/fusionpbx
