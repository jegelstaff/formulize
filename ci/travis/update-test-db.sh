#!/bin/bash

if [ "master" == "${TRAVIS_BRANCH}" -a "false" == "${TRAVIS_PULL_REQUEST}" ]; then
    echo 'Updating formulize_test_db.sql dump with current database.'
    mkdir /dbupdate/
    sudo chmod 777 /dbupdate/
    cd /dbupdate/
    git clone https://github.com/jegelstaff/formulize.git
    sudo chmod 777 /dbupdate/ci/formulize_test_db.sql
    mysqldump -u travis formulize > /dbupdate/ci/formulize_test_db.sql
    git config user.email "travis-ci@yourturn.ca"
    git config user.name "Travis CI"
    git config push.default simple
    git add ci/formulize_test_db.sql
    git commit -m "Travis updating test DB [skip ci]"
    git push https://${GITHUB_TOKEN}@github.com/jegelstaff/formulize.git HEAD:master --quiet
else
    echo 'Not Updating formulize_test_db.sql dump with current database because this is not the master branch.'
fi
