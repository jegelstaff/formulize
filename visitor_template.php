<?php

$html = "

<P>$date</P>

<P>$name";

if($address) {
    $html .= "<BR>$address";
}
if($city OR $province) {
    $html .= "<BR>";
}
if($city) {
    $html .= "$city";
}
if($city AND $province) {
    $html .= ", ";
}
if($province) {
    $html .= "$province";
}
if($pc OR $country) {
    $html .= "<BR>";
}
if($pc) {
    $html .= "$pc";
}
if($pc AND $country) {
    $html .= ", ";
}
if($country) {
    $html .= "$country";
}

$html .= "</P>

<P>$email</P>

<P>Re: $year Academic Year Visiting Professor Appointment</P>

<P>Dear $name:</P>

<P>I would like to invite you to the University of Toronto as a Visiting Professor Position at the rank of $rank with the John H. Daniels Faculty of Architecture, Landscape, and Design, for the period $startdate to $enddate.</P> 

<P>The purpose of your visit will be to teach in the $program:<UL>";
foreach($courses as $course) {
	$html .= "<LI>{$course['code']} - {$course['title']}, Section {$course['section']}<BR>{$course['times']}<BR>{$course['room']}</LI>";
}

$html .= "</UL>It is expected that you will establish office hours to meet with your students and that you be available during midterm and final review weeks to serve on Daniels studio and thesis reviews as well as after end of the term in case there are questions/issues regarding grades.</P>

<P>We will offer an honorarium of CAD $salary for this teaching assignment.";  


if($travelAllowance) {
    $html .= " Upon production of original receipts, you will be eligible for reimbursement for your travel and accommodation expenses, up to a maximum of CAD $travelAllowance.";
}

$html .= "</P><P>While you are here, the Daniels Faculty will provide you with office space, access to it and library resources, and a departmental e-mail address.</P>

<P>The Office of the Vice-President and Provost maintains a set of links to important policies that will govern any teaching or research at <A HREF='http://www.governingcouncil.utoronto.ca/Governing_Council/policies.htm'>http://www.governingcouncil.utoronto.ca/Governing_Council/policies.htm</A>.	In particular, I would like to draw your attention to the <i>Code of Behaviour on Academic Matters</i> at <A HREF='http://www.governingcouncil.utoronto.ca/Assets/Governing+Council+Digital+Assets/Policies/PDF/ppjun011995.pdf'>www.governingcouncil.utoronto.ca/Assets/Governing+Council+Digital+Assets/Policies/PDF/ppjun011995.pdf</A>, and the <i>Policy on Conflict of Interest &mdash; Academic Staff</i> at <A HREF='http://www.governingcouncil.utoronto.ca/Assets/Governing+Council+Digital+Assets/Policies/PDF/ppjun221994.pdf'>www.governingcouncil.utoronto.ca/Assets/Governing+Council+Digital+Assets/Policies/PDF/ppjun221994.pdf</A>.</P>

<P>This letter, and the documents referred to in it, constitute the entire agreement between you and the University. There are no representations, warranties or other commitments apart from these documents.</P>";

if($immigration) {
    
    $html .= "<P><B>Immigration Issues</B><BR>In order to facilitate your entry to Canada, I would suggest that you refer to the Citizenship and Immigration Canada web page to determine where and how you may file an application using the online filing system, to obtain the necessary authorization to work in Canada: <A HREF='http://www.cic.gc.ca/english/e-services/mycic.asp'>www.cic.gc.ca/english/e-services/mycic.asp</A>. Individuals of certain countries require an additional temporary resident visa (TRV) and/or a medical examination. To determine whether you require a TRV, please refer to  <A HREF='http://www.cic.gc.ca/english/visit/visas.asp'>www.cic.gc.ca/english/visit/visas.asp</A>.  
To determine if you require a medical examination (for visits of more than six months), please refer to <A HREF='http://www.cic.gc.ca/english/information/medical/dcl.asp'> www.cic.gc.ca/english/information/medical/dcl.asp</A>.</P>

<P>All foreign nationals (excluding United States citizens) who do not require a TRV must obtain an electronic travel authorization (eTA) prior to entering Canada by air. For more information regarding the eTA, and how to obtain one prior to travel, please visit <A HREF='http://www.cic.gc.ca/english/visit/eta-start.asp'>www.cic.gc.ca/english/visit/eta-start.asp</A>. If you do not require a TRV, you are permitted to apply for a work permit directly upon your arrival from abroad at the Immigration office at the Canadian port of entry (border crossing or airport). Your application will be adjudicated on the spot.</P>

<P>At the time of application for your work permit, you will need to include the information for any accompanying family members and dependents.</P>

<P>In addition, you will need a letter from your home institution attesting to the fact that you will be retaining your position there to resume your duties in your home country after $enddate.</P>  

<P>Lastly, the University must provide you with an <B>Offer of Employment, A#</B>. This number, along with that home institution letter and this letter of invitation must be presented to a Visa Post nearest you (or as outlined above, to a Port of Entry immigration officer). A Work Permit will then be processed pursuant to Regulation 205(b) IRPA, Labour Market Exemption Code C22. The processing fee for a work permit is currently CAD $155, which must be paid at the time you apply for a work permit.</P>  

<P>Upon receipt of your Work Permit, it is necessary that you obtain a Social Insurance Number (SIN). For information on how to obtain a new SIN, please refer to the Federal Government's website: <A HREF='http://www.servicecanada.gc.ca/en/sc/sin/index.shtml'>www.servicecanada.gc.ca/en/sc/sin/index.shtml</A>.</P>";

$html .= "<P><B>Health Insurance</B><BR>Enrolment in the University Health Insurance Plan (UHIP) is compulsory for non-resident Visiting Professors and their dependents whose visit to the University exceeds three weeks. To enrol in UHIP, please contact the Human Resources (HR) office for your division. A complete list of HR contacts can be found at <A HREF='http://contact.hrandequity.utoronto.ca/'>contact.hrandequity.utoronto.ca</A>. For additional information concerning UHIP, please refer to <A HREF='http://www.uhip.ca'>www.uhip.ca</A>.</P> 

</P>This offer is conditional on you being legally entitled to work in Canada, in this position. A copy of your work permit must be provided to $bo, Business Officer, immediately upon arrival. Your visit with the University is conditional upon satisfactory immigration status maintained for the duration of your stay.</P>";

}

$html .= "<P>If you accept this offer, I would appreciate you signing a copy of this letter and returning it to $bo, Business Officer (via email $boemail) no later than $signbackDate";
if($immigration) {
    $html .= " together with a copy of your valid passport";
}
$html .= ".</P><P>Should you have any questions regarding this offer, please do not hesitate to contact $programDirector, Program Director, $program $programDirectorEmail</P>

<P>We expect that you will govern yourself in accordance with all applicable faculty and University policies.</P>

<P>Sincerely,</P><BR><BR><P>$cao<BR>Chief Administrative Officer</P>

<P>";
$startDirs = true;
foreach($programDirs as $dir) {
    if(!$startDirs) {
        $html .= "<BR>";
    }
    $startDirs = false;
    $html .= "cc: ".$dir['programDirector'].", Program Director, ".$dir['pdProgram'];
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

