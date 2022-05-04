#!/bin/bash

## Description: Use esfix on a folder or file for linting javascript code based on Drupal Coding Standards.
## Usage: eslint [path]
## Example: "ddev eslint web/modules/contrib/js/my-js-file.js"

npx eslint --fix $*
