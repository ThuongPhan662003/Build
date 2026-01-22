# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/freeswitch/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/freeswitch/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/freeswitch/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/freeswitch/DEBIAN/postrm   2>/dev/null || true
##
dpkg-deb --build services/freeswitch
