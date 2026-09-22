#!/bin/bash
# Deploy florabyklo-admin binks -> vader. Excludes writable (rsync -a clobbers its perms),
# then re-applies apache ownership/SELinux + runs migrations + reloads.
set -e
# 🔴 #2950 (2026-09-22): rsync --delete from this tree SILENTLY ERASED another lane's live work —
# they had edited vader directly, the next deploy from here did not have their files, and nothing
# warned. So: every deploy records a checksum manifest of what it shipped (writable/ survives the
# rsync), and the NEXT deploy refuses if the live tree no longer matches it — i.e. someone changed
# vader since. Merge their change into this repo first. FORCE=1 overrides, deliberately.
LIVE=/home/www/florabyklo-admin
SUMS='find . -path ./writable -prune -o -path ./.env -prune -o -type f -print0 | sort -z | xargs -0 sha1sum'
if [ "${FORCE:-0}" != 1 ]; then
  drift=$(ssh vader "cd $LIVE && [ -f writable/.deploy-manifest ] && { $SUMS | diff writable/.deploy-manifest - | grep -E '^[<>]' | awk '{print \$3}' | sort -u; } || true")
  if [ -n "$drift" ]; then
    echo "REFUSED: live files changed on vader since the last deploy (someone edited live):"
    echo "$drift" | head -40
    echo "Merge those changes into this repo, or re-run with FORCE=1 to overwrite them."
    exit 1
  fi
fi
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
ssh vader "cd $LIVE && $SUMS > writable/.deploy-manifest"
echo "deploy done"
