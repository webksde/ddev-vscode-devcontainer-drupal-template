#!/usr/bin/env bash

## Description: Startup A Drupal core development DDEV Environment (joachim-n/drupal-core-development-project), using DROWL Best Practices
## Usage: drowl-init-dev [flags]
## Example: "ddev drowl-init-dev", "ddev drowl-init-dev --non-interactive"
## Flags: [{"Name":"non-interactive","Usage":"Never prompt, the personal settings are skipped"}]

# Sets up a Drupal core development project
# (https://github.com/joachim-n/drupal-core-development-project): Drupal core
# is a git clone in repos/drupal, symlinked to web/core. Run it once in a
# freshly cloned copy of the template.
#
# Note: Keep this script compatible with bash 3.2 (macOS).

set -eo pipefail

# shellcheck source=../../drowl-init/lib.sh
source .ddev/drowl-init/lib.sh

DOCROOT="web"
PHP_VERSION=8.5
PROJECT_TYPE="drupal12"
CORE_REPO="repos/drupal"

# Drupal modules added to "require" and enabled:
# @todo Add devel, devel_php and stage_file_proxy (and set the kint dumper and
#   the "access devel information" permissions like drowl-init), once they
#   are Drupal 12 compatible.
DRUPAL_MODULES=(config_inspector)
# Drupal modules only added to "require" (not Drupal 12 compatible yet, so not
# enabled):
DRUPAL_MODULES_NOT_ENABLED=(backup_migrate coffee examples)

# ------------------------------------------------------------------------------
# Arguments
# ------------------------------------------------------------------------------

while [ $# -gt 0 ]; do
  case "$1" in
  --non-interactive) NON_INTERACTIVE=1 ;;
  *) die "Unknown argument '$1'. See 'ddev drowl-init-dev --help'." ;;
  esac
  shift
done

# ------------------------------------------------------------------------------
# Setup
# ------------------------------------------------------------------------------

drowl_require_host_commands ddev git
drowl_require_fresh_template

drowl_start "Initialising a Drupal core development environment! This will take about ~5 min..."

step "Personal settings"
drowl_ask_personal_settings

step "Creating the project (joachim-n/drupal-core-development-project)"
drowl_remove_template_files
drowl_ddev_config --docroot="$DOCROOT" --php-version="$PHP_VERSION" --project-type="$PROJECT_TYPE"
drowl_composer_create_project "joachim-n/drupal-core-development-project"
ddev config --update
drowl_add_ddev_addons "${DROWL_DDEV_ADDONS[@]}"
ddev start

step "Core repository"
# The project links vendor into the core repository (so phpunit finds the
# autoloader). Make sure it exists and is not tracked:
if [ ! -e "$CORE_REPO/vendor" ]; then
  ln -s ../../vendor "$CORE_REPO/vendor"
fi
grep -qx "vendor" "$CORE_REPO/.git/info/exclude" 2>/dev/null || echo "vendor" >>"$CORE_REPO/.git/info/exclude"

step "Composer dependencies"
# (drupal/core-dev is already required by the project.)
drowl_composer_base
DRUPAL_PACKAGES=()
for module in "${DRUPAL_MODULES[@]}" "${DRUPAL_MODULES_NOT_ENABLED[@]}"; do
  DRUPAL_PACKAGES+=("drupal/$module")
done
drowl_composer_require "${DRUPAL_PACKAGES[@]}"
drowl_composer_dev_packages
drowl_composer_defaults "$DOCROOT" --example-patch

step "Installing Drupal"
drowl_site_install "$DOCROOT"
drowl_copy_initiation_additions "$DOCROOT"
# The project's phpunit config fits the repos/drupal layout, it replaces the
# phpunit.xml of the initiation additions:
if [ -f phpunit-ddev.xml ]; then
  mv -f phpunit-ddev.xml phpunit.xml
else
  warn "No phpunit-ddev.xml found in the project, kept the template's phpunit.xml. Check its paths before running tests."
fi
drowl_create_directories "$DOCROOT"
drowl_dev_settings
drowl_create_test_user

step "Test directories"
# When web/core is a symlink to repos/drupal/core, Drupal's test runner treats
# repos/drupal as the application root. So it expects
# repos/drupal/sites/default/files (public://) and repos/drupal/sites/simpletest
# (browser test output), which are linked to the ones in the docroot:
mkdir -p "./$DOCROOT/sites/default/files/simpletest" "./$DOCROOT/sites/simpletest/browser_output"
# Keep existing directories, only link where missing or already a link ("-n"
# replaces a link to a directory instead of creating one inside it):
if [ ! -e "$CORE_REPO/sites/default/files" ] || [ -L "$CORE_REPO/sites/default/files" ]; then
  ln -sfn "../../../../$DOCROOT/sites/default/files" "$CORE_REPO/sites/default/files"
fi
if [ ! -e "$CORE_REPO/sites/simpletest" ] || [ -L "$CORE_REPO/sites/simpletest" ]; then
  ln -sfn "../../../$DOCROOT/sites/simpletest" "$CORE_REPO/sites/simpletest"
fi
# Writable for the PHP process:
chmod 0777 "./$DOCROOT/sites/default/files" "./$DOCROOT/sites/default/files/simpletest"
chmod 0777 "./$DOCROOT/sites/simpletest" "./$DOCROOT/sites/simpletest/browser_output"

step "Linters"
drowl_setup_linters

step "Development modules"
# A dump BEFORE enabling contrib modules, in case they break
# ("ddev drowl-reset-db --before-contrib"):
drowl_export_db ./data/sql/db-dump-before-contrib.sql.gz
ddev drush en "${DRUPAL_MODULES[@]}" -y

step "Database dump"
# The "normal" dump, used by "ddev drowl-reset-db":
drowl_export_db ./data/sql/db-complete-dump.sql.gz

step "Cleaning the core repository"
# Discard all changes the setup made in the core repository and update it.
# Not fatal (e.g. offline):
if ! git -C "$CORE_REPO" add --all || ! git -C "$CORE_REPO" reset --hard; then
  warn "Could not reset the core repository in ${CORE_REPO}. Check 'git status' there."
fi
if ! git -C "$CORE_REPO" pull; then
  warn "Could not update the core repository in ${CORE_REPO}. Run 'git pull' there."
fi

DROWL_SUMMARY_TIPS+=("Drupal core is a git clone in ${CORE_REPO} (symlinked to ${DOCROOT}/core), work on core issues there.")
DROWL_SUMMARY_TIPS+=("Some dev modules (${DRUPAL_MODULES_NOT_ENABLED[*]}) are not enabled, as they have no official Drupal 12 support. You can try adjusting their 'core_version_requirement' to make them work.")
drowl_print_summary "$DOCROOT"
