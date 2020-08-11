<?php

include "../../mainfile.php";
icms::$logger->disableLogger();
print (isset($_SESSION['xoopsUserId']) ? 1 : 0);
