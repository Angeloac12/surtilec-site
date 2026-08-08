#!/usr/bin/env bash
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"
REMOTE_IMPORT="import/surtilec-exact-image-batch-20260804"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCAL_DIR="/private/tmp/surtilec-exact-image-batch-20260804/verified-upload"
MODE="dry"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --live) MODE="live"; shift ;;
    --dry-run) MODE="dry"; shift ;;
    *) echo "Argumento desconocido: $1" >&2; exit 2 ;;
  esac
done

[ -f "$LOCAL_DIR/manifest.csv" ] || { echo "No existe $LOCAL_DIR/manifest.csv" >&2; exit 2; }
[ -d "$LOCAL_DIR/images" ] || { echo "No existe $LOCAL_DIR/images" >&2; exit 2; }

if [ "$MODE" = "live" ]; then
  "$REPO_ROOT/scripts/backup.sh"
fi

ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_IMPORT/images"
rsync -az --delete -e "ssh -p $SSH_PORT" "$LOCAL_DIR/" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/"
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_IMPORT/manifest.csv\" $MODE" \
  < "$REPO_ROOT/scripts/import-authorized-product-images.php"
