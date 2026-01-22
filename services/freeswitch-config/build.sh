# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/freeswitch-config/DEBIAN/control   2>/dev/null || true
chmod 0755 services/freeswitch-config/DEBIAN/postinst 2>/dev/null || true
chmod 0755 services/freeswitch-config/DEBIAN/preinst  2>/dev/null || true
chmod 0755 services/freeswitch-config/DEBIAN/prerm    2>/dev/null || true
chmod 0755 services/freeswitch-config/DEBIAN/postrm   2>/dev/null || true
#
dpkg-deb --build services/freeswitch-conf
