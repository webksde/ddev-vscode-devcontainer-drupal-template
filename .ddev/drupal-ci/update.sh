#!/bin/bash
#
# Vendors the files the drupal.org GitLab CI jobs download at runtime
# (default configs and helper scripts) from project/gitlab_templates.
#
# Usage: .ddev/drupal-ci/update.sh [ref]
#   ref: A gitlab_templates tag, branch or commit. Defaults to the ref in the
#        REF file. Contrib projects normally use "1.x-latest", which points to
#        the newest 1.x tag, so pin that tag here.
#
# Can be run on the host or inside the web container (needs curl).

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REF="${1:-$(cat "$DIR/REF")}"
BASE="https://git.drupalcode.org/project/gitlab_templates/-/raw/$REF"

FILES=(
  assets/.cspell.json
  assets/phpcs.xml.dist
  assets/phpstan.neon
  scripts/prepare-cspell.php
)

for file in "${FILES[@]}"; do
  mkdir -p "$DIR/$(dirname "$file")"
  echo "Downloading $file ($REF)"
  curl -fsSL "$BASE/$file" -o "$DIR/$file.tmp"
  mv "$DIR/$file.tmp" "$DIR/$file"
done

echo "$REF" > "$DIR/REF"
echo "Done. Review the changes with: git diff -- .ddev/drupal-ci"
