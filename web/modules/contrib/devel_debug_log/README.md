Description
===========
Devel Debug Log is a developer module that provides a way for developers to
save and display debug messages on a separate page in the web browser. It
serves as an alternative to using Drupal messages or watchdog entries for
debugging, and a complementary module to Devel for those who find viewing
messages in the browser easier than looking for them, say, in a file.

The module provides the ddl($message, $title) function, which the developer can
use to save a debug message. If an object or array is supplied as $message, it
will be displayed using the Kint tool. Messages can be viewed at Reports > Debug
Messages.

Installation
============
Standard module installation procedure. Copy the module to modules directory,
and enable.

Use the ddl($message, $title) function in your module or in a Twig template to
save a debug message.
