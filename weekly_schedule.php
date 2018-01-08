<?php

include "mainfile.php";
include "header.php";

global $xoopsUser;
if(!$xoopsUser) {
    print "You do not have permission to view this page";
}

include "footer.php";
