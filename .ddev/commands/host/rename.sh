#!/bin/bash

## Description: Use this command to change the current projectname and change the vscode attach data, so the ddev code script will run with the new project name
## Usage: rename "name"
## Example: "ddev rename 'myNewName'"

# Stop and unlist old Project:
ddev stop --unlist ${DDEV_PROJECT}
read -p "Please enter your projects new name: " projectname

# Alter project name for VSCode attach process and turn it into hex representation:
projectnameVsCode=ddev-$projectname-web
hexname=`printf $projectnameVsCode | od -A n -t x1 | tr -d '[\n\t ]'`

# Change projectname and vscode attach command:
sed -i "s|name: .*|name: ${projectname}|g" .ddev/config.yaml
sed -i "s|code --folder-uri=.*|code --folder-uri='vscode-remote://attached-container+${hexname}%/var/www/html'|g" .ddev/commands/host/code.sh

# Start new project:
ddev start
