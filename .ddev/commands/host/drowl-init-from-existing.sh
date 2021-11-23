#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using a custom composer file and db dump.
## Usage: drowl-init-from-existing
## Example: "drowl-init-from-existing"
## TODO: Create a --remote tag to initiate a project via remote ssh.

# If there are no flags do this:
if [ $# -eq 0 ] ; then
  read -p "Please put your composer.json in the root-directory of the project and type (y|Y|yes|Yes) to continue..." answer
  case ${answer:0:1} in
    y|Y|yes|Yes|YES )
      echo "Great! Initialising your project with your composer file..."
      ddev composer install
    ;;
    * )
      echo "I don't understand :( Exiting the script..."
      yell() { echo "$0: $*" >&2; }
    ;;
  esac
  # Starting ddev:
  ddev start
  # Drush and Site initialisation:
  ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y

  read -p "Would you like to have development tools enabled?" answer
  case ${answer:0:1} in
    y|Y|yes|Yes|YES )
      echo "Ok! requiring development tools..."
      ddev composer require --dev drupal/core-dev drush/drush phpunit/phpunit drupal/coder phpspec/prophecy-phpunit drupal/devel drupal/devel_debug_log drupal/devel_php
      ddev composer require composer/installers cweagans/composer-patches szeidler/composer-patches-cli drupal/admin_toolbar drupal/backup_migrate drupal/core-composer-scaffold drupal/core-project-message drupal/core-recommended drupal/examples drupal/stage_file_proxy
      # PHP Codesniffer Setup:
      ddev composer require squizlabs/php_codesniffer
      # Initialize development environment tools:
      ddev exec chmod +x vendor/bin/phpcs
      ddev exec chmod +x vendor/bin/phpcbf
      # Register Drupal's code sniffer rules.
      ddev exec phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose
      # Make Codesniffer config file writable for ordinary users in container:
      ddev exec chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf
      # Get VSCode Settings:
      cp -R .ddev/initiation-additions/.vscode/ .
      # get PHPUnit.xml:
      cp .ddev/initiation-additions/phpunit.xml web/core
      # Set the permission for the default folder:
      chmod 755 ./web/sites/default
      # Get settings.php, settings.local.php and services.local.yml:
      cp .ddev/initiation-additions/settings.php web/sites/default
      cp .ddev/initiation-additions/settings.local.php web/sites/default
      cp .ddev/initiation-additions/services.local.yml web/sites/default
      # Create custom module folder:
      mkdir -p web/modules/custom
      # Acitvate required dev-modules:
      ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search examples stage_file_proxy devel devel_debug_log devel_php backup_migrate -y
    ;;
    n|N|no|No|NO )
      echo "Alright! Setting up a clean copy of your project!"
    ;;
    * )
      echo "I don't understand :( Exiting the script..."
      yell() { echo "$0: $*" >&2; }
    ;;
  esac
  # Get Readme.md and .gitignore
  cp .ddev/initiation-additions/README.md .
  cp .ddev/initiation-additions/.gitignore .

  #Import database:
  read -p "Please type in the relative path to your Database dump:" -r src
  echo "Alright! Importing your database...."
  ddev import-db --target-db=db --src=$src
  # Acitvate required dev-modules:
  ddev describe
  exit
fi

# If there is an -r flag do this:
while getopts r flag
do
  case "${flag}" in
          r) echo "TODO: This is not implemented yet!"
                  ;;
          *) echo "Invalid option: -$flag" ;;
  esac
done
