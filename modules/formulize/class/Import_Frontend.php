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
attributes as is to the current groups 
<LI><b><u>Map:</b></u> To map the old code and
attributes to an existing group code.
</UL>
<LI>Following Click  <SPAN STYLE="background: #ffff00"><b><u>Submit</b></u></SPAN>
to proceed with the import, or <SPAN STYLE="background: #ffff00"><b><u>Cancel</b></u></SPAN>to Exit
</UL>
<P ALIGN=CENTER STYLE="margin-bottom: 0.21in; border-top: none; border-bottom: 1.00pt solid #4f81bd; border-left: none; border-right: none; padding-top: 0in; padding-bottom: 0.06in; padding-left: 0in; padding-right: 0in; line-height: 100%">
		<form name="Upload" method="POST" enctype="multipart/form-data">
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

 Import();

Function Import ()
{
	$fileName=$_FILES['file']['name'];
	$fileTmp=$_FILES['file']['tmp_name'];

	if (!file_exists('../upload/')) { mkdir('../upload/', 0777, true); }
	$filePath = '../upload/'.$fileName;

	move_uploaded_file ($fileTmp, $filePath);

	replaces_Prefix_in_file ($filePath);
	displ($filePath);
}

function replaces_Prefix_in_file ($fileName)
{
	
	$str=implode("\n",file($fileName));
	$fp=fopen($fileName,'w');
	
	//replace Prefix word file string with the current DB Prefix 
	$str=str_replace('Prefix',Prefix,$str);

	fwrite($fp,$str,strlen($str));
	fclose($fileName);
}

function displ ($fileName){

	$newGroups = Getall(2);
	$oldGroups = Getall(1, $fileName);
	if ($oldGroups !=null){
	echo '<table border=\'1\'>';
	echo '<tr>';
	echo '<th> No </th>';
	echo '<th> Old Group </th>';
	echo '<th> New Group </th>';
	echo '</tr>';
	$k=1;
	
	foreach($oldGroups as $x=>$x_value)
	{
		echo '<tr>';
		echo '<td>'. $k .'</td>';
		echo '<td>'. $x_value .'</td>';
		echo '<td>'; 
		$id = "$x";
		echo '<select name='.$x.' id='.$x.'>';
		echo '<option value=\'\'>---Select---</option>';
		echo '<option value=\'Ignore\'>Ignore this Group</option>';
		echo '<option value=\'New\'>Add as New</option>';
		foreach($newGroups as $y=>$y_value)
		{ 
			
			echo '<option value='.$y.'>Map This Group To ->'.$y_value.'</option>';
		}
		echo '</select>';
		echo  '</td>';
		echo '</tr>';
		$k++;
	}
	echo '</table> ';
	}
}

function Getall($Flag, $fileName = null){
	global $get;
	$get=array();
	if ($Flag==1){
		session_start();
		$_SESSION['file'] = $fileName;//Send the File Location to Import.php
		$file =$fileName;
		$getlines = file_get_contents($file);
		$get_line=explode(";",$getlines);//print_r($get_line);
		foreach ($get_line as $statement)
		{
		preg_match('/_.*\(\`/', $statement, $table);//To get the Table name
		$rem=array('VALUES','`','(',' ',')','\'');
		$table=str_replace($rem,'', $table[0]);
			if($table=='_groups') {
				preg_match('/\(\'\d*\'\,\'.*\'/', $statement, $matches);
				///print_r($matches);
				str_replace($rem,'', $matches[0]);
				$m=str_replace($rem,'', $matches[0]);
				$m1=explode(',',$m);
				$get[$m1[0]] = $m1[1]; //Array push Group ID and name
			}
		}

		fclose($file);

		return $get;
	}

	else
	{
		$conn=new Connection ();
		$sql='SELECT groupid , name FROM '.Prefix.'_groups;';
		$Query=$conn->connect()->prepare($sql) ;
		$Query->execute();
		while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
		{
			$get[$row['groupid']] = $row['name']; //Array push Group ID and name
		}
		return $get;
	}

}

?>
</form>
