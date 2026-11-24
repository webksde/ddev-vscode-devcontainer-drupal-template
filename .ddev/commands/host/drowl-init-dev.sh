#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using DROWL Best Practices
## Usage: drowl-init-dev
## Example: drowl-init-dev

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

echo -e $"\e\n[32mInitialising a Drupal DEV environment! This will take about ~5 min...\n\e[0m"

PHP_VERSION=8.5
PROJECT_TYPE="drupal12"

# Ask for the personal settings (git identity, DRUPALCODE_TOKEN), written to
# .ddev/.env.web before the containers start:
bash .ddev/drowl-init/ask-personal-settings.sh

# Remove README.md:
rm ./README.md

# Remove git files (keep .gitignore, it ignores personal settings, secrets and generated files):
rm -r ./.git ./.gitattributes -f

# Create the config.yaml:
ddev config --composer-version="stable" --php-version="${PHP_VERSION}" --docroot="web" --webserver-type="apache-fpm" --project-type="${PROJECT_TYPE}" --disable-settings-management --auto

# For the dev version we are requiring https://github.com/joachim-n/drupal-core-development-project:
# "ddev composer create-project" refuses to run with a .gitignore in the project
# root, so park it in .ddev/ meanwhile:
mv ./.gitignore ./.ddev/.drowl-init.gitignore
ddev composer create-project "joachim-n/drupal-core-development-project"
# Restore the .gitignore (appended, in case the created project brought its own):
cat ./.ddev/.drowl-init.gitignore >> ./.gitignore && rm ./.ddev/.drowl-init.gitignore

# Fix autoload path for PHPUnit testing:
# Create a symlink to the vendor directory so phpunit can find the autoloader
ln -sf ../../vendor repos/drupal/vendor
# Add vendor to git exclude so it isn't tracked
echo "vendor" >> repos/drupal/.git/info/exclude

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
ddev composer require drush/drush drupal/backup_migrate drupal/coffee drupal/examples drupal/config_inspector --no-audit

# @todo These modules are currently not Drupal 12 compatible. Activate them in the future:
# drupal/devel drupal/devel_php drupal/stage_file_proxy

# Add DEV dependencies (but no modules due to their database relationship)
ddev composer require --dev drupal/coder:^8 phpstan/phpstan-deprecation-rules kint-php/kint --no-audit

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

# Ensure PHPUnit/Functional test filesystem targets exist.
#
# When `web/core` is a symlink to `repos/drupal/core`, Drupal's test runner
# effectively treats `repos/drupal` as the application root. In that case it
# expects `repos/drupal/sites/default/files` to exist for `public://`.
mkdir -p ./web/sites/default/files
mkdir -p ./web/sites/default/files/simpletest

# simpletest/ is used for browser test output and other artifacts.
mkdir -p ./web/sites/simpletest/browser_output

# Symlink the test filesystem targets into the Drupal core repo.
# Keep existing directories intact; only symlink when missing or already a link.
if [ ! -e ./repos/drupal/sites/default/files ] || [ -L ./repos/drupal/sites/default/files ]; then
  ln -sf ../../../../web/sites/default/files ./repos/drupal/sites/default/files
fi
if [ ! -e ./repos/drupal/sites/simpletest ] || [ -L ./repos/drupal/sites/simpletest ]; then
  ln -sf ../../../web/sites/simpletest ./repos/drupal/sites/simpletest
fi

# Ensure the directories are writable for the PHP process.
chmod 0777 ./web/sites/default/files ./web/sites/default/files/simpletest
chmod 0777 ./web/sites/simpletest ./web/sites/simpletest/browser_output

# Set up the linters like the drupal.org GitLab CI: install core's node modules
# (eslint, stylelint, prettier, cspell) and link core's configs as fallbacks:
echo 'Installing core node modules for the linters... (This might take a bit)'
mkdir -p web/modules/custom
ddev exec /var/www/html/.ddev/drupal-ci/setup.sh

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
ddev drush en config_inspector -y
# @todo These modules are currently not Drupal 12 compatible. Activate them in the future:
# devel devel_generate devel_php stage_file_proxy

# Activate kint as default devel variables dumper
# @todo Enable again, once support is added:
# ddev drush config-set devel.settings devel_dumper kint -y

# Disable css and js aggregation:
ddev drush config-set system.performance css.preprocess 0 -y
ddev drush config-set system.performance js.preprocess 0 -y

# Give authenticated and anonymous users "access devel information" (dsm / kint):
# @todo Enable again, once support is added:
# ddev drush role:perm:add anonymous 'access devel information'
# ddev drush role:perm:add authenticated 'access devel information'

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

# Project information, useful commands and tips:
bash .ddev/drowl-init/print-summary.sh --title="drowl-init-dev finished!" \
  --tip="Some dev modules like examples or backup_migrate are not installed, as they have no official Drupal 12 support. You can try adjusting their 'core_version_requirement' to make them work."
