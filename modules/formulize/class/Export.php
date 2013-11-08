<?php
include  'PDO_Conn.php';//Include the Connection File

//To get the Application ID  from the Export_Import Page
//$ID=1;
$ID =$_GET['aid'];
//echo $ID;
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
	$Instructions[filter][C][0]=";";
	$Instructions[filter][C][1]="&";
	$Instructions[gl_name][C][0]=" "; // Character to Replace 
	$Instructions[gl_name][C][1]="&";
  	$Instructions[fltr_grps][C][0]=";"; // Character to Replace 
	$Instructions[fltr_grps][C][1]="&";
	$Instructions[steptitles][C][0]=";"; // Character to Replace 
	$Instructions[steptitles][C][1]="&";
	$Instructions[fltr_grps][C][0]=";"; // Character to Replace 
	$Instructions[fltr_grps][C][1]="&";
	$Instructions[steps][C][0]=";"; // Character to Replace 
	$Instructions[steps][C][1]="&";
	$Instructions[ele_filtersettings][C][0]=";"; // Character to Replace 
	$Instructions[ele_filtersettings][C][1]="&"; // Replace by eg. Replace all (;) with (&) for the field ele_filtersettings
	$Instructions[ele_value][C][0]=";";
	$Instructions[ele_value][C][1]="&";
	$Instructions[ele_handle][P][0]="^"; // Pad ele_handle value with (^) e.g 1 = ^1^
	$Instructions[form_handle][P][0]="^"; // Pad form_handle value with (^) e.g 1 = ^1^
	$Instructions[desc_form][P][0]="&";
	$exclude = Array(); 
	$conn=new Connection ();
	// Check if Application Exist
	$sql="SELECT count(appid) as appid FROM ".Prefix."_formulize_applications WHERE appid = :id;";
	$Query=$conn->connect()->prepare($sql) ;
	$Query->bindValue(":id",$appid);
	$Query->execute();
	$row=$Query->fetch(\PDO::FETCH_ASSOC);
	//echo "Here";
	if ($row['appid']< 1)	{return false;}
	// Get Form id's to export
	$sql="SELECT fid FROM ".Prefix."_formulize_application_form_link WHERE appid = :id;";
	$Query=$conn->connect()->prepare($sql) ;
	$Query->bindValue(":id",$appid);
	$Query->execute();
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{
		$fid[]=$row[fid];
	}
	$formsid = "(".(implode(",",$fid)).")";
	if ($formsid == "()")
	{
		$linksid ="()"; // No forms
		$screenid = "()"; // No Screens as there are no forms
	}
	else
	{	//Links
		$sql="SELECT fl_id FROM ".Prefix."_formulize_framework_links WHERE fl_form1_id in $formsid or fl_form2_id in $formsid;";
		$Query=$conn->connect()->prepare($sql) ;
		$Query->execute();
		while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
		{
			$flid[]=$row[fl_id];
		}
		$linksid = "(".(implode(",",$flid)).")";
		//Screens
		//echo "$sql </br>";
		$Query=$conn->connect()->prepare($sql) ;
		$Query->execute();
		$row=$Query->fetch(\PDO::FETCH_ASSOC);
		$screenid = "(".(implode(",",$row)).")";
	}
	// tables to export in the format table_name/where criteria field/ criteria condition
	// 0-No Padding/0-Single Insert 1-Multi Row Insert/Table Name/Operator/Where Field/Where Condition
	$tables=array ("0/0/_formulize_applications/appid/=/$appid","0/0/_formulize_id/id_form/in/$formsid","0/0/_formulize_application_form_link/fid/in/$formsid",
	"0/0/_formulize/id_form/in/$formsid", "0/0/_formulize_frameworks/frame_id/in/$linksid","0/0/_formulize_framework_links/fl_id/in/$linksid",
	"0/0/_groups","0/0/_formulize_advanced_calculations/fid/in/$formsid","0/0/_formulize_group_filters/fid/in/$formsid",
	"0/0/_formulize_groupscope_settings/fid/in/$formsid","0/0/_formulize_screen/fid/in/$formsid",
	"0/0/_formulize_notification_conditions/not_cons_fid/in/$formsid","0/0/_formulize_entry_owner_groups/fid/in/$formsid",
	"0/0/_formulize_screen_listofentries/sid/in/$screenid","0/0/_formulize_screen_multipage/sid/in/$screenid");

//******************************************************
    $conn=new Connection ();
	$ssql ="select form_handle from ".Prefix."_formulize_id where form_handle is not NULL and id_form in $formsid" ;
	$Query=$conn->connect()->prepare($ssql);
	$Query->execute();
	$pad ="Create table^";
	//echo "Here2";
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
		{
			if ($row[form_handle])
			{
				$pad .="$row[form_handle],";
				////echo $pad."<br/>";
				array_push($tables,"$pad/1/_formulize_".$row[form_handle]);
			}
			$pad ="Create table^";
		}
		////print_r($tables);
		////echo "<//br>...........................................</br>";
