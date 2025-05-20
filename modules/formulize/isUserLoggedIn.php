<?php

require_once "../../mainfile.php";
icms::$logger->disableLogger();
print (isset($_SESSION['xoopsUserId']) ? 1 : 0);
