#!/usr/bin/env bash
#
# backup.sh — export the WordPress database on the Hostinger server and pull a
# copy down locally. Run this before every deploy.
#
# Server: dumps to ~/backups/surtilec-<date>.sql, keeps only the newest 5.
# Local:  downloads the same dump into ./backups/ (gitignored).
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/ghostwhite-cormorant-218810.hostingersite.com/public_html"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCAL_DIR="$REPO_ROOT/backups"

STAMP="$(date +%Y%m%d-%H%M%S)"
FILENAME="surtilec-$STAMP.sql"
REMOTE_FILE="\$HOME/backups/$FILENAME"

echo "==> Exporting database on server..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "mkdir -p \$HOME/backups && cd \"$REMOTE_WP\" && wp db export \"$REMOTE_FILE\""

echo "==> Rotating server backups (keep newest 5)..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "ls -t \$HOME/backups/surtilec-*.sql 2>/dev/null | tail -n +6 | xargs -r rm -f"

echo "==> Downloading copy to $LOCAL_DIR/ ..."
mkdir -p "$LOCAL_DIR"
scp -P "$SSH_PORT" "$SSH_USER@$SSH_HOST:backups/$FILENAME" "$LOCAL_DIR/$FILENAME"

echo "==> Done. Backup: $LOCAL_DIR/$FILENAME"
