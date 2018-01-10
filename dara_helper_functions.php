<?php

include_once XOOPS_ROOT_PATH."/modules/formulize/include/elementdisplay.php";

$yearParts = strstr($_POST['search_ro_module_year'], 'qsf_') ? explode('_', $_POST['search_ro_module_year']) : array(2=>$_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
$year = $yearParts[2];
$yearData = getData('',21,'master_year_list_year/**/'.$year.'/**/=');
$yearStatus = display($yearData[0], 'master_year_list_status');
$activeYear = $yearStatus != 'Active' ? false : true;

$GLOBALS['clockIncrement'] = 1800;
$GLOBALS['dara_course'] = array();
$GLOBALS['dara_times'] = array();
$GLOBALS['dara_active_year'] = $activeYear;


function makeEmailFromName($name) {
    $partNum = 0;
    $email = "";
    $nameParts = explode(" ", $name);
    foreach($nameParts as $namePart) {
        $partNum++;
        $namePart = str_replace("'","",$namePart);
        $email .= $partNum == 2 ? "." : "";
        if($partNum == 1) {
            $email = strtolower($namePart);
        } elseif($partNum < count($nameParts)) {
            $email .= strtolower(substr($namePart,0,1));
        } elseif($partNum == count($nameParts)) {
            $email .= strtolower($namePart);
        }
    }
    $email .= "@daniels.utoronto.ca";
    return $email;
}

function compData($current, $old) {
    global $compareOn;
    if(!$compareOn) {
        return "$current";
    } elseif($old == '' AND $current != '') {
        return "<span style=\"color: red;\">NEW: $current</span>";
    } elseif($current == '' AND $old != '') {
        return "<span style=\"color: red;\"><strike>$old</strike></span>";
    } elseif($current == $old) {
        return "$current";
    } else {
        return "<span style=\"color: red;\">NEW: $current<br><strike>$old</strike></span>";
    }
}

function makeSectionTimes($entry, $fullDayNames=false) {
    $times = array();
    $days = display($entry, 'section_times_day');
    $days = is_array($days) ? $days : array($days);
    $starts = display($entry, 'section_times_start_time');
    $starts = is_array($starts) ? $starts : array($starts);
    $ends = display($entry, 'section_times_end_time');
    $ends = is_array($ends) ? $ends : array($ends);
    $dayOrder = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    foreach($days as $x=>$day) {
        if(!$day) { continue; }
        $startTimeParts = explode(":",$starts[$x]);
        $startTime = mktime($startTimeParts[0],$startTimeParts[1],$startTimeParts[2]);
        $endTimeParts = explode(":",$ends[$x]);
        $endTime = mktime($endTimeParts[0],$endTimeParts[1],$endTimeParts[2]);
        $fullDayName = $day;
        if(!$fullDayNames) {
            $day = $day == 'Thursday' ? 'R' : substr($day,0,1);
        }
        if($day == "C") {
            return array("<span style=\"color: red;\">CANCELLED</span>");
        } else {
            $keys = array_keys($dayOrder, $fullDayName);
            if(!isset($times[$keys[0]])) {
                $times[$keys[0]] = $day . " " . str_replace(":00", "", date('g:ia', $startTime)) . " - " . str_replace(":00", "", date('g:ia', $endTime));
            } else {
                $times[$keys[0]] .= ", ". str_replace(":00", "", date('g:ia', $startTime)) . " - " . str_replace(":00", "", date('g:ia', $endTime));
            }
        }
    }
    ksort($times);
    // if Monday to Friday...
    $mondayToFriday = false;
    if(count($times)==5 AND !in_array(5,array_keys($times)) AND !in_array(6,array_keys($times))) {
        $mondayToFriday = true;
        $prevValue = false;
        foreach($times as $dayNumber=>$value) {
            if($fullDayNames) {
                $thisValue = str_replace($dayOrder[$dayNumber]." ","",$value);
            } else {
                $thisValue = substr($value, 2);
            }
            if($prevValue !== false AND $thisValue != $prevValue) {
                $mondayToFriday = false;
                break;
            }
            $prevValue = $thisValue;
        }
        if($mondayToFriday) {
            if($fullDayNames) {
                return array("Monday to Friday, ".$thisValue);    
            } else {
                return array("M-F, ".$thisValue);
            }
        }
    }
    return $times;
}

function daraShowYearFilter($yearFilter) {

if(strstr($_POST['search_ro_module_year'], 'qsf_')) {
    $yearParts = explode('_', $_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
}elseif(substr($_POST['search_ro_module_year'],0,1)=="!") {
    $yearParts = array(2=>substr($_POST['search_ro_module_year'], 1, -1)); // remove ! !
}else{
    $yearParts = array(2=>$_POST['search_ro_module_year']); // normal, like coming back from a form submission or something
}
$selectedYear = $yearParts[2];
$yearData = getData('',21);
$locked = "";
$activeYears = "<script>\nvar activeYears = {";
foreach($yearData as $thisYear) {
    $year = display($thisYear, 'master_year_list_year');
    $status = display($thisYear, 'master_year_list_status');
    if($status != 'Archived') {
        $activeYears .= "'".$year."':1,";
    }
    if($year == $selectedYear AND $status == 'Locked' AND (!isset($_GET['sid']) OR $_GET['sid'] != 38)) {
        $locked = "&nbsp;<span style='color: red;'>&#128274; Locked!</span>";
    }
}
print "<br><h2><b>Viewing Year:</b> $yearFilter$locked</h2><br>\n";
print $activeYears."};\n";
?>	
jQuery("#search_ro_module_year option").each(function() {
	if(typeof activeYears[jQuery(this).text()] == 'undefined') {
		jQuery(this).remove();
	}
});
</script>
<?php
return $locked;
}

function daraChangeYearFilters($year, $direction='forward') {
    global $xoopsDB;
    $savedViewsToUpdate = array(18, 19, 22, 24); // ro, directors, ta, course sessional mgmt
    $yearParts = explode('/',$year);
    $nextYear = ($yearParts[0]+1).'/'.($yearParts[1]+1);
    if($direction == 'forward') {
        $newYear = $nextYear;
    } else {
        $newYear = $year;
        $year = $nextYear;
    }
    $sql = "UPDATE ".$xoopsDB->prefix('formulize_saved_views')." SET sv_quicksearches = REPLACE(sv_quicksearches, '".formulize_db_escape($year)."', '".formulize_db_escape($newYear)."') WHERE sv_id IN (".implode(',',$savedViewsToUpdate).")";
    $res = $xoopsDB->queryF($sql);
}

function daraRemoveTeachingLoads($year, $carryover=true) {
    global $xoopsDB;
    $yearParts = explode('/',$year);
    $nextYear = ($yearParts[0]+1).'/'.($yearParts[1]+1);
    if($carryover) {
        $sql = "UPDATE ".$xoopsDB->prefix('formulize_hr_teaching_loads')." as l ".
            " LEFT JOIN ".$xoopsDB->prefix('formulize_hr_teaching_loads')." as s ".
            " ON s.hr_teaching_loads_instructor = l.hr_teaching_loads_instructor ".
            " AND s.hr_teaching_loads_year = '".formulize_db_escape($year)."' ".
            " AND (s.hr_teaching_loads_available_teaching_loa >= 0.5 OR s.hr_teaching_loads_available_teaching_loa < 0) ".
            " SET l.hr_teaching_loads_prior_year_adjustment = s.hr_teaching_loads_available_teaching_loa ".
            " WHERE l.hr_teaching_loads_year = '".formulize_db_escape($nextYear)."'";
        $res = $xoopsDB->queryF($sql);
        // recalc loads for next year
        $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
    	$loadData = getData('',20,'hr_teaching_loads_year/**/'.$nextYear.'/**/=');
    	unset($GLOBALS['formulize_forceDerivedValueUpdate']);
    }
    $sql = "DELETE FROM ".$xoopsDB->prefix('formulize_hr_teaching_loads')." WHERE hr_teaching_loads_year = '".formulize_db_escape($year)."'";
    $res = $xoopsDB->queryF($sql);
    // remove signover statuses too
    $sql = "DELETE FROM ".$xoopsDB->prefix('formulize_hr_annual_accept_status')." WHERE hr_annual_accept_status_year = '".formulize_db_escape($year)."'";
    $res = $xoopsDB->queryF($sql);
}

// for debug/testing use only!!!
function daraRestoreTeachingLoads() {
    global $xoopsDB;
    $sql = "DROP TABLE ia5ba870e_formulize_hr_teaching_loads";
    $res = $xoopsDB->queryF($sql);
    $sql = "CREATE TABLE ia5ba870e_formulize_hr_teaching_loads LIKE ia5ba870e_formulize_hr_teaching_loads_copy";
    $res = $xoopsDB->queryF($sql);
    $sql = "INSERT ia5ba870e_formulize_hr_teaching_loads SELECT * FROM ia5ba870e_formulize_hr_teaching_loads_copy";
    $res = $xoopsDB->queryF($sql);
}

function daraCreateNewYear($sourceYear) {
    global $xoopsDB, $xoopsUser;
    
    $semDates_handler = new formulizeDataHandler(19);
    $roModule_handler = new formulizeDataHandler(3);
    $cc_handler = new formulizeDataHandler(4);
    $times_handler = new formulizeDataHandler(12);
    $taships_handler = new formulizeDataHandler(13);
    $service_handler = new formulizeDataHandler(16);
    $teachingLoads_handler = new formulizeDataHandler(20);
    
    $sql = "SELECT master_year_list_year FROM ".$xoopsDB->prefix('formulize_master_year_list')." ORDER BY master_year_list_year DESC LIMIT 0,1";
    $res = $xoopsDB->query($sql);
    $row = $xoopsDB->fetchRow($res);
    $topYear = $row[0];
    $topYearParts = explode('/',$topYear);
    $newYear = ($topYearParts[0]+1).'/'.($topYearParts[1]+1);
    // make new year entry
    $newYearEntryId = formulize_writeEntry(array(
        'master_year_list_year'=>$newYear,
        'master_year_list_status'=>1));
    // add new year to the options for various elements that contain years
    $elementsToUpdate = array(14, 176, 192, 200, 234);
    foreach($elementsToUpdate as $element) {
        addNewYearToSelectBox($newYear, $element);
    }
    //make new semester dates
    $sems = array('Fall - F'=>array('start_date'=>($topYearParts[0]+1).'-09-01', 'end_date'=>($topYearParts[0]+1).'-12-31', 'inst'=>4),
          'Fall-Winter - Y'=>array('start_date'=>($topYearParts[0]+1).'-09-01', 'end_date'=>($topYearParts[1]+1).'-04-30', 'inst'=>8),
          'Winter/Spring - S'=>array('start_date'=>($topYearParts[1]+1).'-01-01', 'end_date'=>($topYearParts[1]+1).'-04-30', 'inst'=>4),
          'Summer (May, June) - F'=>array('start_date'=>($topYearParts[1]+1).'-05-01', 'end_date'=>($topYearParts[1]+1).'-06-30', 'inst'=>2),
          'Summer - Y'=>array('start_date'=>($topYearParts[1]+1).'-05-01', 'end_date'=>($topYearParts[1]+1).'-08-31', 'inst'=>4),
          'Summer (July, August) - S'=>array('start_date'=>($topYearParts[1]+1).'-07-01', 'end_date'=>($topYearParts[1]+1).'-08-31', 'inst'=>2));
    foreach($sems as $name=>$sem) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_semester_dates_module')." (creation_datetime, mod_datetime, creation_uid, mod_uid, semester_dates_module_year, semester_dates_module_start_date, semester_dates_module_end_date, semester_dates_module_installments, semester_dates_module_semester) VALUES (NOW(), NOW(), ".intval($xoopsUser->getVar('uid')).", ".intval($xoopsUser->getVar('uid')).", '$newYear', '".$sem['start_date']."', '".$sem['end_date']."', ".$sem['inst'].", '".$name."')";
        $res = $xoopsDB->queryF($sql);
        $semDates_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    //make new courses, 
    $idssql = "SELECT entry_id FROM ".$xoopsDB->prefix('formulize_ro_module')." WHERE ro_module_year = '".formulize_db_escape($sourceYear)."'";
    $res = $xoopsDB->query($idssql);
    $courseMap = array();
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_ro_module')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        ro_module_year,
        ro_module_semester,
        ro_module_course_number,
        ro_module_course_title,
        ro_module_lecture_studio,
        ro_module_program,
        ro_module_course_prefix,
        ro_module_course_code,
        ro_module_course_weight_ui,
        ro_module_intensive_course,
        ro_module_enrolment_control_desc,
        ro_module_grad_undergrad,
        ro_module_course_staffed_by_daniels,
        ro_module_is_course_crosslisted,
        ro_module_outside_faculty_instructor,
        ro_module_crosslisted_courses,
        ro_module_enrolment_controls,
        ro_module_coordinatorship_weighting,
        ro_module_coord_weighting_override,
        ro_module_existing_or_new_course,
        ro_module_course_weight,
        ro_module_job_ad_description,
        ro_module_job_ad_qualifications,
        ro_module_job_ad_duties,
        ro_module_year_status)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        '$newYear',
        ro_module_semester,
        ro_module_course_number,
        ro_module_course_title,
        ro_module_lecture_studio,
        ro_module_program,
        ro_module_course_prefix,
        ro_module_course_code,
        ro_module_course_weight_ui,
        ro_module_intensive_course,
        ro_module_enrolment_control_desc,
        ro_module_grad_undergrad,
        ro_module_course_staffed_by_daniels,
        ro_module_is_course_crosslisted,
        ro_module_outside_faculty_instructor,
        ro_module_crosslisted_courses,
        ro_module_enrolment_controls,
        ro_module_coordinatorship_weighting,
        ro_module_coord_weighting_override,
        ro_module_existing_or_new_course,
        ro_module_course_weight,
        ro_module_job_ad_description,
        ro_module_job_ad_qualifications,
        ro_module_job_ad_duties,
        'Active'
        FROM ".$xoopsDB->prefix('formulize_ro_module')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $courseMap[$row[0]] = $xoopsDB->getInsertId();
        $roModule_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$courseMap[$row[0]]);
    }
    // make sections
    $idssql = "SELECT entry_id, sections_practica_course_code, course_components_related_lecture FROM ".$xoopsDB->prefix('formulize_course_components')." WHERE sections_practica_course_code IN (".implode(",",array_keys($courseMap)).")";
    $res = $xoopsDB->query($idssql);
    $sectionMap = array();
    // need to associate related lecture with the updated record for the related lecture, if any
    $relatedLecture = isset($sectionMap[$row[2]]) ? $sectionMap[$row[2]] : "''";
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_course_components')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        sections_section_number,
        sections_estimated_enrollment,
        component_type,
        sections_practica_course_code,
        sections_practica_room,
        course_components_related_lecture,
        teaching_weighting,
        course_components_teaching_weighting_ove)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        sections_section_number,
        sections_estimated_enrollment,
        component_type,
        ".$courseMap[$row[1]].",
        sections_practica_room,
        ".$relatedLecture.",
        teaching_weighting,
        course_components_teaching_weighting_ove
        FROM ".$xoopsDB->prefix('formulize_course_components')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $sectionMap[$row[0]] = $xoopsDB->getInsertId();
        $cc_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$sectionMap[$row[0]]);
    }
    //times
    $idssql = "SELECT entry_id, section_times_section_number FROM ".$xoopsDB->prefix('formulize_section_times')." WHERE section_times_section_number IN (".implode(",",array_keys($sectionMap)).")";
    $res = $xoopsDB->query($idssql);
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_section_times')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        section_times_section_number,
        section_times_day,
        section_times_start_time,
        section_times_end_time)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        ".$sectionMap[$row[1]].",
        section_times_day,
        section_times_start_time,
        section_times_end_time
        FROM ".$xoopsDB->prefix('formulize_section_times')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $times_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    //taships (minus assignments)
    $idssql = "SELECT entry_id, taships_section_number, taships_course FROM ".$xoopsDB->prefix('formulize_taships')." WHERE taships_section_number IN (".implode(",",array_keys($sectionMap)).") OR taships_course IN (".implode(",",array_keys($courseMap)).")";
    $res = $xoopsDB->query($idssql);
    while($row = $xoopsDB->fetchRow($res)) {
        $sectionNumber = (is_numeric($row[1]) AND $row[1]>0) ? $sectionMap[$row[1]] : 'NULL';
        $courseNumber = (is_numeric($row[2]) AND $row[2]>0) ? $courseMap[$row[2]] : 'NULL';
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_taships')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        taships_section_number,
        taships_course,
        taships_type,
        taships_hours,
        taships_classroom_hours_week,
        taships_program)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        $sectionNumber,
        $courseNumber,
        taships_type,
        taships_hours,
        taships_classroom_hours_week,
        taships_program
        FROM ".$xoopsDB->prefix('formulize_taships')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $taships_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    //make new service assignments
    $idssql = "SELECT entry_id FROM ".$xoopsDB->prefix('formulize_service_module')." WHERE service_module_year = '".formulize_db_escape($sourceYear)."'";
    $res = $xoopsDB->query($idssql);
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_service_module')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        service_module_faculty_member,
        service_module_service_assignment,
        service_module_fce_weight,
        service_module_staff_names,
        service_module_program,
        service_module_previous_assignee,
        service_module_year,
        service_module_previous_year)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        service_module_faculty_member,
        service_module_service_assignment,
        service_module_fce_weight,
        service_module_staff_names,
        service_module_program,
        service_module_previous_assignee,
        '$newYear',
        service_module_previous_year
        FROM ".$xoopsDB->prefix('formulize_service_module')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $service_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    //make new teaching loads
    $idssql = "SELECT entry_id FROM ".$xoopsDB->prefix('formulize_hr_teaching_loads')." WHERE hr_teaching_loads_year = '".formulize_db_escape($sourceYear)."'";
    $res = $xoopsDB->query($idssql);
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_hr_teaching_loads')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        hr_teaching_loads_year,
        hr_teaching_loads_instructor,
        hr_teaching_loads_appointment_percent)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        '$newYear',
        hr_teaching_loads_instructor,
        hr_teaching_loads_appointment_percent
        FROM ".$xoopsDB->prefix('formulize_hr_teaching_loads')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $teachingLoads_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    // make new acceptance statuses
    $idssql = "SELECT entry_id FROM ".$xoopsDB->prefix('formulize_hr_annual_accept_status')." WHERE hr_annual_accept_status_year = '".formulize_db_escape($sourceYear)."'";
    $res = $xoopsDB->query($idssql);
    while($row = $xoopsDB->fetchRow($res)) {
        $sql = "INSERT INTO ".$xoopsDB->prefix('formulize_hr_annual_accept_status')." (
        creation_datetime,
        mod_datetime,
        creation_uid,
        mod_uid,
        hr_annual_accept_status_year,
        hr_annual_accept_status_instructor)
        SELECT creation_datetime,
        NOW(),
        creation_uid,
        ".intval($xoopsUser->getVar('uid')).",
        hr_annual_accept_status_year,
        hr_annual_accept_status_instructor
        FROM ".$xoopsDB->prefix('formulize_hr_annual_accept_status')."
        WHERE entry_id = ".intval($row[0]);
        $insertRes = $xoopsDB->queryF($sql);
        $teachingLoads_handler->setEntryOwnerGroups($xoopsUser->getVar('uid'),$xoopsDB->getInsertId());
    }
    $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
    getData(1, 3, 'ro_module_year/**/'.$newYear.'/**/=');
    getData('', 13, 'taships_full_course_title/**//**/=][taships_full_course_title/**//**/ IS NULL ', 'OR');
    getData(14, 20, 'hr_teaching_loads_year/**/'.$newYear.'/**/=');
    unset($GLOBALS['formulize_forceDerivedValueUpdate']);
    
}

