#!/bin/bash

## HostWorkingDir: true
## Description: Check spelling like the drupal.org GitLab CI cspell job. Add --words-only --unique to list the unknown words only.
## Usage: cspell [--option=value ...] [path ...] [-- tool args]
## Example: "ddev cspell web/modules/custom/my_module"

# Paths default to the current directory. Each path is checked as part of its
# project (module, theme, profile or recipe), see .ddev/drupal-ci/lib/common.sh.
exec /var/www/html/.ddev/drupal-ci/bin/cspell "$@"
