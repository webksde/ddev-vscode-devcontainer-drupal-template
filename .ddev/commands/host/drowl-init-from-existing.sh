#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using a custom composer file and db dump.
## Usage: drowl-init-from-existing
## Example: "drowl-init-from-existing"
## TODO: Create a --remote tag to initiate a project via remote ssh.

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

# Create the config.yaml:
ddev config --composer-version="stable" --php-version="8.2" --docroot="web" --create-docroot --webserver-type="apache-fpm" --project-type="drupal10" --disable-settings-management --auto

bool=1
while [ $bool -eq 1 ]; do
  read -p $'\n\e[33mThis command will NOT initialize a production ready copy of your Website! It will create a modified environment, focused on development and debugging.\n\nDO NOT PUSH TO PRODUCTION!\n\nContinue anyway (y/n)?\e[0m'$'\n' answer
  case ${answer:0:1} in
  y | Y | yes | Yes | YES)
    echo -e $'\e\n[32mInitiating...\n\e[0m'
    bool=0
    ;;
  n | N | no | No | NO)
    echo -e $'\e\n[31mAborting...\n\e[0m'
    exit
    ;;
  *)
    echo -e $'\e\n[33mI do not understand. Please repeat that.\n\e[0m'
    ;;
  esac
done

# If there are no flags do this:
if [ $# -eq 0 ]; then
  define_stage_file_proxy=0
  read -p $'\e[36mPlease put your composer.json in the root directory of the project. Make sure any custom composer "scripts" and "scaffold" entries are removed beforehand (Enter to continue)!\e[0m'
  echo -e $'\e\n[32mGreat! Initialising your project with your composer file...\n\e[0m'
  # Use composer update -W instead of install here for existing projects to run the expected hooks:
  ddev composer update -W
  # Starting ddev:
  ddev start
  bool=1
  while [ $bool -eq 1 ]; do
    read -p $'\e\n[33mWould you like to have development tools enabled (y/n)?\e[0m'$'\n' answer
    case ${answer:0:1} in
    y | Y | yes | Yes | YES)
      echo -e $'\e\n[32mOk! requiring development tools...\n\e[0m'
      ddev composer require --dev cweagans/composer-patches szeidler/composer-patches-cli drupal/admin_toolbar drupal/backup_migrate drupal/examples drupal/stage_file_proxy drupal/devel drupal/devel_debug_log drupal/devel_php drupal/coder drupal/examples drupal/webprofiler
      ddev composer require --dev drupal/core-dev:^10 drush/drush phpunit/phpunit:^9.5 phpspec/prophecy-phpunit phpstan/phpstan mglaman/phpstan-drupal phpstan/phpstan-deprecation-rules phpstan/phpstan-phpunit phpstan/extension-installer -W
      # PHP Codesniffer Setup:
      ddev composer require squizlabs/php_codesniffer
      # Initialize development environment tools:
      ddev exec chmod +x vendor/bin/phpcs
      ddev exec chmod +x vendor/bin/phpcbf
      # Get VSCode Settings:
      cp -R .ddev/initiation-additions/.vscode/ .
      # Get phpstan.neon:
      cp .ddev/initiation-additions/phpstan.neon .
      # Get cspell.json:
      cp .ddev/initiation-additions/cspell.json .
      # get PHPUnit.xml:
      cp .ddev/initiation-additions/phpunit.xml web/core
      # Set the permission for the default folder:
      if [ -d "./web/sites/default" ] && [ -f "./web/sites/default/settings.php" ]; then
        chmod 0755 ./web/sites/default
        chmod 0644 ./web/sites/default/settings.php
      fi
      # Get settings.php, settings.local.php and services.local.yml:
      mkdir -p ./web/sites/default
      cp .ddev/initiation-additions/settings.php web/sites/default/settings.php
      cp .ddev/initiation-additions/settings.local.php web/sites/default/settings.local.php
      cp .ddev/initiation-additions/services.local.yml web/sites/default/services.local.yml
      # Get the phpstan.neon:
      cp .ddev/initiation-additions/phpstan.neon .
      # Get packages for eslint:
      echo 'Requiring ESLint npm packages...'
      ddev npm install --save-dev eslint@latest
      ddev npm install --save-dev eslint-config-airbnb-base@latest prettier@latest eslint-config-prettier@latest eslint-plugin-prettier@latest
      ddev npm install --save-dev eslint-plugin-yml@latest
      # Create temp folder and custom module folder and private folder:
      mkdir -p ./files/private
      mkdir -p web/modules/custom
      mkdir -p ./tmp
      define_stage_file_proxy=1
      bool=0
      ;;
    n | N | no | No | NO)
      echo -e $'\e\n[32mAlright! Setting up a clean copy of your project!\n\e[0m'
      bool=0
      ;;
    *)
      echo -e $'\e\n[33mI do not understand. Please repeat that.\n\e[0m'
      ;;
    esac
  done

  #Import database:
  read -p $'\e\n[33mPlease type in the project root relative path to your Database dump (e.g. "./dump.mysql.gz", quotes are NOT required, even if the file name contains spaces). Supports several file formats, including: .sql, .sql.gz, .mysql, .mysql.gz, .tar, .tar.gz, and .zip:\e[0m '$'\n' -r src
  echo -e $'\e\n[32mAlright! Importing your database...\n\e[0m'
  ddev import-db --target-db=db --src="$src"
  # Drush and Site initialisation:
  ddev drush ucrt admin --password admin
  if [ $define_stage_file_proxy -eq 1 ]; then
    # Acitvate required dev-modules:
    ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search examples stage_file_proxy devel devel_debug_log devel_php backup_migrate examples webprofiler -y
    # Give authenticated and anonymous users "access devel information" (dsm / kint):
    ddev drush role:perm:add anonymous 'access devel information'
    ddev drush role:perm:add authenticated 'access devel information'
    # Created authenticated test user:
    ddev drush user:create max --mail='max@example.com' --password='max' -y
    # Get stage file proxy website:
    read -p $'\e\n[36mPlease provide the origin website for the stage_file_proxy module (e.g. "https://www.example.com")\e[0m'$'\n' -r site
    echo -e $'\e\n[32mAlright! setting stage file proxy origin...\n\e[0m'
    # Set stage_file_proxy origin:
    ddev drush config-set stage_file_proxy.settings origin "$site"
  fi
  # Remove all git files, to ensure nothing gets pushed:
  rm -r ./.git ./.gitignore ./.gitattributes -f
  bool=1
  while [ $bool -eq 1 ]; do
    read -p $'\e[33mWould you like to create assets, files, log and scipts folders (y/n)?\e[0m '$'\n' answer
    case ${answer:0:1} in
    y | Y | yes | Yes | YES)
      echo -e $'\e\n[32mCreating the folders...\n\e[0m'
      mkdir -p ./assets ./files ./log ./scipts
      bool=0
      ;;
    n | N | no | No | NO)
      echo -e $'\e\n[32mAlright! Setting up a clean copy of your project!\n\e[0m'
      bool=0
      ;;
    *)
      echo -e $'\e\n[33mI do not understand. Please repeat that.\n\e[0m'
      ;;
    esac
  done
  # Acitvate required dev-modules:
  ddev describe
fi

# If there is an -r flag do this:
while getopts r flag; do
  case "${flag}" in
  r)
    echo "TODO: This is not implemented yet!"
    ;;
  *) echo "Invalid option: -$flag" ;;
  esac
done
