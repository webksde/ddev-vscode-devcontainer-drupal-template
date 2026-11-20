#!/bin/bash

## HostWorkingDir: true
## Description: Run all checks of the drupal.org GitLab CI (phpcs, phpstan, eslint, stylelint, cspell, phpunit) with a summary. --fix autofixes first, --only= / --skip= select tools.
## Usage: drupal-ci [--fix] [--only=tool,...] [--skip=tool,...] [path ...]
## Example: "ddev drupal-ci web/modules/custom/my_module", "ddev drupal-ci --skip=phpunit web/modules/custom/my_module", "ddev drupal-ci --fix --only=phpcs,eslint web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/drupal-ci "$@"
