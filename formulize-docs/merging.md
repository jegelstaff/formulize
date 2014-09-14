---
layout: default
permalink: version_control/merging/
---

# Merging

Once you are satisfied that a branch is ready for review, add a comment to the Pull Request on GitHub and include the thumbs-up symbol (which you can get by typing **:+1** in the comment box).  **Branches should contain the relevant code changes, plus applicable tests, plus relevant changes and additions to the documenatation.** [Tests](../testing) and [docs](../documentation) can be commited to GitHub alongside code.

Another developer will review the branch, by reading it, and in most cases, by running it locally. You should make clear in the comments for the pull request, what steps are necessary in the software to use/trigger the changes that are part of the branch.

Any tests that are part of your branch will automatically be run by Travis and Sauce Labs.

Comments will be made on the branch in one of several ways: inline in the code (GitHub's Pull Request UI allows for this), or in the comments for a specific commit, or in the general conversation comments for the Pull Request.

If further work is required, more commits can be made to the pull request, and a thumbs-up comment should be made once the work is complete.

If the branch is accepted, then the reviewer will merge it with the master branch. Once the merge is complete, then the branch can be deleted, and any new/updated  documentation [can be deployed to GitHub Pages](/formulize/github_pages).