function addNewYearToSelectBox($year, $elementId) {
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get($elementId);
    $ele_value = $elementObject->getVar('ele_value');
    $ele_value[2][$year] = 0;
    $elementObject->setVar('ele_value', $ele_value);
    $element_handler->insert($elementObject);
}


// used in the calendar page
function drawCourseBox($section, $sectionKey, $lecturesWithTutorials) {
    
    $title = $section['title'];
    $code = $section['code'];
	$year = $section['year'];
    $sectionNumber = $section['section'];
    $courseData = $GLOBALS['dara_course'][$year][$code];
    $sectionData = $courseData['sections'][$sectionNumber];
    $entry_id = $sectionData['entry_id'];
    
    print "<div class='sectionblock'>\n";
    $overLibText = $sectionData['isRequired'] == "Yes" ? "<b>REQUIRED SECTION</b><BR><BR>" : "";
	$overLibText .= "<b>{$courseData['desc']}</b><br>{$courseData['semester']} {$courseData['year']}<br>".date("g:ia",$section['start'])." - ".date("g:ia",$section['end'])."<br>Room: {$sectionData['room']}<br>Estimated enrolment: {$sectionData['enrollment']}";
	if($sectionData['notes']) {
		$overLibText .= "<br><br>".str_replace("\r\n","<br>",$sectionData['notes']);
	}
	$linkStart = ($sectionData['type'] != "Tutorial" AND userCanAssignToProgram($courseData['program'])) ? "<a href='' target='".str_replace("/","",$sectionKey)."' class='details-link' onclick='return false;'>" : "";
    $linkEnd = $linkStart ? "</a>" : "";
    $courseCode = $sectionData['isRequired'] == "Yes" ? "<span class='required-section'>".$courseData['code']."</span>" : $courseData['code'];
    print "<h2 onmouseover='return overlib(\"$overLibText\");' onmouseout='nd();'>$linkStart$courseCode$linkEnd</a></h2>\n";
    
    $type = "<div class='title-for-print' style='display: none;'><b>".$courseData['desc']."</b></div><b>".$sectionData['type'] . " ". $sectionData['number'];
    if($sectionData['type']=='Tutorial') {
        $type .= " for ".$sectionData['related'];
    }
    $type .= "</b>".$section['conflicts'];
    // Because of how tutorials are updated separate from the main data flow, we need to pull instructors from the lecture/studio itself for them to be accurate after an update
    if($sectionData['type'] == "Tutorial") {
        $instructors =  $courseData['sections'][$sectionData['related']]['instructors'];
    } else {
        $instructors = $sectionData['instructors'];
    }
    $instructorList = "";
    foreach($instructors as $instructor) {
        if(!$instructorList AND $instructor) {
            $instructorList .= "<br>$instructor";
        } elseif($instructor) {
            $instructorList .= ", $instructor";
        }
    }
    print "<p>$type$instructorList</p>\n";
    if($sectionData['type'] == 'Tutorial') {
        print "<input type='hidden' name='tutorial-lecture-pairs[".$courseData['sections'][$sectionData['related_section']]['entry_id']."]' value='$entry_id' />\n";
        $lecturesWithTutorials[] = $courseData['sections'][$sectionData['related_section']]['entry_id'];
    }
    print "</div>\n";
    return $lecturesWithTutorials;
}

