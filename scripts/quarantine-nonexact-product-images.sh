#!/usr/bin/env bash
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"
REMOTE_IMPORT="import/surtilec-authorized-image-batch-20260803"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MANIFEST_LOCAL="/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv"
MODE="dry"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --live) MODE="live"; shift ;;
    --dry-run) MODE="dry"; shift ;;
    --manifest) MANIFEST_LOCAL="$2"; shift 2 ;;
    *) echo "Argumento desconocido: $1" >&2; exit 2 ;;
  esac
done

[ -f "$MANIFEST_LOCAL" ] || { echo "No existe $MANIFEST_LOCAL" >&2; exit 2; }

if [ "$MODE" = "live" ]; then
  "$REPO_ROOT/scripts/backup.sh"
fi

ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_IMPORT"
rsync -az -e "ssh -p $SSH_PORT" "$MANIFEST_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/manifest.csv"
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_IMPORT/manifest.csv\" $MODE" \
  < "$REPO_ROOT/scripts/quarantine-nonexact-product-images.php"
