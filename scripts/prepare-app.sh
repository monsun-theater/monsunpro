#!/usr/bin/env bash

# Restore hidden files (needed for statamic git integration etc.)
ARCH=./project-archive.tar.gz && [ -f "$ARCH" ] && tar -xzf "$ARCH" -C . && rm -- "$ARCH"


# Reset cache and start app
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan statamic:stache:warm
php artisan statamic:search:update --all
php artisan statamic:static:clear
php artisan up

# Set up GIT
source scripts/set-up-git.sh
