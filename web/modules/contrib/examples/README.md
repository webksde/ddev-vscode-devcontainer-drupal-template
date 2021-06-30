Examples for Developers
=======================

Project site: http://drupal.org/project/examples

Code: https://drupal.org/project/examples/git-instructions

Issues: https://drupal.org/project/issues/examples

What Is This?
-------------

This set of modules is intended to provide working examples of Drupal 8's
features and APIs.  The modules strive to be simple, well documented and
modification friendly, in order to help developers quickly learn their inner
workings.

These examples are meant to teach you about code-level development for Drupal
8. Some solutions might be better served using a contributed module, so that
you don't end up having to re-invent the wheel in PHP. When in doubt, look for
an existing contrib project that already does what you want, and contribute to
that project.


How To Use The Examples
-----------------------

There are three main ways to interact with the examples in this project:

1. Enable the modules and use them within Drupal. Not all modules will have
obvious things to see within your Drupal installation. For instance, while the
Page and Form API examples will display forms, the Database API example does not
have much that is visible within Drupal.

2. Read the code. Much effort has gone into making the example code readable,
not only in terms of the code itself, but also the extensive inline comments
and documentation blocks.

3. Browse the code and documentation on the web. There are two main places to
do this:

* https://api.drupal.org/api/examples is the main API site for all of Drupal.
It has all manner of cross-linked references between the example code and the
APIs being demonstrated. All of the Doxygen-based comments in the code are
parsed and made browseable here.

* http://drupalcode.org/project/examples.git allows you to browse the git
repository for the Examples project.

This project ships with a composer.json file. This is meant to illustrate how
to provide a composer.json file for a Drupal contrib project. You can read more
about how to use Composer with Drupal here: https://www.drupal.org/node/2718229

How To Install The Modules
--------------------------

1. The Examples project installs like any other Drupal module. There is extensive
documentation on how to do this here:
https://drupal.org/documentation/install/modules-themes/modules-8 But essentially:
Download the tarball and expand it into the modules/ directory in your Drupal 8
installation.

2. Within Drupal, enable any Example sub-module you wish to explore in Admin
menu > Extend.

3. Rebuild access permissions if you are prompted to.

4. Profit!  The links for Examples material will appear in your Tools menu. This
menu appears on the left sidebar by default. You'll need to reenable it if you
removed it.

Having seen the behavior of the various example modules, you can move on to
reading the code, experimenting with it, and hopefully grasp how things work.

If you find a problem, incorrect comment, obsolete or improper code or such,
please search for an issue about it at http://drupal.org/project/issues/examples
If there isn't already an issue for it, please create a new one.

Thanks.
