<?php
include  'PDO_Conn.php';//Include the Connection File

//To get the Application ID  from the Export_Import Page
//$ID=1;
$ID =$_GET['aid'];
if (!Export($ID))
{
	echo "<br/>Error [0] : Invalid Application Id Contact the Administrator ...No Export File is Created ... Exiting <br/>";
}

function Export ($ID) {
	global $appid; 
	$appid=$ID; 
	$flid = Array(); // framework id array to export
	//Instructions array to replace and pad values in the selected fields (C) To Replace & (P) To Pad the field with the specified character
	$Instructions = array(array());
	$Instructions[ele_filtersettings][C][0]=";"; // Character to Replace 
	$Instructions[ele_filtersettings][C][1]="&"; // Replace by eg. Replace all (;) with (&) for the field ele_filtersettings
	$Instructions[ele_value][C][0]=";";
	$Instructions[ele_value][C][1]="&";
	$Instructions[ele_handle][P][0]="^"; // Pad ele_handle value with (^) e.g 1 = ^1^
	$exclude = Array(); 
	$conn=new Connection ();
	// Check if Application Exist
	$sql="SELECT count(appid) as appid FROM ".Prefix."_formulize_applications WHERE appid = $appid;";
	$Query=$conn->connect()->prepare($sql) ;
	$Query->execute();
	$row=$Query->fetch(\PDO::FETCH_ASSOC);
	if ($row['appid']< 1)	{return false;}
	// Get Form id's to export
	$sql="SELECT fid FROM ".Prefix."_formulize_application_form_link WHERE appid = $appid;";
	$Query=$conn->connect()->prepare($sql) ;
	$Query->execute();
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{
		$fid[]=$row[fid];
	}
	$formsid = "(".(implode(",",$fid)).")";
	if ($formsid == "()")
	{
		$linksid ="()"; // No forms
	}
	else
	{
		$sql="SELECT fl_id FROM ".Prefix."_formulize_framework_links WHERE fl_form1_id in $formsid or fl_form2_id in $formsid;";
		$Query=$conn->connect()->prepare($sql) ;
		$Query->execute();
		while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
		{
			$flid[]=$row[fl_id];
		}
		$linksid = "(".(implode(",",$flid)).")";
	}
	//echo "formsid = $formsid ..... linksid = $linksid <br/>";
	// tables to export in the format table_name/where criteria field/ criteria condition
	$tables=array ("_formulize_applications/appid/=/$appid","_formulize_id/id_form/in/$formsid","_formulize_application_form_link/fid/in/$formsid",
	"_formulize/id_form/in/$formsid", "_formulize_frameworks/frame_id/in/$linksid","_formulize_framework_links/fl_id/in/$linksid",
	"_groups");
	$lines = Array();
	foreach ($tables as $t)
	{
		$table = explode ('/',$t);
		$criteria = "Where $table[1] $table[2] $table[3]";
		if (is_null($table[1])) {$criteria ="";}
		//echo "$t ... $table[0] ... $criteria <br/>";
		if ($table[3] !== "()")
		{
			$lines=array_merge($lines,expTable("".Prefix.$table[0],$exclude,$criteria,$Instructions));
		}
	}
	//output the insert statement lines
	$text = str_replace("formulize_framework_links","1formulize_framework_links",implode("",$lines));
	Output_File($text);
	echo $text;
	//Print "<br/>..............Completed Sucessfuly............ <br/>";
	return true;
}
	Function Output_File($text)
	{
		ob_start();
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=ApplicationID$ID.");
		header("Pragma: no-cache");
		header("Expires: 1");
		file_put_contents($text);
		ob_end_flush(); //now the headers are sent
	}

		
// Function expTable(table name, excluded columns array, criteria for selection, post selection fields character replacement array)
Function expTable($Table_Name,$Exclude_Columns = Null,$Criteria = Null,$Instructions = Null)
{
	$keys = Array();
	$values = Array();
	// text field types
	$types = Array('varchar','text');
	$conn=new Connection ();
	$ssql ="SELECT COLUMN_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$Table_Name'" ;
	$Query=$conn->connect()->prepare($ssql);
	$Query->bindValue(":id",$Table_Name);
	$Query->execute();

	// Fetch table structure and identify text fields
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{

		if (!in_array($row['COLUMN_NAME'], $Exclude_Columns )) {
			$keys[] = $row['COLUMN_NAME'];
			if (in_array($row['DATA_TYPE'],$types)){
				$values[]="CONCAT(CHAR(39),$row[COLUMN_NAME],CHAR(39))";
			}
			else{	
				$values[]=$row['COLUMN_NAME'];
			}
		}
	}
	unset($Query);
	$Tablename="Prefix";
	$Tablename .=substr($Table_Name,9,strlen($Table_Name)-9);
	// Format the Insert statement
	$insStatment = "INSERT INTO $Tablename (" .implode(",", $keys).") VALUES (";
	$values = implode(",",$values);// Column Names Separated by ,
	$sqlvalues="Select $values from $Table_Name $Criteria;";
	$inslist = Array();
	$Query=$conn->connect()->prepare($sqlvalues) ;
	$Query->execute();
	// Create the insert statements for the required table
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{
		$insrow=$insStatment;
		$k=0;
		foreach($row as $onefield)
		{
			$field=$keys[$k];
			// execute changes to the required fields
			if (isset($Instructions[$field][C][0])){
				$onefield=str_replace($Instructions[$field][C][0],$Instructions[$field][C][1],$onefield);}
			if (isset($Instructions[$field][P][0])){
				$onefield=(chr(39)).$Instructions[$field][P][0].(substr($onefield,1,strlen($onefield)-2)).$Instructions[$field][P][0].(chr(39));}
			$insrow .="$onefield,";
			$k++;
		}
		$insrow= substr($insrow,0,strlen($insrow)-1).");";
		array_push($inslist,$insrow);
	}
	unset($Query);
	return $inslist;
}
?>
