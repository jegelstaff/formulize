# Sites

Each folder here holds one Formulize site's own files, apart from the code. The installer makes `default/`, unless the web server or the environment names another folder with `FORMULIZE_SITE_PATH`.

```
default/
  settings.php   the database login and salt, which environment variables can give instead
  private/       what the site's people wrote: custom code, screen templates, configuration, logs, queues
  uploads/       what's served: uploaded files, appearance files, downloads
  var/           what Formulize generates, which can be deleted and isn't backed up
```

A copy of `settings.php`, `private/` and `uploads/`, with a dump of the database, is a copy of the whole site.

The web server serves nothing here but each site's `uploads/` folder. See `ai-context/site-directory.md`.
