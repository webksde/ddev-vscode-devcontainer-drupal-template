#!/bin/bash

## HostWorkingDir: true
## Description: Use phpunit with xdebug in coverage mode to show test coverage of a folder (e.g. a Drupal module folder).
## Usage: phpunit-coverage [path]
## Example: "ddev phpunit path/to/tests"

if [ $# == 0 ]
then
  enable_xdebug
  phpunit -c /var/www/html/phpunit.xml --coverage-html=./coverage "$PWD"
else
  enable_xdebug
  phpunit -c /var/www/html/phpunit.xml --coverage-html=./coverage "$*"
fi
