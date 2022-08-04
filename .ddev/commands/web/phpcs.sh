#!/bin/bash

## Description: Use phpcs on a Folder for checking Drupal and DrupalPractice Coding standards
## Usage: phpcs [path]
## Example: "ddev phpcs web/modules/contrib/devel"

phpcs -p --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml,phtml --standard=Drupal,DrupalPractice $*
