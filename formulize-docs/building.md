---
layout: default
permalink: building/
---

# Building Formulize for Release

There are a few files and folders that should be removed from the master branch, when creating a Formulize package for release.  These files and folders are used in the development of Formulize, and are not necessary for using Formulize in normal environments.  It does not cause a problem for these files and folders to be included; Formulize will still work. But they are unnecessary for normal operation.

The unncessary files and folders are:

* /.git/
* /ci/
* /formulize-docs/
* .gitignore
* .travis.yml
* README.md

When the master branch is ready to be released, it should be merged into an appropriate branch named after the version number of the release, so it is easy to see what code was part of that specific release.

Then a .zip file and other package files can be created from the branch, without the unnecessary files identified above.