<?php

global $sessional_mgmt_evenodd, $sessional_mgmt_courseWritten, $adsListRows, $assignListRows;

$roEntryIds = internalRecordIds($entry, 3);
if(!in_array($roEntryIds[0], $sessional_mgmt_courseWritten)) {
	$evenOdd = $sessional_mgmt_evenodd['ads'] == 'even' ? 'odd' : 'even';
	
	ob_start();
	print "<tr><td class='$evenOdd'>$selectionCheckbox</td><td class='$evenOdd'>".display($entry, 'sections_practica_course_code')."</td><td class='$evenOdd'>";
	displayElement('', "ro_module_job_ad_description", $roEntryIds[0]);
	print "</td><td class='$evenOdd'>";
	displayElement('', "ro_module_job_ad_duties", $roEntryIds[0]);
	print "</td><td class='$evenOdd'>";
	displayElement('', "ro_module_job_ad_qualifications", $roEntryIds[0]);
	print "</td></tr>";
	$sortKey = display($entry, 'ro_module_year').
		display($entry, 'ro_module_semester').
		display($entry,'sections_practica_course_code').
		display($entry, 'sections_section_number');
	$adsListRows[$sortKey] = ob_get_clean();
	$sessional_mgmt_evenodd['ads'] = $evenOdd;
	$sessional_mgmt_courseWritten[] = $roEntryIds[0];
}

if(display($entry, 'ro_module_year_status')=='Active') {
	
	$evenOdd = $sessional_mgmt_evenodd['assign'] == 'even' ? 'odd' : 'even';
	ob_start();
	print "<tr><td class='$evenOdd'>".display($entry, 'ro_module_year')." ".display($entry, 'ro_module_semester')." ".display($entry, 'sections_practica_course_code')."</td>";
	print "<td class='$evenOdd'>".display($entry, 'sections_section_number')."</td>";
	print "<td class='$evenOdd'>";
	$times = array();
	$days = display($entry, 'section_times_day');
	$days = is_array($days) ? $days : array($days);
	$starts = display($entry, 'section_times_start_time');
	$starts = is_array($starts) ? $starts : array($starts);
	$ends = display($entry, 'section_times_end_time');
	$ends = is_array($ends) ? $ends : array($ends);
	foreach($days as $x=>$day) {
		
		$startTimeParts = explode(":",$starts[$x]);
		$startTime = mktime($startTimeParts[0],$startTimeParts[1],$startTimeParts[2]);
		$endTimeParts = explode(":",$ends[$x]);
		$endTime = mktime($endTimeParts[0],$endTimeParts[1],$endTimeParts[2]);
		
		$times[] = $day . " " . date('g:ia', $startTime) . " - " . date('g:ia', $endTime);
		
	}
	print implode("<br>", $times);
	print "</td>";
	print "<td class='$evenOdd'>".display($entry, 'sections_practica_room')."</td>";
	print "<td class='$evenOdd'>";
	$_GET['program'] = display($entry, 'ro_module_program');
	$instrIds = internalRecordIds($entry, 15);
	foreach($instrIds as $i=>$instrId) {
		if($i>0) { print "<br>"; }
		displayElement('', "instr_assignments_instructor", $instrId);
	}
	print "</td><td class='$evenOdd'>";
	displayElement('', "course_components_program_director_notes", $entry_id);
	print "</td></tr>";
	$sortKey = display($entry, 'ro_module_year').
			display($entry, 'ro_module_semester').
			display($entry,'sections_practica_course_code').
			display($entry, 'sections_section_number');
	$assignListRows[$sortKey] = ob_get_clean();
	$sessional_mgmt_evenodd['assign'] = $evenOdd;
}