#!/bin/bash

## Description: Use phpcs on a Folder using Drupal and DrupalPractice standards
## Usage: phpcs [path]
## Example: "ddev phpcs web/modules/contrib/devel

phpcs --standard=Drupal,DrupalPractice $*

