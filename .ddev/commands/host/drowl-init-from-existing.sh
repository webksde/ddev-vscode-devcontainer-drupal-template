#!/usr/bin/env bash

## Description: Startup A Drupal DDEV Environment, using a custom composer file and db dump.
## Usage: drowl-init-from-existing
## Example: "drowl-init-from-existing"
## TODO: Create a --remote tag to initiate a project via remote ssh.

# exit when any command fails
set -e
# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG

# If there are no flags do this:
if [ $# -eq 0 ] ; then
  bool=1
  define_stage_file_proxy=0
  while [ $bool -eq 1 ]; do
    read -p "Please put your composer.json in the root-directory of the project and type 'yes' to continue..." answer
    case ${answer:0:1} in
      y|Y|yes|Yes|YES )
        echo "Great! Initialising your project with your composer file..."
        # Use composer update -W instead of install here for existing projects to run the expected hooks:
        ddev composer update -W
        bool=0
      ;;
      * )
        echo "I don't understand."
      ;;
    esac
  done
  # Starting ddev:
  ddev start
  bool=1
  while [ $bool -eq 1 ]; do
    read -p "Would you like to have development tools enabled? (WARNING: This changes your composer.json and Drupal configuration! You should NOT push changes back to production afterwards!)"$'\n' answer
    case ${answer:0:1} in
      y|Y|yes|Yes|YES )
        echo "Ok! requiring development tools..."
        echo "Development tools were added to composer.json and Drupal Config of this project! Do NOT push back to production!" > WARNING-DO-NOT-PUSH-BACK-TO-PRODUCTION.txt
        echo "Created a WARNING-DO-NOT-PUSH-BACK-TO-PRODUCTION.txt"
        ddev composer require --dev cweagans/composer-patches szeidler/composer-patches-cli drupal/admin_toolbar drupal/backup_migrate drupal/examples drupal/stage_file_proxy drupal/devel drupal/devel_debug_log drupal/devel_php drupal/coder
        ddev composer require --dev drupal/core-dev^10 drush/drush phpunit/phpunit phpspec/prophecy-phpunit phpstan/phpstan mglaman/phpstan-drupal phpstan/phpstan-deprecation-rules phpstan/extension-installer -W
        # PHP Codesniffer Setup:
        ddev composer require squizlabs/php_codesniffer
        # Initialize development environment tools:
        ddev exec chmod +x vendor/bin/phpcs
        ddev exec chmod +x vendor/bin/phpcbf
        # Get VSCode Settings:
        cp -R .ddev/initiation-additions/.vscode/ .
        # Get phpstan.neon:
        cp .ddev/initiation-additions/phpstan.neon .
        # get PHPUnit.xml:
        cp .ddev/initiation-additions/phpunit.xml web/core
        # Set the permission for the default folder:
        if [ -d "./web/sites/default" ] && [ -f "./web/sites/default/settings.php" ];
        then
          chmod 0755 ./web/sites/default;
          chmod 0644 ./web/sites/default/settings.php;
        fi
        # Get settings.php, settings.local.php and services.local.yml:
        mkdir -p ./web/sites/default
        cp .ddev/initiation-additions/settings.php web/sites/default/settings.php
        cp .ddev/initiation-additions/settings.local.php web/sites/default/settings.local.php
        cp .ddev/initiation-additions/services.local.yml web/sites/default/services.local.yml
        # Get the phpstan.neon:
        cp .ddev/initiation-additions/phpstan.neon .
        # Get packages for eslint:
        echo 'Requiring ESLint npm packages'
        ddev exec npm install --save-dev eslint@^8.9.0
        ddev exec npm install --save-dev eslint-plugin-import@^2.25.3 eslint-plugin-jsx-a11y@^6.5.1 eslint-plugin-react@^7.28.0 eslint-plugin-react-hooks@^4.3.0
        ddev exec npm install --save-dev eslint-config-airbnb@^19.0.4 prettier@^2 eslint-config-prettier@^8.4.0 eslint-plugin-prettier@^4.0
        ddev exec npm install --save-dev eslint-plugin-jquery@^1.5.1 eslint-plugin-yml@^0.14.0
        # Create temp folder and custom module folder and private folder:
        mkdir -p ./files/private
        mkdir -p web/modules/custom
        mkdir -p ./tmp
        define_stage_file_proxy=1
        bool=0
      ;;
      n|N|no|No|NO )
        echo "Alright! Setting up a clean copy of your project!"
        bool=0
      ;;
      * )
        echo "I don't understand."
      ;;
    esac
  done

  #Import database:
  read -p "Please type in the project-root relative path to your Database dump (e.g. dump.mysql.gz). Supports several file formats, including: .sql, .sql.gz, .mysql, .mysql.gz, .tar, .tar.gz, and .zip:"$'\n' -r src
  echo "Alright! Importing your database...."
  ddev import-db --target-db=db --src="$src"
  # Drush and Site initialisation:
  ddev drush si --account-name 'admin' --account-pass 'admin' --account-mail 'admin@admin.de' --site-mail 'site@mail.de' --db-url 'mysql://db:db@db/db' -y
   if [ $define_stage_file_proxy -eq 1 ] ; then
    # Acitvate required dev-modules:
    ddev drush en admin_toolbar admin_toolbar_tools admin_toolbar_search examples stage_file_proxy devel devel_debug_log devel_php backup_migrate -y
    read -p "Please provide the origin website for the stage_file_proxy module (e.g. 'https://www.example.com')"$'\n' -r site
    echo "Alright! setting stage file proxy origin..."
    # Set stage_file_proxy origin:
    ddev drush config-set stage_file_proxy.settings origin "$site"
  fi
  # Remove all git files:
  rm -r ./.git ./.gitignore ./.gitattributes -f
  bool=1
  while [ $bool -eq 1 ]; do
    read -p "Would you like to create assets, files, log and scipts folders? )"$'\n' answer
    case ${answer:0:1} in
      y|Y|yes|Yes|YES )
        echo "Creating the folders..."
        mkdir -p ./assets ./files ./log ./scipts
        bool=0
      ;;
      n|N|no|No|NO )
        echo "Alright! Setting up a clean copy of your project!"
        bool=0
      ;;
      * )
        echo "I don't understand."
      ;;
    esac
  done
  # Acitvate required dev-modules:
  ddev describe
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
