# DDEV Drupal Template With Attached VSCode
The tools inside this repository will create a modified Drupal environment, focused on development and debugging.

**DO NOT USE IN PRODUCTION**
## Quick-Start
**Spin up a ready-to-code Drupal 9/10 CMS DDEV based development container with Visual Studio Code using the power of VSCode Remote-Containers in four commands!** 🚀

May take ~5 min - only needed once, at initialization:
~~~
mkdir ddev-vscode-drupal && cd ddev-vscode-drupal && git clone git@github.com:webksde/ddev-vscode-devcontainer-drupal-template.git . && ddev drowl-init
~~~
Note: You can also initiate a Drupal 9 environment instead, using `ddev drowl-init --version 9`

---

## Features

Provides a plug and play 🔌 DDEV (Docker) based development environment for VSCode IDE with predefined Drupal CMS best practice
- VS-Code Extensions
  - [Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)
  - [PHP Debug (Using XDebug)](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
  - [PHP Getters & Setters](https://marketplace.visualstudio.com/items?itemName=cvergne.vscode-php-getters-setters-cv)
  - [PHP Namespace Resolver](https://marketplace.visualstudio.com/items?itemName=MehediDracula.php-namespace-resolver)
  - [PHP DocBlocker](https://marketplace.visualstudio.com/items?itemName=neilbrayfield.php-docblocker)
  - [PHPStan](https://marketplace.visualstudio.com/items?itemName=SanderRonde.phpstan-vscode)
  - [PHP Sniffer & Beautifier](https://marketplace.visualstudio.com/items?itemName=ValeryanM.vscode-phpsab)
  - [ESLint](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint)
  - [Prettier](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode)
  - [CSpell](https://marketplace.visualstudio.com/items?itemName=streetsidesoftware.code-spell-checker)
  - [Twig Language 2](https://marketplace.visualstudio.com/items?itemName=mblode.twig-language-2)
  - [GitLens](https://marketplace.visualstudio.com/items?itemName=eamodio.gitlens)
  - [TODO Highlight](https://marketplace.visualstudio.com/items?itemName=wayou.vscode-todo-highlight)
  - [Apache Conf](https://marketplace.visualstudio.com/items?itemName=mrmlnc.vscode-apache)
  - [Composer](https://marketplace.visualstudio.com/items?itemName=DEVSENSE.composer-php-vscode)
  - [Peacock](https://marketplace.visualstudio.com/items?itemName=johnpapa.vscode-peacock)

- VS-Code Launch configuration
  - Listen for XDebug

- CLI-Tooling
  - [PHPUnit](https://phpunit.de/)
  - [PHPUnit Code-Coverage](https://phpunit.de/manual/6.5/en/code-coverage-analysis.html)
  - [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer)
  - [PHPStan](https://phpstan.org/)
  - [XDebug](https://xdebug.org/)
  - [DDEV Tooling](https://ddev.readthedocs.io/en/stable/users/cli-usage/)
  - [ESLint](https://eslint.org/)

- Drupal Development Modules
  - [Coder](https://www.drupal.org/project/coder)
  - [Devel](https://www.drupal.org/project/devel)
  - [Devel PHP](https://www.drupal.org/project/devel_php)
  - [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
  - [Backup Migrate](https://www.drupal.org/project/backup_migrate)
  - [Stage File Proxy](https://www.drupal.org/project/stage_file_proxy)
  - [Config Inspector](https://www.drupal.org/project/config_inspector)
  - [Examples](https://www.drupal.org/project/examples)
  - [Web Profiler](https://www.drupal.org/project/webprofiler)

Beautifully packaged for easy project and environment switching.

*Feel free to fork for other Frameworks or improve for lovely Drupal!* ❤️

---

## Prerequisites
  1. Up to date Version of DDEV, Docker, Chrome/Firefox
  2. VSCode installed on your machine locally
  3. The [Remote Development Extension for VSCode (extension name: ms-vscode-remote.vscode-remote-extensionpack)](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.vscode-remote-extensionpack)

---

## How to use
 1. Create a project folder and switch into it: `mkdir project-folder && cd project-folder`
 2. Clone the repository into the just created folder: `git clone git@github.com:webksde/ddev-vscode-devcontainer-drupal-template.git .`
 3. Use either
    1. `ddev drowl-init` to directly start up the environment using Drupal 10 with VSCode / Drupal Best Practice Tools
       1. Alternatively you can explicitly choose the Drupal version, using `ddev drowl-init -v 9` for Drupal 9, or `ddev drowl-init -v 10` for Drupal 10. Running the command without a flag, will initiate a Drupal 10 environment.
    2. `ddev drowl-init-from-existing` to start the environment with an existing composer file and database dump (and alternatviely also Dev Tools enabled).
 4. You are ready to go! Use `ddev describe` to check the status & URLs of your Project and `ddev code` to run your prepared VSCode IDE!
    1. Note, when inside the attached VSCode go to "Extensions" and type in "@recommended" to reveal all the necessary Extensions. Installing them is recommended!

## Typical Use-Cases:
 - Local Drupal development / testing / evaluation instance from scratch or existing with ready-to-go IDE
 - Module / Theme development or evaluation
 - Contrib module issue forks / merge requests / patch creation (Git clone / commit / push / ...)
 - Simple & quick Drupal 10 Sandbox for offline / local

---

## Documentation
### Tooling
 - Use `ddev code` to attach VSCode to your running Container.
 - Use `ddev phpunit path/to/tests` to Test Classes using PHPUnit.
 - Use `ddev phpunit-coverage path/to/cover` to create a test coverage of the given file-directory.
 - Use `ddev phpcs path/to/sniff` to check your Code using Drupal Coding Standards.
 - Use `ddev phpstan path/to/execute` to look for deprecated and 'dirty' code.
 - Use `ddev eslint path/to/sniff (--fix)` for linting / auto-fixing javascript code based on Drupal Coding Standards.
 - Use `ddev xdebug on` to turn on xdebug, then in VSCode go to 'Run and Debug', 'Listen for XDebug' and open your Project in the Browser.
 - Use `ddev import-db --target-db=db --src=db.sql.gz` to import a database file.
 - Use `ddev dump-db ddev` to dump your main database tablewise.
 - Use `ddev deploy-db ddev` to import your tablewise dump.
  - Note: You can additionally add remote SSH projects under .ddev/commands/web/db-targets

### Delete the environment:
 1. `ddev delete -y` deletes the container and unlists the project.
 2. Delete the project folder

### Further ddev Tools and add-ons
 - https://github.com/drud/awesome-ddev
 - https://github.com/drud/ddev-contrib
---

## FAQ / Troubleshooting:
### *How do I install ddev?*

See https://ddev.readthedocs.io/en/stable/users/install/ddev-installation
We recommend to use *brew* for all kinds of installation, as it's easy to install and update

### *How do I update dddev?*

See above. For brew simply use `brew update && brew upgrade`

### *I can not execute the custom "ddev drowl-init" command*

Make sure you have the newest ddev and docker version and try restarting docker first. If the problem still persists, make sure you do not have two ddev projects with the same name!
If there are no duplicate ddev projects, there might have been a ddev project with the same name in the past, which was not properly deleted using `ddev delete`. Check your Docker Container instances and delete the old Docker Cluster.

### *I used "ddev drowl-init-from-existing" and now my Web-Server can't reach the Database*

We are currently investigating this problem. It has something todo with ddev creating a new database when importing a database dump through their db command. In the meantime you can use `ddev drowl-init` and import your database dump after initialisation through PHPMyAdmin (and swap the composer.json).
