#!/bin/bash

## Description: Install core's node modules and link the configs needed to lint like the drupal.org GitLab CI (also for the VSCode extensions). Run again after adding a custom module or theme, or after updating core.
## Usage: drupal-ci-setup [--force]
## Example: "ddev drupal-ci-setup", "ddev drupal-ci-setup --force"

# Prepares the site, so the drupal-ci runners (.ddev/drupal-ci/bin) and the
# VSCode extensions behave like the drupal.org GitLab CI jobs:
# - Installs core's node modules with yarn (ESLint, Stylelint, Prettier, CSpell
#   in the versions core pins), like CI.
# - Links core's ESLint, Stylelint and Prettier configs into the parent folders
#   of the custom projects (web/{modules,themes,profiles}/custom), where they
#   act as the fallback for projects without their own config.
# - Generates the site-wide cspell.json for the VSCode CSpell extension.
#
# --force: Reinstall core's node modules.

# shellcheck source=../../drupal-ci/lib/common.sh
source /var/www/html/.ddev/drupal-ci/lib/common.sh

if [[ ${1:-} == "--force" ]]; then
  rm -rf "$CORE_DIR/node_modules"
fi
ci_require_node_modules

projects=()
for type in modules themes profiles; do
  dir="$WEB_ROOT/$type/custom"
  [[ -d $dir ]] || continue
  ci_link_parent_configs "$dir"
  mapfile -t -O "${#projects[@]}" projects < <(ci_projects_below "$dir")
done
# Also link into the parent folders of nested projects (e.g. custom/foo/bar).
for project in "${projects[@]}"; do
  [[ -n $project ]] && ci_link_parent_configs "$(dirname "$project")"
done

php "$DRUPAL_CI_DIR/lib/cspell.php" vscode "$APP_ROOT/cspell.json" "${projects[@]}" ||
  ci_die "Generating $APP_ROOT/cspell.json failed."
ci_info "Generated $APP_ROOT/cspell.json for ${#projects[@]} custom project(s)."
