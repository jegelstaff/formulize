---
layout: default
permalink: developers/version_control/testing/creating_tests/
---

# Creating Selenium 2 Tests with Selenium Builder

We use run Selenium 2 tests on [Sauce Labs](http://www.saucelabs.com) as part of our [continuous integration platform](../../../ci).  This page describes how to use [Selenium Builder](http://www.saucelabs.com/builder) to create tests in [Firefox](http://www.mozilla.org/firefox).

## Current Tests

Current tests start with a blank slate, and install Formulize, then set up a demo system which is a mock version of an agile project tracker. Test filenames start with a number, as tests are run on Travis based on filename sort order.

## Creating A New Test

### Setup

Before creating a new test, you need to get your local configuration to match the the configuration that the Travis CI system has after it has run all the tests. This way, your new test will build on the setup and configuration of all the other tests. There are two ways you can do this:

1. Run all the existing tests locally
2. Dump all the tables in your database and then import a SQL dump from a system where all the tests have been completed.

If you follow method 2, then you will need to alter the file in your trust path so it has the database table prefix  **selenium** and has the salt **s4RyHEWYxWN9OUAGvCdxljYRqqSgEf9qbsvVSvhWSumtfyI7SNx6ct1n5fypNFdi4**.

Also, you need to ensure that your local installation is being treated as the DocumentRoot of your webserver, ie: when you go to [http://localhost](http://localhost) you see your local installation; your local installation does not have a directory name after the localhost domain name.

If you do not configure your local installation to behave this way, then you will need to manually edit the test file after you save it, to change the references to the URL so they are simply 'localhost' and include no directory names.

### Recording

A test can be recorded in Firefox using the Selenium Builder plugin. Taking a database backup before making changes to forms is advised, so that after recording the test, the database can be restored, and the test can be played back to confirm that it works.

Choose the Selenium 2 version of the test file format. The scripts are text files containing json data which is easy to edit with a regular text editor.

A drawback to the json format is that comments cannot be included in the file, as this breaks json-parsing.

## General Tips

All tests should start with logging in to the website.  When Selenium starts a test, it clears all session information so the new test starts from scratch.

Avoid unnecessary clicks on page elements, and if you do make unnecessary clicks, these steps can be deleted from the test script while still recording.

Do not use the tab key to navigate between text boxes, as it does not work reliably when playing back the script.

Sometimes the script recorder will identify links clicked by text label, though the link text is not unique. These should be edited so that links are clicked by a more reliable xpath or CSS-style selector. In same cases, it may be advisable to add CSS classes to page elements to increase reliability of testing.

A script that works on playback once may not work every time. Test scripts more than once.

## Test Variables

Since testing site domain name or other settings could change between the Travis testing environment and other testing environments, a good practice is to declare variables at the top of the testing script, then use those variables within the script.

For example, a simple script which logs in may declare variables such as these:

    {
      "type": "store",
      "text": "localhost",
      "variable": "test_domain"
    },
    {
      "type": "store",
      "text": "password",
      "variable": "admin_password"
    },

Then the domain variable would be used like this to open the page:

    {
      "type": "get",
      "url": "http://${test_domain}/index.php"
    },

You may wish to simply copy and paste the top portion of an existing script into your own to handle the site login steps. 

Since variables can store arbitrary text, and the test scripts cannot contain comments, variables may be used as a way to insert comments.

## Wait Until the Next Page Loads

Test scripts run much faster than using a browser normally. When a form is submitted, the next step may run without waiting for the page to load, which can cause the script to fail at that point.

There are two ways to stop the script until the next page has loaded. The first is the most reliable, cause the script to wait until text which appears on the next page appears.

    {
      "type": "waitForTextPresent",
      "text": "<unique text on new page>"
    },

If the next page is the same as the current page, as when saving changes to a form, then a different method of waiting for a page to load is needed. This causes the script to pause for a specified number of milliseconds. The wait time may vary with test environment, so try to pick a value long enough for the slowest environment or the script may fail intermittently.

    {
      "type": "pause",
      "waitTime": "2345"
    },

Short pauses may also be used to wait for actions, such as ajax loading, or javascript page changes.

