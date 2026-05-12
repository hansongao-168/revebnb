#!/usr/bin/env bash
set -euo pipefail

missing=0

check_cmd() {
  local cmd="$1"
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "[MISSING] command: $cmd"
    missing=1
  else
    echo "[OK] command: $cmd -> $("$cmd" --version 2>/dev/null | head -n 1 || true)"
  fi
}

check_php_extension() {
  local ext="$1"
  if php -m 2>/dev/null | grep -Eix "${ext}" >/dev/null 2>&1; then
    echo "[OK] php extension: $ext"
  else
    echo "[MISSING] php extension: $ext"
    missing=1
  fi
}

echo "== Laravel 13 environment check =="

check_cmd php
check_cmd composer

if command -v php >/dev/null 2>&1; then
  echo "PHP version: $(php -r 'echo PHP_VERSION;' 2>/dev/null || echo unknown)"
fi

required_exts=(
  bcmath
  ctype
  fileinfo
  json
  mbstring
  openssl
  pdo
  tokenizer
  xml
)

if command -v php >/dev/null 2>&1; then
  for ext in "${required_exts[@]}"; do
    check_php_extension "$ext"
  done
else
  echo "[SKIP] php extension checks (php not found)"
fi

if [[ "$missing" -eq 1 ]]; then
  echo "Environment check failed: missing prerequisites."
  exit 1
fi

echo "Environment check passed."
