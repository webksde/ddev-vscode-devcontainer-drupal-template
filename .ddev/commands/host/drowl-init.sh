#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init [paramters]
## Example: "drowl-init standard"

#TODO: Muss vor ddev start ausgeführt werden
#ddev composer create 'drupal/recommended-project'

ddev composer require --dev drush/drush composer/installers drupal/core-dev phpunit/phpunit phpspec/prophecy-phpunit drupal/coder drupal/devel drupal/devel_debug_log drupal/devel_php -y

ddev composer require drupal/admin_toolbar drupal/core-composer-scaffold drupal/core-project-message drupal/core-recommended drupal/examples drupal/stage_file_proxy

#PHP Codesniffer Setup:
ddev composer require squizlabs/php_codesniffer
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf
# Make Codesniffer config file writable for ordinary users in container:
ddev exec chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf
# Register Drupal's code sniffer rules.
ddev exec phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose

#Drush and Site initialisation:
drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db'


