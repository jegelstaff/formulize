<?php

$courseData = getData('', 3, 'ro_module_full_course_title/**/'.display($entry, 'taships_full_course_title').'/**/=');
$datesData = getData('', 19, 'semester_dates_module_semester/**/'.display($courseData[0],'ro_module_semester').'/**/=][semester_dates_module_year/**/'.display($courseData[0], 'ro_module_year').'/**/=');
$rankData = getData('', 2, 'ranks_type_of_appointment_contract_type/**/TA/**/=][ranks_rank/**/'.display($entry, 'teaching_assistants_rank').'/**/=');
$date = date('F j, Y');
$name = htmlspecialchars_decode(display($entry, 'taships_name'), ENT_QUOTES);
$address = htmlspecialchars_decode(displayBR($entry, 'teaching_assistants_address'), ENT_QUOTES);
$city = htmlspecialchars_decode(display($entry, 'teaching_assistants_city'), ENT_QUOTES);
$province = display($entry, 'teaching_assistants_province');
$pc = display($entry, 'teaching_assistants_postal_code');
$country = display($entry, 'teaching_assistants_country');
$email = display($entry, 'teaching_assistants_email');
$startdate = date('F j, Y', strtotime(display($datesData[0], 'semester_dates_module_start_date')));
$enddate = date('F j, Y', strtotime(display($datesData[0], 'semester_dates_module_end_date')));
$hours = display($entry, 'taships_hours');
$course = display($courseData[0], 'ro_module_course_code');
$supervisor = htmlspecialchars_decode(display($entry, 'taships_course_coordinator_instructor'), ENT_QUOTES);
$rate = display($rankData[0], 'ranks_default_pay');
$rank = display($entry, 'teaching_assistants_rank');
$installments = display($datesData[0], 'semester_dates_module_installments');
$term = display($courseData[0], 'ro_module_semester');

$dean = getData('', 16, 'service_module_service_assignment/**/Associate Dean, Academic/**/=][service_module_year/**/'.display($courseData[0], 'ro_module_year').'/**/=');
$dean = htmlspecialchars_decode(display($dean[0], 'service_module_faculty_member'), ENT_QUOTES);

$progCoordStaff = getData('', 16, 'service_module_service_assignment/**/Program Coordinator (staff)/**/=][service_module_year/**/'.display($courseData[0], 'ro_module_year').'/**/=');
$progCoordStaff = htmlspecialchars_decode(display($progCoordStaff[0], 'service_module_staff_names'), ENT_QUOTES);
$progCoordStaffEmail = makeEmailFromName($progCoordStaff);

if(!$name OR !$email) {
    return array();
}

include_once XOOPS_ROOT_PATH.'/dara_helper_functions.php';


$html .= "

<P>$date</P>


<P>$name<BR>$address<BR>$city, $province<BR>$pc, $country</P>

<P>$email</P>

<P>Re: Teaching Assistantship Offer
</P>

<P>Dear $name:
</P>

<P>I am pleased to offer you an appointment as Teaching Assistant at the John H. Daniels Faculty of Architecture, Landscape, and Design, University of Toronto. The start date of your appointment will be $startdate and this appointment will end on $enddate with no further notice to you.</P>

<P>Your appointment will be for $hours hours for $course, and your supervisor(s) will be Professor $supervisor.  You will be paid $$rate/hour, the $rank rate for this position.  You will be paid in $installments instalments, once per month for the period of your appointment. Your salary will be paid by direct deposit.</P> 

<P>Your payroll documentation will be available online through the University's Employee Self-Service (ESS) at <A HREF='http://www.hrandequity.utoronto.ca/resources/ess.htm'>http://www.hrandequity.utoronto.ca/resources/ess.htm</A>. This includes electronic delivery of your pay statement, tax documentation, and other payroll documentation as made available from time to time. You are able to print copies of these documents directly from ESS. 
By signing this Employment Agreement, you authorize the University to provide your T4 slips electronically and not in a paper format. If you wish to discuss an alternative format, please contact Central Payroll Services at <A HREF='mailto:payroll.hr@utoronto.ca'>payroll.hr@utoronto.ca</A>.</P>

<P>This appointment is being granted on the basis that you are a student or Post-Doctoral Fellow (PDF) at the University of Toronto on the starting date of the appointment. If you are not a student on the starting date of this appointment, this offer is revoked and the University will have no obligations under this letter.</P>

