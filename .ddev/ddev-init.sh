#!/bin/bash

# TODO: Move to drowl-init!
ddev composer require --dev drupal/coder

# TODO: Move to drowl-init!
# Register Drupal's code sniffer rules.
ddev exec phpcs --config-set installed_paths ../vendor/drupal/coder/coder_sniffer --verbose

# Initialize development environment tools:
ddev exec chmod +x ../vendor/bin/phpcs
ddev exec chmod +x ../vendor/bin/phpcbf

# Make Codesniffer config file writable for ordinary users in container:
ddev exec chmod 666 ../vendor/squizlabs/php_codesniffer/CodeSniffer.conf
