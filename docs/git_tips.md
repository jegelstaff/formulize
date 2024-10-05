---
layout: default
permalink: developers/git_tips/
---

# Git Tips and Tricks

## Search for a bit of code

To search for a particular bit of code that was added somewhere, but you don't know which branch, try this:

    git log -p -all -S 'some text you want to find'
    
Press 'q' to exit the results, when you see (END) on screen.
    
More info: [Stack Overflow](https://stackoverflow.com/questions/15292391/is-it-possible-to-perform-a-grep-search-in-all-the-branches-of-git-project/26226807#26226807)

## Make a .zip file of changes between two commits

This lifesaver snippet of code lets you specify two commits and get a .zip containing only the files that are different between them. Perfect for making patches!

    git archive --output=changes.zip HEAD $(git diff --name-only SHA1 SHA2 --diff-filter=ACMRTUXB)
    
Replace SHA1 and SHA2 with the git commits you are interested in.

Note that sometimes it can't determine the differences, if there's some funky series of merges that's happened. Super annoying when that happens, but if so, try getting differences between two commits closer together.