#!/usr/bin/env bash
#
# wp.sh — run WP-CLI on the Hostinger server over SSH.
# Usage: scripts/wp.sh <wp-cli args>
#   scripts/wp.sh core version
#   scripts/wp.sh plugin list
#
set -euo pipefail

SSH_HOST="147.93.37.202"
SSH_PORT="65002"
SSH_USER="u528798895"
WP_PATH="domains/ghostwhite-cormorant-218810.hostingersite.com/public_html"

ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" \
  "cd \"$WP_PATH\" && wp $*"
