<?php

$html = "

<P>$date</P>

<P>$name<BR>$address<BR>$city, $province<BR>$pc, $country</P>

<P>$email</P>

<P>Re: $year Academic Year ";
if(strstr($rank, 'Writing')){
    $html .= "Writing Instructor";
} else {
    $html .= "Sessional Lecturer";
}

$html .= " Appointment</P><P>Dear $name:</P>

<P style=\"text-align:justify;\">On behalf of the John H. Daniels Faculty of Architecture, Landscape, and Design, I am pleased to offer you a $positionBlurb for the period beginning $startdate and ending $enddate.";

if(strstr($rank, 'Writing')){
    $html .= " You will be assigned to $numberOfHours hours under the supervision of $writingCentreCoord, Writing Center Coordinator.";
}

if($immigration) {
    $html .= " This offer is conditional on you being legally entitled to work in Canada, in this position.";
}

$html .= "</P>";

if(strstr($rank, 'Writing')) {
    $html .= "<P style=\"text-align:justify;\">You will be paid $salary per hour plus 4% vacation pay for a total of $totalSalary. Your salary will be paid by direct deposit.</P>";
} else {
    $html .= "<P style=\"text-align:justify;\">You will be paid $totalSalary, inclusive of vacation pay. Your salary will be paid by direct deposit.</P>";
}

$html .= "<P style=\"text-align:justify;\">Your payroll documentation will be available online through the University's Employee Self-Service (ESS) at <A HREF='http://ess.hrandequity.utoronto.ca'>ess.hrandequity.utoronto.ca</A>. This includes electronic delivery of your pay statement, tax documentation, and other payroll documentation as made available from time to time. You are able to print copies of these documents directly from ESS.  By signing this Employment Agreement, you authorize the University to provide your T4 slips electronically and not in a paper format. If you wish to discuss an alternative format, please contact Central Payroll Services at <A HREF='mailto:payroll.hr@utoronto.ca'>payroll.hr@utoronto.ca</A>.</P>";

if(strstr($rank, 'Writing')) {
    $html .= "<P style=\"text-align:justify;\">As a Writing Instructor, you will be a member of the Canadian Union of Public Employees (CUPE) Local 3902, Unit 3 Bargaining unit.</P>";
} else {
    $html .= "<P style=\"text-align:justify;\">You will be responsible for teaching the following course(s):<UL>";
foreach($courses as $course) {
	$html .= "<LI>{$course['code']} - {$course['title']}, Section {$course['section']}, {$course['times']}, {$course['room']}";
    if($course['coinst'] AND count($course['coinst'])>0) {
        $html .= " (co-taught with ".implode(", ", $course['coinst']).")";
    }
    $html .= "</LI>";
}
$html .= "</UL>Any additional work required that arises out of this appointment (e.g. deferred exams) and which is required to take place following the normal ending date of this appointment will be compensated in accordance with Article 29: Remuneration for Teaching-Related Service.</P>";
}

$html .= "<P style=\"text-align:justify;\">Your terms and conditions of employment will be governed by the collective agreement between the University of Toronto and the CUPE Local 3902 Unit 3, which is available on the web at: <A HREF='http://agreements.hrandequity.utoronto.ca/#CUPE3902_Unit3'>agreements.hrandequity.utoronto.ca/#CUPE3902_Unit3</A>. Once you accept this offer of employment, a copy of the agreement will be provided to you if you do not already have one.</P>";

if($travelAllowance) {
    $html .= "<P style=\"text-align:justify;\">Upon production of original receipts, you will be eligible for reimbursement for your travel and accommodation expenses, up to a maximum of CAD $travelAllowance.</P>";
}

$html .= "<P style=\"text-align:justify;\"><B>Mandatory Training</B><BR>You are required to take the following mandatory training. You must complete this training within 60 days of hire.<OL><LI>Basic Occupational Health & Safety Awareness Training Program provided by the Office of Environmental Health & Safety available at <A HREF='http://main.its.utoronto.ca/hsa/'>main.its.utoronto.ca/hsa</A>.</LI><LI>U of T AODA Online Training, provided by the Accessibility for Ontarians with Disabilities Act (AODA) Office, available at <A HREF='http://aoda.hrandequity.utoronto.ca/'>aoda.hrandequity.utoronto.ca</A>.</LI></OL>
<P style=\"text-align:justify;\">Please note that you only need to take the above training programs once with the University (although you may need to retake one or both training programs if they are updated or amended). If you have already taken one or both of these training programs please confirm with your supervisor.</P>";

$html .= "<P style=\"text-align:justify;\">You will also be subject to and bound by University policies of general application and their related guidelines. The policies are listed on the Governing Council website at <A HREF='http://www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm'>www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm</A>. For convenience, a partial list of policies, those applicable to all employees, and related guidelines can be found on the Human Resources and Equity website at <A HREF='http://policies.hrandequity.utoronto.ca/'>policies.hrandequity.utoronto.ca</A>. Printed versions will be provided, upon request, through Human Resources.</P>

<P style=\"text-align:justify;\">You should pay particular attention to those policies which confirm the University's commitment to, and your obligation to support, a workplace that is free from discrimination and harassment as set out in the <I>Human Rights Code</I>, is safe as set out in the <I>Occupational Health and Safety Act</I>, and that respects the University's commitment to equity and to workplace civility.</P>

<P style=\"text-align:justify;\">All of the applicable policies may be amended and/or new policies may be introduced from time to time. When this happens, if notice is required you will be given notice as the University deems necessary and the amendments will become binding terms of your employment contract with the University.</P> 
 
