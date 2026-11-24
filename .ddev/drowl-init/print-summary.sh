#!/usr/bin/env bash

# Prints the final summary of the "drowl-init*" host commands: a banner, the
# project information ("ddev describe"), the useful commands and general tips.
#
# Called at the end of the "drowl-init*" host commands, from the project root:
#   bash .ddev/drowl-init/print-summary.sh [options]
#
# Options:
#   --title=TEXT          Banner text (default: "Your DDEV project is ready!").
#   --web-dir=DIR         Docroot, used in the examples (default: "web").
#   --command='CMD|TEXT'  Adds a command to "Useful commands" (repeatable).
#   --tip=TEXT            Adds a tip to "Tips" (repeatable).

set -e

TITLE="Your DDEV project is ready!"
WEB_DIR="web"
EXTRA_COMMANDS=()
EXTRA_TIPS=()

for arg in "$@"; do
  case "$arg" in
  --title=*) TITLE="${arg#*=}" ;;
  --web-dir=*) WEB_DIR="${arg#*=}" ;;
  --command=*) EXTRA_COMMANDS+=("${arg#*=}") ;;
  --tip=*) EXTRA_TIPS+=("${arg#*=}") ;;
  *)
    printf 'print-summary.sh: unknown argument "%s"\n' "$arg" >&2
    exit 1
    ;;
  esac
done

# Section heading, e.g. "-- Useful commands ------":
heading() {
  local line
  line="$(printf '%*s' $((70 - ${#1})) '' | tr ' ' '-')"
  printf '\n\e[1;36m-- %s %s\e[0m\n\n' "$1" "$line"
}

# Banner:
banner_line="$(printf '%*s' 74 '' | tr ' ' '=')"
printf '\n\e[1;32m%s\n  %s\n%s\e[0m\n' "$banner_line" "$TITLE" "$banner_line"

# Project information:
heading "Project"
ddev describe

# Useful commands ("command|description"):
COMMANDS=(
  "ddev code|Attach VS Code to the running web container."
  "ddev xdebug on|Turn on Xdebug, then use 'Run and Debug' > 'Listen for XDebug' in VS Code."
  "ddev phpunit path/to/tests|Run PHPUnit tests."
  "ddev phpunit-coverage path/to/cover|Create a test coverage report of the given directory."
  "ddev phpcs ${WEB_DIR}/modules/custom/my_module|Check code like the drupal.org GitLab CI (also: phpcbf, phpstan, eslint, stylelint, cspell)."
  "ddev eslint --fix path|Autofix JavaScript (or 'ddev stylelint --fix path' for CSS)."
  "ddev drupal-ci-update|Update the drupal.org GitLab CI configs (gitlab_templates 1.x) and the linter setup."
  "ddev drowl-reset-db|Reset the database to its state after the initial setup."
  "ddev import-db --file=dump.sql.gz|Import a database dump."
)
COMMANDS+=("${EXTRA_COMMANDS[@]}")

heading "Useful commands"
for entry in "${COMMANDS[@]}"; do
  printf '  \e[32m%s\e[0m\n      %s\n' "${entry%%|*}" "${entry#*|}"
done

# Tips:
TIPS=(
  "Debug inside the attached VS Code instance: run 'ddev config global --xdebug-ide-location=container && ddev restart'."
  "Enable xhprof profiling: run 'ddev config global --xhprof-mode=xhgui && ddev restart'."
  "Personal settings (git identity, DRUPALCODE_TOKEN) are stored in .ddev/.env.web. Edit it (see .ddev/.example.env.web for all variables), then run 'ddev restart'."
  "Most linters are also available as extensions in the attached VS Code instance."
  "More about the CLI tools: https://github.com/webksde/ddev-vscode-devcontainer-drupal-template"
)
TIPS+=("${EXTRA_TIPS[@]}")

heading "Tips"
for tip in "${TIPS[@]}"; do
  printf '  - %s\n' "$tip"
done
