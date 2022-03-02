#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init
## Example: "drowl-init"

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

# Get newest Drupal Version:
ddev composer create -y 'drupal/recommended-project'

# Starting Drupal DDEV Containers
ddev start

# Add dependencies:
ddev composer require composer/installers cweagans/composer-patches szeidler/composer-patches-cli drupal/core-composer-scaffold drupal/core-project-message drupal/core-recommended drupal/devel drupal/devel_debug_log drupal/devel_php drupal/admin_toolbar drupal/backup_migrate drupal/examples drupal/stage_file_proxy

# Add DEV dependencies (but no modules due to their database relationship):
ddev composer require --dev drupal/core-dev drush/drush phpunit/phpunit drupal/coder phpspec/prophecy-phpunit phpstan/phpstan mglaman/phpstan-drupal phpstan/phpstan-deprecation-rules

# PHP Codesniffer Setup:
ddev composer require squizlabs/php_codesniffer
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf
# Register Drupal's code sniffer rules.
ddev exec phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose
# Make Codesniffer config file writable for ordinary users in container:
ddev exec chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf

# Drush and Site initialisation:
ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y

# Get VSCode Settings:
cp -R .ddev/initiation-additions/.vscode/ .

# get PHPUnit.xml:
cp .ddev/initiation-additions/phpunit.xml web/core

# Set the permission for the default folder:
chmod 0755 ./web/sites/default
chmod 0644 ./web/sites/default/settings.php

# Get settings.php, settings.local.php and services.local.yml:
cp .ddev/initiation-additions/settings.php web/sites/default/settings.php
cp .ddev/initiation-additions/settings.local.php web/sites/default/settings.local.php
cp .ddev/initiation-additions/services.local.yml web/sites/default/services.local.yml

# Get the phpstan.neon:
cp .ddev/initiation-additions/phpstan.neon .

# Get Readme.md and .gitignore
cp .ddev/initiation-additions/README.md .
cp .ddev/initiation-additions/.gitignore .

# Acitvate required dev-modules:
ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search examples stage_file_proxy devel devel_debug_log devel_php backup_migrate -y

# Activate Error Logging:
ddev drush config-set system.logging error_level verbose -y

# Add "patches" and "minimum-stability" section in composer.json:
ddev composer config extra.composer-exit-on-patch-failure true
ddev composer config --json extra.patches.package-mantainer/package "'{\"INSERT WHAT IT DOES\": \"PATH TO PATCH\"}'"
ddev composer config extra.enable-patching true
ddev composer config minimum-stability dev

# Create custom module folder:
mkdir -p web/modules/custom
# Create temp folder:
mkdir -p ./tmp

# Export DB-Dump to data/sql:
mkdir -p ./data/sql
ddev export-db $DDEV_PROJECT > ./data/sql/db-complete-dump.sql
echo "Created full database dump under data/sql/db-complete-dump.sql"

# Ask if .git should be removed:
bool=1
  while [ $bool -eq 1 ]; do
    read -p "Would you like to delete your .git directory and .gitignore file?"$'\n' answer
    case ${answer:0:1} in
      y|Y|yes|Yes|YES )
        echo "Ok, deleting your .gitignore and .git..."
        rm -r ./.git ./.gitignore ./.gitattributes -f
        bool=0
      ;;
      n|N|no|No|NO )
        echo "Ok, I won't delete your .gitignore and .git file"
        bool=0
      ;;
      * )
        echo "I don't understand."
      ;;
    esac
  done

# Give all Project informations:
ddev describe

# Helper Messages
echo "Use 'ddev phpunit path/to/tests' to Test Classes using PHPUnit"
echo "Use 'ddev phpcs path/to/sniff' to check if your Code is using Drupal Coding Standards"
echo "Use 'ddev phpcbf path/to/execute' format your Code using Drupal Coding Standards"
echo "Use 'ddev phpstan path/to/execute' to look for deprecated and 'dirty' code"
