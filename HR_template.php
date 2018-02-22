<?php

if(!isset($_POST['contractYear']) OR $_POST['contractYear']=='none') {
    return array();
}

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

$apptType = display($entry, 'hr_module_type_of_appointment_contract_t');
if($_POST['memos'] AND !strstr($apptType, 'Core')) {
    return array();
}

$date = date('F j, Y');
$name = htmlspecialchars_decode(display($entry, 'hr_module_name'), ENT_QUOTES);
$address = htmlspecialchars_decode(displayBR($entry, 'hr_module_address'), ENT_QUOTES);
$city = htmlspecialchars_decode(display($entry, 'hr_module_city'), ENT_QUOTES);
$province = display($entry, 'hr_module_province_state');
$pc = display($entry, 'hr_module_postal_code');
$country = display($entry, 'hr_module_country');
$email = display($entry, 'hr_module_e_mail');
$year = $_POST['contractYear']; // selected by user when they generate the contracts

$startdates = array();
$enddates = array();
$startdate = "2069-05-09";
$enddate = "1969-05-09";
$semesterDates = getData('', 19, 'semester_dates_module_year/**/'.$year.'/**/=');
foreach($semesterDates as $thisSem) {
    $startdates[display($thisSem, 'semester_dates_module_semester')] = display($thisSem, 'semester_dates_module_start_date');
    $enddates[display($thisSem, 'semester_dates_module_semester')] = display($thisSem, 'semester_dates_module_end_date');
}
$sections = getData(7, 4, 'instr_assignments_instructor/**/'.$name.'/**/=][ro_module_year/**/'.$year.'/**/=');
$courses = array();
$programs = array();
foreach($sections as $section) {
    
    // ignore cancelled sections
    $times = display($section, 'section_times_day');
    $times = is_array($times) ? $times : array($times);
    if(in_array('Cancelled', $times)) {
        continue;
    }
    
    // ignore sections on courses not offered
    if(display($section, 'ro_module_course_active') == 'No') {continue;}
    
    
    $sectionData['title'] = htmlspecialchars_decode(display($section, 'ro_module_course_title'), ENT_QUOTES);
    $sectionData['code'] = display($section, 'ro_module_course_code');
    $sectionData['section'] = display($section, 'sections_section_number');
    $sectionData['times'] = implode(", ", makeSectionTimes($section, 'fullDayNames'));
    $sectionData['room'] = "Room: ".display($section, 'sections_practica_room');
    global $xoopsDB;
    $sectionIds = internalRecordIds($section, 4);
	$sql = 'SELECT COUNT(entry_id) FROM '.$xoopsDB->prefix('formulize_instr_assignments').' WHERE instr_assignments_section_number = '.$sectionIds[0].' AND instr_assignments_instructor IS NOT NULL';
	$res = $xoopsDB->query($sql);
	$row = $xoopsDB->fetchRow($res);
	$numberOfInstructors = $row[0];
    $sectionData['weighting'] = number_format(display($section, 'teaching_weighting')/$numberOfInstructors,3);
    $thisSem = display($section, 'ro_module_semester');
    $sectionData['sem'] = $thisSem;
    if(strtotime($startdates[$thisSem]) < strtotime($startdate)) {
        $startdate = $startdates[$thisSem];
    }
    if(strtotime($enddates[$thisSem]) > strtotime($enddate)) {
        $enddate = $enddates[$thisSem];
    }
    $thisProgram = display($section, 'ro_module_program');
    if(!in_array($thisProgram, $programs)) {
        $programs[] = $thisProgram;
    }
    $courses[] = $sectionData;
}
if(count($programs)>1) {
    $program = implode(", ",$programs)." programs";    
} else {
    $program = $programs[0]." program";
}
$startdate = date('F j, Y', strtotime($startdate));
$enddate = date('F j, Y', strtotime($enddate));

if(display($entry, 'hr_module_appointment_term')=="Annual") {
    $yearParts = explode("/",$year);
    $startdate = "July 1, ".$yearParts[0];
    $enddate = "June 30, ".$yearParts[1];
}


$rank = display($entry, 'hr_module_rank');
$percents = display($entry, 'hr_teaching_loads_appointment_percent'); // varies by year!!
$load_years = display($entry, 'hr_teaching_loads_year'); // varies by year!!
if(!is_array($percents)) { $percents = array($percents); }
if(!is_array($load_years)) { $load_years = array($load_years); }
foreach($percents as $i=>$thisPercent) {
    if($load_years[$i] == $year) {
        $percent = $thisPercent;
        break;
    }
}

if($apptType == "Sessional") {
    $rankData = getData('',2,'ranks_rank/**/'.$rank.'/**/=][ranks_type_of_appointment_contract_type/**/'.$apptType.'/**/=');
    $salary = "$".number_format(floatval(display($rankData[0],'ranks_default_pay')),2,".",",");
} else {
    $salary = "$".number_format(intval(display($entry, 'hr_module_pay')),0,".",",");
}
$travelAllowance = display($entry, 'hr_module_travel_allowance');
$travelAllowance = floatval($travelAllowance) > 0 ? "$".number_format(floatval($travelAllowance),2,".",",") : "";
$immigration = display($entry, 'hr_module_canadian') == 'Yes' ? false : true;

$dean = getData('', 16, 'service_module_service_assignment/**/Associate Dean, Academic/**/=][service_module_year/**/'.$year.'/**/=');
$dean = htmlspecialchars_decode(display($dean[0], 'service_module_faculty_member'), ENT_QUOTES);

