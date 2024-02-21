#!/bin/bash

## HostWorkingDir: true
## Description: Use stylelint on a Folder for checking Drupal CSS coding standards.
## Usage: stylelint [path]
## Example: "ddev stylelint web/modules/custom/my_module"

if [ $# == 0 ]
then
  npx stylelint --config /var/www/html/web/core/.stylelintrc.json $PWD/**/*.css
else
  npx stylelint --config /var/www/html/web/core/.stylelintrc.json $*/**/*.css
fi
