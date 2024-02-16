#!/usr/bin/env bash

## Description: Startup A Drupal DDEV DEV Environment, using DROWL Best Practices
## Usage: drowl-init-dev
## Example: drowl-init-dev

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

echo -e $"\e\n[32mInitialising a Drupal 11.x-dev environment! This will take about ~5 min...\n\e[0m"

# Remove README.md:
rm ./README.md

# Remove git files:
rm -r ./.git ./.gitignore ./.gitattributes -f

# Create the config.yaml:
# Note, that project-type "drupal11" isn't supported yet:
ddev config --composer-version="dev" --php-version="8.2" --docroot="web" --create-docroot --webserver-type="apache-fpm" --project-type="drupal10" --disable-settings-management --auto

# Get Drupal 10:
ddev composer create -y --stability dev "drupal/recommended-project:^11.x-dev"

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
ddev composer require composer/installers cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender drupal/core-composer-scaffold:^11.x-dev drupal/core-project-message drupal/core-recommended:^11.x-dev

# Add DEV dependencies (but no modules due to their database relationship)
# Note, that "drupal/core-dev" contains dependencies like phpunit, phpstan, etc.
ddev composer require --dev drupal/core-dev:^11.x-dev --update-with-all-dependencies
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

# Get packages for eslint:
echo 'Requiring ESLint npm packages...'
ddev npm install --save-dev eslint@latest
ddev npm install --save-dev eslint-config-airbnb-base@latest prettier@latest eslint-config-prettier@latest eslint-plugin-prettier@latest
ddev npm install --save-dev eslint-plugin-yml@latest

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

# Create the db dump:
mkdir -p ./data/sql
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