#!/bin/bash

## HostWorkingDir: true
## Description: Use phpUnit to Debug a Folder
## Usage: phpunit [path]
## Example: "ddev phpunit path/to/tests"

if [ $# == 0 ]
then
  phpunit -c /var/www/html/phpunit.xml $PWD
else
  phpunit -c /var/www/html/phpunit.xml $*
fi
