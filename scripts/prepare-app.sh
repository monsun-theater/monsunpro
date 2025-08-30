#!/usr/bin/env bash

# Read .env
set -a; [ -f .env ] && source .env; set +a

# Set up GIT
source scripts/set-up-git.sh

# create htaccess and replace %{DOCUMENT_ROOT} in public/.htaccess as it is misconfigured in deploy now
cp public/.htaccess.template public/.htaccess
sed -i 's|%{DOCUMENT_ROOT}|'"$HOME"'/public/|g' public/.htaccess

# Sync assets
source scripts/sync-assets.sh

# Reset cache and start app
php artisan up
php artisan config:cache
php artisan route:cache
php artisan statamic:search:update --all
php artisan statamic:static:clear
php artisan statamic:stache:clear
php artisan statamic:stache:refresh


# Warm static cache
python3 scripts/warm-static-cache.py