<P>As a Teaching Assistant, you will be a member of the Canadian Union of Public Employees (CUPE) Local 3902, Unit 1 bargaining unit.  Your employment will be governed by the terms and conditions of the collective agreement between the University of Toronto and CUPE Local 3902, which may be found on the web at:
<A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm#agreements'>http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm#agreements</A>. Once you accept the offer of employment, a copy of the agreement will be given to you if you do not already have one.  A statement about the Union prepared by the Union, along with other information about the Union, can be found at <A HREF='http://cupe3902.org/unit-1/'>http://cupe3902.org/unit-1/</A>. All of this information is that of the Union, represents the views of the Union and has not been approved or endorsed by the University.</P>

<P>You will also be subject to and bound by University policies of general application and their related guidelines. The policies are listed on the Governing Council website at <A HREF='http://www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm'>http://www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm</A>. For convenience, a partial list of policies, those applicable to all employees, and related guidelines can be found on the Human Resources and Equity website at <A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm</A>. Printed versions will be provided, upon request, through Human Resources or your supervisor.</P>

<P>You should pay particular attention to those policies which confirm the University's commitment to, and your obligation to support, a workplace that is free from discrimination and harassment as set out in the <I>Human Rights Code</I>, is safe as set out in the <I>Occupational Health and Safety Act</I>, and that respects the University's commitment to equity and to workplace civility.</P>

<P>All of the applicable policies may be amended and/or new policies may be introduced from time to time. When this happens, if notice is required you will be given notice as the University deems necessary and the amendments will become binding terms of your employment contract with the University.</P>

<P>The University has a number of programs and services available to employees who have need of accommodation due to disability through its Health & Well-being Programs and Services (<A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm</A>). A description of the accommodation process is available in the Accommodation for Employees with Disabilities: U of T Guidelines, which may be found at: <A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/health/s/a.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/health/s/a.htm</A>.</P> 

<P>In the event that you have a disability that would impact upon how you would respond to an emergency in the workplace (e.g., situations requiring evacuation), you should contact Health & Well-being Programs & Services at 416.978.2149 as soon as possible so that you can be provided with information regarding an individualized emergency response plan.</P>

<P>The law requires the Employment Standards Act Poster to be provided to all employees; it is available on the HR & Equity website at <A HREF='http://uoft.me/ESA-poster'>http://uoft.me/ESA-poster</A>. This poster describes the minimum rights and obligations contained in the Employment Standards Act. Please note that in many respects this offer of employment exceeds the minimum requirements set out in the Act.</P>

<P>You will be expected to complete all grading for all student work that is completed during the term of the appointment, up to and including final exams.</P> 

<P>Within 15 working days after the date of this letter, you will be given the opportunity to review the Description of Duties and Allocation of Hours (DDAH) form, which will set out more specifically the duties of your position, and the hours assigned to each.</P> 


<P>Please sign below to indicate your acceptance of this offer, and return a copy of this entire letter of offer to me as soon as possible but no later than 2 days after you have been provided with the DDAH form together with the attached tax forms and a void cheque.</P>

<P>If we have not heard from you by this deadline, this offer may be withdrawn. If you are unable to accept this offer, please advise me immediately.</P>

<P>If you have any questions, please contact $progCoordStaff <A HREF='mailto:$progCoordStaffEmail'>$progCoordStaffEmail</A>.</P> 

<P>Yours sincerely,</P>

<P></P>

<P>$dean<BR>Associate Dean, Academic</P> 

<HR>

<P>I accept the above Teaching Assistant Position for $course in the $term session.</P>
<P>_____Yes _____No</P>

<P>Signature __________________________________________________<BR>Name: $name</P>";

$html2 = "

<P>If you have accepted the position above, the following information is required:
</P>

<P>Name: $name</P>

<P>Program and Year of Study: _______________________________</P>

<P>Student Number: ________________________________________</P>

<P>E-mail Address: __________________________________________@ mail.utoronto.ca</P>


<P>Mailing Address On File:<BR>$address<BR>$city, $province<BR>$pc, $country</P>

<P>Updated Mailing Address, if necessary:<BR>_______________________________________________________<BR>_______________________________________________________<BR>_______________________________________________________</P>

<P>Tel: (h)	_____________________	(w)  ______________________</P>


<P>Social Insurance Number:	_____________________________________</P>


<P>Birthdate:  Day ____________ Month ___________________   Year ____________</P>


<P>___ I confirm that I will be registered as a University of Toronto student or PDF on the date that this appointment begins.   I understand that if I should cease to be registered as a University of Toronto student or PDF during the period of this appointment, for any reason other than convocation, I must immediately notify my supervisor, and my appointment may be terminated.</P>




";


return array($html, $html2);