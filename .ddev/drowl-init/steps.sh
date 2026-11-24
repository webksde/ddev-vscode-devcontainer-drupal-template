# shellcheck shell=bash
#
# Setup steps shared by the "drowl-init*" host commands. Sourced via lib.sh,
# not executed. All functions run from the project root.
#
# Note: Keep this file compatible with bash 3.2 (macOS).

# ------------------------------------------------------------------------------
# Configuration (lists, maintained here)
# ------------------------------------------------------------------------------

# Composer plugins to allow (unless composer.json already configures them):
DROWL_COMPOSER_ALLOWED_PLUGINS=(
  cweagans/composer-patches oomphinc/composer-installers-extender
  szeidler/composer-patches-cli tbachert/spi symfony/runtime
  dealerdirect/phpcodesniffer-composer-installer phpstan/extension-installer
  php-http/discovery
)
# Packages added to "require" (unless already present):
DROWL_COMPOSER_PACKAGES=(cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender)
# Packages added to "require-dev" (unless already present). No Drupal modules,
# as they are tied to the database. drupal/core-dev is handled by each
# command, as it has to match the core version:
DROWL_COMPOSER_DEV_PACKAGES=(drush/drush drupal/coder:^8 phpstan/phpstan-deprecation-rules kint-php/kint squizlabs/php_codesniffer:^3)
# DDEV add-ons:
DROWL_DDEV_ADDONS=(ddev/ddev-phpmyadmin ddev/ddev-selenium-standalone-chrome)
# Folders created in the docroot:
DROWL_CUSTOM_CODE_DIRS=(modules/custom themes/custom profiles/custom)
# Folders created in the project root (private files, config sync,
# translations, temp):
DROWL_PROJECT_DIRS=(files/private files/sync files/translations tmp data/sql)
# Copied initiation additions with "web/" paths, which are pointed to the local
# docroot if it differs:
DROWL_DOCROOT_REWRITE_FILES=(.vscode/settings.json phpunit.xml jsconfig.json AGENTS.md)

# Extra "Useful commands" ("command|description") and "Tips" of the summary,
# a command can add its own before calling drowl_print_summary:
DROWL_SUMMARY_COMMANDS=()
DROWL_SUMMARY_TIPS=()

# ------------------------------------------------------------------------------
# Preconditions
# ------------------------------------------------------------------------------

# Usage: drowl_require_host_commands COMMAND...
drowl_require_host_commands() {
  local cmd
  for cmd in "$@"; do
    command -v "$cmd" >/dev/null 2>&1 || die "'$cmd' is required on the host, but was not found. Please install it first."
  done
}

# Refuses to run in a project that was already initialised (or where a
# previous run failed), as the init commands expect a fresh template clone:
drowl_require_fresh_template() {
  local path
  [ -f .ddev/drowl-init/lib.sh ] || die "Run this command from the project root of the template."
  for path in composer.json vendor .ddev/.drowl-init.gitignore; do
    if [ -e "$path" ]; then
      die "'$path' exists, so this project was already initialised (or a previous run failed). Start over from a fresh clone of the template (run \"ddev delete -Oy\" in this folder first)."
    fi
  done
}

# ------------------------------------------------------------------------------
# Personal settings
# ------------------------------------------------------------------------------

DROWL_ENV_FILE=".ddev/.env.web"

# Reads the current value of a variable from the env file (without quotes):
drowl_env_get() {
  [ -f "$DROWL_ENV_FILE" ] || return 0
  sed -n "s/^$1=//p" "$DROWL_ENV_FILE" | tail -n 1 | sed -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/" -e 's/\\\(["$\\]\)/\1/g'
}

# Sets a variable in the env file (double quoted), replacing an existing entry:
drowl_env_set() {
  local tmp value="$2"
  value="${value//\\/\\\\}"
  value="${value//\"/\\\"}"
  value="${value//\$/\\\$}"
  tmp="$(mktemp)"
  if [ -f "$DROWL_ENV_FILE" ]; then
    grep -v "^$1=" "$DROWL_ENV_FILE" >"$tmp" || true
  fi
  printf '%s="%s"\n' "$1" "$value" >>"$tmp"
  mv "$tmp" "$DROWL_ENV_FILE"
}

