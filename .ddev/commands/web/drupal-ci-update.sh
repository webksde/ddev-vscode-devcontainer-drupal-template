#!/bin/bash

## Description: Download the default configs and helper scripts the drupal.org GitLab CI jobs use (from gitlab_templates 1.x-latest, like contrib projects) into .ddev/drupal-ci. Run "ddev drupal-ci-setup" afterwards.
## Usage: drupal-ci-update
## Example: "ddev drupal-ci-update"

set -euo pipefail

# Contrib projects use "1.x-latest", which points to the newest 1.x tag.
REF="1.x-latest"
BASE="https://git.drupalcode.org/project/gitlab_templates/-/raw/$REF"
DIR="/var/www/html/.ddev/drupal-ci"

FILES=(
  assets/.cspell.json
  assets/phpcs.xml.dist
  assets/phpstan.neon
  scripts/prepare-cspell.php
)

# Download everything first, so a failed download leaves the old files intact.
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
for file in "${FILES[@]}"; do
  echo "Downloading $file ($REF)"
  mkdir -p "$TMP/$(dirname "$file")"
  curl -fsSL "$BASE/$file" -o "$TMP/$file"
done
for file in "${FILES[@]}"; do
  mkdir -p "$DIR/$(dirname "$file")"
  mv "$TMP/$file" "$DIR/$file"
done

echo "Updated the drupal.org GitLab CI files in .ddev/drupal-ci ($REF)."
