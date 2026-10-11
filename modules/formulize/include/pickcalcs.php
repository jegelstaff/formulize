<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

// this file generates the pick calculations popup



function calcJavascript() {
?>

<script type='text/javascript'>
<!--

function setCalcCustom(element) {
	var customElement = element+'_custom';
	for (var i=0; i < window.document.pickcalc.elements[element].options.length; i++) {
		if (window.document.pickcalc.elements[element].options[i].selected) {
			break;
		}
	}
	if(i==0) {
		valueToSet = "{BLANK},0";
	}
	if(i==1) {
		valueToSet = "";
	}
	if(i==2) {
		valueToSet = "!{BLANK},!0";
	}
	if(i==3) {
		valueToSet = "{BLANK}";
	}
	if(i==4) {
		valueToSet = "0";
	}
	if(i!=5) {
		window.document.pickcalc.elements[customElement].value = valueToSet;
	}
}

function sendCalcs(formObj) {

	var calc_cols;
	var calc_calcs;
	var calc_blanks;
	var calc_blanks = '';
	var calc_grouping;
	var calc_grouping = '';

<?php
// process POST to find the columns and calcs and then look at form elements directly based on requested columns
// options can change between POSTings, but calcs and columns do not change, hence the need to go to javascript for the options.

if($_POST['column']) {
	$numCols = count((array) $_POST['column']);
	for ($i = 0; $i < $numCols; $i++) {
		$columns[] = $_POST['column'][$i];
	$calcs[] = implode(",", $_POST['calculations']);
		}
/*	foreach($_POST['calculations'] as $acalc) {
		print "calc_blanks = formObj." . $acalc . $_POST['column'] . ".value;\n";
		print "calc_grouping = formObj.grouping_" . $acalc . "_" . $_POST['column'] . ".value;\n";
	}*/
}
foreach($_POST as $k=>$v) {
	if(strstr($k, "reqdcalc_column")) {
		$columns[] = $v;
		$calcs[] = $_POST['reqdcalc_calcs_' . $v];
//		$thesecalcs = explode(",", $_POST['reqdcalc_calcs_' . $v]);

	}
}

$colcount = count((array) $columns);

for($i=0;$i<$colcount;$i++) {
	// make the calc and column arrays and get the options from javascript.
	if($i>0) {
		$calc_cols .= "/";
		$calc_calcs .= "/";
		$sep = "'/'";
		print "calc_blanks = calc_blanks + \"/\";\n";
		print "calc_grouping = calc_grouping + \"/\";\n";
	}
	$calc_cols .= $columns[$i];
	$calc_calcs .= $calcs[$i];
	$thesecalcs = explode(",", $calcs[$i]);
	$start = 1;
	foreach($thesecalcs as $acalc) {
		if(!$start) {
			print "calc_blanks = calc_blanks + \",\";\n";
			print "calc_grouping = calc_grouping + \",\";\n";
		}
		print "calc_blanks = calc_blanks + formObj." . $acalc . $columns[$i] . ".value;\n";
		print "if(formObj.".$acalc.$columns[$i].".value=='custom') {\n";
		print "  var customString = formObj." . $acalc . $columns[$i] . "_custom.value;\n";
		print "  customString = customString.replace(/,/g,\"!@^%*\");\n"; // commas are a separator for when there's more than one calculation on a given column
		print "  calc_blanks = calc_blanks + customString\n";
		print "}";
		print "calc_grouping = calc_grouping + formObj.grouping_" . $acalc . "_" . $columns[$i] . ".value;\n";
		print "if(formObj.grouping2_" . $acalc . "_" . $columns[$i] . ".value) {\n"; // added Oct 29 2008 to handle double level grouping
		print "  calc_grouping = calc_grouping + '!@^%*' + formObj.grouping2_" . $acalc . "_" . $columns[$i] . ".value;\n";
		print "}\n";
		$start = 0;
	}

}

//debug code:
//print "alert(calc_blanks);\n";
//print "alert(calc_grouping);\n";

// assign cols and calcs and options
print "window.opener.document.controls.calc_cols.value = '$calc_cols';\n";
print "window.opener.document.controls.calc_calcs.value = '$calc_calcs';\n";
print "window.opener.document.controls.calc_blanks.value = calc_blanks;\n";
print "window.opener.document.controls.calc_grouping.value = calc_grouping;\n";
print "window.opener.document.controls.hlist.value = 1;\n";
print "window.opener.document.controls.hcalc.value = 0;\n";
print "window.opener.showLoading();\n";
print "window.self.close();\n";
?>
}

-->
</script>


<?php

}


