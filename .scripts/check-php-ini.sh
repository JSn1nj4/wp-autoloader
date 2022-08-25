#!/usr/bin/env bash
INI_FILE="/app/.config/php.ini"
INI_EXAMPLE="/app.config/php.ini-example"

if [[ -f "$INI_FILE" ]]; then
  echo "'.config/php.ini' file found"
  exit 0
fi

echo "Checking that '.config/php.ini-example' exists..."

if [[ ! -f "$INI_EXAMPLE" ]]; then
  echo "Check failed. Please either manually create '.config/php.ini' or create a '.config/php.ini-example' to be copied on build."
  exit 1
fi

echo "Creating '.config/php.ini'..."
cp "$INI_EXAMPLE" "$INI_FILE"
echo "'.config/php.ini' created!"