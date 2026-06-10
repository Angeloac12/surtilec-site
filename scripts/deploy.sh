#!/usr/bin/env bash
#
# deploy.sh — rsync ONLY the child theme and mu-plugins to the Hostinger server.
#
# Safety: each rsync targets a single subfolder with matching trailing slashes,
# so --delete can only remove files inside surtilec-child/ or mu-plugins/.
# It never touches uploads/, other plugins, core, or the database.
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
REMOTE_WP="domains/ghostwhite-cormorant-218810.hostingersite.com/public_html"

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

RSYNC_OPTS=(-avz --delete --exclude '.git' --exclude '.DS_Store')

echo "==> Deploying child theme..."
rsync "${RSYNC_OPTS[@]}" -e "ssh -p $SSH_PORT" \
  "$REPO_ROOT/wp-content/themes/surtilec-child/" \
  "$SSH_USER@$SSH_HOST:$REMOTE_WP/wp-content/themes/surtilec-child/"

echo "==> Deploying mu-plugins..."
rsync "${RSYNC_OPTS[@]}" -e "ssh -p $SSH_PORT" \
  "$REPO_ROOT/wp-content/mu-plugins/" \
  "$SSH_USER@$SSH_HOST:$REMOTE_WP/wp-content/mu-plugins/"

echo "==> Done."