# Asks for the personal settings (git identity, DRUPALCODE_TOKEN) and writes
# them into .ddev/.env.web (gitignored), so they are loaded on "ddev start".
# Run it before the containers start. Existing values are offered as
# defaults, other variables in the file are kept. Skipped in non-interactive
# mode. See .ddev/.example.env.web for all available variables.
drowl_ask_personal_settings() {
  local default_name default_mail existing_token git_name git_mail drupalcode_token
  if [ "$NON_INTERACTIVE" = 1 ]; then
    info "Non-interactive mode, skipping the personal settings. Set them in ${DROWL_ENV_FILE} later (see .ddev/.example.env.web)."
    return 0
  fi

  # Defaults: existing values from the env file (ignoring the placeholders of
  # .example.env.web), then the host git config:
  default_name="$(drowl_env_get GIT_AUTHOR_NAME)"
  [ "$default_name" != "Your Name" ] || default_name=""
  default_name="${default_name:-$(git config --global user.name 2>/dev/null || true)}"
  default_mail="$(drowl_env_get GIT_AUTHOR_EMAIL)"
  [ "$default_mail" != "you@example.com" ] || default_mail=""
  default_mail="${default_mail:-$(git config --global user.email 2>/dev/null || true)}"
  existing_token="$(drowl_env_get DRUPALCODE_TOKEN)"
  [ "$existing_token" != "your-fine-grained-token" ] || existing_token=""

  info "Personal settings (saved to ${DROWL_ENV_FILE}). Leave empty to skip."

  prompt_label "Git name" "" "$default_name"
  prompt_read git_name
  git_name="${git_name:-$default_name}"

  prompt_label "Git mail" "" "$default_mail"
  prompt_read git_mail
  git_mail="${git_mail:-$default_mail}"

  prompt_label "DRUPALCODE_TOKEN" "fine-grained personal access token from git.drupalcode.org, used by Claude Code (see README.md, \"Personal settings\")" "${existing_token:+keep existing}"
  prompt_read -s drupalcode_token
  drupalcode_token="${drupalcode_token:-$existing_token}"

  if [ -z "$git_name" ] && [ -z "$git_mail" ] && [ -z "$drupalcode_token" ]; then
    info "No personal settings given, skipping. You can set them in ${DROWL_ENV_FILE} later (see .ddev/.example.env.web)."
    return 0
  fi

  if [ ! -f "$DROWL_ENV_FILE" ]; then
    printf '%s\n' "# Personal environment variables for the ddev web container (gitignored)." "# See .ddev/.example.env.web for all available variables." >"$DROWL_ENV_FILE"
  fi
  if [ -n "$git_name" ]; then
    drowl_env_set GIT_AUTHOR_NAME "$git_name"
    drowl_env_set GIT_COMMITTER_NAME "$git_name"
  fi
  if [ -n "$git_mail" ]; then
    drowl_env_set GIT_AUTHOR_EMAIL "$git_mail"
    drowl_env_set GIT_COMMITTER_EMAIL "$git_mail"
  fi
  if [ -n "$drupalcode_token" ]; then
    drowl_env_set DRUPALCODE_TOKEN "$drupalcode_token"
  fi
  # The file contains a secret, keep it private:
  chmod 600 "$DROWL_ENV_FILE"
  success "Saved the personal settings to ${DROWL_ENV_FILE}."
}

# ------------------------------------------------------------------------------
# Project
# ------------------------------------------------------------------------------

# Removes the template's README.md and git files. The .gitignore is kept, it
# ignores personal settings, secrets and generated files:
drowl_remove_template_files() {
  DROWL_LOCAL_CHANGED=1
  rm -f ./README.md
  rm -rf ./.git ./.gitattributes
}

# Writes the .ddev/config.yaml with our defaults.
# Usage: drowl_ddev_config --docroot=DIR [further "ddev config" options]
drowl_ddev_config() {
  DROWL_LOCAL_CHANGED=1
  ddev config --composer-version="stable" --webserver-type="apache-fpm" --disable-settings-management --auto "$@"
}