// prepare all the UI for the details box, for calendar pages
function prepDetails($section, $sectionKey, $details, $instructorKeys) {

	global $dara_active_year;
	
    if(isset($details[$sectionKey]) OR !$dara_active_year) {
        return array($details, $instructorKeys);
    }

    $title = $section['title'];
    $code = $section['code'];
	$year = $section['year'];
    $sectionNumber = $section['section'];
    $courseData = $GLOBALS['dara_course'][$year][$code];
    $sectionData = $courseData['sections'][$sectionNumber];
    $semester = $courseData['semester'];
    $entry_id = $sectionData['entry_id'];

    $numberOfSections = countNumberOfSections($courseData);
    
	$details[$sectionKey]['contents'] = "<p>".$courseData['semester']." ".$courseData['year']."</p><br>\n";
    if($sectionData['type'] != "Tutorial" AND $numberOfSections>1 AND userCanAssignToProgram($courseData['program'])) {
        $details[$sectionKey]['contents'] .= "<p>Coodinator weighting: ".number_format($courseData['weighting'],3)."</p>\n";
        ob_start();
        displayElement('','ro_module_course_coordinator',$courseData['course_id']);
        $details[$sectionKey]['contents'] .= "<p>Coordinator: " . ob_get_clean()."</p><br>\n";
    }
    if($sectionData['type'] != 'Tutorial' AND userCanAssignToProgram($courseData['program'])) {
        $details[$sectionKey]['title'] = $courseData['code']." - ".$sectionData['number'];
        $sectionData['weighting'] += isset($sectionData['has_tutorial']) ? $sectionData['has_tutorial'] : 0; // add any tutorial weighting
        $tutorialText = $sectionData['has_tutorial'] ? "<br>&"."nbsp;(includes tutorial)" : "";
        $details[$sectionKey]['contents'] .= "<p>Section weighting: ".number_format($sectionData['weighting'],3).$tutorialText."</p>\n";
        $details[$sectionKey]['contents'] .= "<p>Instructor(s):<br><div id='instructor-list-".str_replace("/","",$sectionKey)."'>\n";
        foreach($sectionData['instr_assign_ids'] as $inst_assign_id) {
            if($inst_assign_id) {
                ob_start();
                $instructorKeys[$entry_id][] = drawInstructorBox('instr_assignments_instructor',$inst_assign_id,$entry_id,$semester);
                $details[$sectionKey]['contents'] .= ob_get_clean();
            }
        }
        // draw one empty box for a new instructor assignment
        ob_start();
        $instructorKeys[$entry_id][] = drawInstructorBox('instr_assignments_instructor',"new",$entry_id,$semester);
        $details[$sectionKey]['contents'] .= ob_get_clean();
        
		ob_start();
        print "</div><br><p>Notes:<br>";
        displayElement('', 'course_components_program_director_notes', $entry_id);
        print "</p>";
        $details[$sectionKey]['contents'] .= ob_get_clean();
    }
    return array($details, $instructorKeys);
}

