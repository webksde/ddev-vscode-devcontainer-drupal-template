#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init-dev
## Example: drowl-init-dev

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

echo -e $"\e\n[32mInitialising a Drupal DEV environment! This will take about ~5 min...\n\e[0m"

PHP_VERSION=8.3

# Remove README.md:
rm ./README.md

# Remove git files:
rm -r ./.git ./.gitignore ./.gitattributes -f

# Create the config.yaml:
ddev config --composer-version="stable" --php-version="${PHP_VERSION}" --docroot="web" --webserver-type="apache-fpm" --project-type="drupal" --disable-settings-management --auto

# For the dev version we are requiring https://github.com/joachim-n/drupal-core-development-project:
ddev composer create -y "joachim-n/drupal-core-development-project"

# Update the config:
ddev config --update

# Require the "PHPMyAdmin" plugin:
echo 'Requiring the "ddev-phpmyadmin" plugin...'
ddev get ddev/ddev-phpmyadmin
echo 'Requiring the "ddev-selenium-standalone-chrome" plugin...'
ddev get ddev/ddev-selenium-standalone-chrome

# Starting Drupal DDEV Containers
ddev start

# Allow specific composer packages:
ddev composer config --no-plugins allow-plugins.cweagans/composer-patches true
ddev composer config --no-plugins allow-plugins.oomphinc/composer-installers-extender true
ddev composer config --no-plugins allow-plugins.szeidler/composer-patches-cli true
ddev composer config --no-plugins allow-plugins.tbachert/spi true

# Add general dependencies:
ddev composer require cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender --no-audit

# Add drupal dependencies:
ddev composer require drupal/devel drupal/devel_php drupal/admin_toolbar drupal/backup_migrate drupal/stage_file_proxy drupal/config_inspector drupal/examples --no-audit

# Add DEV dependencies (but no modules due to their database relationship)
ddev composer require --dev drupal/coder phpstan/phpstan-deprecation-rules kint-php/kint --no-audit

# PHP Codesniffer Setup:
ddev composer require --dev squizlabs/php_codesniffer --no-audit
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf

# Drush and Site initialisation:
ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y

# Get VSCode Settings:
cp -R .ddev/initiation-additions/.vscode/ .

# Get phpstan.neon:
cp .ddev/initiation-additions/phpstan.neon .

# Get cspell.json:
cp .ddev/initiation-additions/cspell.json .

# Get the .prettierrc.json from core, if it exists:
test -e web/core/.prettierrc.json && cp web/core/.prettierrc.json web

# Get the cspell.json from core, if it exists:
test -e web/core/cspell.json && cp web/core/cspell.json .

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
ddev npm install --no-audit
# Get jsconfig.json from initiation additions:
cp .ddev/initiation-additions/jsconfig.json .

# Add "patches" and "minimum-stability" section in composer.json:
ddev composer config extra.composer-exit-on-patch-failure true
ddev exec --raw composer config extra.patches.package-mantainer/package --json '{"description": "path/to/patch"}'
ddev composer config extra.enable-patching true
ddev composer config minimum-stability dev

# Add asset-packagist:
ddev exec --raw composer config repositories.asset-packagist --json '{"type": "composer","url": "https://asset-packagist.org"}'
ddev exec --raw composer config extra.installer-types --json '["npm-asset", "bower-asset"]'
ddev exec --raw composer config extra.installer-paths.web/libraries/{\$name\} --json '["type:drupal-library", "type:npm-asset", "type:bower-asset"]'

# Activate Error Logging:
ddev drush config-set system.logging error_level verbose -y

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

# Acitvate drupal development modules:
ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search stage_file_proxy devel devel_generate devel_php config_inspector -y

# Activate kint as default devel variables dumper
ddev drush config-set devel.settings devel_dumper kint -y

# Disable css and js aggregation:
ddev drush config-set system.performance css.preprocess 0 -y
ddev drush config-set system.performance js.preprocess 0 -y

# Give authenticated and anonymous users "access devel information" (dsm / kint):
ddev drush role:perm:add anonymous 'access devel information'
ddev drush role:perm:add authenticated 'access devel information'

# Create the "normal" db dump:
ddev export-db "$DDEV_PROJECT" > ./data/sql/db-complete-dump.sql.gz
echo "Created full database dump under data/sql/db-complete-dump.sql.gz"

# Clean the drupal core git repository:

echo "Cleaning the Drupal Core Repository..."
cd web/core
git add --all
git reset --hard
git pull
cd ../../

# Get PHPUnit.xml:
cp phpunit-ddev.xml phpunit.xml
# Delete old phpunit.xml:
rm -f phpunit-ddev.xml

# Give all Project informations:
ddev describe

# Notice about debugging inside attached VS-Code:
echo -e $'\e\n[33mNOTE: To debug inside the attached VS-Code instance, run `ddev config global --xdebug-ide-location=container`\n\e[0m'

echo -e $'\e\n[33mSome dev modules like examples, admin_toolbar or backup_migrate are not installed, as they have no official Drupal 11 support. But you can try tinkering with their respective `core_version_requirement` entry to make them work for Drupal 11.\n\e[0m'

# Helper Messages
echo "Use 'ddev code' to attach VSCode to your running Container."
echo "Use 'ddev phpunit path/to/tests' to Test Classes using PHPUnit."
echo "Use 'ddev phpunit-coverage path/to/cover' to create a test coverage of the given file-directory."
echo "Use 'ddev phpcs path/to/sniff' to check your Code using Drupal Coding Standards."
echo "Use 'ddev phpstan path/to/execute' to look for deprecated and 'dirty' code."
echo "Use 'ddev eslint path/to/sniff (--fix)' for linting / auto-fixing javascript code based on Drupal Coding Standards."
echo "Use 'ddev stylelint web/modules/custom/my_module' for linting css files based on Drupal Coding Standards."
echo "Use 'ddev xdebug on' to turn on xdebug, then in VSCode go to 'Run and Debug', 'Listen for XDebug' and open your Project in the Browser."
echo "Use 'ddev drowl-reset-db' to reset the database to its state after initial startup."
echo "Use 'ddev import-db --target-db=db --src=db.sql.gz' to import a database file."

printf "\nNote, that most of the linting services are also available in the attached VSCode Container as Extensions"
printf "\nFor more informations on the cli tools, visit https://github.com/webksde/ddev-vscode-devcontainer-drupal-template\n"