# Runs "ddev composer create-project". It refuses to run with a .gitignore in
# the project root, so the .gitignore is parked in .ddev/ meanwhile and
# appended afterwards (in case the created project brought its own), also if
# create-project fails.
# Usage: drowl_composer_create_project PACKAGE
drowl_composer_create_project() {
  local status=0
  DROWL_LOCAL_CHANGED=1
  mv ./.gitignore ./.ddev/.drowl-init.gitignore
  ddev composer create-project "$1" || status=$?
  cat ./.ddev/.drowl-init.gitignore >>./.gitignore
  rm ./.ddev/.drowl-init.gitignore
  [ "$status" = 0 ] || die "'ddev composer create-project $1' failed."
}

# Usage: drowl_add_ddev_addons ADDON...
drowl_add_ddev_addons() {
  local addon
  for addon in "$@"; do
    info "Adding the DDEV add-on ${addon}..."
    ddev add-on get "$addon"
  done
}

# ------------------------------------------------------------------------------
# Composer (the containers must be running)
# ------------------------------------------------------------------------------

# Reads the local composer.json with the PHP of the web container, so no jq
# is needed on the host. $argv[1] is the mode, the rest are package names
# ("vendor/name[:constraint]"). Prints the ones that are not configured yet:
# - "require": neither in require nor in require-dev,
# - "allow-plugins": not in config.allow-plugins.
# shellcheck disable=SC2016
DROWL_PHP_COMPOSER_MISSING='
$c = json_decode(file_get_contents("composer.json"), TRUE) ?: [];
$mode = $argv[1];
foreach (array_slice($argv, 2) as $p) {
  $n = explode(":", $p, 2)[0];
  if ($mode === "require") {
    $found = isset($c["require"][$n]) || isset($c["require-dev"][$n]);
  }
  else {
    $allowed = $c["config"]["allow-plugins"] ?? [];
    $found = !is_array($allowed) || array_key_exists($n, $allowed);
  }
  if (!$found) {
    echo $p, PHP_EOL;
  }
}'

# Usage: drowl_composer_missing require|allow-plugins PACKAGE...
drowl_composer_missing() {
  # No tty (</dev/null), so the output has no "\r":
  ddev exec --raw php -r "$DROWL_PHP_COMPOSER_MISSING" "$@" </dev/null | tr -d '\r'
}

# Allows the given composer plugins, unless they are already configured:
# Usage: drowl_composer_allow_plugins PLUGIN...
drowl_composer_allow_plugins() {
  local plugin missing
  missing="$(drowl_composer_missing allow-plugins "$@")" || die "Could not read the allow-plugins of composer.json."
  for plugin in $missing; do
    ddev composer config --no-plugins "allow-plugins.$plugin" true
  done
}

