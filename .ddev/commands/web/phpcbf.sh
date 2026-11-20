#!/bin/bash

## HostWorkingDir: true
## Description: Fix PHP coding standard violations, using the same ruleset as the drupal.org GitLab CI phpcs job.
## Usage: phpcbf [--option=value ...] [path ...] [-- tool args]
## Example: "ddev phpcbf web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/phpcbf "$@"
