<?php

include_once XOOPS_ROOT_PATH."/file_generation_code.php";
global $dara_buttonClicked;


print "<div id='tabnav' style='float: right;'>\n";

print "<h2><a href='/modules/formulize/index.php?sid=69' target='week-table'>Switch to Acceptances</a></h2>\n";

print "</div>";


print "<h1>".$screen->getVar('title')."</h1><div style='float: left;'>";
print "$addButton</div><div style='float: left; margin-left: 2em; padding: 0.5em; border: 1px solid black;'>$docButton";
if($dara_buttonClicked == "docButton") {
	daraShowContractLinks(1, 14, "HR");
}
print "<br>Generate for year: ".buildFilter('contractYear', 14);
print "<br>Generate: <input type='radio' name='memos' value='' checked> Offers&"."nbsp;&"."nbsp;&"."nbsp;<input type='radio' name='memos' value='memos'> Memos</div>";

print "</div><div style='float: left; margin-left: 2em; padding: 0.5em; border: 1px solid black;'>$scheduleButton";

if($dara_buttonClicked != "docButton") { // want this to fire in case no button is clicked also, so that the query is seeded properly for when the button is clicked!
	daraShowContractLinks(1, 14, "INST");
}

print "<br>Generate for year: ".buildFilter('showYear', 14);
$availableLocks = getData('', 22, 'lock_dates_year/**/'.$_POST['showYear'].'/**/=');
print "<br>Compare with lock date: <select name='compareDate'><option value=''>None</option>";
foreach($availableLocks as $i=>$thisLock) {
	$lockEntryIds = internalRecordIds($thisLock, 22);
	print "<option value=".$lockEntryIds[0].">".display($thisLock, 'lock_dates_date')."</option>";
}
print "</select></div>";
print "<div style='clear: both;'><br></div>";