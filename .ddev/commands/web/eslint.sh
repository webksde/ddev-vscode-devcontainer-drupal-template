#!/bin/bash

## HostWorkingDir: true
## Description: Lint JavaScript and YAML like the drupal.org GitLab CI eslint job. Add --fix to autofix.
## Usage: eslint [--fix] [--option=value ...] [path ...] [-- tool args]
## Example: "ddev eslint web/modules/custom/my_module", "ddev eslint --fix web/modules/custom/my_module/js/my-file.js"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/eslint "$@"
