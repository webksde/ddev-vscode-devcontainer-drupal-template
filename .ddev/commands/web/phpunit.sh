#!/bin/bash

## Description: Use phpUnit to Debug a Folder
## Usage: phpunit [path]
## Example: "ddev phpunit path/to/tests"

phpunit -c /var/www/html/${DDEV_DOCROOT}/core/phpunit.xml $*
