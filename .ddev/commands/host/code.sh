#!/usr/bin/env bash

## Description: Open VSCode attached to Web-Container of the current Project
## Usage: code
## Example: "ddev code"

# Install the devcontainer cli if not already installed:
package='@devcontainers/cli'
if [ $(npm list --location=global | grep -c $package) -eq 0 ]; then
  bool=1
  while [ $bool -eq 1 ]; do
    read -p $'\e\n[33mThe "@devcontainers/cli" package needs to be globally installed on your host machine for this command to work. Do you want to install "@devcontainers/cli" globally? (y/n)\e[0m'$'\n' answer
    case ${answer:0:1} in
    y | Y | yes | Yes | YES)
      echo -e $'\e\n[32mInstalling "@devcontainers/cli"...\n\e[0m'
      npm install --location=global $package
      bool=0
      ;;
    n | N | no | No | NO)
      bool=0
      exit
      ;;
    *)
      echo -e $'\e\n[33mI do not understand. Please repeat that.\n\e[0m'
      ;;
    esac
  done
fi

# Attach vscode to the webserver using devcontainer up and the hex representation of the webserver name:
CONTAINER_NAME_OR_ID=$(devcontainer up --workspace-folder . | jq -r .containerId) code --folder-uri vscode-remote://attached-container+$(printf "$CONTAINER_NAME_OR_ID" | od -A n -t x1 | sed 's/ *//g' | tr -d '\n')/home
