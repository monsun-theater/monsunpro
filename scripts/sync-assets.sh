#!/usr/bin/env bash

# Load .env file
set -a; [ -f .env ] && source .env; set +a

set -euo pipefail

LFTP_BIN="$HOME/bin/lftp"
LFTP_URL="https://github.com/userdocs/lftp-static/releases/download/4.9.3/lftp-amd64"

LOCAL_DIR="${1:-public/assets}"
mkdir -p "$LOCAL_DIR"

: "${ASSETS_SFTP_HOST:?Need ASSETS_SFTP_HOST}"
: "${ASSETS_SFTP_USERNAME:?Need ASSETS_SFTP_USERNAME}"
PORT="${ASSETS_SFTP_PORT:-22}"

if [[ ! -x "$LFTP_BIN" ]]; then
  echo "Downloading static lftp binary from $LFTP_URL to $LFTP_BIN..."
  mkdir -p "$(dirname "$LFTP_BIN")"
  curl -L "$LFTP_URL" -o "$LFTP_BIN"
  chmod +x "$LFTP_BIN"
fi

if [[ -n "${ASSETS_SFTP_PASSWORD:-}" ]]; then
  URL="sftp://${ASSETS_SFTP_USERNAME}:${ASSETS_SFTP_PASSWORD}@${ASSETS_SFTP_HOST}:${PORT}/"
else
  URL="sftp://${ASSETS_SFTP_USERNAME}@${ASSETS_SFTP_HOST}:${PORT}/"
fi

# Mirror backup to here
"$LFTP_BIN" -e "set sftp:auto-confirm yes; set net:max-retries 1; set net:persist-retries 0; \
       mirror \
         --verbose \
         --only-newer \
         --parallel=5 \
         --no-perms \
         / \"$LOCAL_DIR\"; \
  quit" "$URL"

# Mirror here to backup
"$LFTP_BIN" -e "set sftp:auto-confirm yes; set net:max-retries 1; set net:persist-retries 0; \
       mirror \
         --verbose \
         --only-newer \
         --parallel=5 \
         --reverse \
         --no-perms \
         \"$LOCAL_DIR\" /; \
  quit" "$URL"
