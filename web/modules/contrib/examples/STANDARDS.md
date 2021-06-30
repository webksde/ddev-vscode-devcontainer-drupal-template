Developing with Coding Standards for Examples for Developers
============================================================

Examples uses mostly the same coding standards as Drupal core.

If you see a discrepancy between the coding standards tools used by core and
those used by Examples, please file an issue so that Examples can follow core.

Examples uses the `phpcs` tool to allow for checking PHP coding standards. We
use the `drupal/coder` project for Drupal-specific coding standards.

We also use `eslint` for JavaScript coding standards, and `csslint` for CSS.

Examples has a `phpcs.xml.dist` file at the root of the project. phpcs uses this
file to specify the current coding standards 'sniffs' which code in the project
must pass.

Contributors should install `phpcs` in their local Drupal installation, and then
use that to run `phpcs` against Examples as part of their development and review
process. (See details below on how to install and run this tool.)

Contributors can also patch the `phpcs.xml.dist` file itself, in order to fix
the codebase to pass a given rule or sniff. Patches which do this should be
limited to a single rule or sniff, in order make the patch easier to review.

Examples also uses the Coder project (`drupal/coder`), which adds additional
Drupal-specific coding standards. We're currently locked to Coder version
8.2.8, but this should change to reflect the state of core's coding standards.

Installing phpcs
----------------

Current versions of Drupal 8 core require phpcs and Coder as dev dependencies.
That means they're already probably installed in your core vendor/ directory.

We need to tell `phpcs` to use the Drupal coding standard provided by Coder,
because it isn't configured that way by default.

Like this:

    $ cd my/drupal/root/
    $ ./vendor/bin/phpcs --config-set installed_paths /full/path/to/drupal/vendor/drupal/coder/coder_sniffer/
    // phpcs now knows how to find the Drupal standard. You can test it:
    $ cd core
    $ ../vendor/bin/phpcs -e --standard=Drupal
    // Shows you a bunch of Drupal-related sniffs.

Running phpcs
-------------

Now you can run phpcs:

    $ cd modules/examples
    $ ../../vendor/bin/phpcs -ps
    // phpcs uses Examples' phpcs.xml.dist to verify coding standards.
    // -p shows you progress dots.
    // -s shows you sniff errors in the report.

If there are errors, they can sometimes be fixed with `phpcbf`, which is
part of the `phpcs` package.

    $ ../../vendor/bin/phpcbf
    // phpcbf now performs automated fixes.

Always look at the changes to see what `phpcbf` did.

And always re-run `phpcs` in order to discover whether `phpcbf` handled all the
errors.

Installing eslint
-----------------

`eslint` is a node.js tool. You can and probably should install it globally,
since installing it locally would add files to the examples project.
Instructions available here: https://www.npmjs.com/package/eslint

Examples has an `.eslintrc` file which defines the JavaScript coding standard.
This file should be identical to the current Drupal core standard.

Running eslint
--------------

You can run eslint this way:

    $ cd /path/to/examples
    $ eslint .
