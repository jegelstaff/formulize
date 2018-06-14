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
$email = $email ? $email : display($entry, 'hr_module_utoronto_email');
$email = $email ? $email : display($entry, 'hr_module_alt_email');
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
    if($numberOfInstructors>1) {
        $sectionData['coinst'] = array();
        $splitOverride = 0;
        $sql = 'SELECT h.hr_module_name as name, i.instr_assignments_split_weight_override as split FROM '.$xoopsDB->prefix('formulize_instr_assignments').' AS i LEFT JOIN '.$xoopsDB->prefix('formulize_hr_module').' AS h ON i.instr_assignments_instructor = h.entry_id WHERE i.instr_assignments_section_number = '.$sectionIds[0].' AND i.instr_assignments_instructor IS NOT NULL ';
        if($res = $xoopsDB->query($sql)) {
            while($coinst = $xoopsDB->fetchArray($res)) {
                if($coinst['name'] == $name) {
                    $splitOverride = floatval($coinst['split'])>0 ? floatval($coinst['split']) : 0;
                } else {
                    $sectionData['coinst'][] = $coinst['name'];
                }
            }
        }
        if($splitOverride) {
            $sectionData['weighting'] = number_format((display($section, 'teaching_weighting')*$splitOverride),3);
        } else {
            static $cachedCoTeachingSupplements = array();
            $programName = display($section, 'ro_module_program');
            if(!isset($cachedCoTeachingSupplements[$programName])) {
                $sql = 'SELECT w.activity_weightings_value '.
                'FROM '.$xoopsDB->prefix('formulize_activity_weightings'). ' AS w '.
                'LEFT JOIN '.$xoopsDB->prefix('formulize_master_program_list').' AS p '.
                'ON p.entry_id = w.activity_weightings_program '.
                'WHERE p.master_program_list_program = "'.formulize_db_escape($programName).'" '.
                'AND w.activity_weightings_lecture_or_studio = "Co-teaching supplement"';
                if($res = $xoopsDB->query($sql)) {
                    while($row = $xoopsDB->fetchRow($res)) {
                        $cachedCoTeachingSupplements[$programName] = $row[0];
                    }
                }
            } 
            $coTeachingSupplement = isset($cachedCoTeachingSupplements[$programName]) ? $cachedCoTeachingSupplements[$programName] : 0;
            $sectionData['weighting'] = number_format(((display($section, 'teaching_weighting')/$numberOfInstructors)+$coTeachingSupplement),3);
        }
    } else {
        $sectionData['coinst'] = false;
        $sectionData['weighting'] = number_format((display($section, 'teaching_weighting')/$numberOfInstructors),3);
    }
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
        $totalSalary = "$".number_format(intval(count($courses)*display($rankData[0],'ranks_default_pay')),2,".",",");
        break;
    case 'Sessional Lecturer III':
        $template = 'sessional';
        $percentText = 'six';
        $percentNumber = '6';
        $positionBlurb = 'Sessional Lecturer Appointment position at the rank of '.$rank;
        $totalSalary = "$".number_format(intval(count($courses)*display($rankData[0],'ranks_default_pay')),2,".",",");
        break;
    case 'Writing Instructor I':
    case 'Writing Instructor II':
    case 'Writing Instructor III':
        $numberOfHours = display($entry, 'hr_module_hours_in_current_year');
        $numberOfHours = str_replace(".00", "", $numberOfHours);
        $template = 'sessional';
        $positionBlurb = 'position in the Daniels Writing Center at the rank of '.$rank;
        $hourlyPay = display($rankData[0],'ranks_default_pay'); // gathered above for salary
        $totalSalary = "$".number_format(($numberOfHours*$hourlyPay*1.04),2,".",",");
        $writingCenterCoord = getData('', 16, 'service_module_service_assignment/**/Writing Center Coordinator/**/=][service_module_year/**/'.$year.'/**/=');
        $writingCenterCoord = htmlspecialchars_decode(display($writingCenterCoord[0], 'service_module_faculty_member'), ENT_QUOTES);
        $wccHR = getData('',1,'hr_module_name/**/$writingCenterCoord/**/=');
        $wccEmail = display($wccHR[0], 'hr_module_e_mail');
        if(!$wccEmail) {
            $wccEmail = display($wccHR[0], 'hr_module_alt_email');
        }
        $yearParts = explode("/",$year);
        $startdate = "September 1, ".$yearParts[0];
        $enddate = "April 30, ".$yearParts[1];
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
    $activeTerms = display($entry, 'hr_teaching_loads_active_terms');
    $activeTerms = is_array($activeTerms) ? $activeTerms : array($activeTerms);
    $priorYearAdj = display($entry, 'hr_teaching_loads_prior_year_adjustment');
    $priorYearAdj = is_array($priorYearAdj) ? $priorYearAdj : array($priorYearAdj);
    $key = array_search($year, $loadYears);
    $targetLoad = $targetLoads[$key];
    $availLoad = $availLoads[$key];
    $usedLoad = number_format($targetLoad-$availLoad,3);
    $otherService = $otherServices[$key];
    $otherWeight = $otherWeights[$key];
    $activeTerms = $activeTerms[$key];
    $priorYearAdj = $priorYearAdj[$key];
    
    if(count($courses)==0 AND count($coordCourses)==0 AND count($services)==0 AND !$otherService) {
        return array();
    }
    
} elseif($apptType == 'Core (Tenure Stream)' OR (count($courses)==0 AND !strstr($rank, "Writing"))) {
    return array();
}


$signbackDate = date('F j, Y', strtotime('+7 days'));

foreach($programs as $i=>$thisProgram) {
    $programDirector = getData('', 16, 'service_module_service_assignment/**/Academic Program Director/**/=][service_module_program/**/'.$thisProgram.'/**/=][service_module_year/**/'.$year.'/**/=');
    $programDirector = htmlspecialchars_decode(display($programDirector[0], 'service_module_faculty_member'), ENT_QUOTES);
    $programDirectorEmail = makeEmailFromName($programDirector);
    $pdProgram = $thisProgram;
    $programDirs[$i]['programDirector'] = $programDirector;
    $programDirs[$i]['programDirectorEmail'] = $programDirectorEmail;
    $programDirs[$i]['pdProgram'] = $pdProgram;
}

if($template AND file_exists(XOOPS_ROOT_PATH."/".$template."_template.php")) {
    return include XOOPS_ROOT_PATH."/".$template."_template.php";
} else {
    return array();
}

include_once XOOPS_ROOT_PATH.'/dara_helper_functions.php';