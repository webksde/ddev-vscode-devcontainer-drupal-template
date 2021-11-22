#!/bin/bash

## Description: Use phpunit with xdebug in coverage mode to show test coverage of a folder a Folder
## Usage: phpunit-coverage [path]
## Example: "ddev phpunit path/to/tests"

enable_xdebug
phpunit -c /var/www/html/${DDEV_DOCROOT}/core/phpunit.xml --coverage-html=${DDEV_DOCROOT}/coverage $*

