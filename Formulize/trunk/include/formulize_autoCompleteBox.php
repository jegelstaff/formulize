<?php

include "../../../mainfile.php";

while(ob_get_level()) {
    ob_end_clean();
}

include XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

$testfile = fopen(XOOPS_ROOT_PATH . "/cache/testfile.txt", "w");
fwrite($testfile, $_GET['query'] . "\n" . $_GET['ele_id'] . "\n" . $_GET['form_id']);
fclose($testfile);

// initialize a bunch of things
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
$fid = intval($_GET['form_id']);
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);	
$gperm_handler =& xoops_gethandler('groupperm');
$member_handler =& xoops_gethandler('member');
$mid = getFormulizeModId();

if(!$gperm_handler->checkRight("view_form", $fid, $groups, $mid)) { exit(); }
$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);
$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);

if($view_globalscope) {
    $currentView = "all";
} elseif($view_groupscope) {
    $currentView = "group";
} else {
    $currentView = "mine";
}

$scope = buildScope($currentView, $member_handler, $gperm_handler, $uid, $groups, $fid, $mid);
$scopeToWrite = $scope ? "AND $scope" : "";
$query = htmlspecialchars(strip_tags($_GET['query']));
$element_id = intval($_GET['ele_id']);

global $xoopsDB;

$element_handler =& xoops_getmodulehandler('elements', 'formulize');
$element = $element_handler->get($element_id);

$sql = "SELECT ele_value, ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$fid AND ele_caption=\"" . mysql_real_escape_string(str_replace("'", "`", $element->getVar('ele_caption'))) . "\" AND ele_value LIKE \"" . mysql_real_escape_string($query) . "%\" $scopeToWrite ORDER BY ele_value LIMIT 0,10"; // YUI only shows first 10 results, hence LIMIT
$res = $xoopsDB->query($sql);
$results = array();
while($array = $xoopsDB->fetchArray($res)) {
    $results['result'][] = array('value'=>$array['ele_value'], 'id'=>$array['ele_id']);
}

include "../class/JSON-PHP.php";
$json = new Services_JSON();
$output = $json->encode(array('resultSet'=>$results));

print $output;

?>