<P style=\"text-align:justify;\"><B>Benfits</B><BR>Information regarding the Health Care Spending Account and the enrollment form may be found at <A HREF='http://benefits.hrandequity.utoronto.ca/cupe-local-3902-unit-3-health-care-spending-account/'>benefits.hrandequity.utoronto.ca/cupe-local-3902-unit-3-health-care-spending-account/</A></P>";

if(strstr($rank, 'Sessional')) {
    $html .= "<P style=\"text-align:justify;\">As part of your terms of employment, you are eligible to participate in a Group Registered Retirement Savings Plan (GRRSP). If you join the Plan, you will contribute $percentText percent ($percentNumber%) of eligible income and a matching amount will be contributed by the University. For further information about the Plan, visit <A HREF='http://benefits.hrandequity.utoronto.ca/cupe-local-3902-unit-3-group-registered-retirement-savings-plan/'>http://benefits.hrandequity.utoronto.ca/cupe-local-3902-unit-3-group-registered-retirement-savings-plan/</A>. To enroll, please complete the enclosed form and send it to Central Benefits at 215 Huron Street, 8th floor.</P>";
}

$html .= "<P style=\"text-align:justify;\"><B>Accessibility</B><BR>The University has a number of programs and services available to employees who have need of accommodation due to disability through its Health & Well-being Programs and Services (<A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm</A>). A description of the accommodation process is available in the Accommodation for Employees with Disabilities: U of T Guidelines, which may be found at: <A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/health/s/a.htm'>www.hrandequity.utoronto.ca/about-hr-equity/health/s/a.htm</A></P>

<P style=\"text-align:justify;\">In the event that you have a disability that would impact upon how you would respond to an emergency in the workplace (e.g., situations requiring evacuation), you should contact Health & Well-being Programs & Services at 416-978-2149 as soon as possible so that you can be provided with information regarding an individualized emergency response plan.</P>

<P style=\"text-align:justify;\">The law requires the Employment Standards Act Poster to be provided to all employees; it is available on <A HREF='http://www.labour.gov.on.ca/english/es/pubs/poster.php'>www.labour.gov.on.ca/english/es/pubs/poster.php</A>. This poster describes the minimum rights and obligations contained in the <I>Employment Standards Act</I>. Please note that in many respects this offer of employment exceeds the minimum requirements set out in the Act.</P>";

if($rank == 'Sessional Lecturer I') {
	$html .= "<P style=\"text-align:justify;\">You could be eligible for consideration for advancement to the next rank if with this appointment you will have taught in four (4) of the last six (6) years and at least eight (8) half courses.</P>";
}
if($rank == 'Sessional Lecturer II') {
	$html .= "<P style=\"text-align:justify;\">You could be eligible for consideration for advancement to the next rank if with this appointment you are beginning your fourth year at the rank of Sessional Lecturer II, and have taught an average of four (4) half courses per year in the preceding three (3) years.</P>";
}
if($rank == 'Writing Instructor I') {
    $html .= "<P style=\"text-align:justify;\">You could be eligible for consideration for advancement to the next rank if with this appointment you will have worked four (4) of the last six (6) years and a minimum of six hundred (600) hours.</P>";
}
if($rank == 'Sessional Lecturer I' OR $rank == 'Sessional Lecturer II' OR $rank == 'Writing Instructor I') {
	$html .= "<P style=\"text-align:justify;\">Complete eligibility criteria may be found in the Collective Agreement.  Please contact CUPE 3902 or visit <A HREF='http://www.cupe3902.org'>www.cupe3902.org</A> for more information. The deadline to initiate the advancement process is either September 30 or January 31. I encourage you to apply for advancement when you meet the criteria.</P>";
}


$html .= "

<P style=\"text-align:justify;\">This offer is conditional on your being legally entitled to work in Canada, in this position.</P> 

<P style=\"text-align:justify;\">If you accept this offer, I would appreciate you signing a copy of this letter together with the attached tax forms and a void cheque (unless your banking information remains unchanged) and returning it to $bo, Business Officer (via email <A HREF='mailto:$boemail'>$boemail</A>) no later than $signbackDate. Should you have any questions regarding this offer, please do not hesitate to contact ";
if($writingCenterCoord) {
    $html .= "$writingCenterCoord, Writing Center Coordinator, <A HREF='mailto:$wccEmail'>$wccEmail</A></P>";
} else {
    $html .= "$programDirector, Program Director, $pdProgram <A HREF='mailto:$programDirectorEmail'>$programDirectorEmail</A></P>";
}

$html .= "<P>Yours sincerely,</P><BR><BR><P>$cao<BR>Chief Administrative Officer</P>";

if($writingCenterCoord) {
    $html .= "<P>cc: $writingCenterCoord, Writing Center Coordinator</P>";
} else {
    $html .= "<P>";
    $startDirs = true;
    foreach($programDirs as $dir) {
        if(!$startDirs) {
            $html .= "<BR>";
        }
        $startDirs = false;
        $html .= "cc: ".$dir['programDirector'].", Program Director, ".$dir['pdProgram'];
    }
    $html .= "</P>"; 
}



$html2 = "<P><B><I>I have read this letter, the attachments, and the items referred to in the attachments, and accept employment on the basis of all these provisions.</I></B></P>

<BR>
<BR>
<BR>
	
<P>___________________________<BR>
$name</P>	

<BR>
<BR>
<BR>

<P>___________________________<BR>
Date</P>";

return array($html, $html2);







