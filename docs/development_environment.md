---
layout: default
permalink: developers/development_environment/
---

# Development Environment

Formulize can be run locally using Docker containers. This makes [local debugging with XDebug](/developers/debugging) very easy. There are [step-by-step instructions for setting up Formulize and Docker in VS Code on Windows](/developers/debugging#formulize-vscode).

Here are the relevant files and locations involved in the Docker setup:

1. There is a docker-compose.yaml file in the root of the respository.
2. There is a Docker folder that contains a _Dockerfile_, and a _php_ folder with .ini files in it, and a _mariadb_ folder with the database in it
3. The URL for accessing the Docker container is [http://localhost:8080](http://localhost:8080)

## The Formulize environment in Docker

Key things to know when running Formulize locally in Docker:

1. The __trust path__ is the _/trust/_ folder in the root of the repository
2. The __database location__ is _mariadb_. The database is **not** running on _localhost_
3. The __database name__ is _formulize_
4. The __database username and password__ are _user_ and _password_

Also, if you running Docker in Windows, copy or rename the file: /docker/maraidb/conf.d/lower_case_table_names.cnf.windows to: /docker/maraidb/conf.d/lower_case_table_names.cnf (ie: remove the .windows part on the end).

## You have three choices when running a local installation of Formulize in Docker:

1. Run the monastery branch, which skips the installer.
2. Make a new branch based on the master branch, and go through the installer.
3. Set up a local development version of an existing website.

### Running the monastery branch

1. Checkout the _monastery_ branch.
2. Start the Formulize Docker containers.
3. Navigate to [http://localhost:8080](http://localhost:8080) and login with the username _admin_ and the password _admin_.
4. If you want to maintain a remote copy of the repository, consider where you want to push any changes (ie: your own GitHub repo? Maybe you should fork Formulize first before you do this). The _monastery_ branch on GitHub is meant to remain a pristine copy of Formulize immediately post-install.

### Making a new branch and going through the installer

1. Checkout the _master_ branch.
2. Make a new branch based on master. This is __important__ because you don't want changes you make here being committed to the master branch.
3. Start the Formulize Docker containers.
4. Navigate to [http://localhost:8080](http://localhost:8080) and run the Formulize installer to setup your local Formulize development system.
5. If you want to maintain a remote copy of the repository, consider where you want to push any changes (ie: your own GitHub repo? Maybe you should fork Formulize first before you do this).

### Setting up a local development version of an existing website

1. Checkout the _monastery_ branch (not the master branch).
2. Make a new branch based on monastery. This is __important__ because you don't want changes you make here being committed to the monastery branch.
3. Delete any files you may have in the _docker/mariadb/data/_ folder, other than the .gitignore file. (These files will be the database from the last time you ran the monastery branch in Docker. You may want to save a back up of these files.)
4. Download a dump of the entire database from the live website. Make sure it includes commands to create the tables. Make sure it is using the UTF-8 character set. Make sure it ends with a .sql extension.
5. Place the database dump in the _docker/mariadb/seed/_ folder. Delete any other .sql file that already exists there.
6. Open up the file _trust/8ff851a18bdbd4c79f859830a94089b0.php_ and change the value of SDATA_DB_PREFIX and SDATA_DB_SALT to match the values sepecified in the trust path file from the live site. Note that the filename will be a different random set of characters on your live site.
7. If your site uses any custom files or has made changes to core Formulize files, you need to download/recreate those files and changes in the repository.
8. Start the Formulize Docker containers.
9. Navigate to [http://localhost:8080](http://localhost:8080) and login with any username or password from the live site.
10. If you want to maintain a remote copy of the repository, consider where you want to push any changes (ie: your own GitHub repo? Maybe you should fork Formulize first before you do this).

