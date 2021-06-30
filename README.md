# ddev-vscode-devcontainer-drupal9-template
*Drupal 9 DDEV based development container for Visual Studio Code with Remote - Containers.*

Provides a plug and play DDEV (Docker) based development environment for VSCode IDE with predefined webks GmbH best practice
- Extensions
- Settings
- Launch configuration (Run & Debug)
beautifully packaged for easy project and environment switching.

**TODO - This is work in progress to provide best-practice from our previous setups**

## Typical Use-Cases:
- Create a simple Drupal 9 Sandbox for offline / local
 - Contrib Module / Theme evaluation
 - Contrib Module merge request / patch creation (Git clone / commit / push)
 - Custom Module / Theme development & Testing
 - Full project creation
- Fetch an online project copy for
 - Local development / testing / evaluation

## Future Integrations
**Project Services and Configurations:**
 - Drupal 9 CMS Support & Template
 - Apache Webserver
 - PHP-Support
 - Maria DB
 - Drush
 - Mailhog
 - Phpmyadmin
 - Composer
 
**VS-Code Support**
 - Dev-Container Support
 - Preexisting "Best Practice" Extensions and Settings
 - XDebug, PHPCS, PHP-CS-Fixer, PHP-Unit 

**Drupal "Best Practice" Development Template**
 - stage_file_proxy  
 - examples  
 - devel_php  
 - devel_debug_log  
 - devel  
 - admin_toolbar
 
**Additional Settings and Files**
 - init.sh and custom_init.sh
 - ddev.yml and custom_ddev.yml
 

## Documentation
### Functionality
#### 1. Create an "empty" best-practice working Drupal 9 CMS Development instance
- Sets up best-practice development Server
- Sets up ready to log in latest Drupal 9 Copy
- Installs best-practice Drupal 9 Modules & Configuration

#### 2a. Fetch a 1:1 copy of an existing Drupal 9 CMS Project
- Sets up best-practice development Server
- Fetches existing database
- Fetches existing file structure

#### 2b. Fetch a development copy of an existing Drupal 9 CMS Project with additional development and debugging tools / modules
(2a.) PLUS
- Optionally installs development modules: TODO

#### 3. Extract and push database & configuration changes back to the origin
2 a / b PLUS:
- Log database changes to put into update hook
- Log configuration changes to export
- Push to origin
