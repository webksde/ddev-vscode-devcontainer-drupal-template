#!/bin/bash

## Description: Use phpcbf on a Folder for fixing Code using Drupal and DrupalPractice Coding standards
## Usage: phpcbf [path]
## Example: "ddev phpcbf web/modules/contrib/devel"

phpcbf -p --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml,phtml --standard=Drupal,DrupalPractice $*
