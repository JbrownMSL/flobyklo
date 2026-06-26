#!/bin/bash
# Deploy florabyklo-admin binks -> vader. Excludes writable (rsync -a clobbers its perms),
# then re-applies apache ownership/SELinux + runs migrations + reloads.
set -e
rsync -az --delete \
  --exclude '.git' --exclude '.env' --exclude 'writable' --exclude 'deploy.sh' \
  /home/jason/florabyklo-admin/ vader:/home/www/florabyklo-admin/
ssh vader 'sudo chown -R root:apache /home/www/florabyklo-admin && \
  sudo find /home/www/florabyklo-admin -type d -exec chmod 750 {} \; && \
  sudo find /home/www/florabyklo-admin -type f -exec chmod 640 {} \; && \
  sudo find /home/www/florabyklo-admin/public -type d -exec chmod 755 {} \; && \
  sudo chmod 755 /home/www/florabyklo-admin/public/index.php && \
  sudo chown -R root:apache /home/www/florabyklo-admin/writable && sudo chmod -R 2770 /home/www/florabyklo-admin/writable && \
  sudo chcon -R -t httpd_sys_content_t /home/www/florabyklo-admin 2>/dev/null; \
  sudo chcon -R -t httpd_sys_rw_content_t /home/www/florabyklo-admin/writable 2>/dev/null; \
  cd /home/www/florabyklo-admin && php spark migrate --all 2>&1 | tail -3; \
  sudo systemctl reload php-fpm'
echo "deploy done"