function countNumberOfSections($courseData) {
    $checkNumber = "";
    $numberOfSections = 0;
    foreach($courseData['sections'] as $oneNumber=>$oneSection) {
        if($oneNumber != $checkNumber AND $oneSection['type'] != 'Tutorial') {
            $numberOfSections++;
        }
        $checkNumber = $oneNumber;
    }
    return $numberOfSections;
}

function drawInstructorBox($element, $id, $parentId,$semester) {
    static $masterInstructorCount = 0;
    static $newInstructors = array();
    print "&"."nbsp;";
    
    $_GET['ro_module_semester'] = $semester; // so the active term filter in the instructor assignment box will pick up the semester of the course when we determine which instructors to show in the box
    
    if($id>0) { // draw the existing instructor assignment box
        displayElement('',$element,$id);
        $instructorKey = $id;
    } else { // draw a box for a new assignment, plus a corresponding hidden element to tie this assignment back to the right section
        if(!isset($newInstructors[$parentId])) {
            $masterInstructorCount++;
            $newInstructors[$parentId] = $masterInstructorCount;
        } 
        displayElement('',$element,"new".$newInstructors[$parentId]);
        print "<input type='hidden' id='decue_15_new".$newInstructors[$parentId]."_133' name='decue_15_new".$newInstructors[$parentId]."_133' value=1 />\n";
        print "<input type='hidden' id='de_15_new".$newInstructors[$parentId]."_133' name='de_15_new".$newInstructors[$parentId]."_133' value=".$parentId." />\n";
        $instructorKey = 'new'.$newInstructors[$parentId];
    }
    print "<br>\n";
    return $instructorKey;
}

