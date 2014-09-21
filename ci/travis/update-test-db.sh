# - mysqldump -u travis formulize > ci/formulize_test_db.sql
# - git config user.email "formulize@travis.ci"
# - git config user.name "Travis CI"
# - git config push.default simple
# - git add ci/formulize_test_db.sql
# - git commit -m "Travis updating test DB [skip ci]"
# - stty -echo
# - git push https://${GITHUB_TOKEN}@github.com/jegelstaff/formulize.git HEAD:${TRAVIS_BRANCH}
# - stty echo

