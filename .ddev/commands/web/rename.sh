#!/bin/bash

## Description: Use phpcbf on a Folder for fixing Code using Drupal and DrupalPractice Coding standards
## Usage: rename "name"
## Example: "ddev rename 'myNewName'"

read -p "Please enter your projects new name: " projectname
echo "$projectname"
hexname=`printf $projectname | od -A n -t x1 | tr -d '[\n\t ]'`
echo "$hexname"

#TODO: Delete everything after "code --folder-uri=" then do this:
sed -i "s|^code --folder-uri=*|code --folder-uri='vscode-remote://attached-container+${hexname}%/var/www/html'|" .ddev/commands/host/code.sh

#TODO: Change name in config.yaml