// draw the calendar grid for the specified days
function drawCalendar($days, $lecturesWithTutorials=array(), $details=array(), $instructorKeys=array()) {
    
    if(count($days)>1) {
        $tableId = 'id="week-table"';
        $headerClass = 'tableheader';
        $masterDayBorder = ' dayborder';
    } else {
        $tableId = 'id="'.$days[0].'-table" class="day-table"';
        $headerClass = 'dayheader';
        $masterDayBorder = '';
    }
    
    $drawnDays = array();
    $clock = mktime(9,0,0);
    $stopClock = $clock+43200;
    global $clockIncrement;
    
    print "<table $tableId>";
    print "<tr>";
    foreach($days as $day) {
        if(($day == 'Saturday' OR $day == 'Sunday') AND !isset($GLOBALS['dara_times'][$day])) { continue; }
        if(count($days)>1) {
            print "<td class='$headerClass' colspan=121><a href='' class='calnavlink' target='".$day."-table'>$day</a></td>";
        } else {
            $dayList = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
            $curDayKey = array_search($day,$dayList);
            $prevLink = "";
            $nextLink = "";
            if($curDayKey>0 AND isset($GLOBALS['dara_times'][$dayList[$curDayKey-1]])) {
                $prevLink = "<a href='' class='calnavlink' target='".$dayList[$curDayKey-1]."-table'><<</a>&"."nbsp;&"."nbsp;&"."nbsp;";
            }
            if($curDayKey<6 AND isset($GLOBALS['dara_times'][$dayList[$curDayKey+1]])) {
                $nextLink = "&"."nbsp;&"."nbsp;&"."nbsp;<a href='' class='calnavlink' target='".$dayList[$curDayKey+1]."-table'>>></a>";
            }
            print "<td class='$headerClass' colspan=121>$prevLink$day$nextLink<br><a href='' class='calnavlink' target='week-table'>Show All Days</a></td>";
        }
    }
    print "</tr>";
    
    $showTime = true; // every second slot atm is half hour, and we want to skip those...keeping track of a toggle is the cheapest fastest way to do it
    $masterCounter = 1;
    
    for($i=$clock;$i<=$stopClock;$i=$i+$clockIncrement) {
        $time24 = date("H:i:s",$i);
        $niceTime = $showTime ? "&nbsp;".date("g",$i)."&nbsp;" : '';
        $showTime = $showTime == true ? false : true;
        print "<tr class='timeslot-row'>\n";
        foreach($days as $day) {
            if(($day == 'Saturday' OR $day == 'Sunday') AND !isset($GLOBALS['dara_times'][$day])) { continue; }
            //$dayBorder = $day != 'Monday' ? $masterDayBorder : '';
            $dayBorder = $masterDayBorder;
            print "<td class='timestamp-cell$dayBorder'>$niceTime</td>\n";
            $drawnDays[$day] = true;
            $courseDrawn = false;
            $maxRemainder = 0;
            $times = $GLOBALS['dara_times'][$day];
        
            
            // draw 120 cells per day
            for($col=1;$col<=120;$col++) {
                
                if(isset($jumpToCol[$day][$col])) {
                    list($sectionKey, $newCol) = $jumpToCol[$day][$col];
                    // decrement the rowspan, until zero then unset, because that will have been the last time we need to do that for this course
                    $spans[$day][$sectionKey]['rowspan'] = $spans[$day][$sectionKey]['rowspan'] - 1;
                    if($spans[$day][$sectionKey]['rowspan'] > 0) {
                        $col = $newCol;
                        continue;
                    } else {
                        for($x=$spans[$day][$sectionKey]['startCol'];$x<$newCol;$x++) {
                            unset($jumpToCol[$day][$x]);
                        }
                        unset($spans[$day][$sectionKey]);
                    } 
                }
                
                // figure out how many columns we have available
                $x = $col;
                $nextSpannedCol = 0;
                while($x <= 120) {
                    if(isset($jumpToCol[$day][$x])) {
                        list($sectionKey, $newCol) = $jumpToCol[$day][$x];
                        if($spans[$day][$sectionKey]['rowspan'] == 1) { // remove any spans that we're done with
                            for($y=$spans[$day][$sectionKey]['startCol'];$y<$newCol;$y++) {
                                unset($jumpToCol[$day][$y]);
                            }
                            unset($spans[$day][$sectionKey]);
                        } elseif(!$nextSpannedCol) {
                            $nextSpannedCol = $x-1;
                        }
                    }
                    $x++;
                }
                
                if($nextSpannedCol) {
                    $availCols = $nextSpannedCol - $col + 1;
                    $targetColForSpacer = $nextSpannedCol;
                } else {
                    $availCols = 121-$col;
                    $targetColForSpacer = 120;
                }
                
                $coursesCheckedForThisCell = 0;
                foreach($times[$time24] as $sectionKey=>$section) {
                    $coursesCheckedForThisCell++;
                    
                    if(!isset($spans[$day][$sectionKey])) {
                    
                        // figure out colspans for sections based on max number of sections during any timeslot that the course occupies
                        $courses = 1;
                        $takenCols = 0;
                        foreach($section['timeslots'] as $timeslot) {
                            if(isset($times[$timeslot])) {
                                $slotCourses = count($times[$timeslot]);
                                $courses = $slotCourses > $courses ? $slotCourses : $courses;
                            }
                        }
                        $colspan = intval(120/$courses);
                        //$remainder = 120-($colspan*$courses);
                        //$maxRemainder = $remainder > $maxRemainder ? $remainder : $maxRemainder;
                        $spans[$day][$sectionKey] = array('rowspan'=>$section['rowspan'],'colspan'=>$colspan, 'startCol'=>$col);
                        $newCol = $col+$colspan-1;
                        for($x=$col;$x<$newCol;$x++) {
                            $jumpToCol[$day][$x] = array($sectionKey, $newCol);
                        }
                        $col = $newCol;
                        $origAC = $availCols;
                        $availCols = $availCols - $colspan;
                        print "<td class='daytimeblock-cell$dayBorder' rowspan={$section['rowspan']} colspan=$colspan>\n";
                        
                        $masterCounter++;

						$lecturesWithTutorials = drawCourseBox($section, $sectionKey, $lecturesWithTutorials);
                        list($details, $instructorKeys) = prepDetails($section, $sectionKey, $details, $instructorKeys);
                        
                        print "</td>\n";
                        break; // only draw one course at a time...need to parse out the next column position to see what space we have
                    }
                    
                }
                
                if($coursesCheckedForThisCell == count($times[$time24]) AND $availCols) {
                    // draw a placeholder if there's nothing else to draw in this space
                    print "<td class='timeslotdivider-cell$dayBorder' colspan=$availCols>";
                    print "&"."nbsp;</td>\n"; // jul 4
                    
                    $masterCounter++;
                    $col = $col+$availCols;
                }
                $dayBorder = '';
            }
            
        }
        print "</tr>";
    }
    print "</table>";
    return array($lecturesWithTutorials, $details, $instructorKeys, $drawnDays);
    
}


