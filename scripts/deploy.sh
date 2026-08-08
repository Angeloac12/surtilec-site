#!/usr/bin/env bash
#
# deploy.sh — rsync ONLY the child theme and/or mu-plugins to the Hostinger server.
#
# Usage:
#   scripts/deploy.sh
#   scripts/deploy.sh --child-theme-only
#   scripts/deploy.sh --mu-plugins-only
#
# Safety: each rsync targets a single subfolder with matching trailing slashes,
# so --delete can only remove files inside surtilec-child/ or mu-plugins/.
# It never touches uploads/, other plugins, core, or the database.
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/surtilec.com/public_html"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

RSYNC_OPTS=(-avz --delete --exclude '.git' --exclude '.DS_Store')

DEPLOY_CHILD=1
DEPLOY_MU=1

for arg in "$@"; do
  case "$arg" in
    --child-theme-only)
      DEPLOY_CHILD=1
      DEPLOY_MU=0
      ;;
    --mu-plugins-only)
      DEPLOY_CHILD=0
      DEPLOY_MU=1
      ;;
    --all)
      DEPLOY_CHILD=1
      DEPLOY_MU=1
      ;;
    *)
      echo "Argumento desconocido: $arg" >&2
      exit 2
      ;;
  esac
done

if [ "$DEPLOY_CHILD" -eq 1 ]; then
  echo "==> Deploying child theme..."
  rsync "${RSYNC_OPTS[@]}" -e "ssh -p $SSH_PORT" \
    "$REPO_ROOT/wp-content/themes/surtilec-child/" \
    "$SSH_USER@$SSH_HOST:$REMOTE_WP/wp-content/themes/surtilec-child/"
fi

if [ "$DEPLOY_MU" -eq 1 ]; then
  echo "==> Deploying mu-plugins..."
  rsync "${RSYNC_OPTS[@]}" -e "ssh -p $SSH_PORT" \
    "$REPO_ROOT/wp-content/mu-plugins/" \
    "$SSH_USER@$SSH_HOST:$REMOTE_WP/wp-content/mu-plugins/"
fi

echo "==> Done."
