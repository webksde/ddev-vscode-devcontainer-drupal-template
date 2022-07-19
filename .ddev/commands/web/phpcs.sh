#!/bin/bash

## Description: Use phpcs on a Folder for checking Drupal and DrupalPractice Coding standards
## Usage: phpcs [path]
## Example: "ddev phpcs web/modules/contrib/devel"

phpcs -p --extensions=inc,theme,install,module,profile,php,phtml,md --standard=Drupal,DrupalPractice $*
