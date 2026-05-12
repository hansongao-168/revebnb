#!/usr/bin/env bash
set -euo pipefail

TARGET_DIR="${1:-my-app}"

if ! command -v composer >/dev/null 2>&1; then
  echo "composer not found. Install Composer first."
  exit 1
fi

if [[ "$TARGET_DIR" != "." ]] && [[ -e "$TARGET_DIR" ]] && [[ -n "$(ls -A "$TARGET_DIR" 2>/dev/null || true)" ]]; then
  echo "Target directory '$TARGET_DIR' is not empty. Choose another directory or clean it first."
  exit 1
fi

echo "Creating Laravel 13 project in: $TARGET_DIR"
composer create-project laravel/laravel "$TARGET_DIR" "^13.0"
echo "Laravel 13 project created successfully."