// this function makes the hidden fields that carry the calculations that have been requested already
// also colates the requested calculations into an array for display lower down on the page
function addReqdCalcs() {
	$indexer = 0;
	$hidden = "";

	// add the most recently submitted calc if it is necessary...
	if($_POST['submitx']) {
		$numCols = count((array) $_POST['column']);
		for ($i = 0; $i < $numCols; $i++) {
			$hidden .= reqdCalcHidden('reqdcalc_column_' . $_POST['column'][$i], $_POST['column'][$i]);
			// flatten $_POST['calculation'] array
			$hidden_calcs = implode(",", $_POST['calculations']);
			$hidden .= reqdCalcHidden('reqdcalc_calcs_' . $_POST['column'][$i], $hidden_calcs);
			$rc[$indexer]['column'] = $_POST['column'][$i];
			$rc[$indexer]['calcs'] = $hidden_calcs;
			$indexer++;
		}
	}

	// get previously requested calcs
	foreach($_POST as $k=>$v) {
		if(strstr($k, "reqdcalc_column")) {
			$hidden .= reqdCalcHidden('reqdcalc_column_' . $v, $v);
			$rc[$indexer]['column'] = $v;
			$hidden .= reqdCalcHidden('reqdcalc_calcs_' . $v, $_POST['reqdcalc_calcs_' . $v]);
			$rc[$indexer]['calcs'] = $_POST['reqdcalc_calcs_' . $v];
		}
		$indexer++;
	}

	$to_return['hidden'] = $hidden;
	$to_return['rc'] = $rc;
	return $to_return;
}

// one of the hidden fields that carry the requested calculations
function reqdCalcHidden($name, $value) {
	return "<input type='hidden' name='" . htmlspecialchars($name) . "' id='" . htmlspecialchars($name) . "' value='" . htmlspecialchars($value) . "' />\n";
}

// delete calculations from request list
function handleDelete() {
	$delete = 0;
	foreach($_POST as $k=>$v) {
		if(strstr($k, "delete_")) {
			$delete = 1;
			$column = substr($k, 7);
			unset($_POST['reqdcalc_column_' . $column]);
			unset($_POST['reqdcalc_calcs_' . $column]);
			unset($_POST['column']);
		}
	}
	return $delete;
}

//This function takes the GET params and puts them into POST so they can be handled like properly requested calculations
function setURLCalcs() {

	if($_GET['calc_cols']) {
     	$cols = explode("/", $_GET['calc_cols']);
     	$calcs = explode("/", $_GET['calc_calcs']);
     	$blanks = explode("/", $_GET['calc_blanks']);
     	$grouping = explode("/", $_GET['calc_grouping']);

	for($i=0;$i<count((array) $cols);$i++) {
		$_POST['reqdcalc_column_' . $cols[$i]] = $cols[$i];
		$_POST['reqdcalc_calcs_' . $cols[$i]] = $calcs[$i];
		unset($ex_calcs);
		unset($ex_blanks);
		unset($ex_grouping);
		$ex_calcs = explode(",", $calcs[$i]);
		$ex_blanks = explode(",", $blanks[$i]);
		$ex_grouping = explode(",", $grouping[$i]);
		for($z=0;$z<count((array) $ex_calcs);$z++) {
			if(substr($ex_blanks[$z],0,6)=="custom") {
				$_POST[$ex_calcs[$z] . $cols[$i]] = "custom";
				$_POST[$ex_calcs[$z] . $cols[$i]."_custom"] = substr(str_replace("!@^%*", ",", $ex_blanks[$z]),6);
			} else {
				$_POST[$ex_calcs[$z] . $cols[$i]] = $ex_blanks[$z];
			}
			$_POST['grouping_' . $ex_calcs[$z] . "_" . $cols[$i]] = $ex_grouping[$z];
		}
	}
	}
//	print_r($_POST);

}


require_once "../../../mainfile.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}


