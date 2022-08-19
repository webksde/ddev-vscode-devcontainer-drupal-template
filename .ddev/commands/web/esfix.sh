#!/bin/bash

## HostWorkingDir: true
## Description: Use esfix on a folder or file for formatting javascript code based on Drupal Coding Standards.
## Usage: esfix [path]
## Example: "ddev esfix web/modules/contrib/js/my-js-file.js"


if [ $# == 0 ]
then
  npx eslint --fix $PWD; exit 0
else
  npx eslint --fix $*; exit 0
fi
