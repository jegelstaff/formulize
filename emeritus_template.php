<?php

$html = "$reservedFlag

<P>$date</P>

<P>$name<BR>$address<BR>$city, $province<BR>$pc, $country</P>

<P>$email</P>

<P>Re: $year Academic Year Emeritus Appointment

<P>Dear $name:</P>

<P style=\"text-align:justify;\">On behalf of the John H. Daniels Faculty of Architecture, Landscape and Design, I am pleased to offer you a position of Emeritus, at the rank of $rank for the period beginning $startdate and ending $enddate.  Your casual employment may be terminated at any time at the sole discretion of the University.</P>

<P style=\"text-align:justify;\">As a casual employee, your terms and conditions of employment will continue to be governed by the collective agreement between the University of Toronto and United Steelworkers, Local 1998, applicable to casual employees, a copy of which is available on the web at: <A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm#USW_Local_1998_(Casual)'>http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm#USW_Local_1998_(Casual)</A>  As a casual employee of the University you will not be eligible to enroll in the University’s benefit plans.</P>

<P style=\"text-align:justify;\">Your duties will consist teaching the following course";
if(count($courses)>1) {
    $html .= "s";   
}
$html .= ":<UL>";
foreach($courses as $course) {
	$html .= "<LI>{$course['code']} - {$course['title']}, Section {$course['section']}{$course['reserved']}<BR>{$course['times']}<BR>{$course['room']}";
    if($course['coinst'] AND count($course['coinst'])>0) {
        $html .= "<BR>Co-taught with ".implode(", ", $course['coinst']);
    }
    $html .= "</LI>";
}

$html .= "</UL>You will be paid $salary, plus 4% vacation pay, subject to deductions required by law. You will be paid in four instalments, once per month for the period of your appointment.</P>";

if($travelAllowance) {
    $html .= "<P style=\"text-align:justify;\">Upon production of original receipts, you will be eligible for reimbursement for your travel and accommodation expenses, up to a maximum of CAD $travelAllowance.</P>";
}

