<?php
// code modified from export.php where the template is made
include_once "mainfile.php";
$formulize_doingManualExport = true;
include XOOPS_ROOT_PATH."/modules/formulize/include/export.php";
$queryData = file(XOOPS_ROOT_PATH."/cache/exportQuery_".intval($_GET['eq']).".formulize_cached_query_for_export");
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
do_update_export($queryData, intval($_GET['frid']), intval($_GET['fid']), $groups); // needs $_GET['cols'] 