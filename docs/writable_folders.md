---
layout: default
permalink: documentation/deploying_a_website/writable_folders/
redirect_from:
 - deploying_a_website/writable_folders/
title: Folders in Formulize that need to be writable
---

# Folders in Formulize that need to be writable

The web server writes to one folder: the [site folder](../site_folder), which holds everything that belongs to your site rather than to Formulize. It's `sites/default` unless you've put it somewhere else. If the web server can't write there, Formulize won't work properly.
{% for path in site.data.writableFilesAndFolders.paths %}
- /{{ path }}{% if path == 'modules' %} &mdash; only during installation, while the installer adds the Protector module. It can be read-only afterwards.{% endif %}{% endfor %}

Nothing else in Formulize needs to be writable, so the rest of the files can belong to another user, or be read-only.

If you put the site folder somewhere else, with `FORMULIZE_SITE_PATH`, that folder needs to be writable instead of `/sites`.

## Sites installed before the site folder

A site installed with an earlier version of Formulize keeps its files in folders throughout the code, and the web server has to be able to write to all of them:

- /mainfile.php &mdash; only during installation
- /cache, /templates_c, /tokens, /uploads, /logs, /themes
- /modules/formulize/cache, /modules/formulize/code, /modules/formulize/export, /modules/formulize/queue, /modules/formulize/temp, /modules/formulize/templates/screens
- /modules/formulize/language/english/mail_template and /modules/formulize/language/english/formulize_dateformats.php, and the same in any other language your site uses

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



