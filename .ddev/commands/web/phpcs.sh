#!/bin/bash

## HostWorkingDir: true
## Description: Check PHP code against the Drupal coding standards, like the drupal.org GitLab CI phpcs job (uses the project's own phpcs.xml(.dist), else the CI default).
## Usage: phpcs [--option=value ...] [path ...] [-- tool args]
## Example: "ddev phpcs web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/phpcs "$@"
