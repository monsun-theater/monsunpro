#!/usr/bin/env bash

# Read .env
set -a; [ -f .env ] && source .env; set +a

# Set up GIT
source scripts/set-up-git.sh

# Reset cache and start app
php artisan up
php artisan config:cache
php artisan route:cache
php artisan statamic:search:update --all
php artisan statamic:static:clear
php artisan statamic:stache:refresh


