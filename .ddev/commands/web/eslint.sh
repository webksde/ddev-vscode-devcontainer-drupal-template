#!/bin/bash

## HostWorkingDir: true
## Description: Use eslint on a folder or file for linting javascript code based on Drupal Coding Standards. Alternatively you can also try to autofix the problems, using the "--fix" suffix.
## Usage: eslint [path], eslint --fix [path]
## Example: "ddev eslint /var/www/html/web/modules/custom/my_module/js/my-js-file.js"

# If no argument given, lint current dir:
if [[ $# == 0 ]]; then
  npx eslint --no-error-on-unmatched-pattern --ignore-pattern="*.es6.js" --resolve-plugins-relative-to=$DDEV_PROJECT/web/core --ext=.js,.yml "$PWD"; exit 0
# If only argument is "--fix", fix current dir:
elif [[ $# == 1 && $* == '--fix' ]]; then
  npx eslint --fix --no-error-on-unmatched-pattern --ignore-pattern="*.es6.js" --resolve-plugins-relative-to=$DDEV_PROJECT/web/core --ext=.js,.yml "$PWD"; exit 0
# else do whatever you typed in (e.g. path only or path with --fix):
else
  npx eslint --no-error-on-unmatched-pattern --ignore-pattern="*.es6.js" --resolve-plugins-relative-to=$DDEV_PROJECT/web/core --ext=.js,.yml "$*"; exit 0
fi
