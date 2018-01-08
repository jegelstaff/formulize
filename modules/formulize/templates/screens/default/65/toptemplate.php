<?php

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

$yearParts = strstr($_POST['search_ro_module_year'], 'qsf_') ? explode('_', $_POST['search_ro_module_year']) : array(2=>$_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
$year = $yearParts[2];
$yearData = getData('',21,'master_year_list_year/**/'.$year.'/**/=');
$yearStatus = display($yearData[0], 'master_year_list_status');
$activeYear = $yearStatus != 'Active' ? false : true;
$locked = $yearStatus == 'Locked' ? " <span style='color: red;'>🔒 Locked!</span>" : "";
$archived = $yearStatus == 'Archived' ? " <span style='color: red;'>Archived!</span>" : "";

// Start text

print "<h1>Weekly Schedule</h1>";
print "<div class='clearboth'></div>";
print "<hr>";

print "<div id='filter_rest'>\n";
print "<h2>Filter by year: $quickFilterro_module_year$locked$archived</h2>\n";
print "<h2>Filter by program: $quickFilterro_module_program</h2>\n";
print "<h2>Filter by building/room: $quickSearchsections_practica_room</h2>\n";
print "<h2>Filter by course code or title: ".$quickSearchro_module_full_course_title."</h2>\n";
print "<h2>Filter by instructor: ".$quickSearchinstr_assignments_instructor."</h2>\n";

print "</div>";

print "<div id='filter_semester'>";

print "<h2>Filter for courses in these semesters:</h2>\n";
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$semesterElement = $element_handler->get('ro_module_semester');
$semesters = array_keys($semesterElement->getVar('ele_value'));
$allChecked = (!isset($_POST['search_ro_module_semester']) OR $_POST['search_ro_module_semester'] == '') ? 'checked' : '';
print "<input type='checkbox' id='all-sem' class='allsem' name='sem' value='' $allChecked> <label for='all-sem'>All</label><br>\n";     
foreach($semesters as $sem) {
    $checked = (isset($_POST['search_ro_module_semester']) AND strstr($_POST['search_ro_module_semester'], $sem)) ? 'checked' : '';
    print "<input type='checkbox' id='$sem' class='sem' name='sem' value='$sem' $checked> <label for='$sem'>$sem</label><br>\n";     
}
print "</div>";
// floating save button
print "<input type='hidden' id='scrollx' name='scrollx' value='' />\n";
print "<div id='update_button'><input type='button' id='formulize_saveButton' class='formulize_button' value='Update' onclick='jQuery(\"#scrollx\").val(jQuery(window).scrollTop());showLoading();' /></div>";
print "<div class='clearboth'></div>";
print "<hr><br>\n";