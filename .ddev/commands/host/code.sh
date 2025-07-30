#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Get the webserver container name:
WEBSERVER_NAME=ddev-"$DDEV_SITENAME"-web

# Check if container is running
if ! docker ps --format "{{.Names}}" | grep -q "^$WEBSERVER_NAME$"; then
    echo "Container $WEBSERVER_NAME is not running. Starting DDEV..."
    ddev restart
fi

# Attach vscode to the webserver using a hex representation of the webserver name:
code --folder-uri=vscode-remote://attached-container+$(printf "$WEBSERVER_NAME" | od -A n -t x1 | sed 's/ *//g' | tr -d '\n')/var/www/html
