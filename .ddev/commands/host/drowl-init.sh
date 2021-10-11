#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init
## Example: "drowl-init"

#Get newest Drupal Version:
ddev composer create -y 'drupal/recommended-project' --stability 'dev'

#Starting Drupal DDEV Containers
ddev start

ddev composer require --dev drupal/core-dev drush/drush phpunit/phpunit:^9.5 phpspec/prophecy-phpunit drupal/coder drupal/devel drupal/devel_debug_log drupal/devel_php

ddev composer require composer/installers drupal/admin_toolbar drupal/backup_migrate drupal/core-composer-scaffold drupal/core-project-message drupal/core-recommended drupal/examples drupal/stage_file_proxy

#PHP Codesniffer Setup:
ddev composer require squizlabs/php_codesniffer
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf
# Register Drupal's code sniffer rules.
ddev exec phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose
# Make Codesniffer config file writable for ordinary users in container:
ddev exec chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf

#Drush and Site initialisation:
ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db'

#Copy VSCode Settings
cp -R .ddev/initiation-additions/.vscode/ .

#Create PHPUnit.xml
cp .ddev/initiation-additions/phpunit.xml web/core

#Get Readme.md and .gitignore
cp .ddev/initiation-additions/README.md .
cp .ddev/initiation-additions/.gitignore .

#Acitvate required dev-modules:
ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search examples stage_file_proxy devel devel_debug_log devel_php backup_migrate -y

#Give all Project informations:
ddev describe

#Helper Messages
echo "Use 'ddev phpunit path/to/tests' to Test Classes using PHPUnit"
echo "Use 'ddev phpcs path/to/sniff' to check if your Code is using Drupal Coding Standards"
echo "Use 'ddev phpcbf path/to/execute' format your Code using Drupal Coding Standards"
