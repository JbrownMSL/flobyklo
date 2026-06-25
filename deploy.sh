#!/bin/bash
# Deploy weekendtoolrentals binks -> vader. Excludes writable (rsync -a clobbers its perms),
# then re-applies apache ownership/SELinux + runs migrations + reloads.
set -e
rsync -az --delete \
  --exclude '.git' --exclude '.env' --exclude 'writable' --exclude 'deploy.sh' \
  /home/jason/weekendtoolrentals/ vader:/home/www/weekendtoolrentals/
ssh vader 'sudo chown -R root:apache /home/www/weekendtoolrentals && \
  sudo find /home/www/weekendtoolrentals -type d -exec chmod 750 {} \; && \
  sudo find /home/www/weekendtoolrentals -type f -exec chmod 640 {} \; && \
  sudo find /home/www/weekendtoolrentals/public -type d -exec chmod 755 {} \; && \
  sudo chmod 755 /home/www/weekendtoolrentals/public/index.php && \
  sudo chown -R root:apache /home/www/weekendtoolrentals/writable && sudo chmod -R 2770 /home/www/weekendtoolrentals/writable && \
  sudo chcon -R -t httpd_sys_content_t /home/www/weekendtoolrentals 2>/dev/null; \
  sudo chcon -R -t httpd_sys_rw_content_t /home/www/weekendtoolrentals/writable 2>/dev/null; \
  cd /home/www/weekendtoolrentals && php spark migrate --all 2>&1 | tail -3; \
  sudo systemctl reload php-fpm'
echo "deploy done"
