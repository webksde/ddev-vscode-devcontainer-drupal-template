#!/bin/bash

## Description: Use phpUnit to Debug a Folder
## Usage: phpunit [path]
## Example: "ddev phpunit path/to/tests"

phpunit -c web/core/phpunit.xml $*
