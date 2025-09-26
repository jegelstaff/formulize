# Docker DB help guide

## Development environment

Different environments require specific configuration files to ensure that case sensitivity is handled correctly.

### Windows

You should rename or copy the file: `/docker/maraidb/conf.d/lower_case_table_names.cnf.windows` to: `/docker/maraidb/conf.d/lower_case_table_names.cnf`
(ie: remove the .windows part on the end). Then make the file 'read-only' (it will be ignored by MariaDB if it is world writable).

### MacOS

You should rename or copy the file: `/docker/maraidb/conf.d/lower_case_table_names.cnf.macos` to: `/docker/maraidb/conf.d/lower_case_table_names.cnf`
(ie: remove the .windows part on the end). Then make the file 'read-only' (it will be ignored by MariaDB if it is world writable).

This file will ensure that when MariaDB starts, it properly handles case sensitivity in your table names, and can handle identifiers in SQL dumps from a case sensitive production environment, like Linux. Note that any SQL produced locally, from phpMyAdmin for example, will be generated with lowercase identifiers, because internally MariaDB will be storing identifiers in lowercase on Windows. If the corresponding identifiers on the production environment include uppercase characters, you will run into issues when running SQL from your local environment in your production environment. This can be avoided by correcting any identifiers in the SQL before running it in production, to ensure it matches the case used on the production environment.

## Setting up a database
SQL files in the `/docker/mariadb/seed/` folder will be parsed and run (in alphabetical order) when the MariaDB Docker container starts, if there is no database already in the `/docker/mariadb/data/` folder.

Note: make sure your sql files are UTF8 encoded!! Depending on OS, etc, you may need to convert the encoding of the file prior to starting Docker.

## Persisting a database between sessions
The MariaDB database files will be stored in the `/docker/mariadb/data/` folder and any files there will be used by MariaDB when the container is next instantiated.

## Swapping databases
While the MariaDB container is not running, you can move the files in `/docker/mariadb/data/` (except .gitignore) somewhere else, and replace them with the files of a different database. Then, the next time the MariaDB container starts, it will use these database files.

## Getting a dump of a production database
To get a dump of a live database from a server, to use as a seed (see above), you can export it from phpmyadmin, or whatever control panel your server uses. If the .sql file does not work, you may get better results by manually running mysqldump on the command line with the --single_transaction flag. Doing so will cause the generated SQL to be structured differently and more simply.

## Getting a dump of your local database
If you're using the Docker setup in Formulize, you can dump the DB to a file like this (in a Powershell terminal):

`docker exec formulize-mariadb-1 mariadb-dump --user user --password=password --default-character-set=utf8mb4 --databases formulize > formulize.sql`

## Running SQL against your local database
You can run sql against the DB manually by putting a .sql file in the /docker/mariadb/seed folder and then doing this:

`docker exec formulize-mariadb-1 /bin/sh -c 'mariadb -u user --password=password </docker-entrypoint-initdb.d/formulize.sql'`

Remember that .sql files in the `/docker/mariadb/seed/` folder will be parsed and run when the MariaDB container starts, if there is no database already in the `/docker/mariadb/data/` folder.

## Connecting to MaraiDB directly
From outside Docker, you can connect directly to MariaDB through localhost:3306 but if you are connecting from the web container in Docker, then you need to use `mariadb:3306`

## Getting to the command line on a docker container
You can bring up the command line on any container by refencing the container name in a command like this:

`docker exec -it formulize-mariadb-1 bash`

Once on the command line of the MariaDB container, you can access MariaDB's console with this:

`mariadb --user user --password=password`

## Root password for MariaDB
The root password for accessing MariaDB is `abc123` (as specified in the docker-compose.yml file)
