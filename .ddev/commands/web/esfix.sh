#!/bin/bash

## Description: Use esfix on a folder or file for formatting javascript code based on Drupal Coding Standards.
## Usage: esfix [path]
## Example: "ddev esfix web/modules/contrib/js/my-js-file.js"

npx eslint --fix $*; exit 0
