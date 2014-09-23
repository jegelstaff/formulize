#!/bin/bash

if [ "master" == "${TRAVIS_BRANCH}" ]; then
    echo 'Updating formulize_test_db.sql dump with current database.'

    mysqldump -u travis formulize > ci/formulize_test_db.sql
    git config user.email "travis-ci@yourturn.ca"
    git config user.name "Travis CI"
    git config push.default simple
    git add ci/formulize_test_db.sql
    git commit -m "Travis updating test DB [skip ci]"
    git push https://${GITHUB_TOKEN}@github.com/jegelstaff/formulize.git HEAD:master --quiet
else
    echo 'Not Updating formulize_test_db.sql dump with current database because this is not the master branch.'
fi
