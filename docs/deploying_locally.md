---
layout: default
permalink: developers/deploying_locally/
title: Deploying Locally
redirect_from:
 - developers/development_environment/
---

# Deploying Formulize Locally

Formulize can be run locally using Docker containers. Among other things, this makes [local debugging with XDebug](/developers/debugging) very easy.

## First, if you're running Windows

1. Find this file in your repository: ```/docker/maraidb/conf.d/lower_case_table_names.cnf.windows```

2. Copy or rename it to: ```/docker/maraidb/conf.d/lower_case_table_names.cnf```\
ie: Remove the ```.windows``` part on the end.

3. __make the file read-only__ (if it is writable, MariaDB will ignore it).

Next, you might want to look at the [step-by-step instructions for setting up Formulize and Docker in VS Code on Windows](/developers/debugging#formulize-vscode).

## <a name='quick-start'></a>Quick Start

```bash
git checkout -b my-formulize-branch monastery
docker compose up
```

Browse to [http://localhost:8080](http://localhost:8080) to access Formulize. Login with:
- username: _admin_
- password: _admin_

## Things to know about running Formulize locally

The official Formulize release packages, and the ```master``` branch, are ready for installation on a web server. If you use one of those, you will need to [go through the installer](/deploying_a_website/installing_formulize) in order to setup Formulize.

The ```monastery``` branch is a working Formulize system, ready to use. It has no forms or users, it is empty, but the installer has already been run. It is generally a better place to start for local development and testing.

If you have a copy of files from an existing Formulize website, you could use those too. However, you would need to have a SQL dump of the database from that website in order to load it up properly inside Docker.

## Running the monastery branch (recommended)

1. Checkout the ```monastery``` branch.

2. Make your own copy of the branch, if you intend to do work and keep track of changes, start a pull request, etc.

3. Run ```docker compose up```, or right click on the docker-compose.yaml file in your IDE, etc

4. Browse to [http://localhost:8080](http://localhost:8080)

5. Login with:
- username: _admin_
- password: _admin_

## Running the master branch, or a release (going through the installer)

1. Checkout the ```monastery``` branch.

2. Make a new branch, if you intend to do work and keep track of changes, start a pull request, etc.

3. Run ```docker compose up```, or right click on the docker-compose.yaml file in your IDE, etc

4. Browse to [http://localhost:8080](http://localhost:8080)

5. Follow the steps for [going through the installer](deploying_a_website/installing_formulize) in order to setup Formulize.

## Setting up a local development version of an existing website

1. Checkout the ```monastery``` branch

2. Make a new branch, if you intend to do work and keep track of changes, start a pull request, etc.

4. Delete any database in ```docker/mariadb/data/``` folder, other than the ```.gitignore file```. (These files will be the database from the last time you ran the ```monastery``` branch in Docker. You may want to save a back up of these files!)

5. Download a dump of the entire database from the live website. Make sure it includes commands to create the tables. Make sure it is using the UTF-8 character set. Make sure it ends with a ```.sql``` extension.

6. Place the database dump in the ```docker/mariadb/seed/``` folder. Delete any other ```.sql``` files in that folder.

7. Find the trust path file in your live website. If you don't know where it is, check ```mainfile.php``` in the root of your website and look for code like this near the top:
```php
define( 'XOOPS_TRUST_PATH', '/var/www/mysite.com/sadg876kjhg89' );
include_once XOOPS_TRUST_PATH . '/r87678sd908asdf48ffecfbfd223af293d.php' ;
```

8. Open the trust path file, and note the ```SDATA_DB_PREFIX``` and ```SDATA_DB_SALT``` values

9. In your local Formulize, open up the file: ```trust/e039c9b9cb48ffecfbfd223af293d984.php``` and change the value of ```SDATA_DB_PREFIX``` and ```SDATA_DB_SALT``` to match the values sepecified in the trust path file in your live site.

10. If your site uses any custom code files or has made changes to core Formulize files, or has extra files of its own, you need to download/recreate those files and changes in the local repository.

11. Run ```docker compose up```, or right click on the docker-compose.yaml file in your IDE, etc

12. Browse to [http://localhost:8080](http://localhost:8080) and login with any username and password from the live site.

## The Formulize environment in Docker

1. The webroot is the root of the repository. The full path to this folder within the Docker environment is ```/var/www/html```

2. The __trust path__ is the ```/trust/``` folder in the root of the repository. This is the folder where the database credentials are stored. The full path to this folder within the running Docker environment is ```/var/www/trust```

3. The __database location__ is _mariadb_. The database is **not** running on _localhost_

4. The __database name__ is _formulize_

5. The __database username and password__ are _user_ and _password_

6. If you're running the ```monastery``` branch, you can login to your local Formulize with:
- username: _admin_
- password: _admin_

## Key files and locations related to Docker and Formulize

1. There is a ```docker-compose.yaml``` file in the root of the respository.

2. There is a ```docker``` folder that contains a ```Dockerfile```, and a ```php``` folder with ```.ini``` files in it, and a ```mariadb``` folder with the database in it. The database persists between Docker sessions.

3. You can delete the contents of the ```docker/mariadb/data``` folder to erase the database and start over. (__Pro tip:__ don't delete the .gitignore file in there!)

4. The ```docker/mariadb/seed``` folder can contain ```.sql``` files which Docker will execute when it first sets up the database. If there is an existing database, the ```docker/mariadb/seed``` folder is ignored. __It can take a little while for the ```.sql``` files to be processed, depending on their size and the speed of your computer!__

5. The URL for accessing the Docker container is [http://localhost:8080](http://localhost:8080)
