<?php

global $xoopsDB;

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

if(!function_exists('drawRow')) {

function drawRow($instStart, $prevCode, $i, $codes, $titles, $sections, $revSections, $secTitles, $revSecTitles, $weightings, $revWeightings, $times, $revTimes, $rooms, $revRooms) {
    $html = "";
    if(!$instStart) {
        $html .= "<tr nobr=\"true\"><td></td>";
    }
    
    if($codes[$i] != $prevCode) {
        $html .= "<td style=\"border-top: 1px solid black;\" ><b>".$codes[$i]."</b></td>";
        $html .= "<td style=\"border-top: 1px solid black;\" >".$titles[$i];
    } else {
        $html .= "<td></td><td>";
    }
    
    $section = $sections[$i];
    
    if($sectionTitle = compData($secTitles[$i],$revSecTitles[$i])) {
        if($codes[$i] != $prevCode) {
            $html .= "<br>";
        }
        $html .= "<i>$sectionTitle</i>";
    }
    $html .= "</td><td style=\"border-top: 1px solid black;\"><b>".compData($section, $revSections[$i])."</b></td>";
    $html .= "<td style=\"border-top: 1px solid black;\">".compData($weightings[$i], $revWeightings[$i])."</td>";
    
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
    $html .= "<td style=\"border-top: 1px solid black;\">".compData($rooms[$i], $revRooms[$i])."</td>";
    
    $html .= "</tr>";
    return $html;
}

}

if(!$_POST['showYear']) { return "";}

// load the revision data...
global $indexedLockData, $lockSectionsByInst, $compareOn;

if(!is_array($indexedLockData) AND isset($_POST['compareDate']) AND $_POST['compareDate'] !== '') {
    
    $lockDataSource = getData('',22,$_POST['compareDate']);
    $lockData = unserialize(display($lockDataSource[0], 'lock_dates_data'));
    foreach($lockData as $thisLockedEntry) {
        $sectionIds = internalRecordIds($thisLockedEntry, 4);
        $indexedLockData[$sectionIds[0]] = $thisLockedEntry;
        $instructors = display($thisLockedEntry, 'instr_assignments_instructor');
        $instructors = is_array($instructors) ? $instructors : array($instructors);
        foreach($instructors as $instructor) {
            $lockSectionsByInst[$instructor][] = $sectionIds[0];
        }
    }
    $compareOn = true;
} elseif(!is_array($indexedLockData)) {
    $compareOn = false;
}

$inst = display($entry, 'hr_module_name');
$instSectionData = getData(4, 4, 'instr_assignments_instructor/**/'.$inst.'/**/=][ro_module_year/**/'.$_POST['showYear'].'/**/=][ro_module_course_active/**/3/**/!=][ro_module_course_active/**/2/**/!=][course_components_reserved_section/**/Yes/**/!=', 'AND', '', '', '', 'ro_module_course_code');

// also, figure out where they're the coordinator, and show that
$coordData = getData('', 3, 'ro_module_course_coordinator/**/'.$inst.'/**/=][ro_module_year/**/'.$_POST['showYear'].'/**/=][ro_module_course_active/**/3/**/!=][ro_module_course_active/**/2/**/!=', 'AND', '', '', '', 'ro_module_course_code');
$coordCourses = array();
foreach($coordData as $thisCourse) {
    $coordCourses[display($thisCourse, 'ro_module_course_code')] = $thisCourse;
}

$allSectionIds = array();
$html = "";

