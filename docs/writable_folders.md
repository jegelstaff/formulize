---
layout: default
permalink: writable_folders/
title: Folders in Formulize that need to be writable
---

# Folders in Formulize that need to be writable

There are certain folders in Formulize which must be writable by the web server. If they are not, Formulize will not work properly.

- /cache
- /logs
- /templates_c
- /uploads
- /modules/formulize/cache
- /modules/formulize/code
- /modules/formulize/export
- /modules/formulize/queue
- /modules/formulize/temp
- /modules/formulize/templates/screens (and all subs)
- /modules/formulize/upload

# File Permissions and Git

One _gotcha_ with using ```git``` is that the user doing the git operations, and the web server user, should generally be in the same group.

What you must avoid, is having the files created by git, be unwritable by the web server. Generally, if the git user and the web server user are in the same group, this isn't a problem.

But on some servers, it is definitely a problem so you will need to make sure the file ownership and permissions work in such a way that the web server can write to the files and folders above.

There are different ways to solve this problem. In general, the most reliable is for the git user and the web server user to be in the same group, and for the files to be writable by the group.

## umask 022

Depending how your server is configured, you may find that it's necessary to set a ```umask``` command in your ```.bashrc``` file:

```bash
# Ensure 664, 775 permissions generally
umask 002
```
OR
```bash
# Ensure 644, 755 permissions generally
umask 022
```

Putting this in your ```.bashrc``` folder will set the default permissions for files and folders created by your user. This can be critical depending on the way your server is setup and the overall group and user setup, etc.



