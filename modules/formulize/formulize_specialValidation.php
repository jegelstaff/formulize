<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                  Copyright (c) 2018 The Formulize Project                 ##
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
##  Author of this file: Julian Egelstaff                                    ##
##  Project: Formulize                                                       ##
###############################################################################

// grab the metadata from the markupId
// grab the value from value
// lookup what the special validation code is for this element
// run that code
// echo result

include_once "../../mainfile.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/extract.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// markupId is de_fid_entryId_elementId
$metaData = explode("_", $_GET['markupId']);
$element_id = intval($metaData[3]);
$entry_id = intval($metaData[2]);
$fid = intval($metaData[1]);
$value = strip_tags(htmlspecialchars($_GET['value']));

// get the special validation code for this element
// invoke element handler
// get element object
// get the code specified

// hard coded to validate rooms for dara for now
if($element_id==88) {
    $specialValidationCode = '
    
    // get the whole set of data for this section, based on relationship 1, which is the one the section records typically use in the standard forms
    // course component form is number 4
    $data = getData(1, 4, $entry_id);
    $entry = $data[0];

    // for each of the day/time combinations, check for other day/times using the same room
    $year = display($entry, "ro_module_year");
    $days = display($entry, "section_times_day");
    $days = is_array($days) ? $days : array($days);
    $starts = display($entry, "section_times_start_time");
    $starts = is_array($starts) ? $starts : array($starts);
    $ends = display($entry, "section_times_end_time");
    $ends = is_array($ends) ? $ends : array($ends);
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
        $room = $value;
        if($room) {
            $targetEnd = date("H:i:s",$endTime);
            $targetStart = date("H:i:s", $startTime);
            $dayCodes = array("Monday"=>"M","Tuesday"=>"T","Wednesday"=>"W","Thursday"=>"R","Friday"=>"F","Saturday"=>"Sat","Sunday"=>"Sun");
			$baseFilter = "sections_practica_room/**/$room/**/=][section_times_start_time/**/$targetEnd/**/<][section_times_end_time/**/$targetStart/**/>][section_times_day/**/".$dayCodes[$day]."/**/=][entry_id/**/$entry_id/**/!=][ro_module_year/**/$year/**/=][ro_module_course_active/**/1/**/="; 
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
                $conflictCode = display($conflict, "sections_practica_course_code");
                $conflictCode = substr($conflictCode,12,strpos($conflictCode," ",12)-12);
                $conflictSections[] = $conflictCode."-".display($conflict,"sections_section_number");
            }
        }
        if(count($conflictSections)>0) {
            $conflictText = "".implode(", ",$conflictSections);
        } else {
            $conflictText = "";
        }
        if($conflictText) {
            if($totalConflictText) {
                $totalConflictText .= ", ".$conflictText;
            } else {
                $totalConflictText = "Conflicts with: ".$conflictText;
            }
        }
    }
    return $totalConflictText;
    ';
} else {
    $specialValidationCode = "";
}

if($specialValidationCode) {
    print eval($specialValidationCode);
}