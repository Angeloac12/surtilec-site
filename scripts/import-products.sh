#!/usr/bin/env bash
#
# import-products.sh — validate / import products from data/products-master.csv
# into WooCommerce on the Hostinger server.
#
# Usage:
#   scripts/import-products.sh --dry-run        # validate only, no DB writes
#   scripts/import-products.sh                  # backup + live import
#   scripts/import-products.sh --skip-backup    # live import without backup
#
# Live import ALWAYS backs up first (scripts/backup.sh) unless --skip-backup.
# Dry-run never touches the DB and never backs up.
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/ghostwhite-cormorant-218810.hostingersite.com/public_html"
REMOTE_IMPORT="import"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CSV_LOCAL="$REPO_ROOT/data/products-master.csv"
IMG_LOCAL="$REPO_ROOT/data/images/"

MODE="live"
SKIP_BACKUP=0
for arg in "$@"; do
  case "$arg" in
    --dry-run) MODE="dry" ;;
    --skip-backup) SKIP_BACKUP=1 ;;
    *) echo "Argumento desconocido: $arg" >&2; exit 2 ;;
  esac
done

[ -f "$CSV_LOCAL" ] || { echo "No existe $CSV_LOCAL" >&2; exit 2; }

if [ "$MODE" = "live" ] && [ "$SKIP_BACKUP" -eq 0 ]; then
  echo "==> Backup obligatorio antes de importar..."
  "$REPO_ROOT/scripts/backup.sh"
fi

echo "==> Subiendo CSV e imágenes al servidor (~/$REMOTE_IMPORT)..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_IMPORT/images"
rsync -az -e "ssh -p $SSH_PORT" "$CSV_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/products-master.csv"
if [ -d "$IMG_LOCAL" ]; then
  rsync -az --delete -e "ssh -p $SSH_PORT" "$IMG_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/images/"
fi

echo "==> Ejecutando importador (modo: $MODE)..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_IMPORT/products-master.csv\" $MODE" \
  < "$REPO_ROOT/scripts/import-products.php"
