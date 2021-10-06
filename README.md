# ddev-vscode-devcontainer-drupal9-template
*Drupal 9 DDEV based development container for Visual Studio Code with Remote - Containers.*

Provides a plug and play DDEV (Docker) based development environment for VSCode IDE with predefined webks GmbH best practice
- Extensions
- Settings
- Launch configuration (Run & Debug)
beautifully packaged for easy project and environment switching.

## Prerequisites:
  1. Up to date Version of Ddev, Docker, Chrome/Firefox
  2. VSCode installed on your machine locally
  3. The "Remote Development" Extension in VSCode (extension name: ms-vscode-remote.vscode-remote-extensionpack)

## How to Use it:
 1. Download the the repo to a new empty project directory: `git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal9-template.git`
 2. Change into the directory: `cd ddev-vscode-devcontainer-drupal9-template`
 3. Use `ddev drowl-init` to start up the environment
 4. You are ready to go! Use `ddev describe` to check the status of your Project!

## Tooling
 - Use `ddev code` to attach VSCode to your running Container.
 - Use `ddev phpunit path/to/tests` to Test Classes using PHPUnit.
 - Use `ddev phpcs path/to/sniff` to check your Code using Drupal Coding Standards.
 - Use `ddev phpcbf path/to/execute` format your Code using Drupal Coding Standards.
 - Use `ddev xdebug on` to turn on xdebug, then in VSCode go to 'Run and Debug', 'Listen for XDebug' and open your Project in the Browser.
 - Use `ddev import-db --src=path/to/src` to import a database file.
 - Use `ddev rename` to rename your project. !THIS COMMAND IS BUGGY, PLEASE DO NOT USE IT YET!
 
 (BEWARE OF SOME BUGS USING THESE TOOLS! CHECK ISSUES FOR MORE INFORMATION!! documentation tag)

## Typical Use-Cases:
- Create a simple Drupal 9 Sandbox for offline / local
 - Contrib Module / Theme evaluation
 - Contrib Module merge request / patch creation (Git clone / commit / push)
 - Custom Module / Theme development & Testing
 - Full project creation
- Fetch an online project copy for
 - Local development / testing / evaluation

## Documentation
### Functionality

#### 0. Create an empty DDev Environment
- use ddev start

#### 1. Create an "empty" best-practice working Drupal 9 CMS Development instance
- Sets up best-practice development Server
- Sets up ready to log in latest Drupal 9 Copy
- Installs best-practice Drupal 9 Modules & Configuration


#### TODO: 2a. Fetch a 1:1 copy of an existing Drupal 9 CMS Project
- drowl-init --project "github Link"
- Sets up best-practice development Server
- Fetches existing database
- Fetches existing file structure

#### TODO:  2b. Fetch a development copy of an existing Drupal 9 CMS Project with additional development and debugging tools / modules
(2a.) PLUS
- Optionally installs development modules: TODO

#### TODO: 3. Extract and push database & configuration changes back to the origin
2 a / b PLUS:
- Log database changes to put into update hook
- Log configuration changes to export
- Push to origin
