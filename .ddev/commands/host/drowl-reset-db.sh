#!/bin/bash

## HostWorkingDir: true
## Description: Resets the Drupal database to its original state after project
## initialisation. (or before conrtib modules get installed using
## `--before-contrib`)
## Usage: drowl-reset-db
## Example: "ddev phpunit drowl-reset-db"

# Go down directories, until the current directory is the ddev project folder
# (there is no DDEV_PROJECT_HOST_ABSOLUTE_PATH variable, so we need this
# workaround):
while [[ $PWD != '/' && ${PWD##*/} != "$DDEV_PROJECT" ]]; do cd ..; done
# If no argument given, just simply reset the database to the status after
# project initialisation:
if [[ $# == 0 ]]; then
  ddev import-db --database="db" --file="./data/sql/db-complete-dump.sql.gz"; exit 0
# If given the "--before-contrib", argument reset the database to a state before
# contrib modules where installed:
elif [[ $# == 1 && $* == '--before-contrib' ]]; then
  ddev import-db --database="db" --file="./data/sql/db-dump-before-contrib.sql.gz"; exit 0
fi
