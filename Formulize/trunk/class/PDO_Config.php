<?php

include "../../../mainfile.php";
//Everything Needed from the Mainfile Goes here .Using my Own Define
defined('DB_TYPE') ? NULL : define('DB_TYPE', 'mysql');
defined('DB_HOST') ? NULL : define('DB_HOST', XOOPS_DB_HOST);
defined('DB_USER') ? NULL : define('DB_USER', XOOPS_DB_USER);
defined('DB_PASS') ? NULL : define('DB_PASS', XOOPS_DB_PASS);
defined('DB_NAME') ? NULL : define('DB_NAME', XOOPS_DB_NAME);
defined('Prefix') ? NULL : define('Prefix',XOOPS_DB_PREFIX);

