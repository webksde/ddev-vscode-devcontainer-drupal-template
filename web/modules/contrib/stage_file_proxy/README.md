CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Stage File Proxy module saves you time and disk space by sending requests to
your development environment's files directory to the production environment and
making a copy of the production file in your development site. It makes it
easier to manage local development environments. This module should not be
installed on a server that faces the internet.

* For a full description of the module visit
https://www.drupal.org/project/stage_file_proxy

* To submit bug reports and feature suggestions, or to track changes visit
https://www.drupal.org/project/issues/stage_file_proxy


REQUIREMENTS
------------

This module does not require any additional modules outside of Drupal core.


INSTALLATION
------------

Install the Stage File Proxy module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for more information.


CONFIGURATION
-------------

1. Enable Stage File Proxy, either via "Extend" (/admin/modules) or via drush:
$ drush en --yes stage_file_proxy

2. Configure connection to the source. This is available via the UI, at
Configuration > Stage File Proxy Settings (admin/config/system/stage_file_proxy)

As this module should only be used on non-production sites, it is preferable to
configure this within your settings.php or settings.local.php file. Detailed
descriptions of each setting, and syntax for defining the configuration in code
is in INSTALL.md


MAINTAINERS
-----------

* Baris Wanschers (BarisW) - https://www.drupal.org/u/barisw
* Greg Knaddison (greggles) - https://www.drupal.org/u/greggles
* Rob Wilmshurst (robwilmshurst) - https://www.drupal.org/u/robwilmshurst
* netaustin - https://www.drupal.org/user/199298
