# Grocy on Railway: the LinuxServer.io image (the one Grocy's own README points to),
# pinned by version and digest, plus a small boot step for Railway.
# To update: bump the tag and digest below and add a CHANGELOG.md entry.
FROM linuxserver/grocy:v4.7.1-ls352@sha256:ef5ea5094aa2ed32df74528c4bdd94713679038c97add02045d881550c2a59a8

# s6 oneshot "init-railway" runs after the image's own init and before nginx/php-fpm start:
# nginx on $PORT, a /healthz route, and the first admin from GROCY_ADMIN_PASSWORD.
COPY root/ /

RUN chmod +x /etc/s6-overlay/s6-rc.d/init-railway/run \
 && php -l /app/railway/bootstrap.php \
 && php -l /app/railway/healthz.php

# No VOLUME line: Railway's builder rejects it. Mount a Railway volume at /config.
