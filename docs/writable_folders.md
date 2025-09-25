---
layout: default
permalink: deploying_a_website/writable_folders/
title: Folders in Formulize that need to be writable
---

# Folders in Formulize that need to be writable

There are certain folders in Formulize which must be writable by the web server. If they are not, Formulize will not work properly.

- /cache
- /logs
- /templates_c
- /uploads
- /tokens
- /modules/formulize/cache
- /modules/formulize/code
- /modules/formulize/export
- /modules/formulize/language/english/mail_template (and 'mail_template' in other language folders, if applicable)
- /modules/formulize/queue
- /modules/formulize/temp
- /modules/formulize/templates/screens (and all subs)
- /modules/formulize/upload

## Make mainfile.php writable if you're installing for the first time

If you're going through [the installer to setup Formulize](../installing_formulize) for the first time, make sure that ```mainfile.php``` in the root of the website is writable by the web server.

## File Permissions and Git

One _gotcha_ with using ```git``` is that on some servers the the user doing the git operations, and the web server user, have incompatible permissions and/or group memberships. This can screw up the file permissions/ownerships, and cause problems.

There are different ways to solve this problem. In general, the most reliable is for the git user and the web server user to be in the same group, and for the files to be writable by the group. Then the web server can do what it needs to do with the files when it needs to create and edit them.

But if git operations result in the files that need to be writable, not being writable by the web server, then you need to fix that before Formulize will work correctly.

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



