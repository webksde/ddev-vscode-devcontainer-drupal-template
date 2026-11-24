#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init [flags]
## Example: "ddev drowl-init", "ddev drowl-init -v 10", "ddev drowl-init --non-interactive"
## Flags: [{"Name":"version","Shorthand":"v","Type":"string","Usage":"Drupal major version to install: 10 or 11 (default: 11)"},{"Name":"non-interactive","Usage":"Never prompt, the personal settings are skipped"}]

# Sets up a fresh Drupal project (drupal/recommended-project) with our dev
# stack. Run it once in a freshly cloned copy of the template.
#
# Note: Keep this script compatible with bash 3.2 (macOS).

set -eo pipefail

# shellcheck source=../../drowl-init/lib.sh
source .ddev/drowl-init/lib.sh

DOCROOT="web"
DRUPAL_VERSION=11
PHP_VERSION=8.3

# Drupal modules added to "require" and enabled:
DRUPAL_MODULES=(devel devel_php coffee backup_migrate stage_file_proxy config_inspector examples)
# Further modules to enable (submodules of the ones above):
DRUPAL_SUBMODULES=(devel_generate)

# ------------------------------------------------------------------------------
# Arguments
# ------------------------------------------------------------------------------

set_version() {
  case "$1" in
  10 | 11) DRUPAL_VERSION="$1" ;;
  # Drupal 9 has reached end-of-life, serve the oldest supported version:
  9)
    DRUPAL_VERSION=10
    warn "Drupal 9 has reached its end of life, installing Drupal 10 instead."
    ;;
  *) die "Unsupported Drupal version '$1'. Use 10 or 11 (for Drupal core development, use 'ddev drowl-init-dev')." ;;
  esac
}

while [ $# -gt 0 ]; do
  case "$1" in
  -v=* | --version=*) set_version "${1#*=}" ;;
  -v | --version)
    [ $# -ge 2 ] || die "Missing value for $1. Use e.g. 'ddev drowl-init -v 10'."
    set_version "$2"
    shift
    ;;
  --non-interactive) NON_INTERACTIVE=1 ;;
  *) die "Unknown argument '$1'. See 'ddev drowl-init --help'." ;;
  esac
  shift
done

# ------------------------------------------------------------------------------
# Setup
# ------------------------------------------------------------------------------

drowl_require_host_commands ddev
drowl_require_fresh_template

drowl_start "Initialising a Drupal ${DRUPAL_VERSION} environment! This will take about ~5 min..."

step "Personal settings"
drowl_ask_personal_settings

step "Creating the project (drupal/recommended-project:^${DRUPAL_VERSION})"
drowl_remove_template_files
drowl_ddev_config --docroot="$DOCROOT" --php-version="$PHP_VERSION" --project-type="drupal"
drowl_composer_create_project "drupal/recommended-project:^${DRUPAL_VERSION}"
# Detect the Drupal version for the project type:
ddev config --update
drowl_add_ddev_addons "${DROWL_DDEV_ADDONS[@]}"
ddev start

step "Composer dependencies"
drowl_composer_base
DRUPAL_PACKAGES=()
for module in "${DRUPAL_MODULES[@]}"; do
  DRUPAL_PACKAGES+=("drupal/$module")
done
drowl_composer_require "${DRUPAL_PACKAGES[@]}"
# "drupal/core-dev" contains the dev dependencies like phpunit, phpstan, ...:
drowl_composer_require --dev -W "drupal/core-dev:^${DRUPAL_VERSION}"
drowl_composer_dev_packages
drowl_composer_defaults "$DOCROOT" --example-patch

step "Installing Drupal"
drowl_site_install "$DOCROOT"
drowl_copy_initiation_additions "$DOCROOT"
drowl_create_directories "$DOCROOT"
drowl_dev_settings
drowl_create_test_user

step "Linters"
drowl_setup_linters

step "Development modules"
# A dump BEFORE enabling contrib modules, in case they break
# ("ddev drowl-reset-db --before-contrib"):
drowl_export_db ./data/sql/db-dump-before-contrib.sql.gz
ddev drush en "${DRUPAL_MODULES[@]}" "${DRUPAL_SUBMODULES[@]}" -y
# Activate kint as default devel variables dumper:
ddev drush config-set devel.settings devel_dumper kint -y
# Give authenticated and anonymous users "access devel information" (dsm / kint):
ddev drush role:perm:add anonymous 'access devel information'
ddev drush role:perm:add authenticated 'access devel information'

step "Database dump"
# The "normal" dump, used by "ddev drowl-reset-db":
drowl_export_db ./data/sql/db-complete-dump.sql.gz

drowl_print_summary "$DOCROOT"
