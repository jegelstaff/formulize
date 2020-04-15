<?php

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

if(!function_exists("getInstructorName")) {
    function getInstructorName($instructor, $activeYear, $courseCodeAcceptedString) {
        $instructorName = display($instructor, 'instr_assignments_instructor');
        $acceptanceStatus = getData('', 23, "hr_annual_accept_status_instructor/**/$instructorName/**/=][hr_annual_accept_status_year/**/$activeYear/**/=");
        $acceptanceStatus = display($acceptanceStatus[0], "hr_annual_accept_status_status");
        $acceptanceStatus = is_array($acceptanceStatus) ? $acceptanceStatus : array($acceptanceStatus);
        foreach($acceptanceStatus as $thisAcceptanceStatus) {
            if($thisAcceptanceStatus == $courseCodeAcceptedString) {
                return $instructorName;
            }
        }
        return "TBA";
    }
}

$courseActive = display($entry, 'ro_module_course_active');
if(!strstr($courseActive, 'Yes')) {
    return '';
}

$code = display($entry, 'ro_module_course_code');
$title = display($entry, 'ro_module_course_title');
$displayEnrollment = true; //(isset($_POST['search_ro_module_grad_undergrad']) AND $_POST['search_ro_module_grad_undergrad'] == '=Undergraduate' ) ? true : false;

// load the revision data...
global $indexedLockData, $compareOn;

if(!is_array($indexedLockData) AND isset($_POST['compareDate']) AND $_POST['compareDate'] !== '') {
    
    $lockDataSource = getData('',22,$_POST['compareDate']);
    $lockData = unserialize(display($lockDataSource[0], 'lock_dates_data'));
    foreach($lockData as $thisLockedEntry) {
        $sectionIds = internalRecordIds($thisLockedEntry, 4);
        $indexedLockData[$sectionIds[0]] = $thisLockedEntry;
    }
    $compareOn = true;
} elseif(!is_array($indexedLockData)) {
    $compareOn = false;
}

$primaryTeachingMethod = display($entry, "ro_module_lecture_studio");
if(strstr($primaryTeachingMethod,"Prac")) {
    $sortPrepend = "AAA"; // put this on practica if that's the primary teaching method, so they will sort first below
} else {
    $sortPrepend = "";
}

$activeYearParts = explode("_", $_POST['search_ro_module_year']);
$activeYear = $activeYearParts[2]; // qsf filter on the RO page will have _ in it that means the third item is the actual year value
$sectionIds = internalRecordIds($entry, 4);
$sections = array();
$revSections = array();
$times = array();
$revTimes = array();
$rooms = array();
$revRooms = array();
$inst = array();
$revInst = array();
$tentInst = array();
$revTentInst = array();
$titles = array();
$revTitles = array();
$ec = array();
$revEc = array();
$online = array();
$revOnline = array();
foreach($sectionIds as $i=>$sectionId) {
    $section = getData(18, 4, $sectionId, 'AND', '', '', '', 'sections_section_number');
    if(display($section[0], "course_components_reserved_section")=="Yes") {
        continue; // skip sections that are reserved
    }
    $sectionNumber = display($section[0], 'sections_section_number');
    if($sortPrepend AND strstr($sectionNumber, "P")) { // put the AAA on practica so they sort right
        $sections[$i] = $sortPrepend.$sectionNumber;
    } else {
        $sections[$i] = $sectionNumber;
    }
    $revSections[$i] = $compareOn ? display($indexedLockData[$sectionId], 'sections_section_number') : "";
    
    $online[$i] = display($section[0], 'course_components_online');
    $online[$i] = $online[$i] == 'Online' ? 'Online' : '';
    $revOnline[$i] = $compareOn ? display($indexedLockData[$sectionId], 'course_components_online') : "";
    $asynch = (stristr($online[$i], 'online') AND stristr(display($section[0], 'course_components_online_asynch'), 'asynch')) ?  true : false;
    $revAsynch = (stristr($revOnline[$i], 'online') AND stristr(display($indexedLockData[$sectionId], 'course_components_online_asynch'), 'asynch')) ?  true : false;
    $times[$i] = makeSectionTimes($section[0], false, $asynch);
    $revTimes[$i] = $compareOn ? makeSectionTimes($indexedLockData[$sectionId], false, $revAsynch) : "";
    $rooms[$i] = display($section[0], 'sections_practica_room');
    $revRooms[$i] = $compareOn ? display($indexedLockData[$sectionId], 'sections_practica_room') : "";
    $titles[$i] = display($section[0], 'course_components_section_title_optional');
    $revTitles[$i] = $compareOn ? display($indexedLockData[$sectionId], 'course_components_section_title_optional') : "";
    $instructorData = getData('', 15, 'instr_assignments_section_number/**/'.$sectionId.'/**/=');
    foreach($instructorData as $instructor) {
        $inst[$i][] = getInstructorName($instructor, $activeYear, display($section[0], 'ro_module_course_code')."-".$sectionNumber.": Accepted");
        $tentInst[$i][] = display($instructor, 'instr_assignments_instructor');
    }
    if($compareOn) {
        $revInstructors = display($indexedLockData[$sectionId], 'instr_assignments_instructor');
        if(is_array($revInstructors)) {
            foreach($revInstructors as $thisRevInstructor) {
                $revInst[$i][] = getInstructorName($thisRevInstructor, $activeYear, display($section[0], 'ro_module_course_code')."-".$sectionNumber.": Accepted");
                $revTentInst[$i][] = display($thisRevInstructor, 'instr_assignments_instructor');
            }
        } else {
            $revInst[$i][] = getInstructorName($revInstructors, $activeYear, display($section[0], 'ro_module_course_code')."-".$sectionNumber.": Accepted");
            $revTentInst[$i][] = display($revInstructors, 'instr_assignments_instructor');
        }
    }
    if($displayEnrollment) {
        $ec[$i] = display($section[0], 'course_components_enrolment_controls');
        $ec[$i] = strstr($ec[$i], ' - ') ? substr($ec[$i],0,1) : $ec[$i];
        $ec[$i] = $ec[$i] . " " . display($section[0], 'course_components_enrolment_control_desc');
        if($compareOn) {
            $revEc[$i] = display($indexedLockData[$sectionId], 'course_components_enrolment_controls');
            $revEc[$i] = strstr($revEc[$i], ' - ') ? substr($revEc[$i],0,1) : $revEc[$i];
            $revEc[$i] = $revEc[$i] . " " . display($indexedLockData[$sectionId], 'course_components_enrolment_control_desc');
        }
    }

}
if(count($sections)==0) {
    return '';
}
asort($sections);

