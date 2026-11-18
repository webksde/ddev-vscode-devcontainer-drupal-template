#!/bin/bash

## Description: Install core's node modules and link the configs needed to lint like the drupal.org GitLab CI (also for the VSCode extensions). Run again after adding a custom module or theme, or after updating core.
## Usage: drupal-ci-setup [--force]
## Example: "ddev drupal-ci-setup", "ddev drupal-ci-setup --force"

exec /var/www/html/.ddev/drupal-ci/bin/setup "$@"
