#!/bin/bash

## HostWorkingDir: true
## Description: Use phpstan on a Folder for checking deprecated and dirty code.
## Usage: phpstan [path]
## Example: "ddev phpstan web/modules/contrib/devel"

# Determine the primary target directory (defaults to current directory if no arguments are passed)
TARGET_DIR="${1:-$PWD}"

# Check if phpstan.neon exists in the target directory; fallback to default if not
if [ -f "$TARGET_DIR/phpstan.neon" ]; then
  CONFIG_FILE="$TARGET_DIR/phpstan.neon"
else
  CONFIG_FILE="/var/www/html/phpstan.neon"
fi

if [ $# -eq 0 ]; then
  phpstan analyse -c "$CONFIG_FILE" "$PWD"
else
  phpstan analyse -c "$CONFIG_FILE" "$@"
fi
