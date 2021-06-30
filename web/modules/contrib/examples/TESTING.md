Testing Drupal Examples for Developers
======================================

The Drupal Examples for Developers project uses DrupalCI testing on drupal.org.

That means: It runs the testbot on every patch that is marked as 'Needs Review.'

Your patch might not get reviewed, and certainly won't get committed unless it
passes the testbot.

The testbot runs a script that's in your Drupal installation called
`core/scripts/run-tests.sh`. You can run `run-tests.sh` manually and approximate
the testbot's behavior.

You can find information on how to run `run-tests.sh` locally here:
https://www.drupal.org/node/645286

Examples is always targeted to the dev branch of Drupal core for the latest
release. As of this writing, the latest release of Drupal core is 8.2.5, which
means development for Examples should be against the Drupal 8.2.x development
branch. When Drupal 8.3.0 is released, we'll start targeting Examples to 8.3.x,
and so on.

You should at least run `run-tests.sh` locally against all the changes in your
patch before uploading it.

Keep in mind that unless you know you're changing behavior that is being tested
for, the tests are not at fault. :-)

Note also that, currently, using the `phpunit` tool under Drupal 8 will not find
PHPUnit-based tests in submodules, such as phpunit_example. There is no
suggested workaround for this, since there is no best practice to demonstrate as
an example. There is, however, this issue in core:
https://www.drupal.org/node/2499239

How To Run The Tests In The Drupal UI
-------------------------------------

Generally, you should run tests from the command line. This is generally easier
than using Drupal's testing UI. However, here's how you can do it that way:

Enable the Testing module.

Visit the test list page at `admin/config/development/testing`.

Since the tests are organized by module, you can search for a module name and
get all the tests for that module. For instance, type in 'node_type_example' for
all the tests related to that module.

Click the check boxes next to the tests you want to run. If you find this
tedious, it's time to learn to use the command line. :-)

Click 'Run Tests.' You're now running the tests.

Step-by-step: How To Run The Tests.
-----------------------------------

Begin with an installed Drupal codebase. Make a codebase, set up the database,
etc. Note that you can use an existing Drupal instance but the best practice is
to start fresh. Something not working right? Try a new installation.

Use the dev branch of core for the latest release of Drupal. As of this writing,
it's 8.2.x. When Drupal 8.3.0 is released, we'll target 8.3.x.

Open the terminal window and move to the root directory of the Drupal
installation:

	$ cd path/to/drupal

Put Examples into the `modules/` folder of the Drupal installation. If you are
doing development on Examples, you should have already checked out the git
repository into `modules/`, like this:

	$ git clone --branch 3.x https://git.drupalcode.org/project/examples.git modules/examples

Now you can run `run-tests.sh`, which, despite having a `.sh` suffix is not a
shell script. It's a PHP script.

You'll use the `--directory` option to have the test runner scan the Examples
module directory for tests.

Also, importantly, if your test site has its own URL, you'll need to supply that
with the `--url` option. For instance, under MAMP, you must specify
`--url http://localhost:8888/`.

You can also use `--concurrency` to speed up the test run, and `--browser` to
see detailed test results in a web browser instead of just text output in the
terminal.

	$ php ./core/scripts/run-tests.sh --browser --concurrency 10 --url http://localhost:8888/ --directory modules/examples

This should run all the tests present in Examples. If you add a test and it
doesn't appear in the list of tests to run, then you'll need to double-check
that it's in the proper test namespace and that the class name (and thus the
file name) ends in Test.

What Tests Should An Example Module Have?
------------------------------------------

Examples has a checklist for each module:
https://www.drupal.org/node/2209627

The reason we care about these tests is that we want the documentation
of these APIs to be correct. If Core changes APIs, we want our tests to
fail so that we know our documentation is incorrect.

Our list of required tests includes:
* Functional tests which verifies a 200 result for each route/path defined by
    the module.
* Functional tests of permission-based restrictions.
* Functional tests which submit forms and verify that they behave as
    expected.
* Unit tests of unit-testable code.
* Other. More. Better.
