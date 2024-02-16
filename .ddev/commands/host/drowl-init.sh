#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init
## Example: drowl-init, drowl-init -v 9, drowl-init -v 10
## Flags: [{"Name":"version","Shorthand":"v","Usage":"Set the Drupal Version (Drupal 9 and 10 supported)"}]

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

DRUPAL_VERSION=10;
PHP_VERSION=8.2

if [[ $# = 1 ]]; then
  echo "Missing parameter given. Use either 'ddev drowl-init -v 9' or 'ddev drowl-init -v 10'";
  exit;
fi

if [[ $# = 2 && ( "$1" != "-v" && "$1" != "--version" )]]; then
  echo "Unkown flag '$1' given. Use either 'ddev drowl-init -v 9' or 'ddev drowl-init -v 10'";
  exit;
fi

if [[ $# = 2 && ( "$1" = "-v" || "$1" = "--version" ) && ( "$2" != "9" && "$2" != "10" ) ]]; then
  echo "Unkown parameter '$2' given. Use either 'ddev drowl-init -v 9' or 'ddev drowl-init -v 10'";
  exit;
fi

if [[ $# = 2 && ( "$1" = "-v" || "$1" = "--version" ) && "$2" = 9 ]]; then
  DRUPAL_VERSION=9;
  PHP_VERSION=8.1
fi

echo -e $"\e\n[32mInitialising a Drupal ${DRUPAL_VERSION} environment! This will take about ~5 min...\n\e[0m"

# Remove README.md:
rm ./README.md

# Remove git files:
rm -r ./.git ./.gitignore ./.gitattributes -f

# Create the config.yaml:
ddev config --composer-version="stable" --php-version="${PHP_VERSION}" --docroot="web" --create-docroot --webserver-type="apache-fpm" --project-type="drupal${DRUPAL_VERSION}" --disable-settings-management --auto

# Get Drupal 10:
ddev composer create -y --stability RC "drupal/recommended-project:^${DRUPAL_VERSION}"

# Require the "PHPMyAdmin" plugin:
echo 'Requiring the "ddev-phpmyadmin" plugin...'
ddev get ddev/ddev-phpmyadmin

# Starting Drupal DDEV Containers
ddev start

# Allow specific composer packages:
ddev composer config --no-plugins allow-plugins.cweagans/composer-patches true
ddev composer config --no-plugins allow-plugins.oomphinc/composer-installers-extender true
ddev composer config --no-plugins allow-plugins.szeidler/composer-patches-cli true

# Add dependencies:
ddev composer require composer/installers cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender drupal/core-composer-scaffold:^${DRUPAL_VERSION} drupal/core-project-message drupal/core-recommended:^${DRUPAL_VERSION} drupal/devel drupal/devel_php drupal/admin_toolbar drupal/backup_migrate drupal/stage_file_proxy drupal/config_inspector drupal/examples

# Add DEV dependencies (but no modules due to their database relationship)
# Note, that "drupal/core-dev" contains dependencies like phpunit, phpstan, etc.
ddev composer require --dev drupal/core-dev:^${DRUPAL_VERSION} --update-with-all-dependencies
ddev composer require --dev drush/drush drupal/coder phpstan/phpstan-deprecation-rules kint-php/kint

# PHP Codesniffer Setup:
ddev composer require --dev squizlabs/php_codesniffer
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf

# Drush and Site initialisation:
ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y

# Get VSCode Settings:
cp -R .ddev/initiation-additions/.vscode/ .

# Get PHPUnit.xml:
cp .ddev/initiation-additions/phpunit.xml .

# Get phpstan.neon:
cp .ddev/initiation-additions/phpstan.neon .

# Get cspell.json:
cp .ddev/initiation-additions/cspell.json .

# Get the .prettierrc.json from core, if it exists:
test -e web/core/.prettierrc.json && cp web/core/.prettierrc.json web

# Set the permission for the default folder:
chmod 0755 ./web/sites/default
chmod 0644 ./web/sites/default/settings.php

# Get settings.php, settings.local.php and services.local.yml:
cp .ddev/initiation-additions/settings.php web/sites/default/settings.php
cp .ddev/initiation-additions/settings.local.php web/sites/default/settings.local.php
cp .ddev/initiation-additions/services.local.yml web/sites/default/services.local.yml

# Get packages for eslint and JS code completion:
echo 'Requiring npm dev packages... (This might take a bit)'
cp web/core/package.json .
ddev npm install
# Get jsconfig.json from initiation additions:
cp .ddev/initiation-additions/jsconfig.json .

# Activate Error Logging:
ddev drush config-set system.logging error_level verbose -y

# Add "patches" and "minimum-stability" section in composer.json:
ddev composer config extra.composer-exit-on-patch-failure true
ddev composer config --json extra.patches.package-mantainer/package '{"INSERT WHAT IT DOES": "PATH TO PATCH"}'
ddev composer config extra.enable-patching true
ddev composer config minimum-stability dev

# Add asset-packagist:
ddev composer config --json repositories.asset-packagist '{"type": "composer","url": "https://asset-packagist.org"}'
ddev composer config --json extra.installer-types '["npm-asset", "bower-asset"]'
ddev composer config --json extra.installer-paths.web/libraries/{\$name\} '["type:drupal-library", "type:npm-asset", "type:bower-asset"]'

# Created authenticated test user:
ddev drush user:create max --mail='max@example.com' --password='max' -y

# Create custom module folder:
mkdir -p web/modules/custom
# Create temp folder:
mkdir -p ./tmp

# Create private files directory:
mkdir -p ./files/private

# Export a DB-Dump to "data/sql", BEFORE enabling contrib modules, in cases,
# where they break:
mkdir -p ./data/sql
ddev export-db "$DDEV_PROJECT" > ./data/sql/db-dump-before-contrib.sql.gz
echo "Created full database dump under data/sql/db-dump-before-contrib.sql.gz"

# Acitvate required dev-modules:
ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search stage_file_proxy devel devel_generate devel_php backup_migrate config_inspector examples -y

# Activate kint as default devel variables dumper
ddev drush config-set devel.settings devel_dumper kint -y

# Give authenticated and anonymous users "access devel information" (dsm / kint):
ddev drush role:perm:add anonymous 'access devel information'
ddev drush role:perm:add authenticated 'access devel information'

# Create the "normal" db dump:
ddev export-db "$DDEV_PROJECT" > ./data/sql/db-complete-dump.sql.gz
echo "Created full database dump under data/sql/db-complete-dump.sql.gz"

# Give all Project informations:
ddev describe

# Helper Messages
echo "Use 'ddev code' to attach VSCode to your running Container."
echo "Use 'ddev phpunit relative-path/to/tests' to Test Classes using PHPUnit"
echo "Use 'ddev phpcs relative-path/to/sniff' to lint your PHP Code using Drupal Coding Standards"
echo "Use 'ddev phpcbf relative-path/to/execute' to format your PHP Code using Drupal Coding Standards"
echo "Use 'ddev phpstan relative-path/to/execute' to lint your PHP Code for deprecation"
echo "Use 'ddev eslint relative-path/to/execute (--fix)' to lint / format javascript code based on Drupal Coding Standards."

printf "\nNote, that most of the linting services are also available in the attached VSCode Container as Extensions"
printf "\nFor more informations on the cli tools, visit https://github.com/webksde/ddev-vscode-devcontainer-drupal-template\n"
