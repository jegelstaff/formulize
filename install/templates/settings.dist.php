<?php
/**
 * This site's settings.
 *
 * Formulize reads this file on every request, before anything else. It's the only file a site needs to say how to
 * reach its database. Everything else that is the site's own is in this folder too: private/, uploads/ and var/.
 * See ai-context/site-directory.md in the Formulize code, or "The site folder" in the Formulize documentation.
 *
 * Each value can come from an environment variable instead, which is how a container is usually given its settings.
 * The variable takes the place of what's written here.
 *
 * Keep this file private. It holds the database password and the salt.
 * Never change the salt once the site is live: passwords and encrypted values depend on it.
 */

define('SDATA_DB_HOST', getenv('FORMULIZE_DB_HOST') !== false ? getenv('FORMULIZE_DB_HOST') : {DB_HOST});
define('SDATA_DB_USER', getenv('FORMULIZE_DB_USER') !== false ? getenv('FORMULIZE_DB_USER') : {DB_USER});
define('SDATA_DB_PASS', getenv('FORMULIZE_DB_PASS') !== false ? getenv('FORMULIZE_DB_PASS') : {DB_PASS});
define('SDATA_DB_NAME', getenv('FORMULIZE_DB_NAME') !== false ? getenv('FORMULIZE_DB_NAME') : {DB_NAME});
define('SDATA_DB_PREFIX', getenv('FORMULIZE_DB_PREFIX') !== false ? getenv('FORMULIZE_DB_PREFIX') : {DB_PREFIX});
define('SDATA_DB_SALT', getenv('FORMULIZE_DB_SALT') !== false ? getenv('FORMULIZE_DB_SALT') : {DB_SALT});

// Options for the database connection, if any are needed, eg: array(PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem')
define('SDATA_DB_OPTIONS', array());

// The site's address, when Formulize can't work it out from the request, such as behind a proxy that changes it.
// The FORMULIZE_URL environment variable does the same.
// define('FORMULIZE_URL', 'https://forms.example.com');

// Behind a proxy that adds X-Forwarded-Host and X-Forwarded-Port headers, use them for the site's address. Only
// when the proxy is the only way to reach the site, since anyone else could send them.
// define('FORMULIZE_TRUST_PROXY_HEADERS', true);

// Any of the folders named in include/sitepaths.php can be put somewhere else here, such as var/ on a disk of the
// server's own:
// define('FORMULIZE_VAR_PATH', '/var/lib/formulize/var');
