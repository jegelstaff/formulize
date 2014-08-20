---
layout: default
permalink: ci/
---

# Continuous Integration

We use [Travis](https://travis-ci.org/jegelstaff/formulize/builds) and [Sauce Labs](http://www.saucelabs.com) as our continuous integration platform.  We currently test only using Selenium 2 in-browser tests of the Formulize application.  This page describes the configuration and setup of our integration with these CI tools.

If all the tests pass successfully, then this lets us know that the current build of Formulize has not broken any features that are used in the recorded Selenium tests. In addition, Sauce Labs records videos of the tests running in the browser so that you can see exactly what happens when a test fails.

[Learn more about creating tests](/formulize/selenium_builder).

## Sauce Labs Setup

On Sauce Labs, Julian has signed up for an "Open Sauce" account that is free and allows for automated, unlimited Selenium testing, provided it is only used to test open source software.  Selenium tests are application level tests, based on recorded actions in a web browser. Sauce Labs plays back the tests, using a spontaneously built cloud server that Travis has spun into existence in response to the latest Github commit.

The credentials for this account need to be used by Travis in order to establish a connection to Sauce Labs. Using a Ruby gem, the credentials have been encrypted in a way that Travis and Sauce can work with. You can read about that process on this page of the Sauce documentation: [https://docs.saucelabs.com/ci-integrations/travis-ci/](https://docs.saucelabs.com/ci-integrations/travis-ci/)

## Travis Configuration

Julian has signed up for a free account on Travis. In the profile for that account, there is an option to connect it to the Formulize Github repository (since the Travis account knows Julian's GitHub username). When that connection is switched on, Travis listens for commits to Github. 

When there is a commit, Travis reads the **.travis.yml** file in the root of the repository. That file contains the code like this:
    
    env:
      global:
      - secure: f1Dkf/AObhtZJf/OtM/NREIZRzVeuw/QXQkV82WrbszKdpLrm1RwfwTAgcUa+JDiE931LJ41W86JWUia3PbS3pLVSL9gSwfBPk+PU6VM7AeYIVuje1V1tXpZoGlf+0RcfGHZA3JzeNSo65QjKl5iUxrT31wnpQpQ9rdG/bXQrUg=
      - secure: Xjc2qZs42LLIk4lRMc/JdFxeN5pmXUTLFd/stohOTX3g1rmRInXXqYpnXW/S15FjShqgtQAvvmnU/SV9QNpfaMhQVgS1ORVTS10gvy3aLPodW5hB9AGCDiQHXD0r9p+73LfNzenvdtmDme9Pp8PdLwb+spSroRbXgJ3tqHpXntM=
      phpenv:
      - version-name: "5.5.10"
    
    language: php
    
    php:
      - 5.5
    
    install:
      - sudo apt-get update
      - sudo apt-get install apache2 libapache2-mod-fastcgi
      # enable php-fpm
      - sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
      - sudo a2enmod rewrite actions fastcgi alias
      - echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
      - ~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm
      # configure apache virtual hosts
      - sudo cp -f ci/travis/travis-ci-apache /etc/apache2/sites-available/default
      - sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
      # set 777 on necessary directories
      - sudo chmod 777 cache
      - sudo chmod 777 templates_c
      - sudo chmod 777 uploads
      - sudo chmod 777 install
      - sudo chmod 777 mainfile.php
      - sudo chmod 777 cache
      - sudo chmod 777 modules/formulize/cache
      - sudo chmod 777 modules/formulize/temp
      - sudo chmod 777 modules/formulize/upload
      - sudo chmod 777 modules/formulize/templates/screens/default
      - sudo chmod 777 modules/formulize/export
      # make the trust path because the fcgi process cannot
      - sudo mkdir selenium-848d24bb54d726d
      - sudo chmod 777 selenium-848d24bb54d726d
      # add to .ini and restart
      - phpenv config-add ci/travis/php.ini
      - sudo service apache2 restart
    
    addons:
      sauce_connect: true
    
    before_script:
      - npm install -g se-interpreter
      - mysql -e 'create database formulize;'
      - echo "USE mysql;\nUPDATE user SET password=PASSWORD('password') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root
    
    script:
      - se-interpreter ci/travis/interpreter_config.json
      - cat "error.log"
      - curl http://localhost/index.php
    
    after_script:

### What the sections do

Some of the contents of the file are based on the Sauce-Travis documentation linked to above: [https://docs.saucelabs.com/ci-integrations/travis-ci/](https://docs.saucelabs.com/ci-integrations/travis-ci/).  Here is an explanation of the main sections of the file:


#### env

This records global variables for the username and password, and declares the php environment we're going to use.

#### install

This runs a series of commands on the spontaneous cloud server that Travis builds for us.  These commands install and Apache server with PHP, and configure it so the webroot folder is the folder where Travis has copied all the files from our GitHub repository.

These commands depend on two other files:

* **ci/travis/php.ini** - a file with some additional php.ini instructions we want the server to use
* **ci/travis/travis-ci-apache** - an Apache configuration file that we do some search/replace operations on, in order for Apache to serve up pages from our webroot.

For more information on setting up Apache and PHP on Travis, see: [http://docs.travis-ci.com/user/languages/php/](http://docs.travis-ci.com/user/languages/php/)

#### addons

This tells Travis that it should open up a special tunnel with Sauce Labs, so that we can run our Selenium tests there, and point them at the server Travis has built.

#### before_script

This installs the se-interpreter which is a tool for reading the Selenium tests and passing instructions to Sauce Labs.  It also creates the MySQL database that we will use (we don't need to setup mysql in the 'install' section because Travis servers already all have MySQL installed).

#### script

This runs the se-interpreter, and points it at a configuration file.  It also dumps the error log to the screen after se-interpreter is finished, and tries to load the site index.php file as a last resort to give us a clue what's going on just in case all else fails.

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
            "ci/selenium2-tests/*"
          ]
        }
      ]
    }

#### Critical points:

* **driverOptions** - this declares the host and port that are used to connect to Selenium on Sauce. These are simply *by convention*; they don't arise from any other part of the configuration.
* **browserOptions** - Sauce Labs supports all kinds of browser and OS combinations.  Here we declare that we're using Firefox, and we also pass in our Sauce Labs username and password, and metadata about the Travis instance that is spawning these tests.
* **scripts** - this points to the directory where the actual Selenium tests are located.  They are run in alphanumeric order according to their file names.

You can read more about se-interpreter on its GitHub page: [https://github.com/Zarkonnen/se-interpreter](https://github.com/Zarkonnen/se-interpreter)

## Viewing Test Results

Once the tests have run, you can see the results on Travis: [https://travis-ci.org/jegelstaff/formulize/builds](https://travis-ci.org/jegelstaff/formulize/builds)

That page is publically available.  Clicking on a build number will take you to a detailed results page, that shows the complete command line output of the server over its lifetime, including a full listing of the results of every step of every Selenium test.

To see the Selenium tests in action, you have to log in to the Open Sauce account on Sauce Labs.  From there, you can see screencasts of the tests while they run in Firefox.









