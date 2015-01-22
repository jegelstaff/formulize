<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
<TITLE>Application Import</TITLE>

<STYLE TYPE="text/css">
<!--
@page { size: 8.5in 11in; margin: 1in }
P { margin-bottom: 0.08in; direction: ltr; widows: 2; orphans: 2 }
H2 { margin-top: 0.14in; margin-bottom: 0in; direction: ltr; color: #4f81bd; page-break-inside: avoid; widows: 2; orphans: 2 }
H2.western { font-family: "Cambria", serif; font-size: 13pt }
H2.cjk { font-family: "Cambria", serif; font-size: 13pt }
H2.ctl { font-family: "Cambria", serif; font-size: 13pt }
#submit{ margin-top: 10px;}
table{margin-top: 30px; width: 378px;}
-->
</STYLE>
</HEAD>
<BODY LANG="en-CA" DIR="LTR">
<P ALIGN=CENTER STYLE="margin-bottom: 0.21in; border-top: none; border-bottom: 1.00pt solid #4f81bd; border-left: none; border-right: none; padding-top: 0in; padding-bottom: 0.06in; padding-left: 0in; padding-right: 0in; line-height: 100%">
<FONT COLOR="#17365d"><FONT FACE="Cambria, serif"<FONT SIZE=6 STYLE="font-size: 26pt"><FONT FACE="Calibri, serif">Importing
Applications</FONT></FONT></FONT></FONT></P>
<P STYLE="margin-bottom: 0.14in">This utility imports previously
exported <FONT COLOR="#4f81bd">Fourmalize</FONT> applications. 
</P>
<H2 CLASS="western"><FONT FACE="verdana, serif">Instructions</FONT></H2>
<UL>
<LI>Press the Button <SPAN STYLE="background: #ffff00"><b><u>Choose File</b></u></SPAN>
to select the proper import file, the file Name is “ApplicationIDxx”
where the “xx” represents the imported Application Id.
<LI>Press the Button <SPAN STYLE="background: #ffff00"><b><u>Upload</b></u></SPAN>
to load the import contents.
<LI>Application will then prompt
the user to map the imported application “<FONT COLOR="#4f81bd"><U><B>Groups</B></U></FONT>”
to the current  <FONT COLOR="#4f81bd">Fourmalize</FONT> 
<LI>The users will need to select
one of the following options to complete the required mapping:
<UL  class="checkmark">
<LI><b><u>Ignore:</b></u> To drop  this group
from the imported list.
<LI><b><u>Add:</b></u> To add the selected group
attributes as is to the current groups. 
<LI><b><u>Map:</b></u> To map the old code and
attributes to an existing group code.
</UL>
<LI>Following Click  <SPAN STYLE="background: #ffff00"><b><u>Submit</b></u></SPAN>
to proceed with the import.
</UL>
<P ALIGN=CENTER STYLE="margin-bottom: 0.21in; border-top: none; border-bottom: 1.00pt solid #4f81bd; border-left: none; border-right: none; padding-top: 0in; padding-bottom: 0.06in; padding-left: 0in; padding-right: 0in; line-height: 100%">
		<form   action = "" name="Upload" method="POST" enctype="multipart/form-data">
			<input type="file" name="file"/>
			<input type="submit" value="Upload"/>
		</form>
        <form   action = "ui.php" method="GET" id="submit" name="SubmitResults">
          <input type="Submit" value="Submit" />
 			<input type="hidden" name = "page" value="import" />
 			<input type="hidden" name = "next_import" value="import" />
		  <br/>

<?php

include 'PDO_Conn.php';
include 'Import_Backend.php';

if (!empty($_FILES)){

	$import_file_obj = new Import_file_upload();
	$import_file_obj->Import();
	unset($_POST , $_FILES);   	
	
}else{

	if (isset($_GET['next_import'])){

		echo '<div id ="Log report" style="font-weight: bold; margin-top: 10px; margin-bottom: 20px;">';
	 	echo 'Import Log:<br> ';
	 	
	 	$import_model_obj = new Import_model();
		echo '<div style="font-weight: bold; margin-left: 30px; margin-top: 20px; margin-bottom: 20px;">';

		$import_model_obj->Create_Applications();
		echo '</div>';
		echo 'Successfully Imported all Insert Statements .</br>';
		echo '</div>';
	}
}

?>
</form>
