<?php
include 'PDO_Conn.php';//Include the Connection File
	
	//Check_Uniquines(null,5,$ele_Handle);
	//Application Runs From Here:
	Check_Post_Parameter();
	//print_r (Fourm_elements(7));
	//Function To check if the Button Export or Import is Pressed and Loads the Function
	//echo Check_Uniquines (null,5,'12');//Form Handle
	//Check_Uniquines (ID,5);
	Function Check_Post_Parameter()
	{
	if(isset($_GET['select']))
	{
			switch ($_GET['select'])
			{
				case 'Export':
				Export_All();
				break;
				case 'Import':
				Import ();
				break;
			}
	}
	else
	{
	echo "Failed For Some Reason.Please Refresh The Page";
	}
	
	}	
	Function Create_Insert_Statments($table)
	{//
	/*
	This function creates the insert statements for the requested table.It  uses the AppID to extract all the Forum in the Links table .
	Stpes on how it works:
	1)Prepares the Insert statement syntax .It starts be creating the insert statement by INSERT INTO Prefix_tablename VALUES.Prefix is added to the insert statement when the file
	is imported it changes the prefix to the current formulize version prefix. 
	2)Then it queries the DB table for the values .
	3)The result is returned in Array of Object.So it get the result we need to loop the array with the Column Field name.
	4)Query the DB for the table column.
	5)Gets the field content using the Tale column result .
	6)Checks this Field if its included in the the text field array or not.To be able to decide whether this Field is a string or Integer 
	6)Creates the Insert statement.
	7)The Insert string is reversed back to step 1 to be used again if more rows from this needs to be created. 
	///Idea\\\
	App can Onliy be One 
	Fourm Can be Many 
	Links Can be Mnny
	//[FIXED String Field!!!]
	*/
	STATIC $getfid=array();//To get The Form ID that's being Exported to send it to Formulize table and declared Static so won't loose its value when function exists
	//$getfid=array();//To get The Form ID that,s being Exported to send it to Formulize table
	$getfield=Get_FieldNames($table);
	$appid = intval($_GET['aid']);
	$TextField=array('id_form','lockedform','defaultform','defaultlist','store_revisions','appid','id_form','ele_id','ele_order','ele_req','ele_encrypt','ele_forcehidden','ele_private' );//To decide whether the field is a string or not 
	//$table='_formulize_applications';
	
	if ($table=='_formulize_application_form_link') {
	$k1=1;
	 $Links=Application_Fourm_Links($appid);
	 	  
	  $Insert="Insert INTO Prefix".$table." (linkid,appid,fid) VALUES  (";
	 foreach ($Links as $key => $column) {
	
	 foreach($getfield as $k => $cur)
	{
	
	//Fixed
	if ($k1==count($getfield)){
	$Insert.=$column[$cur['Field']];
	$Insert.=');';
	array_push($SQLStatments,$Insert);
	echo $Insert;
	  $Insert="Insert INTO Prefix".$table." (linkid,appid,fid) VALUES (";
	}else {
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	++$k1;
	
	}
	
	}
	if ($k1==3){$k1=1;}
	
	}
	 }else if ($table=='_formulize_applications'){
	 $k1=1;
	 $Links=Application_Fourm_Links($appid);
	  $Insert="Insert INTO Prefix".$table." (appid, name,description) VALUES (";
	 foreach ($Links as $key => $column) {
	 $getapp=Get_App($column['appid']);
	 foreach ($getapp as $key => $column) {
	 foreach($getfield as $k => $cur)
	{
	//Need To Fix the '' Insert Statments///[DONE]
	if ($k1==count($getfield)){
	$Insert.="'";
	$Insert.=$column[$cur['Field']]."'";
	$Insert.=');';
	array_push($SQLStatments,$Insert);
	echo $Insert;
	  $Insert="Insert INTO Prefix".$table." (appid, name,description) VALUES (";
	}else {
	if (in_array($cur['Field'],$TextField)){ //Checks if the Field is a string or not 
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	++$k1;}
	else 
	{
	$Insert.="'";
	$Insert.=$column[$cur['Field']]."',";
	++$k1;
	}
	}
	}
	if ($k1==3){$k1=4;}
	}
	 }//echo $Insert;
	 }else if ($table=='_formulize_id'){
	 $k1=1;
	 $Links=Application_Fourm_Links($appid);
	  $Insert="Insert INTO Prefix".$table." (`id_form`, `desc_form`, `singleentry`, `headerlist`, `tableform`, `lockedform`, `defaultform`, `defaultlist`, `menutext`, `form_handle`, `store_revisions`) VALUES (";
	  foreach ($Links as $key => $column) {
	 $getForm=Get_Form($column['fid']);
	 array_push($getfid,$column['fid']);//To get all the FID and send it to _Formulize Table
	 foreach ($getForm as $key => $column) {
	 foreach($getfield as $k => $cur)
	{

	if ($k1==count($getfield)){
	$Insert.=$column[$cur['Field']];
	$Insert.=');';
	
	array_push($SQLStatments,$Insert);
	echo $Insert;
	 $Insert="Insert INTO Prefix".$table." (`id_form`, `desc_form`, `singleentry`, `headerlist`, `tableform`, `lockedform`, `defaultform`, `defaultlist`, `menutext`, `form_handle`, `store_revisions`) VALUES (";
	}else {
	//echo $Insert;
	//echo $k1;
	//To Check if the field is string or not .If it does then Add "" to it 
	if (in_array($cur['Field'],$TextField)){
	echo $Insert;
	$Insert="";
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	++$k1;}else {echo $Insert;
	$Insert="'";
	$Insert.=$column[$cur['Field']]."'";
	$Insert.=',';
	++$k1;}
	}
	}
	}
	if ($k1==11){$k1=1;}
	
	 }}else if ($table=='_formulize') {	
	  foreach ($getfid as $key => $column) {//Loop to get all the Fid
	 //var_dump($column);
	 echo  $column;
	 $Elements=Fourm_Elements($column);//To get the Row for this FID
	 $Insert="INSERT INTO Prefix_formulize (`id_form`, `ele_id`, `ele_type`, `ele_caption`, `ele_desc`, `ele_colhead`, `ele_handle`, `ele_order`, `ele_req`, `ele_encrypt`, `ele_value`, `ele_uitext`, `ele_delim`, `ele_display`, `ele_disabled`, `ele_filtersettings`, `ele_forcehidden`, `ele_private`) VALUES ( " ;
	  //print_r($Elements);
	 foreach ($Elements as $key => $column1) {

	 foreach($getfield as $k => $cur) {
	 if ($cur['Field']=='ele_private'){
	 $Insert.=$column1[$cur['Field']].");";
	 echo $Insert;
	 $Insert="INSERT INTO Prefix_formulize (`id_form`, `ele_id`, `ele_type`, `ele_caption`, `ele_desc`, `ele_colhead`, `ele_handle`, `ele_order`, `ele_req`, `ele_encrypt`, `ele_value`, `ele_uitext`, `ele_delim`, `ele_display`, `ele_disabled`, `ele_filtersettings`, `ele_forcehidden`, `ele_private`) VALUES ( " ;
	 }else{
	 if ($cur['Field']=='ele_value'|| $cur['Field']=='ele_filtersettings' ){
	 $Insert.="'".(str_replace(';', '&', $column1[$cur['Field']]))."',";//This Is a Special If Statement because we replace the ; with & and when we Import we switch & back to ;.At beginning. we break the statements based on ; so this will cause the sterilized array to break apart. 
	 }else{
	 if ($cur['Field']=='ele_handle'){
	// $Insert.="#'".$column1[$cur['Field']]."'#,";//This Marks the Handle so when we Import we could locate which Field is the Handle by Preg Match
	 $Insert.="'".$column1[$cur['Field']]."',";//This Marks the Handle so when we Import we could locate which Field is the Handle by Preg Match
	 }else {
	 if (in_array($cur['Field'],$TextField)){
	  $Insert.=$column1[$cur['Field']].",";
	  }else {
	  $Insert.="'".$column1[$cur['Field']]."',";
	  }} }}
	 
	}//End getting the Field Names
	}//End of Getting Rows from Formulize table
	}//End of FID Values loop
	}
}
	//Function to Write the Insert Statements
	Function Export_All()
	{
		//First Create Applications,Fourms and The Link them
		Create_Insert_Statments("_formulize_applications");
		//echo "<br/>";
		Create_Insert_Statments("_formulize_id");
		//echo "<br/>";
		Create_Insert_Statments("_formulize_application_form_link");
		//echo "<br/>";
		Create_Insert_Statments("_formulize");
		
		Write_To_File();
	
	}
	
	function Fourm_Elements ($fid=null,$Uniq=null,$ele_form=null){
	        global $result;//To be Used in the Function
			$result =array();
	
	if (!empty($fid)){
		$table=Prefix;
		$table.='_formulize';
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("select * from ".$table." where id_form= :id") ;
        $Query->bindValue(":id",$fid);
        $Query->execute();
		while ($row = $Query->fetch(\PDO::FETCH_OBJ))
        {
            $result[]=(array)$row;
        }
	
	}else {
	
	switch ($Uniq) {
		case 1:
		$table=Prefix;
		$table.='_formulize';
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where ele_id= :id") ;
        $Query->bindValue(":id",$ele_form);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		break;
	case 2:
		$table=Prefix;
		$table.='_formulize';
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where ele_handle= :handlename") ;
        $Query->bindValue(":handlename",$ele_form);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		break;
		}
		
	}
	return $result;
	}
	 function Application_Fourm_Links($appid, $Uniq=null)
    {
	/*
	1)Function to get the Forums/Links that's Linked with the requested AppID from URL. 
	2)if Uniq field is passed it checks the ID by returning the Count of this ID in the Table this is used by the checking function when it checks if the ID
	is in use or not.
	*/
		if (empty($Uniq)){
		$table=Prefix;
		$table.='_formulize_application_form_link';
        $result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("select * from ".$table." where appid= :id") ;
        $Query->bindValue(":id",$appid);
        $Query->execute();
		 //$row = $Query->fetch(PDO::FETCH_OBJ);
       // $result=(array)$row;
		//To Pass Everything as a Single Array 
		while ($row = $Query->fetch(\PDO::FETCH_OBJ))
        {
            $result[]=(array)$row;
        }
        
       
        $a= $Query->rowCount();
		}else {
		if ($Uniq==1):{
		$table=Prefix;
		$table.='_formulize_application_form_link';
        $result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where linkid= :id") ;
        $Query->bindValue(":id",$appid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		}elseif ($Uniq==2):
		{
		$table=Prefix;
		$table.='_formulize_applications';
        $result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where appid= :id") ;
        $Query->bindValue(":id",$appid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		}elseif  ($Uniq==3):
		{
		$table=Prefix;
		$table.='_formulize_id';
        $result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where id_form= :id") ;
        $Query->bindValue(":id",$appid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		}endif;
		
		
		
		} return $result; }
		
	//Function to get the App by ID \\\\Its  Use for Debugging \\\When the File is clear from all errors will be taken out as its not a core function in any of the Funcitons
	Function Get_Link()
	{
	$appid = intval($_GET['aid']);
	$Links=Application_Fourm_Links($appid);
	foreach ($Links as $key => $column) {
	
	 echo $key . ':    AppID:' . $column['appid'].'     Fourm ID:'.$column['fid'].'<br/>';
	 echo "+++++++++++++++++++++++++++++++++++++".$key."++++++++++++++++++++++++++++++++++++++++++ </br>";
	 
	 $getapp=Get_App($column['appid']);
	 foreach ($getapp as $key1 => $column1) {
	 echo $key1 . ':    AppID:' . $column1['name'].'     Description:'.$column1['description'].'<br/>';
	 }
	 
	  echo "+++++++++++++++++++++++++++++++++++++Forum++++++++++++++++++++++++++++++++++++++++++ </br>";
	 $getForm=Get_Form($column['fid']);
	 foreach ($getForm as $key2 => $column2) {
	 echo $key2 . ':    FormID:' . $column2['desc_form'].'     Fourm Handle :'.$column2['form_handle'].'<br/>';
	 }
	 
	 echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ </br>";
	  
	}
	}
	//Function to get the Application Rows by AppID and Also 
	Function Get_App($appID, $Uniq=null)
	{
	
	$app_table=Prefix;
	$app_table.='_formulize_applications';
	$result =array();
	$conn=new Connection ();
	 $Query=$conn->connect()->prepare("select * from ".$app_table." where appid= :id") ;
		$Query->bindValue(":id",$appID);
        $Query->execute();
		while ($row = $Query->fetch(\PDO::FETCH_OBJ))
        {
            $result[]=(array)$row;
			
        }
		//var_dump (isset ($result));
		
        return $result;
	}
	//Function to get the Form Rows
	function Get_Form($formID, $Uniq=null)
	{
	$Form_table=Prefix;
	$Form_table.='_formulize_id ';
	$result =array();
	$conn=new Connection ();
	 $Query=$conn->connect()->prepare("select * from ".$Form_table." where id_form= :id") ;
     $Query->bindValue(":id",$formID);
     $Query->execute();
	 while ($row = $Query->fetch(\PDO::FETCH_OBJ))
        {
            $result[]=(array)$row;
        }
        return $result;
	
	}
	Function Get_FieldNames($tablename)
	{
	//This Function get the Field names form the DB /This is Used when Creating the Insert statements.
	$Form_table=Prefix;
	$Form_table.=$tablename;
	$result =array();
	$conn=new Connection ();
	$Query=$conn->connect()->prepare("SHOW COLUMNS FROM ".$Form_table."");
	 $Query->execute();
	while ($row = $Query->fetch(\PDO::FETCH_OBJ))
        {

            $result[]=(array)$row;

        }
		return $result;
	}

	function Write_To_File()
	{
	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=ApplicationID".$_GET['aid']."");
	header("Pragma: no-cache");
	header("Expires: 0");
	}
	//This Functions replaces the Prefix word  in the SQL insert statements with the DB Prefix
	function replaces_Prefix_in_file ($filename)
	{
	$str=implode("\n",file(".$filename."));
	$fp=fopen(".$filename.",'w');
	//replace Prefix word file string with the current DB Prefix 
	$str=str_replace('Prefix',Prefix,$str);
	fwrite($fp,$str,strlen($str));
	}
	function Creat_Applications($filename)
	{
	/*
	This Function consists of 3 checks when it Imports the Insert statements ; App,fourm and Link for Inserting the statements.
	1)In App, it reads the Line then strips the App ID and check if this ID is currently in Use or not .If not then it strips out the ID from the Insert statement and allow the 
	auto Increment to assign it a new ID and Update the Insert statements .
	2)In Form, it does the same thing as the App but also changes the field desc_form .It Adds a C_ to the Field name if ID already exists in the Table.
	
	3)The Link  also does the same thing ,first it needs to check if the ID is exists in the Table or not.However,in both cases they follow the same procedure.After checking the ID it checks if the APP_ID has been updated or not if yes then updates the Insert Statement and the same goes for the Form. 
	*/
	//Those  variables will store the New AppID/Form ID  if Needed.
	global $APP_ID_Replace;//To store the old ID with the New one.The Format is OLDAPPID:NewAPPID.If AppID is not updated then the variable stay empty.
	global $Form_ID_Replace; //Every time ID is replaced the old FormID will be pushed here to be able to match it in the Table name Form_Mapping  
	$Form_ID_Replace=array();
    //Reads the File 
	$file = fopen(".$filename.", "r");
	//while (!feof($file)) {
	$getlines = fgets($file);
	 //echo $getlines. "<br />";
	$get_line=explode(";",$getlines);
	//print_r( $get_line);
	 global $x2x;
   $x2x=0;
	foreach ($get_line as $statement)
   {

   preg_match('/\( \w*\,\w*/', $statement, $matches);
  // echo $statement;
  // }}fclose($file);}
	if(strstr($statement, "_formulize_applications")) {
	preg_match('/\(\d*\,/', $statement, $matches);//To get Any Digit number .Not just 2 digit number as the old preg match did
	$x1=explode('(',$matches[0]);
	$x2=explode(',',$x1[1]);
	if (Check_Uniquines ($x2[0],2)==0) //If Unique then no need to Update the ID
	{
	echo"New App <br/>";
	$conn=new Connection ();
	echo $statement;
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	
	}else {
	echo"App Exists Updating ID <br/>";
	//Do this $APP_ID_Replace =$matches1[0].New[];
	$Se=preg_replace('/\(\d*\,/', "('',", $statement);
	print_r($Se);
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($Se);
	$Query->execute();
	//This Query the Formulize_Application to get the Updated AppID  and stores it in App_ID_Replace in order to be used when it creates the Links 
	$Query=$conn->connect()->prepare("SELECT max(appid) FROM ".Prefix."_formulize_applications");
	$Query->execute();
	$result=$Query->fetch(\PDO::FETCH_ASSOC);
	$APP_ID_Replace=$x2[0].":".$result['max(appid)']; 
	
    }} 
	//The Formalize Updates to Fields in the INsert statement if needed.It Updates the ID and the Desc_From 
	if(strstr($statement, "_formulize_id")) {
	echo "Here";
	preg_match('/\(\d*\,/', $statement, $matches);
	$s=explode("(",$matches[0]);//Stupid Preg-match :@:@:@ just to get the number between ( and ,
	$s6=explode(",",$s[1]);
	//echo $s2[0];
	//$x1=explode('(',$matches[0]);
	echo $s6[0];
	if (Check_Uniquines($s6[0],3)==0) //If Unique then no need to Update the ID
	{
	echo"New Form <br/>";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	echo $statement;
	
	}else {
	echo"Forom Exist Updating Row ID and Unique Field <br/>";
	preg_match ('/\w*VALUES \(\d*\,\'\w*\'/',$statement,$stripp);
	preg_match('/\'\w*\'/',$stripp[0],$desc_form);
	$C_desc_form=explode("'",$desc_form[0]);//This Adds a C_ to the Desc_Form because of Uniq. constraint 
	$C_desc_form[1].="'";
	//$sw=.'".$i."'";
	$st="VALUES ('','C_".$C_desc_form[1];
	$Se=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\'\w*\'/', $st, $statement);
	echo $Se;
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($Se);
	$Query->execute();
	$Query1=$conn->connect()->prepare("SELECT max(id_form) FROM ".Prefix."_formulize_id");
	$Query1->execute();
	//This Query the Formulize_ID to get the Updated FormID  and gets the Updated  ID to create the Form ID mapping in DB to be used later on by the Link 
	$result=$Query1->fetch(\PDO::FETCH_ASSOC);
	$Query=$conn->connect()->prepare("INSERT INTO form_mapping VALUES (:id ,:id2)");
	$Query->bindValue(":id",$s6[0]);
	$Query->bindValue(":id2",$result['max(id_form)']);
	$Query->execute();
	//The link uses the array to check if the ID that exists in the Link statement has been updated or not 
	array_push($Form_ID_Replace,$s6[0]);
	print_r($Form_ID_Replace);
	echo $s6[0];
	}
	++$i;
    }
	//}fclose($file);}/*}}fclose($file);}
	//First, it checks if the Link Id is in use or not.If not then no need to update the Form ID.Second,It checks if the AppID has been updated by checking  the APP ID variable.If it's empty then it doesn't update the APP ID in the Link insert statement.Third,It checks if the Form ID that's in the link insert statement has been updated or not.If the ID is updated it will be included in the Form_ID_Replace array.After,if gets the Old ID it query the Form_Map table to check the new ID that corresponds to the OLD ID and then updates the Insert Statement and Insert the new Row. 
	if(strstr($statement, "_formulize_application_form_link")) {
	echo "In link";
	preg_match('/\(\d*\,/', $statement, $matches);
	$x1=explode('(',$matches[0]);
	//echo $matches[0];
	preg_match ('/\,\d*\)/',$statement,$stripp);
	$st=explode(')',$stripp[0]);
	$Form_ID_Check=explode(',',$st[0]);//This Gets the FID from the Statement it self and runs a check when it enters one of the Ifs
	echo $Form_ID_Check[1];
	//echo (Check_Uniquines ($x1[1],1));
	if (Check_Uniquines ($x1[1],1)==0)
	{ /////////

	echo"Unique";
	if (empty($APP_ID_Replace))
	{ 
		//Working ///:)
		if (!in_array($Form_ID_Check[1],$Form_ID_Replace)){
		$conn=new Connection ();
		$Query=$conn->connect()->prepare($statement);
		$Query->execute();}
		else{  
		//Check If the //Need a Check Mechanism for The Update//Changeeeeeeeeee the Array [Done]
		echo"Yes Form Update";
		$result =array();
		$conn=new Connection ();
		
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss2=preg_replace('/\,\d*\)/', ",".$result['N_FormID'].")",$statement );
		$Query=$conn->connect()->prepare($ss2);
		$Query->execute();
		
	}
	}else {
	//$Form_ID_Replace=90;
	echo "Not Empty AppID";
	$aPP_id_2=explode (":",$APP_ID_Replace);
	$ss2=preg_replace('/\,\d*\,/', ",".$aPP_id_2[1].",", $statement);
	echo $ss2."<br/>";
	if (!in_array($Form_ID_Check[1],$Form_ID_Replace)){
	echo"Form Empty";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($ss2);
	$Query->execute();
	}else
	{
	$result =array();
		$conn=new Connection ();
        $Fid1=$Form_ID_Replace[$x2x];
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss3=preg_replace('/\,\d*\)/', ",".$result['N_FormID'].")",$ss2 );
		$Query=$conn->connect()->prepare($ss3);
		$Query->execute();
		
	}
	}
	}
	else {
	//Use the Auto Increment  
	$ee=preg_replace('/\(\d*\,/', "('',", $statement);
	/////////
	echo"Not Unique";
	//For Now it will be Null
	//This will store the New App ID if Needed
		if (empty($APP_ID_Replace))
	{ 
	echo $APP_ID_Replace;
		//Working ///:)
		if (!in_array($Form_ID_Check[1],$Form_ID_Replace)){
		$conn=new Connection ();
		$Query=$conn->connect()->prepare($ee);
		$Query->execute();
		}
		else{  
		echo"Yes Form Update";
		$result =array();
		$conn=new Connection ();
		$Fid1=$Form_ID_Replace[$x2x];
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		++$x2x;
		$ss2=preg_replace('/\,\d*\)/', ",".$result['N_FormID'].")",$ee );
		$Query=$conn->connect()->prepare($ss2);
		$Query->execute();
		
	}
	}else {
	//$Form_ID_Replace=90;
	
	$aPP_id_2=explode (":",$APP_ID_Replace);
	echo $APP_ID_Replace;
	$ss2=preg_replace('/\,\d*\,/', ",".$aPP_id_2[1].",", $ee);
	echo $ss2."<br/>";
	if (!in_array($Form_ID_Check[1],$Form_ID_Replace)){
	echo"Form Empty";
	$conn=new Connection ();
	echo $ss2;
	$Query=$conn->connect()->prepare($ss2);
	$Query->execute();
	}else
	{
	$result =array();
		$conn=new Connection ();
		echo"New Form Check";
		//echo $Form_ID_Replace[0];
        $Fid1=$Form_ID_Replace[$x2x];//Get the Rows 
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		++$x2x;
		$ss3=preg_replace('/\,\d*\)/', ",".$result['N_FormID'].")",$ss2 );
		$Query=$conn->connect()->prepare($ss3);
		$Query->execute();
		
	}
	}
	}
	}
	if(strstr($statement, "_formulize ")) {
	preg_match('/\( \w*\,\w*/', $statement, $matches);
	preg_match('/\\^\'\w*\'\^/', $statement, $matches1);//To get the Ele Handle 
	//echo $statement;
	//print_r($statement);
	//echo $statement;\^\'\w*\'\^
	$ele_Handle_Text=explode("^",$matches[0]);//To Get the Ele Handle As Text 
	$ele_Handle_Int=explode("'",$ele_Handle_Text[1]); //To get Handle as Integear 
	$ele_ID=explode(',',$matches[0]);
	$Flag;//This Flag will be Used to determine if the ee_Handle is Text or Integer 
	if ($ele_ID[1]==$ele_ID[1]){$Flag=1;}else {$Flag=0;} //If Flag =1 then the Ele_handle is Text
	Check_Uniquines(null,5,$ele_Handle);
	//echo $ele_ID[1];
	$Form_ID_Check=explode('(',$ele_ID[0]);
	//echo $Fourm_ID[1];
	$statement=str_replace('&', ';',$statement);//This to bring back the ; to the Sterilized Array after we broke the statements with ; 
	if (Check_Uniquines($ele_ID[1],4)==0)
	{
	if (!in_array($Form_ID_Check[1],$Form_ID_Replace))
	{
	echo "IN Check Uniq";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	}else {
	echo "IN Check FID";
	$result =array();
	$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		++$x2x;
		//print_r($result);
		$ss3=preg_replace('/\w*VALUES \( \d*\,/', "VALUES (".$result['N_FormID'].",",$statement);
		$Query=$conn->connect()->prepare($ss3);
		$Query->execute();
	}
	
	
	}else {
	echo "Not Unique  here";
	$ee=preg_replace('/\w*VALUES \( \d*\,\d*/', "VALUES (".$Form_ID_Check[1].",''", $statement);
	echo $ee;
	//Replace Auto Increment //
	if (!in_array($Form_ID_Check[1],$Form_ID_Replace))
	{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($ee);
	$Query->execute();
	echo "Not";
	}else {
	echo "Here FID";
	$result =array();
	$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Form_ID_Check[1]);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		++$x2x;
		//print_r($result);
		$ss3=preg_replace('/\w*VALUES \( \d*\,/', "VALUES (".$result['N_FormID'].",",$ee );
		//echo $ee;
		$Query=$conn->connect()->prepare($ss3);
		$Query->execute();
	//Replace FID in the INsert Statment
	
	}

	}
	
	
	}
	}//End of the IF Get Statment
	//}//End While Loop
	fclose($file);
	Truncate_Map();//Remove Mapping in DB//
	//Not Empty File
	}

	Function Import ()
	{
				//Handles all the Functions
				require_once "upload.php";
				$filename = '/Upload/'.UploadFile;
				//As soon it loads the file it changes the PREFIX word in the file to the current DB Prefix.
				replaces_Prefix_in_file ($filename);
				Creat_Applications($filename);
				
	
	}
	Function Check_Uniquines ($ID,$field,$Text=null)
	{
	/*
	This Function Checks if the ID are Unique or not .If Unique then it will return 0.
	*/
	$Check;
	switch ($field){
	case 1:
	$Check=Application_Fourm_Links($ID,1);
	break;
	case 2:
	$Check=Application_Fourm_Links($ID,2);
	break;
	case 3:
	$Check=Application_Fourm_Links($ID,3);
	break;
	case 4:
	$Check=Fourm_Elements (null,1,$ID);
	break;
	case 5:
	$Check=Fourm_Elements (null,2,$Text);
	break;
	}
	return $Check['num'];
	}
	
	Function Truncate_Map()//This Function is Used every time a file is Imported and its Form ID got updated .This to avoid any keys mismatch in the Future 
	{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare("TRUNCATE form_mapping");
	$Query->execute();
	}


	//Table Names
	//_formulize_id 
	//_formulize_applications
	//_formulize_application_form_link
