#!/bin/bash

## THIS DOES NOT WORK!! https://github.com/drud/ddev/issues/1569
## HOW CAN WE START VSCODE in a container???
ddev start
code ddev ssh
cd /var/www/html
code .
