#!/bin/bash

## HostWorkingDir: true
## Description: Run PHPStan like the drupal.org GitLab CI phpstan job (uses the project's own phpstan.neon, else the CI default: level 0).
## Usage: phpstan [--option=value ...] [path ...] [-- tool args]
## Example: "ddev phpstan web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/phpstan "$@"