$bo = getData('', 16, 'service_module_service_assignment/**/Business Officer/**/=][service_module_year/**/'.$year.'/**/=');
$bo = htmlspecialchars_decode(display($bo[0], 'service_module_staff_names'), ENT_QUOTES);
$boemail = makeEmailFromName($bo);

$cao = getData('', 16, 'service_module_service_assignment/**/CAO/**/=][service_module_year/**/'.$year.'/**/=');
$cao = htmlspecialchars_decode(display($cao[0], 'service_module_staff_names'), ENT_QUOTES);


switch($rank) {
    case 'Sessional Lecturer I':
    case 'Sessional Lecturer II':
        $template = 'sessional';
        $percentText = 'five';
        $percentNumber = '5';
        $positionBlurb = 'Sessional Lecturer Appointment position at the rank of '.$rank;
        break;
    case 'Sessional Lecturer III':
        $template = 'sessional';
        $percentText = 'six';
        $percentNumber = '6';
        $positionBlurb = 'Sessional Lecturer Appointment position at the rank of '.$rank;
        break;
    case 'Writing Instructor I':
    case 'Writing Instructor II':
    case 'Writing Instructor III':
        $template = 'sessional';
        $positionBlurb = 'position in the Daniels Writing Center at the rank of '.$rank;
        $totalSalary = "$".number_format((intval(display($entry, 'hr_module_pay'))*1.04),0,".",",");
        break;
}

switch($apptType) {
    case 'Core (Teaching Stream)':
        $stream = "Teaching Stream";
        $template = 'core';
        break;
    case 'Core (Non-Tenure Stream)':
        $stream = "Non Tenure Stream";
        $template = 'core';
        break;
    case 'Adjunct/Casual Academic':
        $template = 'casual';
        break;
    case 'Emeritus':
        $template = 'emeritus';
        break;
    case 'Visitor':
        $template = 'visitor';
        break;
}

if($_POST['memos']) {
    $template = 'memo';
    
    // get coordinatorships
    $coords = getData('',3,'ro_module_course_coordinator/**/'.$name.'/**/=][ro_module_year/**/'.$year.'/**/=][ro_module_course_active/**/2/**/!=');
    $coordCourses = array();
    foreach($coords as $course) {
        $code = display($course, 'ro_module_course_code');
        $sem = display($course, 'ro_module_semester');
        $coordCourses[$sem][$code]['title'] = display($course, 'ro_module_course_title');
        $coordCourses[$sem][$code]['weighting'] = display($course, 'ro_module_coordinatorship_weighting');
    }
    
    // get service assignments
    $serviceData = getData('', 16, 'service_module_faculty_member/**/'.$name.'/**/=][service_module_year/**/'.$year.'/**/=');
    $services = array();
    foreach($serviceData as $service) {
        $serviceProgram = display($service, 'service_module_program');
        $serviceAssignment = display($service, 'service_module_service_assignment');
        $key = $serviceProgram ? $serviceAssignment . ", " . $serviceProgram : $serviceAssignment;
        $services[$key] = display($service, 'service_module_fce_weight');
    }
    
    // get other assignments and load totals
    $targetLoads = display($entry, 'hr_teaching_loads_target_teaching_load');
    $targetLoads = is_array($targetLoads) ? $targetLoads : array($targetLoads);
    $availLoads = display($entry, 'hr_teaching_loads_available_teaching_loa');
    $availLoads = is_array($availLoads) ? $availLoads : array($availLoads);
    $otherServices = display($entry, 'hr_teaching_loads_other_deductions_desc');
    $otherServices = is_array($otherServices) ? $otherServices : array($otherServices);
    $otherWeights = display($entry, 'hr_teaching_loads_other_deductions_amt');
    $otherWeights = is_array($otherWeights) ? $otherWeights : array($otherWeights);
    $loadYears = display($entry, 'hr_teaching_loads_year');
    $loadYears = is_array($loadYears) ? $loadYears : array($loadYears);
    $key = array_search($year, $loadYears);
    $targetLoad = $targetLoads[$key];
    $availLoad = $availLoads[$key];
    $usedLoad = number_format($targetLoad-$availLoad,3);
    $otherService = $otherServices[$key];
    $otherWeight = $otherWeights[$key];
    
    if(count($courses)==0 AND count($coordCourses)==0 AND count($services)==0 AND !$otherService) {
        return array();
    }
    
} elseif($apptType == 'Core (Tenure Stream)' OR (count($courses)==0 AND !strstr($rank, "Writing"))) {
    return array();
}


$signbackDate = date('F j, Y', strtotime('+7 days')); 
$programDirector = getData('', 16, 'service_module_service_assignment/**/Academic Program Director/**/=][service_module_program/**/'.$programs[0].'/**/=][service_module_year/**/'.$year.'/**/=');
$programDirector = htmlspecialchars_decode(display($programDirector[0], 'service_module_faculty_member'), ENT_QUOTES);
$programDirectorEmail = makeEmailFromName($programDirector);
$pdProgram = $programs[0]; 

if($template AND file_exists(XOOPS_ROOT_PATH."/".$template."_template.php")) {
    return include XOOPS_ROOT_PATH."/".$template."_template.php";
} else {
    return array();
}

include_once XOOPS_ROOT_PATH.'/dara_helper_functions.php';