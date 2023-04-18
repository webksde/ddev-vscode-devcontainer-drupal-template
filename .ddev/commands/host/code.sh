#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Note the Numbers ar the hex Value of the Container Name we are attaching VSCode to
WEBSERVER_NAME=ddev-"$DDEV_SITENAME"-web
code --folder-uri vscode-remote://attached-container+$(printf "$WEBSERVER_NAME" | xxd -p)/var/www/html
