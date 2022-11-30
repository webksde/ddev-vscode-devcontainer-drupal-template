#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Note the Numbers ar the hex Value of the Container Name we are attaching VSCode to
code --folder-uri="vscode-remote://attached-container+646465762d646465762d7673636f64652d646576636f6e7461696e65722d64727570616c2d74656d706c6174652d776562%/var/www/html"
