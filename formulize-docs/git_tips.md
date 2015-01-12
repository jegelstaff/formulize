---
layout: default
permalink: developers/git_tips/
---

# Git Tips and Tricks

To search for a particular bit of code that was added somewhere, but you don't know which branch, try this:

    git log -p -all -S 'some text you want to find'
    
Press 'q' to exit the results, when you see (END) on screen.
    
More info: [Stack Overflow](https://stackoverflow.com/questions/15292391/is-it-possible-to-perform-a-grep-search-in-all-the-branches-of-git-project/26226807#26226807)