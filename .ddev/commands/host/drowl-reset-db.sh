#!/usr/bin/env bash

## HostWorkingDir: true
## Description: Resets the Drupal database to its state after the project initialisation (or before the contrib modules were enabled, using "--before-contrib")
## Usage: drowl-reset-db [flags]
## Example: "ddev drowl-reset-db", "ddev drowl-reset-db --before-contrib"
## Flags: [{"Name":"before-contrib","Usage":"Reset to the state before the contrib modules were enabled"}]

set -eo pipefail

# Go up the directories, until the current directory is the ddev project folder
# (there is no DDEV_PROJECT_HOST_ABSOLUTE_PATH variable, so we need this
# workaround):
while [[ $PWD != '/' && ${PWD##*/} != "$DDEV_PROJECT" ]]; do cd ..; done

# shellcheck source=../../drowl-init/lib.sh
source .ddev/drowl-init/lib.sh

DUMP="./data/sql/db-complete-dump.sql.gz"
case "$*" in
"") ;;
--before-contrib) DUMP="./data/sql/db-dump-before-contrib.sql.gz" ;;
*) die "Unknown argument '$*'. See 'ddev drowl-reset-db --help'." ;;
esac

[ -f "$DUMP" ] || die "The dump ${DUMP} does not exist (it is created by the drowl-init* commands)."

info "Resetting the database to ${DUMP}..."
ddev import-db --database="db" --file="$DUMP"
success "Database reset to ${DUMP}."