function getPriorInstructors($year, $code, $sectionNumber) {
	$yearParts = explode("/",$year);
	$priorYear = ($yearParts[0]-1)."/".($yearParts[1]-1);
	$prior_instructors = getData(15, 4, 'ro_module_year/**/'.$priorYear.'/**/=][ro_module_course_code/**/'.$code.'/**/=][sections_section_number/**/'.$sectionNumber.'/**/=');
	$prior_instructors = display($prior_instructors[0], 'instr_assignments_instructor');
	$prior_instructors = is_array($prior_instructors) ? $prior_instructors : array($prior_instructors);
	if($text = implode("</li><li>",$prior_instructors)) {
		return "<li>$text</li>";
	} else {
		return "";
	}
}


function getNamesPlusAvailLoads($instructors, $year) {
    if(is_array($instructors)) {
        $returnArray = true;
    } else {
        $returnArray = false;
        $instructors = array($instructors);
    }
	$namesPlusLoads = array();
	static $foundLoads = array();
	foreach($instructors as $i=>$instructor) {
        if(!isset($foundLoads[$instructor][$year])) {		
            $loads = getData('', 20, 'hr_teaching_loads_instructor/**/'.$instructor.'/**/=][hr_teaching_loads_year/**/'.$year.'/**/=');
            if(count($loads)>0) {
                $load = floatval(display($loads[0], 'hr_teaching_loads_available_teaching_loa'));
                $load = " (".number_format($load, 3).")";
            } else {
                $load = "";
            }
            $foundLoads[$instructor][$year] = $instructor . $load;
        }
        $namesPlusLoads[$i] = $foundLoads[$instructor][$year];
	}
	return $returnArray ? $namesPlusLoads : $namesPlusLoads[0];
}