global $xoopsDB, $xoopsUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/popup_functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid="";
	if(!$fid = $_GET['fid']) {
		$fid = intval($_POST['fid']);
	}
	$frid = "";
	if(!$frid = $_GET['frid']) {
		$frid = intval($_POST['frid']);
	}

	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;


	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}

// main body of page goes here...

/* desired calculations:
sum
min
max
average (mean, median, mode)
counts (blank, non-blank)
percentage breakdowns

Need subtotalling/grouping capability, ie: show intermediate totals for the sum of all students in activity logs for each student, or show percentage breakdown of 1-5 ratings of all activities for each volunteer
--premise is that subtotalling/grouping can be done by any value in another column, or by any metadata for entries.

UI:  semi-wizard based.  pick a column (or pick add calculation column), pick calculation options, including grouping results.

Note:  calculation columns (difference between order date and ship date for this record, for instance) will be implemented later, if necessary.

*/

// convert URL passed calcs to $_POST array
setURLCalcs();

$cols = getAllColList($fid, $frid, $groups);

$deleted = handleDelete(); // returns 1 if a deletion was made, 0 if not.

$visible_columns = explode(",", $_GET['cols']);

$usedvals = array();

foreach($cols as $f=>$vs) {
    foreach($vs as $row=>$values) {
        if (in_array($values['ele_handle'], $visible_columns)) {
            $reqdcol = 'reqdcalc_column_' . $values['ele_id'];
            if(!in_array($values['ele_id'], $usedvals)) { // exclude duplicates...the array is not uniqued above because we don't want to merge it an unique it since that throws things out of order.
                $usedvals[] = $values['ele_id'];
                if(!$_POST[$reqdcol] AND (!is_array($_POST['column']) OR !in_array($values['ele_id'], $_POST['column']))) { // Also exclude columns that have been used already.
                    if($values['ele_colhead'] != "") {
                        $options[$values['ele_id']] = printSmart(trans($values['ele_colhead']), 60);
                    } else {
                        $options[$values['ele_id']] = printSmart(trans(strip_tags($values['ele_caption'])), 60);
                    }
                }
                // used for the grouping list box
                if($values['ele_colhead'] != "") {
                    $options2[$values['ele_id']] = "Group by: " . printSmart(trans($values['ele_colhead']));
                } else {
                    $options2[$values['ele_id']] = "Group by: " . printSmart(trans(strip_tags($values['ele_caption'])));
                }
            }
        }
    }
}

ob_start();
calcJavascript();
formulize_popupHeader(_formulize_DE_PICKCALCS, ob_get_clean());

// both cards are in the one form, as the script reads the options of the requested calculations out of it
print "<form id='pickcalc' name='pickcalc' class='fz-stack' method='post' action='" . htmlspecialchars($_SERVER["REQUEST_URI"]) . "'>\n";

$returned = addReqdCalcs();
print $returned['hidden'];

$columnOptions = array();
if(!in_array("creation_uid", (array)$_POST['column']) AND !$_POST['reqdcalc_column_uid']) {
	$columnOptions["creation_uid"] = _formulize_DE_CALC_CREATOR;
}
if(!in_array("mod_uid", (array)$_POST['column']) AND !$_POST['reqdcalc_column_proxyid']) {
	$columnOptions["mod_uid"] = _formulize_DE_CALC_MODIFIER;
}
if(!in_array("creation_datetime", (array)$_POST['column']) AND !$_POST['reqdcalc_column_creation_date']) {
	$columnOptions["creation_datetime"] = _formulize_DE_CALC_CREATEDATE;
}
if(!in_array("mod_datetime", (array)$_POST['column']) AND !$_POST['reqdcalc_column_mod_date']) {
	$columnOptions["mod_datetime"] = _formulize_DE_CALC_MODDATE;
}
if(!in_array("creator_email", (array)$_POST['column']) AND !$_POST['reqdcalc_column_creator_email']) {
	$columnOptions["creator_email"] = _formulize_DE_CALC_CREATOR_EMAIL;
}
if(!in_array("owner_groups", (array)$_POST['column']) AND !$_POST['reqdcalc_column_owner_groups']) {
	// Do nothing, no underlying data in the query so we cannot operate over this data for calculations
	// $columnOptions["owner_groups"] = _formulize_DE_CALC_OWNERGROUPS;
}
$columnOptions = $columnOptions + (array) $options;

