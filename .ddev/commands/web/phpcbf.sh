#!/bin/bash

## Description: Use phpcbf on a Folder for fixing Code using Drupal and DrupalPractice Coding standards
## Usage: phpcbf [path]
## Example: "ddev phpcbf web/modules/contrib/devel"

phpcbf --standard=Drupal,DrupalPractice $*
