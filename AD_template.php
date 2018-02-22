<?php

// we receive the ID of a session. We need to get the course,
// and then get all the sessions for that course with CUPE Sessional
// and build the ad based on that

global $sessional_mgmt_adsProcessed;

$courseTitle = display($entry, 'sections_practica_course_code');
if(!in_array($courseTitle, $sessional_mgmt_adsProcessed)) {
    
    $courseCode = display($entry, 'ro_module_course_code');
    $courseName = display($entry, 'ro_module_course_title');
    $description = display($entry, 'ro_module_job_ad_description');
    $qualifications = display($entry, 'ro_module_job_ad_qualifications');
    $pref_qualifications = display($entry, 'ro_module_job_ad_preferred_qualifications');
    $duties = display($entry, 'ro_module_job_ad_duties');
    $session = str_replace(array(' - Y', ' - S'), '', display($entry, 'ro_module_semester'));
    
    // gather the sections...
    $sections = getData(4, 4, 'instr_assignments_instructor/**/CUPE Sessional/**/=][sections_practica_course_code/**/'.$courseTitle.'/**/=');
    $sectionDetails = array();
    $i = 0;
    $totalPositions = 0;
    foreach($sections as $section) {
        $sectionDetails[$i]['number'] = display($section, 'sections_section_number');
        $days = display($section, 'section_times_day');
        $days = is_array($days) ? $days : array($days);
        $starts = display($section, 'section_times_start_time');
        $starts = is_array($starts) ? $starts : array($starts);
        $ends = display($section, 'section_times_end_time');
        $ends = is_array($ends) ? $ends : array($ends);
        foreach($days as $x=>$day) {
            
            $startTimeParts = explode(":",$starts[$x]);
            $startTime = mktime($startTimeParts[0],$startTimeParts[1],$startTimeParts[2]);
            $endTimeParts = explode(":",$ends[$x]);
            $endTime = mktime($endTimeParts[0],$endTimeParts[1],$endTimeParts[2]);
            
            $sectionDetails[$i]['daytimes'][$x] = $day . " " . date('g:ia', $startTime) . " - " . date('g:ia', $endTime);
            
        }
        $enrolment = display($section, 'sections_estimated_enrollment');
        $sectionDetails[$i]['enrolment'] = $enrolment;
        $sectionDetails[$i]['TAblurb'] = ($enrolment>25 OR display($entry, 'ro_module_lecture_studio') == 'Practical/Studio') ? "Approximately 60 hours TA Support will be provided" : "";
        $sectionDetails[$i]['room'] = display($section, 'sections_practica_room');
        $positions = display($section, 'instr_assignments_instructor');
        $positions = is_array($positions) ? $positions : array($positions);
        $sectionDetails[$i]['positions'] = count($positions);
        $totalPositions = $totalPositions + count($positions);
        // taship hours... need ta hours for tutorials specifically linked to this section??
        $i++;
    }

    $html = "
    
    <table style=\"border: 1px solid black;\" cellpadding=\"10\">
    <tbody>
    <tr><td style=\"border: 1px solid black;\" width=\"150\"><b>Course Code</b></td><td style=\"border: 1px solid black;\" width=\"350\">$courseCode</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Title</b></td><td style=\"border: 1px solid black;\">$courseName</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Number of positions</b></td><td style=\"border: 1px solid black;\">$totalPositions</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Sections</b></td><td style=\"border: 1px solid black;\">";
    
    foreach($sectionDetails as $i=>$section) {
        if($i>0) {
            $html .= "<br><br>";
        }
        $html .= "<b>".$section['number']."</b>";
        if($section['positions']>1) {
            $html .= " - Number of positions: ". $section['positions'];
        }
        $html .= "<br>&nbsp;&nbsp;&nbsp;";
        foreach($section['daytimes'] as $daytime) {
            $html .= $daytime;
            if($section['room']) {
                $html .= " | " . $section['room'];
            }
            $html .= "<br>&nbsp;&nbsp;&nbsp;";
        }
        $html .= "Estimated enrolment: ".$section['enrolment'];
        if($section['TAblurb']) {
            $html .= "<br>&nbsp;&nbsp;&nbsp;".$section['TAblurb'];   
        }
    }
    
    $html .= "</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Description</b></td><td style=\"border: 1px solid black;\">$description</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Session</b></td><td style=\"border: 1px solid black;\">$session</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Minimum Qualifications</b></td><td style=\"border: 1px solid black;\">$qualifications</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Preferred Qualifications</b></td><td style=\"border: 1px solid black;\">$pref_qualifications</td></tr>
    
    <tr><td style=\"border: 1px solid black;\"><b>Duties</b></td><td style=\"border: 1px solid black;\">$duties</td></tr>
    
    </tbody>
    </table>
    
    ";
    

    $sessional_mgmt_adsProcessed[] = $courseTitle;
    
    return $html;
    
} else {
    return "";
}