if($immigration) {
    $html .= "<P style=\"text-align:justify;\"><B>Immigration Issues</B><BR>This offer is subject to compliance with the immigration laws of Canada (as contained in the <I>Immigration and Refugee Protection Act</I> and in the regulations made in pursuance of that <I>Act</I>) and it is conditional upon any approvals, authorizations and/or permits in respect of your employment that may be required under that <I>Act</I> or the regulations.</P>
<P style=\"text-align:justify;\">Upon your acceptance of our offer of employment you will receive from the Office of the Vice-President and Provost instructions on how to begin the process for applying for the temporary Work Permit that you will require for your employment with the University and for Permanent Resident (\"landed immigrant\") status in Canada. To assist with both of these processes we have engaged the Toronto law firm of Rekai LLP. As the University's legal counsel, we have instructed the law firm of Rekai LLP to assist you with all aspects of both your temporary and permanent immigration law requirements. Mr. Peter Rekai will be in touch with you directly as soon as Service Canada has confirmed our offer of employment to you. By accepting the services of the law firm of Rekai LLP, you consent to the release of any and all information pertaining to your and accompanying family members' admissibility to Canada by Rekai LLP to the Office of the Vice-President and Provost of the University of Toronto. This information will be held in strict confidence by the Office of the Vice-President and Provost and will not be released by that Office without your prior written permission.</P>
<P style=\"text-align:justify;\">The University will be responsible for all of Rekai LLP’s routine legal fees (save and except as noted below) and for the Government of Canada's filing fees for your applications provided you remain employed by the University of Toronto. You will be responsible for all other incidental expenses related to your immigration law requirements. This includes, but is not limited to, such incidental matters as the cost of medical examinations, photos, documents, police clearance certificates as well as the expenses to be incurred by Rekai LLP on your behalf for couriers, translations, photocopying, telecopying and long distance. Should your employment with the University cease for any reason and you decide to continue with your Application for Permanent Residence (APR) in Canada, you will be responsible for any remaining fees.  Please note that the University of Toronto will not cover legal fees related to <B>non-routine matters</B> such as overcoming any issue of medical or criminal inadmissibility for you or any accompanying family member(s). If you have any questions about which fees are covered by the University, please contact the Faculty Immigration at <A HREF='mailto:faculty.immigration@utoronto.ca'>faculty.immigration@utoronto.ca</A>.</P>
<P style=\"text-align:justify;\">The University considers it to be a term of our offer of employment to you that you cooperate fully with the law firm of Rekai LLP and promptly deal with any requests that they may make of you. Specifically, because the confirmation of employment (positive Labour Market Opinion) will not be valid for more than three (3) years and there is no arrangement in place with Service Canada for it to be renewed, it is vital that all reasonable steps be taken to complete your permanent immigration to Canada within that time. In addition, several Canadian granting agencies only fund grants to Canadian citizens and permanent residents of Canada and, for that reason, it also may be in your best professional interests to cooperate with the law firm of Rekai LLP in completing the application process as expeditiously as possible.</P>
<P style=\"text-align:justify;\">As part of the process of applying for permanent residency in Canada, and, in some cases, as part of the non-immigrant visa process as well, it will be necessary for you and your accompanying family members to undergo medical examinations and to provide information with respect to criminal and security background investigations that are conducted by Citizenship and Immigration Canada (CIC) on all applicants. These routine immigration procedures are conducted with a view to ensuring that there are no grounds upon which you, or any member of your accompanying family, could be determined to be an \"inadmissible person\" for immigration to Canada. If you require clarification or if you have any questions regarding these matters, you will be able to discuss them with one of the partners at Rekai LLP, but only after you have been contacted by the firm.</P>
<P style=\"text-align:justify;\">Upon receipt of your Work Permit, it is necessary that you obtain a Social Insurance Number (SIN). For information on how to obtain a new SIN, please refer to the Federal Government's website: <A HREF='http://www.servicecanada.gc.ca/en/sc/sin/index.shtml'>http://www.servicecanada.gc.ca/en/sc/sin/index.shtml</A>. Also, you may visit U of T's Human Resources & Equity website for additional information: <A HREF='www.hrandequity.utoronto.ca/about-hr-equity/Payroll/social-insurance-number.htm'>www.hrandequity.utoronto.ca/about-hr-equity/Payroll/social-insurance-number.htm</A>.</P>";

$html .= "<P style=\"text-align:justify;\"><B>Health Insurance</B><BR>The provincial health insurance plan (OHIP) normally commences coverage three months after application. You should apply for this coverage on your arrival to ensure there is no further delay. (Please refer to the Faculty Relocation Service website: <A HREF='www.facultyrelocation.utoronto.ca'>www.facultyrelocation.utoronto.ca</A> for more information). If your existing health insurance coverage does not apply to this waiting period, then it is compulsory that you apply immediately for the University's Health Insurance Plan (UHIP; <A HREF='www.uhip.ca'>www.uhip.ca</A>). For further information, please contact Jasmin Olarte in the University of Toronto Human Resources office at 416-946-5638.</P>";

    $html .= "<P style=\"text-align:justify;\">This offer is conditional on you being legally entitled to work in Canada, in this position. In order to facilitate your entry to Canada, you will need to provide us a copy of your valid passport and a letter from your home institution attesting to the fact that you will be retaining your position there to resume your duties in $country after $enddate. Please note that you are required to be in possession of a valid passport and it will be necessary for the passport to be valid for the entire length of stay in Canada.</P>";
}

$html .="<P style=\"text-align:justify;\">Your payroll documentation will be available online through the University’s Employee Self-Service (ESS) at <A HREF='http://www.hrandequity.utoronto.ca/resources/ess.htm'>http://www.hrandequity.utoronto.ca/resources/ess.htm</A>. This includes electronic delivery of your pay statement, tax documentation, and other payroll documentation as made available from time to time. You are able to print copies of these documents directly from ESS. By signing this Employment Agreement, you authorize the University to provide your T4 slips electronically and not in a paper format. If you wish to discuss an alternative format, please contact Central Payroll Services at <A HREF='mailto:payroll.hr@utoronto.ca'>payroll.hr@utoronto.ca</A>.</P>

