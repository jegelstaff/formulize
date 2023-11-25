---
layout: default
permalink: developers/ci/
---

# Continuous Integration

**This is a nice idea, but we don't actively use Travis or Sauce Labs currently**

We use [Travis](https://travis-ci.org/jegelstaff/formulize/builds) and [Sauce Labs](http://www.saucelabs.com) as our continuous integration platform.  We currently test only using Selenium 2 in-browser tests of the Formulize application.  This page describes the configuration and setup of our integration with these CI tools.

If all the tests pass successfully, then this lets us know that the current build of Formulize has not broken any features that are used in the recorded Selenium tests. In addition, Sauce Labs records videos of the tests running in the browser so that you can see exactly what happens when a test fails.

[Learn more about creating tests](../version_control/testing/creating_tests).

## Sauce Labs Setup

On Sauce Labs, Julian has signed up for an "Open Sauce" account that is free and allows for automated, unlimited Selenium testing, provided it is only used to test open source software.  Selenium tests are application level tests, based on recorded actions in a web browser. Sauce Labs plays back the tests, using a spontaneously built cloud server that Travis has spun into existence in response to the latest Github commit.

The credentials for this account need to be used by Travis in order to establish a connection to Sauce Labs. Using a Ruby gem, the credentials have been encrypted in a way that Travis and Sauce can work with. You can read more about that process on this page: [https://docs.travis-ci.com/user/encryption-keys/](https://docs.travis-ci.com/user/encryption-keys/)

These credentials are dependent on the repo; the same encrypted credentials will not work when transplanted into a forked copy of the repo.  Therefore, forks will need to specify their own Sauce Labs credentials in order for builds to work.

## Travis Configuration

Julian has signed up for a free account on Travis. In the profile for that account, there is an option to connect it to the Formulize Github repository (since the Travis account knows Julian's GitHub username). When that connection is switched on, Travis listens for commits to Github.

When there is a commit, Travis spins up a Debian Linux server in the cloud, and copies the contents of the GitHub repository to this folder on the server: **formulize/**.  It then reads the **.travis.yml** file in the root of the repository, and proceeds to apply further configuration to the server. That file contains code like this:

    env:
      global:
      - secure: f1Dkf/AObhtZJf/OtM/NREIZRzVeuw/QXQkV82WrbszKdpLrm1RwfwTAgcUa+JDiE931LJ41W86JWUia3PbS3pLVSL9gSwfBPk+PU6VM7AeYIVuje1V1tXpZoGlf+0RcfGHZA3JzeNSo65QjKl5iUxrT31wnpQpQ9rdG/bXQrUg=
      - secure: Xjc2qZs42LLIk4lRMc/JdFxeN5pmXUTLFd/stohOTX3g1rmRInXXqYpnXW/S15FjShqgtQAvvmnU/SV9QNpfaMhQVgS1ORVTS10gvy3aLPodW5hB9AGCDiQHXD0r9p+73LfNzenvdtmDme9Pp8PdLwb+spSroRbXgJ3tqHpXntM=
      - secure: n7FDSdDI6AlTIe91ZFfaWA89PloZGjR36M2U1XZwKK9FMFDHRJMORmW30veV1OuC6e7/F/56MpQ+PHMKeeBPdTEIQRJeCTwyLTkIQ8eh18oVE6M9pdXNrUlhLf5dejXk2kAl3vWe8LBC+x1pWsql0mb8AAkdLwQfRddPlCbkRHY=
      - WEB_PORT_80_TCP_ADDR=localhost

    language: php

    sudo: required
    dist: trusty

    php:
        - 7.0

    addons:
      sauce_connect: true
      hosts:
        - local.dev

    cache:
      - apt

    before_install:

      # Install Apache2
      - sudo apt-get update
      - sudo apt-get install -y --force-yes apache2
      # Add PHP 7.0 Repository
      - sudo add-apt-repository -y ppa:ondrej/php
      # Install PHP 7.0
      - sudo apt-get update
      - sudo apt-get install -y --force-yes php7.0-mysql php7.0 libapache2-mod-php7.0 php7.0-fpm php7.0-gd php7.0-xml
      # Copy our virtual host template to sites-enabled overwriting the default site conf
      - sudo cp ci/travis/defaultsite.tpl /etc/apache2/sites-available/000-default.conf
      # Enable mod rewrite module
      - sudo a2enmod rewrite
      # Set ServerName Globally
      - sudo cp ci/travis/servername.tpl /etc/apache2/conf-available/servername.conf
      # add to .ini
      - phpenv config-add ci/travis/php.ini
      # Restart apache
      - sudo service apache2 restart
      # Restart PHP
      - sudo service php7.0-fpm restart
      # copy Formulize to the web root
      - cd ..
      - sudo cp -R formulize/* /var/www
      # copy selenium_debug.js which modifies some javascript behaviour to behave better with selenium
      - sudo cp -f /var/www/ci/travis/selenium_debug.js /var/www/modules/formulize/selenium_debug.js
      # set 777 on necessary directories
      - sudo chmod 777 /var/www/cache
      - sudo chmod 777 /var/www/templates_c
      - sudo chmod 777 /var/www/uploads
      - sudo chmod 777 /var/www/install
      - sudo chmod 777 /var/www/mainfile.php
      - sudo chmod 777 /var/www/cache
      - sudo chmod 777 /var/www/modules
      - sudo chmod 777 /var/www/modules/formulize/cache
      - sudo chmod 777 /var/www/modules/formulize/temp
      - sudo chmod 777 /var/www/modules/formulize/upload
      - sudo chmod 777 /var/www/modules/formulize/templates/screens/default
      - sudo chmod 777 /var/www/modules/formulize/export
      - sudo chmod 777 /var/www/modules/formulize/custom_code
      # make the trust path because the fcgi process cannot
      - sudo mkdir /var/www/selenium-848d24bb54d726d
      - sudo chmod 777 /var/www/selenium-848d24bb54d726d

    before_script:
      - npm install -g se-interpreter
      - mysql -e 'create database formulize;'
      # Uncomment the next three lines to initialize the database as if all existing tests have run.
      # The next two setup the environment as if the installer has completed.
      # This is useful for quickly running just one new test, to see if it is working.
      # Alter the ci/travis/interpreter_config.json file to refer to your one test by name
      # instead of referring to * for all tests.
      # - mysql formulize < /var/www/ci/formulize_test_db.sql
      # - sudo cp -f /var/www/ci/mainfile.php /var/www/mainfile.php
      # - sudo cp -f /var/www/ci/0dd528b71d72ed19bb9dd658ab2dad58.php /var/www/selenium-848d24bb54d726d/0dd528b71d72ed19bb9dd658ab2dad58.php
      - echo "USE mysql;\nUPDATE user SET password=PASSWORD('password') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root

    script:
      - se-interpreter /var/www/ci/travis/interpreter_config.json
      - tail -100 /tmp/error.log
      - curl http://localhost/

    after_script:
      # Include '[update test db]' in a commit message from the master branch, if you want
      # to reset the ci/formulize_test_db.sql to be based on all the current tests.
      # ONLY DO THIS WITH COMMITS FROM THE MASTER BRANCH!!
      # DO NOT DO THIS IN A BRANCH THAT HAS AN ACTIVE PULL REQUEST!!
      - if [[ $TRAVIS_COMMIT_MESSAGE == *"[update test db]"* ]] ; then bash /var/www/ci/travis/update-test-db.sh; fi

### What the sections do

The current version of the .travis.yml file owes a lot to [the work of Mitchell Krog](https://github.com/mitchellkrogza/Travis-CI-for-Apache-For-Testing-Apache-and-PHP-Configurations). Here is an explanation of the main sections of the file:

#### env

This records global variables for the Sauce username and password, and a GitHub access token we may need if the commit has requested an update to our official copy of the test application database. For details about how to encrypt these credentials to include them in your .travis.yml file, see [https://docs.travis-ci.com/user/encryption-keys/](https://docs.travis-ci.com/user/encryption-keys/).

#### addons

This tells Travis that it should open up a special tunnel with Sauce Labs, so that we can run our Selenium tests there, and point them at the server Travis has built. You can see a diagram of how this works, on the Sauce website here: [https://saucelabs.com/images/docs/sauce-connect/sauce-connect-architecture.jpg](https://saucelabs.com/images/docs/sauce-connect/sauce-connect-architecture.jpg)

#### before_install

This runs a series of commands on the spontaneous cloud server that Travis builds for us.  These commands install an Apache server with PHP, and copy all the files that Travis has grabed from our GitHub repository, and puts them in the webroot. These commands also give global write permissions to certain folders that need to be writable by the application.

These commands depend on some other files:

* [ci/travis/defaultsite.tpl](https://github.com/jegelstaff/formulize/blob/master/ci/travis/defaultsite.tpl) and [ci/travis/servername.tpl](https://github.com/jegelstaff/formulize/blob/master/ci/travis/servername.tpl) - files containing Apache configuration information

* [ci/travis/php.ini](https://github.com/jegelstaff/formulize/blob/master/ci/travis/php.ini) - a basic PHP configuration file

#### before_script

This installs the se-interpreter (see below) which is a tool for reading the Selenium tests and passing instructions to Sauce Labs.  It also creates the MySQL database that we will use (we don't need to setup mysql in the 'install' section because Travis servers already all have MySQL installed).

Optionally, you can uncomment the line **# - mysql formulize < ci/formulize_test_db.sql** to instruct Travis to prepopulate the database as if all the tests had already been run. This is useful when you are trying to perfect a single new test. In this case, you will need to modify the **interpreter_config.json** file (see below) so that it runs only your one test and not all the tests.

See [Creating Tests](../version_control/testing/creating_tests) for more information about testing.

#### script

This runs the se-interpreter, and points it at a configuration file.  It also dumps the error log to the screen after se-interpreter is finished, and tries to load the site index.php file as a last resort to give us a clue what's going on just in case all else fails.

#### after_script

This listens to the commit message, and if **[update test db]** is present in the message, then Travis will push a copy of the database to the master branch on GitHub.  See the **before_script** section above for how to start a build using that copy of the database.

**[update test db]** can only be used from the master branch.

These commands depend on a shell script that will do the actual dump and push to GitHub, using the secure access token encrypted in the **env** section:  [ci/travis/update-test-db.sh](https://github.com/jegelstaff/formulize/blob/master/ci/travis/update-test-db.sh)

### se-interpreter configuration

The se-interpreter reads this file: **ci/travis/interpreter_config.json**, which contains all the settings that are used to trigger Sauce Labs and the Selenium tests:

    {
      "type": "interpreter-config",
      "configurations": [
        {
          "settings": [
            {
              "driverOptions": {
                  "host": "localhost",
                  "port": 4445
               },
              "browserOptions": {
                  "browserName": "firefox",
                  "username": "${SAUCE_USERNAME}",
                  "accessKey": "${SAUCE_ACCESS_KEY}",
                  "tunnel-identifier": "${TRAVIS_JOB_NUMBER}",
                  "build": "${TRAVIS_BUILD_NUMBER}",
                  "tags": "${TRAVIS_BRANCH}"
               }
            }
          ],
          "scripts": [
            "/var/www/ci/selenium2-tests/*"
          ]
        }
      ]
    }

#### Critical points:

* **driverOptions** - this declares the host and port that are used to connect to the Selenium WebDriver on Sauce. These are critical, and are simply the defaults used by Sauce Connect. They do not arise from nor are dependent on any other part of the configuration.
* **browserOptions** - Sauce Labs supports all kinds of browser and OS combinations.  Here we declare that we're using Firefox, and we also pass in our Sauce Labs username and password, and metadata about the Travis instance that is spawning these tests.
* **scripts** - this points to the directory where the actual Selenium tests are located.  They are run in alphanumeric order according to their file names. If you need to run only one test, then you should specify it by name here, instead of using the asterisk.

You can read more about se-interpreter on its GitHub page: [https://github.com/Zarkonnen/se-interpreter](https://github.com/Zarkonnen/se-interpreter)

## Viewing Test Results

Once the tests have run, you can see the results on Travis: [https://travis-ci.org/jegelstaff/formulize/builds](https://travis-ci.org/jegelstaff/formulize/builds)

That page is publically available.  Clicking on a build number will take you to a detailed results page, that shows the complete command line output of the server over its lifetime, including a full listing of the results of every step of every Selenium test.

To see the Selenium tests in action, you have to log in to the Open Sauce account on Sauce Labs.  From there, you can see screencasts of the tests while they run in Firefox.

## Getting Build Status in GitHub

By adding this line to the readme.md file in the Formulize repo, we can get an automatic indication of the current build status:

    [![Build Status](https://travis-ci.org/jegelstaff/formulize.png)](https://travis-ci.org/jegelstaff/formulize)

In addition, in the list of pull requests, there will be green checkmarks or red x's, depending if the code passes or not. Note that the Sauce credentials are only valid when used in conjunction with the original, jegelstaff/formulize repo. A pull request from a forked repo will automatically fail because of that dependency, but a forked repo could alter the .travis.yml file to use their own Sauce credentials and then it should work.









