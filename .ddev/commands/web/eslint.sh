#!/bin/bash

## HostWorkingDir: true
## Description: Use eslint on a folder or file for linting javascript code based on Drupal Coding Standards. Alternatively you can also try to autofix the problems, using the "--fix" suffix.
## Usage: eslint [path], eslint --fix [path]
## Example: "ddev eslint /var/www/html/web/modules/custom/my_module/js/my-js-file.js"

# If no argument given, lint current dir:
if [[ $# == 0 ]]; then
  npx eslint -c /var/www/html/"${DDEV_DOCROOT}"/core/.eslintrc.json "$PWD"; exit 0
# If only argument is "--fix", fix current dir:
elif [[ $# == 1 && $* == '--fix' ]]; then
  npx eslint -c /var/www/html/"${DDEV_DOCROOT}"/core/.eslintrc.json --fix "$PWD"; exit 0
# else do whatever you typed in (e.g. path only or path with --fix):
else
  npx eslint -c /var/www/html/"${DDEV_DOCROOT}"/core/.eslintrc.json "$*"; exit 0
fi
