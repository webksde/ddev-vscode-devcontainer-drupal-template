#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init
## Example: drowl-init, drowl-init -v 9, drowl-init -v 10
## Flags: [{"Name":"version","Shorthand":"v","Usage":"Set the Drupal Version (Drupal 9 and 10 supported)"}]

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

DRUPAL_VERSION=11;
PHP_VERSION=8.3

if [[ $# = 1 ]]; then
  echo "Missing parameter given. Use 'ddev drowl-init -v 10' instead";
  exit;
fi

if [[ $# = 2 && ( "$1" != "-v" && "$1" != "--version" )]]; then
  echo "Unkown flag '$1' given. Use 'ddev drowl-init -v 10' instead";
  exit;
fi

if [[ $# = 2 && ( "$1" = "-v" || "$1" = "--version" ) && ( "$2" != "9" && "$2" != "10") ]]; then
  echo "Unkown parameter '$2' given. Use 'ddev drowl-init -v 10' instead";
  exit;
fi

# Drupal 9 has reached end-of-life, so requests for it are served the oldest
# version we still support (10). "-v 10" explicitly installs Drupal 10 too;
# for Drupal core development, use 'ddev drowl-init-dev' instead.
if [[ $# = 2 && ( "$1" = "-v" || "$1" = "--version" ) && ( "$2" = "9" || "$2" = "10" ) ]]; then
  DRUPAL_VERSION=10;
  PHP_VERSION=8.3;
fi

echo -e $"\e\n[32mInitialising a Drupal ${DRUPAL_VERSION} environment! This will take about ~5 min...\n\e[0m"

# Ask for the personal settings (git identity, DRUPALCODE_TOKEN), written to
# .ddev/.env.web before the containers start:
bash .ddev/drowl-init/ask-personal-settings.sh

# Remove README.md:
rm ./README.md

# Remove git files (keep .gitignore, it ignores personal settings, secrets and generated files):
rm -r ./.git ./.gitattributes -f

# Create the config.yaml:
ddev config --composer-version="stable" --php-version="${PHP_VERSION}" --docroot="web" --webserver-type="apache-fpm" --project-type="drupal" --disable-settings-management --auto

# "ddev composer create-project" refuses to run with a .gitignore in the project
# root, so park it in .ddev/ meanwhile:
mv ./.gitignore ./.ddev/.drowl-init.gitignore

# Create the composer create command:
ddev composer create-project "drupal/recommended-project:^${DRUPAL_VERSION}"

# Restore the .gitignore (appended, in case the created project brought its own):
cat ./.ddev/.drowl-init.gitignore >> ./.gitignore && rm ./.ddev/.drowl-init.gitignore

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
ddev composer config --no-plugins allow-plugins.symfony/runtime true

# Add general dependencies:
ddev composer require cweagans/composer-patches szeidler/composer-patches-cli oomphinc/composer-installers-extender --no-audit

# Add drupal dependencies:
ddev composer require drupal/devel drupal/devel_php drupal/coffee drupal/backup_migrate drupal/stage_file_proxy drupal/config_inspector drupal/examples --no-audit

# Add DEV dependencies (but no modules due to their database relationship)
# Note, that "drupal/core-dev" contains dependencies like phpunit, phpstan, etc.
ddev composer require --dev drupal/core-dev:^${DRUPAL_VERSION} --update-with-all-dependencies --no-audit
ddev composer require --dev drush/drush drupal/coder:^8 phpstan/phpstan-deprecation-rules kint-php/kint --no-audit

# PHP Codesniffer Setup:
ddev composer require --dev squizlabs/php_codesniffer:^3 --no-audit
# Initialize development environment tools:
ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf

# Drush and Site initialisation:
ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y

# Set the permission for the default folder:
chmod 0755 ./web/sites/default
chmod 0644 ./web/sites/default/settings.php

# Copy all initiation additions (VSCode settings, phpunit.xml, .mcp.json,
# AGENTS.md, CLAUDE.md, jsconfig.json, web/sites/default/settings*, ...):
cp -R .ddev/initiation-additions/. .

# @todo Remove "raw" for all --json config calls (current workaround for https://github.com/ddev/ddev/issues/6628):
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

# Create the custom module, theme and profile folders:
mkdir -p web/modules/custom web/themes/custom web/profiles/custom

# Set up the linters like the drupal.org GitLab CI: install core's node modules
# (eslint, stylelint, prettier, cspell) and link core's configs as fallbacks:
echo 'Installing core node modules for the linters... (This might take a bit)'
ddev exec /var/www/html/.ddev/drupal-ci/setup.sh
# Create temp folder:
mkdir -p ./tmp

# Create private files, config sync and translations directories:
mkdir -p ./files/private ./files/sync ./files/translations

# Export a DB-Dump to "data/sql", BEFORE enabling contrib modules, in cases,
# where they break:
mkdir -p ./data/sql
ddev export-db "$DDEV_PROJECT" > ./data/sql/db-dump-before-contrib.sql.gz
echo "Created full database dump under data/sql/db-dump-before-contrib.sql.gz"

# Acitvate drupal development modules:
ddev drush en coffee stage_file_proxy devel devel_generate devel_php backup_migrate config_inspector examples -y

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

# Project information, useful commands and tips:
bash .ddev/drowl-init/print-summary.sh --title="drowl-init finished!"
