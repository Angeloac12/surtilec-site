#!/usr/bin/env bash
#
# import-family-images.sh — upload one visual family's image batch.
#
# One family at a time, on purpose: a bad mapping caught on a 26-product family
# is a footnote, the same mistake across all 1,068 is an outage.
#
# Usage:
#   bash scripts/import-family-images.sh --family=vntc-bandeja --dry-run
#   bash scripts/import-family-images.sh --family=vntc-bandeja --live
#   bash scripts/import-family-images.sh --family=vntc-bandeja --live --lote=20260811
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

MODE="dry"
FAMILY=""
BATCH="$(date +%Y%m%d)"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --family=*) FAMILY="${1#*=}"; shift ;;
    --lote=*)   BATCH="${1#*=}"; shift ;;
    --live)     MODE="live"; shift ;;
    --dry-run)  MODE="dry"; shift ;;
    *) echo "Argumento desconocido: $1" >&2; exit 2 ;;
  esac
done

[ -n "$FAMILY" ] || { echo "Falta --family=<slug>" >&2; exit 2; }

LOCAL_DIR="/private/tmp/surtilec-family-image-batch-$BATCH/$FAMILY"
REMOTE_DIR="import/surtilec-family-image-batch-$BATCH/$FAMILY"

echo "==> Construyendo el lote de '$FAMILY'..."
ruby "$REPO_ROOT/scripts/build-family-image-manifest.rb" "$FAMILY" "$BATCH"

[ -f "$LOCAL_DIR/manifest.csv" ] || { echo "No existe $LOCAL_DIR/manifest.csv" >&2; exit 2; }
[ -d "$LOCAL_DIR/images" ] || { echo "No existe $LOCAL_DIR/images" >&2; exit 2; }

if [ "$MODE" = "live" ]; then
  "$REPO_ROOT/scripts/backup.sh"
fi

echo "==> Subiendo el lote..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_DIR/images"
rsync -az --delete -e "ssh -p $SSH_PORT" "$LOCAL_DIR/" "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "==> Importando en modo $MODE..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_DIR/manifest.csv\" $MODE \"lote=$BATCH\"" \
  < "$REPO_ROOT/scripts/import-authorized-product-images.php"
