# ddev-vscode-devcontainer-drupal9-template

## Quick-Start
~~~
git clone git@github.com:webksde/ddev-vscode-devcontainer-drupal9-template.git && cd ddev-vscode-devcontainer-drupal9-template/ && ddev drowl-init
~~~
**Spin up a ready-to-code Drupal 9 CMS DDEV based development container for Visual Studio Code using the power of VSCode Remote-Containers in three commands!** 🚀

Provides a plug and play 🔌 DDEV (Docker) based development environment for VSCode IDE with predefined Drupal CMS best practice
- Extensions
  - GitLens
  - ... TODO
- Settings
  - Linting (Drupal Coding Standards)
  - ... TODO
- Launch configuration
  - Run
  - Test (PHPUnit)
  - Debug (XDebug)
  - Profile (XDebug)
  - ... TODO
- Tooling
  - ... TODO
beautifully packaged for easy project and environment switching.

*Feel free to forked for other Frameworks or improve for lovely Drupal!* ❤️

## Prerequisites
  1. Up to date Version of Ddev, Docker, Chrome/Firefox
  2. VSCode installed on your machine locally
  3. The [Remote Development Extension for VSCode (extension name: ms-vscode-remote.vscode-remote-extensionpack)}(https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.vscode-remote-extensionpack)

## How to use
 1. Clone the the repo to a new empty project directory: `git clone https://github.com/webksde/ddev-vscode-devcontainer-drupal9-template.git`
 2. Start / Restart Docker and make sure it is updated to the current version
 3. Change into the directory: `cd ddev-vscode-devcontainer-drupal9-template`
 4. Use `ddev drowl-init` or `ddev drowl-init-from-existing` to start up the environment / start the environment with an existing composer file and database dump.
 5. You are ready to go! Use `ddev describe` to check the status & URL of your Project and `ddev code` to run your prepared VSCode IDE!

## Tooling
 - Use `ddev code` to attach VSCode to your running Container.
 - Use `ddev phpunit path/to/tests` to Test Classes using PHPUnit.
 - Use `ddev phpunit-coverage path/to/cover` to create a test coverage of the given file-system.
 - Use `ddev phpcs path/to/sniff` to check your Code using Drupal Coding Standards.
 - Use `ddev xdebug on` to turn on xdebug, then in VSCode go to 'Run and Debug', 'Listen for XDebug' and open your Project in the Browser.
 - Use `ddev import-db --src=path/to/src` to import a database file.
 - Use `ddev rename` to rename your project. !THIS COMMAND IS BUGGY, PLEASE DO NOT USE IT YET!
 - Use `ddev dump-db ddev` to dump your main db tablewise.
 - Use `ddev deploy-db ddev` to import your tablewise dump.
  - Note: You can additionally add remote SSH projects under .ddev/commands/web/db-targets
 (BEWARE OF SOME BUGS USING THESE TOOLS! CHECK ISSUES FOR MORE INFORMATION!!)

## Typical Use-Cases:
 - Local Drupal development / testing / evaluation instance from scratch or existing with ready-to-go IDE
 - Module / Theme development or evaluation
 - Contrib module issue forks / merge requests / patch creation (Git clone / commit / push / ...)
 - Simple & quick Drupal 9 Sandbox for offline / local

## Documentation

### Functionality

#### 0. Create an empty DDev Environment
- use ddev start

#### 1. Create an "empty" best-practice working Drupal 9 CMS Development instance
`ddev drowl-init` + `ddev code`
- Sets up best-practice Drupal VSCode IDE
- Sets up ready to log in latest Drupal 9 Copy
- Installs best-practice Drupal 9 Modules & Configuration

#### 2. Create an environment from a local db dump and composer file
`ddev drowl-init-from-existing` + `ddev code`
- Sets up project via composer and db dump
- (Optional) Installs development Modules & Configuration


#### TODO: 3a. Fetch a 1:1 copy of an existing Drupal 9 CMS Project via SSH / Github
TODO
- drowl-init-from-existing --remote "github Link"
- Sets up best-practice development Server
- Fetches existing database
- Fetches existing file structure

#### TODO:  3b. Fetch a development copy of an existing Drupal 9 CMS Project with additional development and debugging tools / modules
(3a.) PLUS
- Optionally installs development modules: TODO

#### 4. TODO: Extract and push database & configuration changes back to the origin
3 a / b PLUS:
- Log database changes to put into update hook
- Log configuration changes to export
- Push to origin

### Use different configuration or settings by default
Fork me! And help us to split configuration smart for future updates :)

### Update the environment
TODO

### Delete the environment:
 1. `ddev delete -y` deletes the container and unlists the project.
 2. Delete the project folder

## Further ddev Tools and add-ons
 - https://github.com/drud/awesome-ddev
 - https://github.com/drud/ddev-contrib
