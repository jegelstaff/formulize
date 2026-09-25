---
layout: default
permalink: documentation/git_tips/
redirect_from:
 - developers/git_tips/
title: Git Tips and Tricks
---

# Git Tips and Tricks

## Everyday commands

A quick reference for the commands that come up most often, with the placeholders
written out in angle brackets.

### See remote list

    git remote -vv

### See branches and their remotes

    git branch -vv

### See status of repo

    git status

### Create a branch

    git branch <newbranchname>

### Set the upstream of a branch

    git branch -u <remotename>/<branchname>

### Delete a local branch

    git branch -D <branchname>

### Switch files to a given branch

    git checkout <branchname>

### Checkout a remote branch into a new local branch

    git checkout -b <newlocalbranchname> --track <remote>/<branchname>

### Stage files

    git add <various to identify file(s)>

### Unstage files

    git reset <filepath>

Optionally add `--hard` and `HEAD`, or another identifier of a branch, etc.

### Get rid of changes in a file

    git restore <filepath>

### Get rid of all changes in the working directory

    git reset --hard HEAD

### Change a file in the working directory to the version from a prior commit

    git restore -s <SHA1> -- <filepath>

### Commit all staged changes

    git commit -m "Message here"

### See stashes

    git stash list

### See changes from a given stash (exclude whitespace)

`<Number>` is the number of the stash.

    git stash show -p -w <Number>

### Rebase repository history on top of another branch with a common history

    git rebase <otherbranchname>

### Rebase forcing the acceptance of a certain set of changes

Works with merge too. Note that `theirs` and `ours` are semantically reversed in
rebase mode.

    git rebase <branch> -s recursive -X <theirs|ours>

### Rebase on a branch, forcing all conflicts to resolve using the code in the branch you are rebasing onto

Again, rebase reverses the normal meaning of `ours` and `theirs`.

    git rebase -X ours <branch>

### Rebase the last X commits of the current branch on top of a different branch

    git rebase HEAD~X --onto <branch>

### Push to remote

Add `-f` to force push, which is necessary after a rebase.

    git push

### Push the head to a different remote branch

    git push <remote> HEAD:<branchname>

### Pull down all changes from remote for the active branch

    git pull

### Get everything from the remote, without changing the contents of the working directory

    git fetch

### Get everything from all remotes

    git fetch --all

### Get tags from all remotes

    git fetch --tags --all

### Show untracked files and directories that could be cleaned

    git clean -n -d

### Actually clean and remove untracked files and directories

    git clean -f -d

### Ignore a file in the working directory until further notice

This does not affect checkout, only the detection of changes in work in progress.

    git update-index --skip-worktree /c/code/formulize/.vscode/mcp.json

### Start tracking the file again

    git update-index --no-skip-worktree /c/code/formulize/.vscode/mcp.json

### Count the number of commits since a given hash

Or between two hashes.

    git rev-list <hash>..HEAD --count


## Search for a bit of code

To search for a particular bit of code that was added somewhere, but you don't know which branch, try this:

    git log -p -all -S 'some text you want to find'

Press 'q' to exit the results, when you see (END) on screen.

More info: [Stack Overflow](https://stackoverflow.com/questions/15292391/is-it-possible-to-perform-a-grep-search-in-all-the-branches-of-git-project/26226807#26226807)

## Make a .zip file of changes between two commits

This lifesaver snippet of code lets you specify two commits and get a .zip containing only the files that are different between them. Perfect for making patches!

    git archive --output=changes.zip HEAD \
      $(git diff --name-only SHA1 SHA2 --diff-filter=ACMRTUXB)

Replace SHA1 and SHA2 with the git commits you are interested in.

Note that sometimes it can't determine the differences, if there's some funky series of merges that's happened. Super annoying when that happens, but if so, try getting differences between two commits closer together.