function readSection($entry, $entry_id) {
    $title = display($entry, 'ro_module_full_course_title');
    $code = display($entry, 'ro_module_course_code');
    $sectionNumber = display($entry, 'sections_section_number');
    $days = display($entry, 'section_times_day');
    $starts = display($entry, 'section_times_start_time');
    $ends = display($entry, 'section_times_end_time');
    $instructors = display($entry, 'instr_assignments_instructor');
    $days = is_array($days) ? $days : array($days);
    $starts = is_array($starts) ? $starts : array($starts);
    $ends = is_array($ends) ? $ends : array($ends);
    $instructors = is_array($instructors) ? $instructors : array($instructors);
	$instructorIds = internalRecordIds($entry, 15); // 15 is instructor assignments form
	$year = display($entry, 'ro_module_year');
    $semester = display($entry, 'ro_module_semester');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['entry_id'] = $entry_id;
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['type'] = display($entry, 'component_type');
	$GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['year'] = $year;
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['number'] = $sectionNumber;
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['weighting'] = display($entry, 'teaching_weighting');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['enrollment'] = display($entry, 'sections_estimated_enrollment');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['room'] = display($entry, 'sections_practica_room');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['related'] = display($entry, 'course_components_related_lecture');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['days'] = $days;
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['starts'] = $starts;
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['ends'] = $ends;
	$GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['prior_instructors'] = getPriorInstructors($year, $code, $sectionNumber);
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['instructors'] = getNamesPlusAvailLoads($instructors, $year);
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['instr_assign_ids'] = $instructorIds; 
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['related_section'] = display($entry, 'course_components_related_lecture');
	$GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['notes'] = display($entry, 'course_components_program_director_notes');
    $GLOBALS['dara_course'][$year][$code]['sections'][$sectionNumber]['isRequired'] = display($entry, 'course_components_section_required');
    if(!isset($GLOBALS['dara_course'][$year][$code]['title'])) {
        $GLOBALS['dara_course'][$year][$code]['title'] = $title;
        $GLOBALS['dara_course'][$year][$code]['desc'] = display($entry, 'ro_module_course_title');
        $GLOBALS['dara_course'][$year][$code]['code'] = $code;
        $GLOBALS['dara_course'][$year][$code]['coordinator'] = getNamesPlusAvailLoads(display($entry, 'ro_module_course_coordinator'), $year);
        $GLOBALS['dara_course'][$year][$code]['type'] = display($entry, 'ro_module_existing_or_new_course');
		$GLOBALS['dara_course'][$year][$code]['year'] = $year;
        $GLOBALS['dara_course'][$year][$code]['semester'] = $semester;
        $GLOBALS['dara_course'][$year][$code]['program'] = display($entry, 'ro_module_program');
        $GLOBALS['dara_course'][$year][$code]['weighting'] = display($entry, 'ro_module_coordinatorship_weighting');
        $courseIds = internalRecordIds($entry, 3); // 3 is RO form
        $courseId = $courseIds[0];
        $GLOBALS['dara_course'][$year][$code]['course_id'] = $courseId;
    }
    global $clockIncrement;
    global $xoopsDB;
    $totalConflictText = "";
    foreach($days as $i=>$day) {
        $start = $starts[$i];
        $end = $ends[$i];
        $startTimeParts = explode(":",$start);
        $startTime = mktime($startTimeParts[0],$startTimeParts[1],$startTimeParts[2]);
        $endTimeParts = explode(":",$end);
        $endTime = mktime($endTimeParts[0],$endTimeParts[1],$endTimeParts[2]);
        
        // check for conflicts for every instructor for this time
        $conflictSections = array();
        foreach($instructors as $instructor) {
            if(!$instructor OR $instructor == "CUPE Sessional" OR $instructor == "Other") { continue; }
            $targetEnd = date("H:i:s",$endTime + 3600);
            $targetStart = date("H:i:s", $startTime - 3600);
            $dayCodes = array("Monday"=>"M","Tuesday"=>"T","Wednesday"=>"W","Thursday"=>"R","Friday"=>"F","Saturday"=>"Sat","Sunday"=>"Sun");
			$baseFilter = "instr_assignments_instructor/**/$instructor/**/=][section_times_start_time/**/$targetEnd/**/<][section_times_end_time/**/$targetStart/**/>][section_times_day/**/".$dayCodes[$day]."/**/=][entry_id/**/$entry_id/**/!=][ro_module_year/**/$year/**/=][ro_module_course_active/**/1/**/="; // sections_section_number/**/$sectionNumber/**/!=][
            if($semester == "Fall-Winter - Y") {
                $semFilter = "ro_module_semester/**/Fall - F/**/=][ro_module_semester/**/Winter/Spring - S/**/=][ro_module_semester/**/Fall-Winter - Y/**/=";
            } elseif($semester == "Fall - F") {
                $semFilter = "ro_module_semester/**/Fall - F/**/=][ro_module_semester/**/Fall-Winter - Y/**/=";
            } elseif($semester == "Winter/Spring - S") {
                $semFilter = "ro_module_semester/**/Winter/Spring - S/**/=][ro_module_semester/**/Fall-Winter - Y/**/=";
            } elseif($semester == "Summer - Y") {
                $semFilter = "ro_module_semester/**/Summer (May, June) - F/**/=][ro_module_semester/**/Summer (July, August) - S/**/=][ro_module_semester/**/Summer - Y/**/=";
            } elseif($semester == "Summer (May, June) - F") {
                $semFilter = "ro_module_semester/**/Summer (May, June) - F/**/=][ro_module_semester/**/Summer - Y/**/=";
            } elseif($semester == "Summer (July, August) - S") {
                $semFilter = "ro_module_semester/**/Summer (July, August) - S/**/=][ro_module_semester/**/Summer - Y/**/=";
            }
            $filter[0][0] = "AND";
            $filter[0][1] = $baseFilter;
            $filter[1][0] = "OR";
            $filter[1][1] = $semFilter;
            $conflicts = getData(7,4, $filter);
            foreach($conflicts as $conflict) {
                $conflictCode = display($conflict, 'sections_practica_course_code');
                $conflictCode = substr($conflictCode,12,strpos($conflictCode," ",12)-12);
                $conflictSections[] = $conflictCode.", ".display($conflict,"sections_section_number");
            }
        }
        if(count($conflictSections)>0) {
            $conflictText = "<br><span class='conflict'>Conflicts with ".implode("</span><br><span class='conflict'>Conflicts with ",$conflictSections)."</span>";
        } else {
            $conflictText = "";
        }
        $totalConflictText .= $conflictText;
        
        $rowspan = ($endTime - $startTime) / $clockIncrement;
        $courseTimeSlots = array();
        for($time=$startTime;$time<$endTime;$time=$time+$clockIncrement) {
            $courseTimeSlots[] = date("H:i:s",$time);
        }
        for($time=$startTime;$time<$endTime;$time=$time+$clockIncrement) {
            $GLOBALS['dara_times'][$day][date("H:i:s",$time)][$year.$code.$sectionNumber] = array('title'=>$title, 'code'=>$code, 'section'=>$sectionNumber, 'rowspan'=>$rowspan, 'timeslots'=>$courseTimeSlots, 'start'=>$startTime, 'end'=>$endTime, 'conflicts'=>$totalConflictText, 'year'=>$year);
        }
    }
    $semesterOrder = array("Fall - F"=>1, "Fall-Winter - Y"=>2,"Winter/Spring - S"=>3,"Summer (May, June) - F"=>4,"Summer - Y"=>5,"Summer (July, August) - S"=>6);
    $sortKey = $year." ".$semesterOrder[$semester]." ".$code." ".$sectionNumber;
    $GLOBALS['dara_sort'][$sortKey] = array($title, $sectionNumber, $totalConflictText, $code, $year);
}

// checks that the program matches what the user is supposed to be able to update
// in the future we could check the active user's group memberships
function userCanAssignToProgram($program) {
    global $xoopsUser, $dara_active_year;
	if(!$dara_active_year) { return false; }
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
    $member_handler = xoops_gethandler('member');
    foreach($groups as $group) {
        $groupObject = $member_handler->getGroup($group);
        if(strstr($groupObject->getVar('name'),$program)) {
            return true;
        }
    }
    return false;
}