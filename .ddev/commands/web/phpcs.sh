#!/bin/bash

## HostWorkingDir: true
## Description: Use phpcs on a Folder for checking Drupal and DrupalPractice Coding standards
## Usage: phpcs [path]
## Example: "ddev phpcs web/modules/contrib/devel"

if [ $# == 0 ]
then
  phpcs -p --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml,phtml --standard=Drupal,DrupalPractice $PWD
else
  phpcs -p --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml,phtml --standard=Drupal,DrupalPractice $*
fi

