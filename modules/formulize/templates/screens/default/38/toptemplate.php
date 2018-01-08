<?php

include_once XOOPS_ROOT_PATH."/file_generation_code.php";
include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

print "<h1>".$screen->getVar('title')."</h1>";

print "<p>$addButton&"."nbsp;&"."nbsp;&"."nbsp;&"."nbsp;&"."nbsp;&"."nbsp;$docButton";
daraShowContractLinks(13, 11, "TA");
print "</p>";

daraShowYearFilter($quickFilterro_module_year);