//******************************************************
	$lines = Array();
	//print_r($tables);
	foreach ($tables as $t)
	{
		$table = explode ('/',$t);
		$criteria = "Where $table[3] $table[4] $table[5]";
		if (is_null($table[3])) {$criteria ="";}
		//echo "$t ... $table[2] ... $criteria <br/>";
		if ($table[5] !== "()")
		{
			$lines=array_merge($lines,expTable($table[0],$table[1],"".Prefix.$table[2],$exclude,$criteria,$Instructions));
			//print_r($lines);
		}
	}
	//output the insert statement lines
	$text = str_replace("formulize_framework_links","1formulize_framework_links",implode("",$lines));
	echo $text;
	//Output_File($text);
	//Print "<br/>..............Completed Sucessfuly............ <br/>";
	return true;
}
	Function Output_File($output)
	{
	$file ="Application -".$_GET['aid'];
	$f = fopen($file, "w");  
	fwrite($f, $output);  
	fclose($f);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.basename($file));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	ob_clean();
	flush();
	readfile($file);
	}

// Function expTable(padding,insert style,table name, excluded columns array, criteria for selection, post selection fields character replacement array)
Function expTable($padText,$insStyle,$Table_Name,$Exclude_Columns = Null,$Criteria = Null,$Instructions = Null)
{
	$keys = Array();
	static $x;
	$values = Array();
	// text field types
	//('varchar','bigint','longtext','datetime','int','tinyint','decimal','double','char','timestamp','set','enum','longblob','binary','mediumtext','smallint','text','blob','float','time','date','mediumint','mediumblob','tinytext`)
	$types = Array('varchar','longtext','datetime','char','timestamp','enum','mediumtext','text','time','date','tinytext');
	$conn=new Connection ();
	$sql ="SELECT COLUMN_NAME,DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :id;" ;
	$Query=$conn->connect()->prepare($sql);
	$Query->bindValue(":id",$Table_Name);
	$Query->execute();

	// Fetch table structure and identify text fields
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{
		if (!in_array($row['COLUMN_NAME'], $Exclude_Columns )) 
		{
			$keys[] = $row['COLUMN_NAME'];
			if (in_array($row['DATA_TYPE'],$types))
			{	
				$values[]="CONCAT(CHAR(34),`".$row['COLUMN_NAME']."`,CHAR(34))";
			}
			else
			{	
					$values[]="`".$row['COLUMN_NAME']."`";
			}
		}
	}
	unset($Query);
	$Tablename="Prefix";
	$Tablename .=substr($Table_Name,9,strlen($Table_Name)-9);
	// Format the Insert statement
	if ($padText =="0") 
	{
	   $insStatment .= "INSERT INTO $Tablename (`".implode("`,`",$keys)."`) VALUES (";
	   }
	else
		{
		    $insStatment = "$padText" .implode(",", array_slice($keys,5))."^INSERT INTO Table_Name VALUES ";
		}
	$values = implode(",",$values);// Column Names Separated by ,
	$sqlvalues="Select $values from $Table_Name $Criteria;";
	$inslist = Array();
	$Query=$conn->connect()->prepare($sqlvalues) ;
	$Query->execute();
	//$cow=$Query->fetch(\PDO::FETCH_ASSOC);
	
	if ($insStyle =="1") {$insrow=$insStatment;}
	// Create the insert statements for the required table
	
	while ($row=$Query->fetch(\PDO::FETCH_ASSOC))
	{
	
	if ($insStyle =="0") {$insrow=$insStatment;}
	if ($insStyle =="1") {$insrow .="(";}
		$k=0;
		foreach($row as $onefield)
		{
			
			$field=$keys[$k];
			//echo $keys[$k].":".$onefield."<br/>";
			$onefield=str_replace("'","\'",$onefield);
		if (gettype($onefield)==NULL or is_null($onefield)){$onefield="NULL";}
			// execute changes to the required fields
			if (isset($Instructions[$field][C][0])){
			//if (strstr($onefield,"'")) {$onefield=str_replace("'","\'",$onefield);}
				$onefield=str_replace($Instructions[$field][C][0],$Instructions[$field][C][1],$onefield);}
			if (isset($Instructions[$field][P][0])){
				//if (strstr($onefield,"'")) {$onefield=str_replace("'","\'",$onefield);}
				$onefield=(chr(34)).$Instructions[$field][P][0].(substr($onefield,1,strlen($onefield)-2)).$Instructions[$field][P][0].(chr(34));}
			
			$insrow .="$onefield,";

			$k++;
		}
		if ($insStyle =="0")
		   {
				//$insrow= str_replace("'","''",substr($insrow,0,strlen($insrow)-1).");");
				$insrow= substr($insrow,0,strlen($insrow)-1).");";
				array_push($inslist,$insrow);
		   }
		else { $insrow= substr($insrow,0,strlen($insrow)-1)."),";}
	}
	if ($insStyle =="1")
		{
				$insrow= substr($insrow,0,strlen($insrow)-1).";";
				array_push($inslist,$insrow);
		}
	unset($Query);
	//unset($keys);
	//print_r($inslist);
	return $inslist;
}
?>