# Requires the given packages, skipping the ones already present (they are
# never moved between require and require-dev).
# Usage: drowl_composer_require [--dev] [-W] PACKAGE...
drowl_composer_require() {
  local args=() packages=() package missing
  while [ $# -gt 0 ] && [[ "$1" == -* ]]; do
    args+=("$1")
    shift
  done
  missing="$(drowl_composer_missing require "$@")" || die "Could not read the packages of composer.json."
  for package in $missing; do
    packages+=("$package")
  done
  if [ ${#packages[@]} -eq 0 ]; then
    info "Already present: $*"
    return 0
  fi
  ddev composer require "${args[@]}" "${packages[@]}" --no-audit --no-interaction
}

# Our composer.json defaults: patching, minimum-stability and asset-packagist.
# With --example-patch, a placeholder patch entry is added as a template.
# @todo Remove "--raw" for all --json config calls (current workaround for
#   https://github.com/ddev/ddev/issues/6628).
# Usage: drowl_composer_defaults DOCROOT [--example-patch]
drowl_composer_defaults() {
  local docroot="$1"
  ddev composer config extra.composer-exit-on-patch-failure true
  ddev composer config extra.enable-patching true
  if [ "$2" = "--example-patch" ]; then
    ddev exec --raw composer config extra.patches.package-maintainer/package --json '{"description": "path/to/patch"}'
  fi
  ddev composer config minimum-stability dev
  ddev exec --raw composer config repositories.asset-packagist --json '{"type": "composer","url": "https://asset-packagist.org"}'
  ddev exec --raw composer config extra.installer-types --json '["npm-asset", "bower-asset"]'
  ddev exec --raw composer config "extra.installer-paths.${docroot}/libraries/{\$name}" --json '["type:drupal-library", "type:npm-asset", "type:bower-asset"]'
}

# Allows our composer plugins and requires our general packages. Run it
# before requiring anything else (drupal/core-dev needs some of the plugins):
drowl_composer_base() {
  drowl_composer_allow_plugins "${DROWL_COMPOSER_ALLOWED_PLUGINS[@]}"
  drowl_composer_require "${DROWL_COMPOSER_PACKAGES[@]}"
}

# Requires our dev packages (DROWL_COMPOSER_DEV_PACKAGES). Run it after
# drupal/core-dev, which has to match the core version exactly:
drowl_composer_dev_packages() {
  drowl_composer_require --dev "${DROWL_COMPOSER_DEV_PACKAGES[@]}"
  ddev exec chmod +x vendor/bin/phpcs vendor/bin/phpcbf
}

# ------------------------------------------------------------------------------
# Drupal (the containers must be running)
# ------------------------------------------------------------------------------

# Installs Drupal with the admin / admin account.
# Usage: drowl_site_install DOCROOT
drowl_site_install() {
  ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y
  chmod 0755 "./$1/sites/default"
  chmod 0644 "./$1/sites/default/settings.php"
}

# Copies all initiation additions (VSCode settings, phpunit.xml, .mcp.json,
# AGENTS.md, CLAUDE.md, jsconfig.json, web/sites/default/settings*, ...). The
# web/ folder goes into the given docroot, the "web/" paths of
# DROWL_DOCROOT_REWRITE_FILES are pointed to it.
# Usage: drowl_copy_initiation_additions DOCROOT
drowl_copy_initiation_additions() {
  local docroot="$1" item file tmp
  chmod u+w "./$docroot/sites/default" 2>/dev/null || true
  # "*" and ".[!.]*" match all files incl. dotfiles (.vscode, .claude, ...),
  # but not "." and "..":
  for item in .ddev/initiation-additions/* .ddev/initiation-additions/.[!.]*; do
    [ -e "$item" ] || continue
    if [ "$(basename "$item")" = "web" ]; then
      mkdir -p "./$docroot"
      cp -R "$item/." "./$docroot/"
    else
      cp -R "$item" .
    fi
  done
  chmod 0755 "./$docroot/sites/default"
  chmod 0644 "./$docroot/sites/default/settings.php"
  [ "$docroot" != "web" ] || return 0
  # Only paths like "./web/", "/var/www/html/web/", "\"web/", "`web/", "(web/".
  # Works through a temp file, as "sed -i" differs between GNU and macOS:
  tmp="$(mktemp)"
  for file in "${DROWL_DOCROOT_REWRITE_FILES[@]}"; do
    [ -f "$file" ] || continue
    sed -E "s#(/var/www/html/|\./|[\"\`(])web/#\1${docroot}/#g" "$file" >"$tmp"
    cat "$tmp" >"$file"
  done
  rm -f "$tmp"
  info "Rewrote the 'web/' paths in ${DROWL_DOCROOT_REWRITE_FILES[*]} to '${docroot}/'."
}

# Creates the custom code folders in the docroot and the project folders.
# Usage: drowl_create_directories DOCROOT
drowl_create_directories() {
  local dir
  for dir in "${DROWL_CUSTOM_CODE_DIRS[@]}"; do
    mkdir -p "./$1/$dir"
  done
  for dir in "${DROWL_PROJECT_DIRS[@]}"; do
    mkdir -p "./$dir"
  done
  # Database dumps may hold personal data, only the current user may read them:
  chmod 0700 ./data/sql
}

# Development settings: verbose error logging, no CSS / JS aggregation:
drowl_dev_settings() {
  ddev drush config-set system.logging error_level verbose -y
  ddev drush config-set system.performance css.preprocess 0 -y
  ddev drush config-set system.performance js.preprocess 0 -y
}

# An authenticated test user (max / max):
drowl_create_test_user() {
  ddev drush user:create max --mail='max@example.com' --password='max' -y
}

# Sets up the linters like the drupal.org GitLab CI: installs core's node
# modules (eslint, stylelint, prettier, cspell) and links core's configs as
# fallbacks. Not fatal, it can be retried with "ddev drupal-ci-update".
drowl_setup_linters() {
  info "Installing core's node modules for the linters (this might take a bit)..."
  if ! ddev exec /var/www/html/.ddev/drupal-ci/setup.sh; then
    warn "The drupal-ci setup failed. Run 'ddev drupal-ci-update' to retry it."
  fi
}

# Exports the database (readable by the current user only).
# Usage: drowl_export_db FILE
drowl_export_db() {
  (
    umask 077
    ddev export-db "$DDEV_PROJECT" >"$1"
  )
  success "Created a full database dump in $1."
}

# ------------------------------------------------------------------------------
# Summary
# ------------------------------------------------------------------------------

# Prints the final summary: banner, project information ("ddev describe"),
# useful commands, tips, then the collected notes, to dos and warnings.
# Usage: drowl_print_summary DOCROOT
drowl_print_summary() {
  local entry commands tips
  DROWL_CURRENT_STEP="Summary"

  commands=(
    "ddev code|Attach VS Code to the running web container."
    "ddev xdebug on|Turn on Xdebug, then use 'Run and Debug' > 'Listen for XDebug' in VS Code."
    "ddev phpunit path/to/tests|Run PHPUnit tests."
    "ddev phpunit-coverage path/to/cover|Create a test coverage report of the given directory."
    "ddev phpcs ${1}/modules/custom/my_module|Check code like the drupal.org GitLab CI (also: phpcbf, phpstan, eslint, stylelint, cspell)."
    "ddev eslint --fix path|Autofix JavaScript (or 'ddev stylelint --fix path' for CSS)."
    "ddev drupal-ci-update|Update the drupal.org GitLab CI configs (gitlab_templates 1.x) and the linter setup."
    "ddev drowl-reset-db|Reset the database to its state after the initial setup."
    "ddev import-db --file=dump.sql.gz|Import a database dump."
  )
  tips=(
    "Debug inside the attached VS Code instance: run 'ddev config global --xdebug-ide-location=container && ddev restart'."
    "Enable xhprof profiling: run 'ddev config global --xhprof-mode=xhgui && ddev restart'."
    "Personal settings (git identity, DRUPALCODE_TOKEN) are stored in ${DROWL_ENV_FILE}. Edit it (see .ddev/.example.env.web for all variables), then run 'ddev restart'."
    "Most linters are also available as extensions in the attached VS Code instance."
    "More about the CLI tools: https://github.com/webksde/ddev-vscode-devcontainer-drupal-template"
  )

  banner "${DROWL_NAME} finished in $(elapsed_time)!"

  heading "Project"
  ddev describe

  heading "Useful commands"
  for entry in "${commands[@]}" "${DROWL_SUMMARY_COMMANDS[@]}"; do
    printf '  %s%s%s\n      %s\n' "$C_GREEN" "${entry%%|*}" "$C_RESET" "${entry#*|}"
  done

  heading "Tips"
  for entry in "${tips[@]}" "${DROWL_SUMMARY_TIPS[@]}"; do
    bullet "$entry"
  done

  if [ ${#NOTES[@]} -gt 0 ]; then
    heading "Notes"
    for entry in "${NOTES[@]}"; do
      bullet "$entry"
    done
  fi

  if [ ${#TODOS[@]} -gt 0 ]; then
    heading "To do" "$C_YELLOW"
    for entry in "${TODOS[@]}"; do
      bullet "$entry"
    done
  fi

  if [ ${#WARNINGS[@]} -gt 0 ]; then
    heading "Warnings" "$C_RED"
    for entry in "${WARNINGS[@]}"; do
      bullet "$entry"
    done
  fi
}
