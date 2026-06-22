# Docker DB help guide

> **This Docker setup is for local development only — it is NOT meant for production.** Both the web server (port 8080) and MariaDB (port 3306) are published only on `127.0.0.1`, so they are reachable from your own machine but not from other machines on your network or the internet. This is a deliberate safeguard for local dev. The configuration also uses well-known default credentials and is not hardened, so it should never be used to host a live site.

## Starting everything up
From the root of the repo, run `docker compose up` to start all the containers (web + MariaDB). The first time you do this, MariaDB will create its data volume and run any seed files (see below). Once it's running, the site is available at `http://localhost:8080` (the web container maps port 8080 on your machine to port 80 in the container, as set in `docker-compose.yaml`).

## Connection details
For quick reference, the local MariaDB instance uses these settings (defined in `docker-compose.yml`):

| Setting | Value |
| --- | --- |
| Host (from outside Docker) | `localhost:3306` |
| Host (from the web container) | `mariadb:3306` |
| Database name | `formulize` |
| User | `user` |
| Password | `password` |
| Root password | `abc123` |

Note on container names: the commands below assume the MariaDB container is named `formulize-mariadb-1`. The container name is derived from the name of the folder you cloned the repo into, so if your folder isn't named `formulize`, your container will be `<your-folder>-mariadb-1`. Run `docker ps` to see the actual name.

## Setting up a database
SQL files in the `/docker/mariadb/seed/` folder will be parsed and run (in alphabetical order) when the MariaDB Docker container starts, if it has no database in its volume already.

Only files ending in `.sql` are run. The `.gitignore` in the seed folder means seed files are NOT committed to the repo, so a fresh clone starts with an empty seed folder. You need to supply your own seed `.sql` file (for example, a dump of an existing Formulize database — see below) before the first `docker compose up`, otherwise you'll start with an empty database.

To keep a dump in the seed folder without it being run, rename it to end in `.sql.off` (anything that isn't `.sql` will be ignored). This is a handy way to keep several databases on hand and swap which one is active.

Note: make sure your sql files are UTF8 encoded!! Depending on OS, etc, you may need to convert the encoding of the file prior to starting Docker.

## Persisting a database between sessions
The database is stored in a docker volume, named `FOLDERNAME_mariadb_data` volume (where FOLDERNAME is the directory name of your formulize codebase). This volume is created when you run `docker compose up` for the first time. It is persisted between runs and will remain persistent even if you perform a `docker compose down` operation.

## Deleting the database/starting over
In order to purge your volume perform a `docker compose down -v` to ensure volumes are deleted as well as containers. The next time you run `docker compose up`, the seed files will be run again against the fresh volume.

## Getting a dump of a production database
To get a dump of a live database from a server, to use as a seed (see above), you can export it from phpmyadmin, or whatever control panel your server uses. If the .sql file does not work, you may get better results by manually running mysqldump on the command line with the --single_transaction flag. Doing so will cause the generated SQL to be structured differently and more simply.

## Getting a dump of your local database
If you're using the Docker setup in Formulize, you can dump the DB to a file like this (in a Powershell terminal):

`docker exec formulize-mariadb-1 mariadb-dump --user user --password=password --default-character-set=utf8mb4 --databases formulize > formulize.sql`

## Running SQL against your local database
You can run sql against the DB manually by putting a `.sql` file in the `/docker/mariadb/seed` folder (which is mounted inside the container at `/docker-entrypoint-initdb.d/`) and then running it like this:

`docker exec formulize-mariadb-1 /bin/sh -c 'mariadb -u user --password=password --default-character-set=utf8mb4 </docker-entrypoint-initdb.d/formulize.sql'`

Change `formulize.sql` to match the name of your file. This command does not select a database, so your `.sql` file needs to specify its own database with a `USE` (or `CREATE DATABASE ... USE`) statement, or you can add `formulize` after the password flag to target the default database. The `--default-character-set=utf8mb4` flag matches the dump command above and helps avoid encoding problems on import.

Remember that `.sql` files in the `/docker/mariadb/seed/` folder will also be parsed and run automatically when the MariaDB container starts, if there is no database in its volume already (see Persisting above).

## Connecting to MariaDB directly
From outside Docker, you can connect directly to MariaDB through localhost:3306 but if you are connecting from the web container in Docker, then you need to use `mariadb:3306`

## Getting to the command line on a docker container
You can bring up the command line on any container by referencing the container name in a command like this:

`docker exec -it formulize-mariadb-1 bash`

Once on the command line of the MariaDB container, you can access MariaDB's console with this:

`mariadb --user user --password=password`
