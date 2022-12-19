#!/bin/bash

## HostWorkingDir: true
## Description: Use phpstan on a Folder for checking deprecated and dirty code.
## Usage: phpstan [path]
## Example: "ddev phpstan web/modules/contrib/devel"

if [ $# == 0 ]
then
  phpstan analyse -c /var/www/html/phpstan.neon "$PWD"
else
  phpstan analyse -c /var/www/html/phpstan.neon "$*"
fi
