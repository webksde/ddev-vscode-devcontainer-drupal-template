#!/bin/bash

## HostWorkingDir: true
## Description: Lint CSS like the drupal.org GitLab CI stylelint job. Add --fix to autofix.
## Usage: stylelint [--fix] [--option=value ...] [path ...] [-- tool args]
## Example: "ddev stylelint web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/stylelint "$@"