<P style=\"text-align:justify;\">In the event that you obtain and concurrently work in another position (or positions) at the University in the future, please advise all departments of your employment in the other department(s).</P>

<P style=\"text-align:justify;\">You will also be subject to and bound by University policies of general application and their related guidelines. The policies are listed on the Governing Council website at <A HREF='http://www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm'>http://www.governingcouncil.utoronto.ca/Governing_Council/Policies.htm</A>. For convenience, a partial list of policies, those applicable to all employees, and related guidelines can be found on the Human Resources and Equity website at <A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/policies-guidelines-agreements.htm</A>. Printed versions will be provided, upon request, through Human Resources.</P>

<P style=\"text-align:justify;\">You should pay particular attention to those policies which confirm the University's commitment to, and your obligation to support, a workplace that is free from discrimination and harassment as set out in the Human Rights Code, is safe as set out in the Occupational Health and Safety Act, and that respects the University's commitment to equity and to workplace civility.</P>

<P style=\"text-align:justify;\">All of the applicable policies may be amended and/or new policies may be introduced from time to time. When this happens, if notice is required you will be given notice as the University deems necessary and the amendments will become binding terms of your employment contract with the University.</P> 

<P style=\"text-align:justify;\">Please carefully review all applicable policies and guidelines. By signing this letter, you acknowledge that you understand them and agree to be bound by them. If you have questions about any of these policies or guidelines you should raise them with HR before accepting this offer.</P>

<P style=\"text-align:justify;\"><B>Accessibility</B><BR>The University has a number of programs and services available to employees who have need of accommodation due to a disability through its Health & Well-being Programs and Services (<A HREF='http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm'>http://www.hrandequity.utoronto.ca/about-hr-equity/health.htm</A>). A description of the accommodation process is available in the Accommodation for Employees with Disabilities: U of T Guidelines, which may be found at: <A HREF='http://well-being.hrandequity.utoronto.ca/services/#accommodation'>http://well-being.hrandequity.utoronto.ca/services/#accommodation</A>.</P>

<P style=\"text-align:justify;\">In the event that you have a disability that would impact upon how you would respond to an emergency in the workplace (e.g., situations requiring evacuation), you should contact Health & Well-being Programs & Services at 416 978-2149 as soon as possible so that you can be provided with information regarding an individualized emergency response plan.</P> 

<P style=\"text-align:justify;\">The law requires the Employment Standards Act Poster to be provided to all employees; it is available on <A HREF='http://www.labour.gov.on.ca/english/es/pubs/poster.php'>http://www.labour.gov.on.ca/english/es/pubs/poster.php</A>. This poster describes the minimum rights and obligations contained in the <I>Employment Standards Act</I>. Please note that in many respects this offer of employment exceeds the minimum requirements set out in the Act.</P>

<P style=\"text-align:justify;\">If you accept this offer, I would appreciate you signing a copy of this letter together with the attached tax forms and a void cheque (unless your banking information remains unchanged) and returning it to $bo, $botitle (via email $boemail) no later than $signbackDate.  Should you have any questions regarding this offer, please do not hesitate to contact your program director (cc'd below).</P> 

<P style=\"text-align:justify;\">Sincerely,</P><BR><BR><P style=\"text-align:justify;\">$cao<BR>Chief Administrative Officer</P>

<P style=\"text-align:justify;\">";
$startDirs = true;
foreach($programDirs as $dir) {
    if(!$startDirs) {
        $html .= "<BR>";
    }
    $startDirs = false;
    $html .= "cc: ".$dir['programDirector'].", Program Director, ".$dir['pdProgram'].", <A HREF='mailto:".$dir['programDirectorEmail']."'>".$dir['programDirectorEmail']."</A>";
}
$html .= "</P>";

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
