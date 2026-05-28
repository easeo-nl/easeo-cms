#!/usr/bin/env bash
# Kopieert fixtures/ naar data/ voor lokaal draaien of CI smoke test.
# Idempotent: overschrijft altijd, want fixture is canonical source.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

cp "$APP_DIR/fixtures/"*.json "$APP_DIR/data/"
echo "0" > "$APP_DIR/data/.schema-version"

echo "Seeded $APP_DIR/data/ from $APP_DIR/fixtures/"
ls -la "$APP_DIR/data/"
