# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/freeswitch-conf/DEBIAN/postinst 2>/dev/null || true

#
dpkg-deb --build services/freeswitch-conf
