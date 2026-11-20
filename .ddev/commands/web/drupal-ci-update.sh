#!/bin/bash

## Description: Update the default configs and helper scripts the drupal.org GitLab CI jobs use to the newest gitlab_templates 1.x release (1.x-latest, like contrib projects), then refresh the linter setup.
## Usage: drupal-ci-update
## Example: "ddev drupal-ci-update"

# shellcheck source=../../drupal-ci/lib/common.sh
source /var/www/html/.ddev/drupal-ci/lib/common.sh

ci_download_ci_files
ci_info "Updated the drupal.org GitLab CI files in .ddev/drupal-ci ($CI_FILES_REF)."
exec /var/www/html/.ddev/drupal-ci/setup.sh