$calcs['sum'] = _formulize_DE_CALC_SUM;
$calcs['avg'] = _formulize_DE_CALC_AVG;
$calcs['min'] = _formulize_DE_CALC_MIN;
$calcs['max'] = _formulize_DE_CALC_MAX;
$calcs['count'] = _formulize_DE_CALC_COUNT;
$calcs['per'] = _formulize_DE_CALC_PER;

print "<div class='fz-card'>\n";
print "<h1 class='fz-card__title'>" . _formulize_DE_PICKCALCS . "</h1>\n";
print "<div class='fz-switcher'>\n";
print formulize_popupField(_formulize_DE_CALC_COL, formulize_popupSelect('column[]', $columnOptions, array(), "size='" . min(count((array) $options) + 6, 18) . "' multiple='multiple'"), '', 'column');
print formulize_popupField(_formulize_DE_CALC_CALCS, formulize_popupSelect('calculations[]', $calcs, array(), "size='" . count((array) $calcs) . "' multiple='multiple'"), '', 'calculations');
print "</div>\n";
print "<div class='fz-cluster'><input type='submit' class='fz-btn fz-btn--primary' name='submitx' id='submitx' value='" . _formulize_DE_CALCSUB . "' /></div>\n";
print "</div>\n";

if(count((array) $returned['rc'])>0) {
	print "<div class='fz-card'>\n";
	print "<h2 class='fz-card__title'>" . _formulize_DE_REQDCALCS . "</h2>\n";
	print "<div class='fz-cluster'><input type='button' class='fz-btn fz-btn--primary' name='done' id='done' value='" . _formulize_DE_CALCGO . "' onclick=\"javascript:sendCalcs(this.form);return false;\" /></div>\n";
}

// the lists of things the results can be grouped by
$groupingOptions = array(
	"none" => _formulize_DE_NOGROUPING,
	"creation_uid" => _formulize_DE_GROUPBYCREATOR,
	"mod_uid" => _formulize_DE_GROUPBYMODIFIER,
	"creation_datetime" => _formulize_DE_GROUPBYCREATEDATE,
	"mod_datetime" => _formulize_DE_GROUPBYMODDATE,
	"creator_email" => _formulize_DE_GROUPBYCREATOREMAIL,
	// Do nothing, no underlying data in the query so we cannot operate over this data for calculations
	//"owner_groups" => _formulize_DE_GROUPBYOWNERGROUPS,
) + (array) $options2;

