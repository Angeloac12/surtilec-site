#!/usr/bin/env bash
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"
REMOTE_IMPORT="import"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CSV_LOCAL="$REPO_ROOT/data/cablescolombia-staging.csv"
MODE="dry"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --live) MODE="live"; shift ;;
    --dry-run) MODE="dry"; shift ;;
    --csv) CSV_LOCAL="$2"; shift 2 ;;
    *) echo "Argumento desconocido: $1" >&2; exit 2 ;;
  esac
done

case "$CSV_LOCAL" in
  /*) ;;
  *) CSV_LOCAL="$REPO_ROOT/$CSV_LOCAL" ;;
esac

[ -f "$CSV_LOCAL" ] || { echo "No existe $CSV_LOCAL" >&2; exit 2; }

if [ "$MODE" = "live" ]; then
  "$REPO_ROOT/scripts/backup.sh"
fi

ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mkdir -p \$HOME/$REMOTE_IMPORT"
rsync -az -e "ssh -p $SSH_PORT" "$CSV_LOCAL" "$SSH_USER@$SSH_HOST:$REMOTE_IMPORT/cablescolombia-staging.csv"
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$REMOTE_WP\" && wp eval-file - \"\$HOME/$REMOTE_IMPORT/cablescolombia-staging.csv\" $MODE" \
  < "$REPO_ROOT/scripts/import-product-staging.php"
