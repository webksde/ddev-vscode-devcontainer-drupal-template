#!/bin/bash
#
# Prepares the site, so the drupal-ci runners (bin/) and the VSCode extensions
# behave like the drupal.org GitLab CI jobs. Runs inside the web container.
# drowl-init runs it once, "ddev drupal-ci-update" runs it again; there is no
# need to run it by hand.
#
# - Downloads the CI default configs from gitlab_templates, if missing.
# - Installs core's node modules with yarn (ESLint, Stylelint, Prettier, CSpell
#   in the versions core pins), like CI, if missing.
# - Provides core's ESLint, Stylelint and Prettier configs site-wide, as the
#   fallback for all projects without their own config (see ci_write_configs
#   in lib/common.sh).
# - Generates the site-wide cspell.json for the VSCode CSpell extension.
#
# Every step is skipped when already done, so it is safe to run again.
#
# Usage: setup.sh [--force]
#   --force: Reinstall core's node modules.

# shellcheck source-path=SCRIPTDIR source=lib/common.sh
source "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")/lib/common.sh"

if [[ ${1:-} == "--force" ]]; then
  rm -rf "$CORE_DIR/node_modules"
fi

ci_require_ci_files
ci_require_node_modules
ci_write_configs

php "$DRUPAL_CI_DIR/lib/cspell.php" vscode "$APP_ROOT/cspell.json" ||
  ci_die "Generating $APP_ROOT/cspell.json failed."
ci_info "Setup done."
