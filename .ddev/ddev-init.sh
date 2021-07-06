chmod +x vendor/bin/phpcs
chmod +x vendor/bin/phpcbf

# Register Drupal's code sniffer rules.
phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer --verbose

# Make Codesniffer config file writable for ordinary users in container.
chmod 666 vendor/squizlabs/php_codesniffer/CodeSniffer.conf
