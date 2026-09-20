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

You might want to look at the [step-by-step instructions for setting up Formulize and Docker in VS Code on Windows](/developers/debugging#formulize-vscode).

## <a name='quick-start'></a>Quick Start

```bash
git checkout -b my-formulize-branch monastery
docker compose up
```

The published `latest` image is based on PHP 8.3. See [Switching PHP Versions](#switching-php-versions) below if you need to test against a different version.

If you want to run more than one copy of Formulize at the same time, see [Running Several Copies At Once](#running-several-copies) below.

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

3. Download a dump of the entire database from the live website. Make sure it includes commands to create the tables. Make sure it is using the UTF-8 character set. This can be a `.sql` file or it can also be a tar/gzipped sql file (.e.g `backup.tar.gz`)

4. Place the database dump in the ```docker/mariadb/seed/``` folder. Delete any other database files in that folder.

5. Find the trust path file in your live website. If you don't know where it is, check ```mainfile.php``` in the root of your website and look for code like this near the top:
```php
define( 'XOOPS_TRUST_PATH', '/var/www/mysite.com/sadg876kjhg89' );
include_once XOOPS_TRUST_PATH . '/r87678sd908asdf48ffecfbfd223af293d.php' ;
```

6. Open the trust path file, and note the ```SDATA_DB_PREFIX``` and ```SDATA_DB_SALT``` values

7. In your local Formulize, open up the file: ```trust/e039c9b9cb48ffecfbfd223af293d984.php``` and change the value of ```SDATA_DB_PREFIX``` and ```SDATA_DB_SALT``` to match the values sepecified in the trust path file in your live site.

8. If your site uses any custom code files or has made changes to core Formulize files, or has extra files of its own, you need to download/recreate those files and changes in the local repository.

9. Run ```docker compose up```, or right click on the docker-compose.yaml file in your IDE, etc

10. Browse to [http://localhost:8080](http://localhost:8080) and login with any username and password from the live site.

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

2. There is a ```docker``` folder that contains the build recipe at ```docker/php/Dockerfile```, and a ```php``` folder with ```.ini``` files in it, and a ```mariadb``` folder with the database in it. The database persists between Docker sessions.

3. There is a ```.env.example``` file in the root of the repository with local Docker variables you can copy into ```.env``` to control which PHP version the web service uses, and which host ports the containers use.

4. There are launcher scripts at ```docker/up.ps1``` (Windows) and ```docker/up.sh``` (macOS/Linux) which pick free ports for you before starting Docker. See [Running Several Copies At Once](#running-several-copies).

5. The ```docker/mariadb/seed``` folder can contain ```.sql``` files which Docker will execute when it first sets up the database. If there is an existing database, the ```docker/mariadb/seed``` folder is ignored. __It can take a little while for the ```.sql``` files to be processed, depending on their size and the speed of your computer!__

6. The URL for accessing the Docker container is [http://localhost:8080](http://localhost:8080) by default

## Database files in Docker

The database is stored in a docker volume. Previous iterations of our docker compose configuration had the data directory mounted on the host's file system. We chose to move to storing the files in a docker linux volume to alleviate any compatibility issues with how different operating systems handle case sensitivity.

The database is stored in the `FOLDERNAME_mariadb_data` volume (Where FOLDERNAME is the directory name of your formulize codebase). This volume is created when you run `docker compose up` for the first time. It is persisted between runs and will remain persistent even if you perform a `docker compose down` operation.

### Deleting the maraidb volume

In order to purge your volume perform a `docker compose down -v` to ensure volumes are deleted as well containers

## <a name='switching-php-versions'></a>Switching PHP Versions

The published `latest` image is based on PHP 8.3. The web service's PHP version is configurable with the `FORMULIZE_PHP_VERSION` env var.

### Using a `.env` file (recommended)

Create a `.env` file from `.env.example`, then set `FORMULIZE_PHP_VERSION` there (for example, `8.1`). Docker Compose reads `.env` automatically, so once it's set there you don't need to repeat it on the command line — just rebuild the `web` image:

```bash
docker compose up -d --build --force-recreate web
```

### Without a `.env` file

You can also set the variable inline for a one-off test, without creating or editing `.env`:

```bash
FORMULIZE_PHP_VERSION=8.1 docker compose up -d --build --force-recreate web
```

PowerShell (Windows) equivalent:

```powershell
$env:FORMULIZE_PHP_VERSION="8.1"
docker compose up -d --build --force-recreate web
```

### Switching back to the default image

If you're using an `.env` file, set `FORMULIZE_PHP_VERSION` back to blank (or delete the `.env` file). Then run:

```bash
docker compose pull web
docker compose up -d --force-recreate web
```

## <a name='running-several-copies'></a>Running Several Copies At Once

By default the web container is published on host port 8080 and the database container on 3306. Only one thing at a time can hold a port, so if you have Formulize checked out in two folders and you run `docker compose up` in both, the second one fails to start.

The fix is to give each copy its own ports. You don't have to pick them yourself — use the launcher script, which finds free ports for you.

### The command to run

There is one launcher, written twice: once for PowerShell and once for the shells used on macOS and Linux. **Run the one that matches your computer.**

On **Windows**, in PowerShell, from the root of the repository:

```powershell
.\docker\up.ps1
```

On **macOS or Linux**, in a terminal, from the root of the repository:

```bash
bash docker/up.sh
```

That is a replacement for `docker compose up` — you run it *instead of* that command, not as well as it. It:

1. Checks whether ports 8080 and 3306 are free, counting upwards until it finds ones that are. A second copy of Formulize lands on 8081/3307, a third on 8082/3308, and so on.
2. Writes the ports it chose into a `.env` file at the root of the repository, creating that file if you don't already have one.
3. Prints the URL to browse to.
4. Starts Docker, exactly as `docker compose up` would.

Anything extra you type is passed straight through to `docker compose up`, so this starts in the background as usual:

```powershell
.\docker\up.ps1 -d
```

To work out the ports without starting anything, add `-PortsOnly` in PowerShell, or `--ports-only` in bash.

### You do not need to create .env yourself

The launcher creates `.env` on its first run. If you have a `.env.example` file it starts from that, so the comments explaining every setting come along too.

`.env` is ignored by git, so it stays local to that one folder and doesn't follow you into a commit.

### The ports are chosen once, not on every start

Because the ports are written to `.env`, each copy of Formulize keeps the same ports for good: your bookmarks keep working, your database client keeps pointing at the right port, and the test suite keeps finding the site.

It also means that after the first run you can go back to using plain `docker compose up` in that folder if you prefer, and it will come up on the same ports — Docker Compose reads `.env` on its own.

The launcher only fills in blanks, so it will never move a copy of Formulize that is already running onto a different port. If you want it to choose again, blank out `FORMULIZE_WEB_PORT` and `FORMULIZE_DB_PORT` in `.env` and run it again.

### If PowerShell refuses to run the script

If you get *"cannot be loaded"* or *"is not digitally signed"*, Windows has marked the file as coming from the internet, which happens if you downloaded the repository as a ZIP rather than cloning it with git. Either unblock the file:

```powershell
Unblock-File .\docker\up.ps1
```

or run it without changing anything permanently:

```powershell
powershell -ExecutionPolicy Bypass -File .\docker\up.ps1
```

### Setting the ports yourself

You can skip the launcher entirely and set the ports by hand in `.env`:

```
FORMULIZE_WEB_PORT=8081
FORMULIZE_DB_PORT=3307
```

Anything you set yourself is left alone by the launcher — it only fills in blanks. As with any Compose variable, you can also set them inline for a one-off run:

```bash
FORMULIZE_WEB_PORT=8081 FORMULIZE_DB_PORT=3307 docker compose up
```

### Reaching Formulize from another device

By default the ports are published on `127.0.0.1`, which means Formulize and its
database are reachable only from the machine Docker is running on. Nothing else
on your network can see them.

If you need to open it up — to test on a phone or tablet on the same wi-fi, for
instance — set `FORMULIZE_BIND_HOST` in `.env`:

```
FORMULIZE_BIND_HOST=0.0.0.0
```

Then restart with `docker compose up -d`, and browse to your machine's address
on the network, such as `http://192.168.1.20:8080`. As with the ports, you can
also set it inline for a one-off run:

```bash
FORMULIZE_BIND_HOST=0.0.0.0 docker compose up
```

Leaving it blank keeps the default of `127.0.0.1`.

This exposes the database as well as the site, and both use the default
development passwords, so only do it on a network you trust.

### Two folders with the same name

Compose names its containers and its database volume after the folder the project is in. If your two checkouts are in folders with *different* names (say `formulize` and `formulize2`) they are separate projects and each gets its own database, which is what you want.

If the two folders happen to have the *same* name, Compose treats them as the same project and they will share a database. To keep them apart, set a distinct project name in one of them, in the same `.env` file:

```
COMPOSE_PROJECT_NAME=formulize-experiment
```
