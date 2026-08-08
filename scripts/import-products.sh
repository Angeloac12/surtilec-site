#!/usr/bin/env bash
#
# import-products.sh — validate / import products from data/products-master.csv
# into WooCommerce on the Hostinger server.
#
# Usage:
#   scripts/import-products.sh --dry-run        # validate only, no DB writes
#   scripts/import-products.sh                  # backup + live import
#   scripts/import-products.sh --skip-backup    # live import without backup
#   scripts/import-products.sh --csv data/products-batch-001.csv --source-register data/product-source-register-001.csv
#
# Live import ALWAYS backs up first (scripts/backup.sh) unless --skip-backup.
# Dry-run never touches the DB and never backs up.
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"
REMOTE_IMPORT="import"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CSV_LOCAL="$REPO_ROOT/data/products-master.csv"
IMG_LOCAL="$REPO_ROOT/data/images/"
SOURCE_REGISTER_LOCAL="$REPO_ROOT/data/product-source-register.csv"

MODE="live"
SKIP_BACKUP=0
while [ "$#" -gt 0 ]; do
  case "$1" in
    --dry-run)
      MODE="dry"
      shift
      ;;
    --skip-backup)
      SKIP_BACKUP=1
      shift
      ;;
    --csv)
      [ "$#" -ge 2 ] || { echo "--csv requiere una ruta" >&2; exit 2; }
      CSV_LOCAL="$2"
      shift 2
      ;;
    --source-register)
      [ "$#" -ge 2 ] || { echo "--source-register requiere una ruta" >&2; exit 2; }
      SOURCE_REGISTER_LOCAL="$2"
      shift 2
      ;;
    *)
      echo "Argumento desconocido: $1" >&2
      exit 2
      ;;
  esac
done

case "$CSV_LOCAL" in
  /*) ;;
  *) CSV_LOCAL="$REPO_ROOT/$CSV_LOCAL" ;;
esac
case "$SOURCE_REGISTER_LOCAL" in
  /*) ;;
  *) SOURCE_REGISTER_LOCAL="$REPO_ROOT/$SOURCE_REGISTER_LOCAL" ;;
esac

[ -f "$CSV_LOCAL" ] || { echo "No existe $CSV_LOCAL" >&2; exit 2; }
[ -f "$SOURCE_REGISTER_LOCAL" ] || { echo "No existe $SOURCE_REGISTER_LOCAL" >&2; exit 2; }

if [ "$MODE" = "live" ] && [ "$SKIP_BACKUP" -eq 0 ]; then
  echo "==> Backup obligatorio antes de importar..."
  "$REPO_ROOT/scripts/backup.sh"
fi

echo "==> Subiendo CSV e imágenes al servidor (~/$REMOTE_IMPORT)..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_IMPORT/images"
rsync -az -e "ssh -p $SSH_PORT" "$CSV_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/products-master.csv"
rsync -az -e "ssh -p $SSH_PORT" "$SOURCE_REGISTER_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/product-source-register.csv"
if [ -d "$IMG_LOCAL" ]; then
  rsync -az --delete -e "ssh -p $SSH_PORT" "$IMG_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/images/"
fi

echo "==> Ejecutando importador (modo: $MODE)..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_IMPORT/products-master.csv\" $MODE \"\$HOME/$REMOTE_IMPORT/product-source-register.csv\"" \
  < "$REPO_ROOT/scripts/import-products.php"
