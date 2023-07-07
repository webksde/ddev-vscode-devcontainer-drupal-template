#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Get the webserver container name:
WEBSERVER_NAME=ddev-"$DDEV_SITENAME"-web
# Attach vscode to the webserver using a hex representation of the webserver name:
code --folder-uri=vscode-remote://attached-container+$(printf "$WEBSERVER_NAME" | od -A n -t x1 | sed 's/ *//g' | tr -d '\n')/var/www/html
