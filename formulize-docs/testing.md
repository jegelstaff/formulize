---
layout: default
permalink: version_control/testing/
---

# Testing

We use Selenium 2 tests to perform application level testing of Formulize.  [Learn more about creating tests](/formulize/selenium_builder).

There is an existing body of tests that creates a sample application in Formulize.  You can make tests for your new functionality using Selenium Builder, and then include those in the branch that you are working on.

The tests are configured to connect to a copy of Formulize running at 'localhost'.  If you place a copy of the GitHub repo at the webroot location of your local development environment, then you can run all the tests locally using Selenium Builder.  The tests assume a database called 'formulize' has already been created.  The installation test will also have trouble with the username and password for accessing the database, since it is likely different on your local installation compared to on the Travis-CI system. However, you should be able to interrupt the test and type in the right information at that point.

The tests run the installation process for Formulize and setup a sample application.  Once that is complete, you can then easily carry on and record new tests that exercise the parts of Formulize that you are working on, or have added.

Tests should be saved in the **/ci/selenium2-tests/** folder.  The tests in that folder are run in alpha-numeric order.  In general, you will want your tests to run after all other tests.

Your tests should be commited as part of your branch, alongside your code.




