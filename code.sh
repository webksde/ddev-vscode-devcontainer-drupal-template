#!/bin/bash

code --folder-uri="vscode-remote://attached-container+646465762d646465762d7673636f64652d646576636f6e7461696e65722d64727570616c392d74656d706c6174652d776562%/var/www/html"

#OTHER IMPLEMENTATION: SOURCE: https://stackoverflow.com/questions/60379221/how-to-attach-a-remote-container-using-vscode-command-line/63715551#63715551
# case $# in
# 1) ;;
# *) echo "Usage: code-remote-container <directory>"; exit 1 ;;
# esac

# dir=`echo $(cd $1 && pwd)`
# hex=`printf ${dir} | od -A n -t x1 | tr -d '[\n\t ]'`
# base=`basename ${dir}`
# code --folder-uri="vscode-remote://dev-container%2B${hex}/workspaces/${base}"