$html = "<tr nobr=\"true\"><td style=\"border-top: 1px solid black;\" ><b>$code</b></td>";
$html .= "<td style=\"border-top: 1px solid black;\" >$title";
$start = true;
if(isset($_POST['showCoords']) AND $_POST['showCoords'] AND $coordName = display($entry,'ro_module_course_coordinator')) {
    $html .= "</td>
    <td style=\"border-top: 1px solid black;\"><b>Coordinator</b></td>
    <td style=\"border-top: 1px solid black;\"></td>";
    if(isset($_POST['showRooms']) AND $_POST['showRooms']=="Yes") {
        $html .= "<td style=\"border-top: 1px solid black;\"></td>";
    }
    $html .= "<td style=\"border-top: 1px solid black;\">".$coordName."</td>";
    if(isset($_POST['showTentInst']) AND $_POST['showTentInst']=="Yes") {
        $html .= "<td style=\"border-top: 1px solid black;\"></td>";
    }
    if($displayEnrollment) {
        $html .= "<td style=\"border-top: 1px solid black;\"></td>";
    }
    $html .= "</tr>";
    $start = false;
}

foreach($sections as $i=>$section) {
    $section = str_replace($sortPrepend,"",$section); // remove any AAA we prepended
    if(!$start) {
        $html .= "<tr nobr=\"true\"><td></td><td>";
    }
    if($sectionTitle = compData($titles[$i],$revTitles[$i])) {
        if($start) {
            $html .= "<br>";
        }
        $html .= "<i>$sectionTitle</i>";
    }
    $onlineIndicator = compData($online[$i], $revOnline[$i]);
    $onlineIndicator = strstr($onlineIndicator, '<span') ? $onlineIndicator : "<span style=\"color: green;\">$onlineIndicator</span>"; // colour it green unless it already is red due to comparison
    $onlineIndicator = $onlineIndicator ? "<br>$onlineIndicator" : "";
    $html .= "</td><td style=\"border-top: 1px solid black;\"><b>".compData($section, $revSections[$i])."</b>$onlineIndicator</td>";
    $html .= "<td style=\"border-top: 1px solid black;\">";
    $timeStart = true;
    foreach($times[$i] as $x=>$time) {
        if(!$timeStart) {
            $html .= "<br>";
        }
        $html .= compData($time, $revTimes[$i][$x]);
        $timeStart = false;
    }
    $html .= "</td>";
    if(isset($_POST['showRooms']) AND $_POST['showRooms']=="Yes") {
        $html .= "<td style=\"border-top: 1px solid black;\">".compData($rooms[$i], $revRooms[$i])."</td>";
    }
    $html .= "<td style=\"border-top: 1px solid black;\">";
    $instStart = true;
    foreach($inst[$i] as $x=>$instructor) {
        if(!$instStart) {
            $html .= "<br>";
        }
        $instText = compData($instructor, $revInst[$i][$x]);
        $html .= $instText ? $instText : 'TBA';
        $instStart = false;
    }
    if($instStart) { // no instructors so TBA
        $html .= 'TBA';
    }
    $html .= "</td>";
    if(isset($_POST['showTentInst']) AND $_POST['showTentInst']=="Yes") {
        $html .= "<td style=\"border-top: 1px solid black;\">";
        $tentInstStart = true;
        foreach($tentInst[$i] as $x=>$instructor) {
            if(!$tentInstStart) {
                $html .= "<br>";
            }
            $tentInstText = compData($instructor, $revTentInst[$i][$x]);
            $html .= $tentInstText ? $tentInstText : 'TBA';
            $tentInstStart = false;
        }
        $html .= "</td>";
    }
    if($tentInstStart) {
        $html .= 'TBA';
    }
    
    if($displayEnrollment) {
        $html .= "<td style=\"border-top: 1px solid black;\">".compData($ec[$i], $revEc[$i])."</td>";
    }
    
    $html .= "</tr>";
    $start = false;
}




return $html;