foreach($returned['rc'] as $hidden) {
	switch($hidden['column']) {
		case "creation_uid":
			$colname = _formulize_DE_CALC_CREATOR;
			break;
		case "mod_uid":
			$colname = _formulize_DE_CALC_MODIFIER;
			break;
		case "creation_datetime":
			$colname = _formulize_DE_CALC_CREATEDATE;
			break;
		case "mod_datetime":
			$colname = _formulize_DE_CALC_MODDATE;
			break;
		case "creator_email":
			$colname = _formulize_DE_CALC_CREATOR_EMAIL;
			break;
		case "owner_groups":
			// Do nothing, no underlying data in the query so we cannot operate over this data for calculations
			//$colname = _formulize_DE_CALC_OWNERGROUPS;
			break;
		default:
			$temp_cap = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '" . $hidden['column'] . "'");
			$colname = trans($temp_cap[0]['ele_caption']);
	}

	// each column that has calculations: its name and the button that removes it, then the options of each calculation
	print "<div class='fz-stack fz-border-t fz-pt-4'>\n";
	print "<div class='fz-toolbar'>
	<div class='fz-toolbar__start'><h3 class='fz-text-base fz-font-semibold'>" . $colname . "</h3></div>
	<div class='fz-toolbar__end'><input type='submit' class='fz-btn fz-btn--sm' name='delete_" . htmlspecialchars($hidden['column']) . "' id='delete_" . htmlspecialchars($hidden['column']) . "' value='" . _formulize_DE_REMOVECALC . "' /></div>
	</div>\n";
	$calcs = explode(",", (string)$hidden['calcs']);
	foreach($calcs as $calc) {
		$tempname = $calc . $hidden['column'];
		if(!$_POST[$tempname]) {
			$current_val = "noblanks";
		} else {
			$current_val = $_POST[$tempname];
		}
		if($_POST[$tempname] === "custom") { // if custom is selected;
			$current_val_custom = htmlspecialchars(strip_tags($_POST[$tempname."_custom"]));
		} else {
			switch($_POST[$tempname]) {
				case "all": // include blanks and zeros
					$current_val_custom = "";
					break;
				case "onlyblanks":
					$current_val_custom = "!{BLANK},!0";
					break;
				case "justnoblanks":
					$current_val_custom = "{BLANK}";
					break;
				case "justnozeros";
					$current_val_custom = "0";
					break;
				case "noblanks":
				default:
					$current_val_custom = "{BLANK},0";
					break;
			}
		}

		// convert $calc to actual calculation name
		switch($calc) {
			case "sum":
				$calc_name = _formulize_DE_CALC_SUM;
				break;
			case "avg":
				$calc_name = _formulize_DE_CALC_AVG;
				break;
			case "min":
				$calc_name = _formulize_DE_CALC_MIN;
				break;
			case "max":
				$calc_name = _formulize_DE_CALC_MAX;
				break;
			case "count":
				$calc_name = _formulize_DE_CALC_COUNT;
				break;
			case "per":
				$calc_name = _formulize_DE_CALC_PER;
				break;
		}

		$tempname = htmlspecialchars($tempname);
		$tempcalc1 = formulize_popupSelect($tempname, array(
			"noblanks" => _formulize_DE_CALCNOBLANKS,
			"all" => _formulize_DE_CALCALL,
			"onlyblanks" => _formulize_DE_CALCONLYBLANKS,
			"justnoblanks" => _formulize_DE_CALCJUSTNOBLANKS,
			"justnozeros" => _formulize_DE_CALCJUSTNOZEROS,
			"custom" => _formulize_DE_CALCCUSTOM,
		), $current_val, "onchange='javascript:setCalcCustom(\"".$tempname."\");'");
		$tempcalcCustom = "<input type='text' class='fz-input' name='".$tempname."_custom' id='".$tempname."_custom' size='12' maxlength='255' value='".$current_val_custom."' onclick='javascript:window.document.pickcalc.elements[\"".$tempname."\"].options[5].selected = true;window.document.pickcalc.elements[\"".$tempname."\"].value=\"custom\"' />";

		$groupingDefaults = explode("!@^%*", (string)$_POST['grouping_' . $calc . "_" . $hidden['column']]); // get the individual grouping settings from the one value that has been passed back
		$groupingDefaults1 = $groupingDefaults[0];
		if(isset($_POST['grouping2_' . $calc . "_" . $hidden['column']])) {
			$groupingDefaults2 = $_POST['grouping2_' . $calc . "_" . $hidden['column']];
		} elseif(isset($groupingDefaults[1])) {
			$groupingDefaults2 = $groupingDefaults[1];
		} else {
			$groupingDefaults2 = "";
		}

		// grouping options
		$groupingName = htmlspecialchars('grouping_' . $calc . "_" . $hidden['column']);
		$grouping2Name = htmlspecialchars('grouping2_' . $calc . "_" . $hidden['column']);

		print "<fieldset class='fz-field'>\n";
		print "<legend class='fz-field__label fz-text-xs fz-uppercase fz-tracking-wider fz-text-muted'>" . $calc_name . "</legend>\n";
		print "<div class='fz-auto-grid fz-auto-grid--max-3'>\n";
		print formulize_popupField(_formulize_DE_CALC_BTEXT, $tempcalc1 . $tempcalcCustom, '', $tempname);
		print formulize_popupField(_formulize_DE_CALC_GTEXT, formulize_popupSelect($groupingName, $groupingOptions, $groupingDefaults1), '', $groupingName);
		print formulize_popupField(_formulize_DE_CALC_GTEXT2, formulize_popupSelect($grouping2Name, $groupingOptions, $groupingDefaults2), '', $grouping2Name);
		print "</div>\n";
		print "</fieldset>\n";
	}
	print "</div>\n";
}

if(count((array) $returned['rc'])>0) {
	print "</div>\n";
}

print "</form>\n";

formulize_popupFooter();