if(count($instSectionData)>0 OR count($coordCourses)>0) {

    $html .= "<tr nobr=\"true\"><td style=\"border-top: 1px solid black;\">$inst</td>";
    $instStart = true;
    
    foreach($instSectionData as $thisSection) {
        
        $code = display($thisSection, 'ro_module_course_code');
        $title = display($thisSection, 'ro_module_course_title');
    
        // draw coordinator if applicable...
        if(isset($coordCourses[$code])) {
            $html .= drawRow($instStart,$prevCode,0,array($code),array($title),array('Coordinator'),'','','',array(display($coordCourses[$code],'ro_module_coordinatorship_weighting')));
            $instStart = false;
            unset($coordCourses[$code]);
        }
    
        $sectionIds = internalRecordIds($thisSection, 4);
        $sectionId = $sectionIds[0];
        $allSectionIds[] = $sectionId;
        $sections = array();
        $revSections = array();
        $times = array();
        $revTimes = array();
        $rooms = array();
        $revRooms = array();
        $secTitles = array();
        $revSecTitles = array();
        $weightings = array();
        $revWeightings = array();
        $codes = array();
        $titles = array();
        
        // used to be in a loop...but fact is we only process one section at a time, so the arrays would only ever have one member! Refactoring opportunity!!
        $i=0;
        $section = getData(18, 4, $sectionId, 'AND', '', '', '', 'sections_section_number');
        $sections[$i] = display($section[0], 'sections_section_number');
        $revSections[$i] = display($indexedLockData[$sectionId], 'sections_section_number');
        $times[$i] = makeSectionTimes($section[0]);
        $revTimes[$i] = makeSectionTimes($indexedLockData[$sectionId]);
        $rooms[$i] = display($section[0], 'sections_practica_room');
        $revRooms[$i] = display($indexedLockData[$sectionId], 'sections_practica_room');
        $secTitles[$i] = display($section[0], 'course_components_section_title_optional');
        $revSecTitles[$i] = display($indexedLockData[$sectionId], 'course_components_section_title_optional');

        $weighting = display($section[0], 'teaching_weighting');
        $assignedInstructors = display($section[0], 'instr_assignments_instructor');
        $numInstructors = is_array($assignedInstructors) ? count($assignedInstructors) : 1;
        $weightings[$i] = number_format($weighting/$numInstructors,3);
        
        $revWeighting = display($indexedLockData[$sectionId], 'teaching_weighting');
        $revAssignedInstructors = display($indexedLockData[$sectionId], 'instr_assignments_instructor');
        $numInstructors = is_array($revAssignedInstructors) ? count($revAssignedInstructors) : 1;
        $revWeightings[$i] = number_format($weighting/$numInstructors,3);

        // check if this is a new course for this instructor
        $revAssignedInstructors = is_array($revAssignedInstructors) ? $revAssignedInstructors : array($revAssignedInstructors);
        if(!in_array($inst, $revAssignedInstructors)) {
            $revSections[$i] = "";
            $revTimes[$i] = "";
            $revRooms[$i] = "";
            $revSecTitles[$i] = "";
            $revWeightings[$i] = "";
        }
        
        $codes[$i] = $code;
        $titles[$i] = $title;
            
        $html .= drawRow($instStart, $prevCode, 0, $codes, $titles, $sections, $revSections, $secTitles, $revSecTitles, $weightings, $revWeightings, $times, $revTimes, $rooms, $revRooms);
        $instStart = false;
        $prevCode = $codes[$i];
        
    }

}

// if we didn't draw a coordinatorship already for a course the instructor has, draw it now
foreach($coordCourses as $course) {
    $code = display($course,'ro_module_course_code');
    $title = display($course,'ro_module_course_title');
    $html .= drawRow($instStart,$prevCode,0,array($code),array($title),array('Coordinator'),'','','',array(display($course,'ro_module_coordinatorship_weighting')));
    $instStart = false;
    $prevCode = $code;
}

// additionally, loop through any sections assigned in the lock data, which are not in the current data
$sectionIndex = $i;
foreach($lockSectionsByInst[$inst] as $sectionId) {
    if(!in_array($sectionId, $allSectionIds)) {
        
        $revSections[0] = display($indexedLockData[$sectionId], 'sections_section_number');
        $revTimes[0] = makeSectionTimes($indexedLockData[$sectionId]);
        $revRooms[0] = display($indexedLockData[$sectionId], 'sections_practica_room');
        $revSecTitles[0] = display($indexedLockData[$sectionId], 'course_components_section_title_optional');
        
        $revWeighting = display($indexedLockData[$sectionId], 'teaching_weighting');
        $revAssignedInstructors = display($indexedLockData[$sectionId], 'instr_assignments_instructor');
        $numInstructors = is_array($revAssignedInstructors) ? count($revAssignedInstructors) : 1;
        $revWeightings[0] = $weighting/$numInstructors;
        
        $codes[0] = display($indexedLockData[$sectionId],'ro_module_course_code');
        $titles[0] = display($indexedLockData[$sectionId],'ro_module_course_title');
        
        $html .= drawRow($instStart, $prevCode, 0, $codes, $titles, array(''), $revSections, array(''), $revSecTitles, array(''), $revWeightings, array(''), $revTimes, array(''), $revRooms);
        $instStart = false;
        $prevCode = $codes[$i];
    }
}

return $html;
