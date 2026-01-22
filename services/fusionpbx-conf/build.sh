# đảm bảo quyền executable cho maintainer scripts
chmod 0755 services/fusionpbx-conf/DEBIAN/postinst 2>/dev/null || true

#
dpkg-deb --build services/fusionpbx-conf
