#!/bin/bash

## Description: Use phpstan on a Folder for checking deprecated and dirty code.
## Usage: phpstan [path]
## Example: "ddev phpstan web/modules/contrib/devel"

phpstan analyse -c /var/www/html/phpstan.neon "$*"
