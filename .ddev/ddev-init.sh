#!/bin/bash

ddev composer require drupal/coder

ddev exec chmod +x vendor/bin/phpcs
ddev exec chmod +x vendor/bin/phpcbf

# Register Drupal's code sniffer rules.
ddev exec phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose

# Make Codesniffer config file writable for ordinary users in container.
ddev exec chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf
