---
layout: default
permalink: developers/version_control/
---

# Version Control

We use [Github](http://www.github.com/jegelstaff/formulize/) as our version control system.  We have a unified process in Github for branching, testing, documentation and merging:

1. Checkout the master branch [help](./branching)
2. Create a new branch based on master [help](./branching)
3. Start a pull request an GitHub [help](./branching)
4. Make code changes, push to your branch regularly
5. Create Selenium tests [help](./testing/creating_tests)
6. Verify that the tests work in the Cl environment 
7. Update/create documentation where applicable [help](./documentation)
8. Give "thumbs up" in your pull request when you think it is ready
9. A maintainer will review the pull request and merge it
10. A maintainer will publish the documentation updates, if any [help](../github_pages)

For more details about each major part of this process, see the following pages:

* [New Features and Branching](branching)
* [Testing](testing)
* [Documentation](documentation)
* [Code Review, Pull Requests and Merging](merging)
