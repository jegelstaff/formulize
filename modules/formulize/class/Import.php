<?php
include  'PDO_Conn.php';//Include the Connection File
Import();

	//****************************************End Of Export Functions**************************************************************************************************************************
	//Import Functions Begins Below
	function Creat_Applications()
	{
	get_Groups();
	/*
	This Function consists of 3 checks when it Imports the Insert statements ; App,fourm and Link for Inserting the statements.
	1)In App, it reads the Line then strips the App ID and check if this ID is currently in Use or not .If not then it strips out the ID from the Insert statement and allow the 
	auto Increment to assign it a new ID and Update the Insert statements .
	2)In Form, it does the same thing as the App but also changes the field desc_form .It Adds a C_ to the Field name if ID already exists in the Table.
	
	3)The Link  also does the same thing ,first it needs to check if the ID is exists in the Table or not.However,in both cases they follow the same procedure.After checking the ID it checks if the APP_ID has been updated or not if yes then updates the Insert Statement and the same goes for the Form. 
	*/
	//Those  variables will store the New AppID/Form ID  if Needed.
	global $APP_ID_Replace;//To store the old ID with the New one.The Format is OLDAPPID:NewAPPID.If AppID is not updated then the variable stay empty.
	global $Element_ID; //Every time ID is replaced the old FormID will be pushed here to be able to match it in the Table name Form_Mapping  
	$Element_ID=array();
	session_start();
	$getlines=$_SESSION['file'];
	$get_line=explode(";",$getlines);
	foreach ($get_line as $statement)
   {

   preg_match('/\( \w*\,\w*/', $statement, $matches);

	if(strstr($statement, "_formulize_applications")) {
	preg_match('/\(\d*\,/', $statement, $matches);//To get Any Digit number .Not just 2 digit number as the old preg match did
	$x1=explode('(',$matches[0]);
	$x2=explode(',',$x1[1]);
	if (Check_Uniquines ($x2[0],2)==0) //If Unique then no need to Update the ID
	{
	echo"New App <br/>";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	}else {
	echo"App Exists Updating ID <br/>";
	//Do this $APP_ID_Replace =$matches1[0].New[];
	$Se=preg_replace('/\(\d*\,/', "('',", $statement);
	$NewID=getlastID ($Se);
	$APP_ID_Replace=$x2[0].":".$NewID; 
    }} 
	//The Formalize Updates to Fields in the INsert statement if needed.It Updates the ID and the Desc_From 
	if(strstr($statement, "_formulize_id")) {
	global $NewID;
	preg_match('/\(\d*\,/', $statement, $matches);
	$s=explode("(",$matches[0]);//Stupid Preg-match :@:@:@ just to get the number between ( and ,
	$s6=explode(",",$s[1]);
	
	formIdMap(1,"FormOri",$s6[0],2); // Used For Warning
	if (Check_Uniquines($s6[0],3)==0) //If Unique then no need to Update the ID
	{
	//This Is a Special Case :If a New ID is Inserted but the desc_form is not unique in DB
	preg_match ('/\w*VALUES \(\d*\,\'\w*\'/',$statement,$stripp);
	preg_match('/\'\w*\'/',$stripp[0],$desc_form);
	$C_desc_form=explode("'",$desc_form[0]);
	if (Check_Uniquines($C_desc_form[1],8)!=0){
	$x=1;
	$C_desc_form[1].="'";
	$st="VALUES (".$s6[0].",'C_".$C_desc_form[1];
	$statement=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\'\w*\'/', $st, $statement);
	echo"New Form but the Form Description is Not Unique Change the Name to C_".$C_desc_form[1]." <br/>";}
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	echo $statement;
	if ($x == null) { echo"New Form <br/>";} 
	//
	}else {
	echo"Froum Exist Updating Row ID and Unique Field <br/>";
	preg_match ('/\w*VALUES \(\d*\,\'\w*\'/',$statement,$stripp);
	preg_match('/\'\w*\'/',$stripp[0],$desc_form);
	$C_desc_form=explode("'",$desc_form[0]);//This Adds a C_ to the Desc_Form because of Uniq. constraint 
	$C_desc_form[1].="'";
	$st="VALUES ('','C_".$C_desc_form[1];
	$Se=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\'\w*\'/', $st, $statement);
	//echo $Se;
	$NewID=getlastID ($Se);
	formIdMap(1,"Form",$s6[0],$NewID);
	echo "ID Replaced =".$s6[0]. ":" .formIdMap(2,"Form",$s6[0])."<br/>";
	}
    }
	//First, it checks if the Link Id is in use or not.If not then no need to update the Form ID.Second,It checks if the AppID has been updated by checking  the APP ID variable.If it's empty then it doesn't update the APP ID in the Link insert statement.Third,It checks if the Form ID that's in the link insert statement has been updated or not.If the ID is updated it will be included in the Form_ID_Replace array.After,if gets the Old ID it query the Form_Map table to check the new ID that corresponds to the OLD ID and then updates the Insert Statement and Insert the new Row. 
	if(strstr($statement, "_formulize_application_form_link")) {
	global $NewID;
	preg_match('/\(\d*\,/', $statement, $matches);
	$x1=explode('(',$matches[0]);
	//echo $matches[0];
	preg_match ('/\,\d*\)/',$statement,$stripp);
	$st=explode(')',$stripp[0]);
	$Form_ID_Check=explode(',',$st[0]);//This Gets the FID from the Statement it self and runs a check when it enters one of the Ifs
	if (Check_Uniquines ($x1[1],1)==0)
	{ /////////
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	echo "New Link Form/App  <br/>";
	}
	else {
	//Use the Auto Increment 
	echo "Updating the Link ID  <br/>";
	$ee=preg_replace('/\(\d*\,/', "('',", $statement);
	$NewID=getlastID ($ee);
	}
	//Post Process To Update the Link with the Latest App and Form
	$id = (null == $NewID) ? $x1[1] : $NewID;
	if (!empty($APP_ID_Replace)) { echo "Here";
	$aPP_id_2=explode (":",$APP_ID_Replace);
	Post_Process ($id,$aPP_id_2[1],null,2,2);
	echo "Updating Fourm Link APPID To =".$aPP_id_2[1]."<br/>";
	}
	if (formIdMap(2,"Form",$Form_ID_Check[1])!=null ) {
	$NewFormID=formIdMap(2,"Form",$Form_ID_Check[1]);
	Post_Process ($id,$NewFormID,null,2,3);
	echo "Updating Fourm Link FID from =".$Form_ID_Check[1]." to ".$NewFormID."<br/>";
	}
	}
	if(strstr($statement, "_formulize ")) {
	
	preg_match('/\(\d*\,\d*/', $statement, $matches);
	preg_match('/\'\^\w*\^\'/', $statement, $matches1);//To get the Ele Handle 
	$ele_Handle_Text=explode("^",$matches1[0]);//To Get the Ele Handle As Text 
	$ele_ID=explode(',',$matches[0]);
	global $Flag;//This Flag will be Used to determine if the ee_Handle is Text or Integer 
	$Flag=is_numeric($ele_Handle_Text[1]);//This Flag will be Used to determine if the ele_Handle is Text or Integer Flag=1 if Yes or Null
	$Form_ID_Check=explode('(',$ele_ID[0]);
	$statement=str_replace('&', ';',$statement);//This to bring back the ; to the Sterilized Array after we broke the statements with ; 
	formIdMap(1,"ElementOri",$ele_ID[1],2); //Used For Warning 
	//print_r($ele_ID);echo Check_Uniquines($ele_ID[1],4);
	if (Check_Uniquines($ele_ID[1],4)==0)//Check IF Ele_ID is Unique or not 
	{$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	if (formIdMap(2,"Form",$Form_ID_Check[1])!=null ) {
	$NewFormID=formIdMap(2,"Form",$Form_ID_Check[1]);
	Post_Process ($ele_ID[1],$NewFormID,null,2,4);
	array_push($Element_ID,$ele_ID[1]);
	echo "Ele_ID Unique but Updating FID in Forumulize from =".$Form_ID_Check[1]." to ".$NewFormID."<br/>";
	}
	echo "New Formulize row <br/>";
	Post_Process ($ele_ID[1],$ele_Handle_Text[1],$Flag,1); //Example Of Post Process
	}else {
	echo "Updating the Ele_ID in Formulize  <br/>";
	$ee=preg_replace('/\w*VALUES \(\d*\,\d*/', "VALUES (".$Form_ID_Check[1].",''", $statement);
	$NewID=getlastID ($ee);
	array_push($Element_ID,$NewID);
	formIdMap(1,"Element",$ele_ID[1],$NewID);
	//AddMapp to Element;
	if (formIdMap(2,"Form",$Form_ID_Check[1])!=null ) {
	$NewFormID=formIdMap(2,"Form",$Form_ID_Check[1]);
	Post_Process ($NewID,$NewFormID,null,2,4);
	}
	if (!empty($Flag)){Post_Process ($NewID,$NewID,$Flag,1); } else {Post_Process ($NewID,$ele_Handle_Text[1],$Flag,1);}//Example Of Post Process,this Is Different Because we Update the Ele_Handle Field with New ID
	}
	
	}
	if(strstr($statement, "_formulize_framework")) {

	preg_match('/\(\d*\,/', $statement, $matches);
	$frame_ID=explode('(',$matches[0]);
	$frame_ID=explode(',',$frame_ID[1]);
	echo $frame_ID[0];
	if (Check_Uniquines($frame_ID[0],6)==0)
	{
	echo "New Frameworks <br/>";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	}
	else 
	{
	$ee=preg_replace('/\(\d*\,/', "('',", $statement);
	$NewID=getlastID ($ee);
	formIdMap(1,"Frame",$frame_ID[0],$NewID);
	echo "Updating the Frame ID  in Frameworks from = ".$frame_ID[0]." to ".$NewID."<br/>";
	}
	}
	if(strstr($statement, "_1formulize_framework_links")) {
	$statement=str_replace('_1', '_',$statement);
	preg_match('/\(\d*,\d*,\d*,\d*,\d*,\d*/', $statement, $matches);//This will Get all the 6 Fields 
	$frame_links=explode('(',$matches[0]);
	$frame_links=explode(',',$frame_links[1]); //Frame links [0]=link ID ,1=Frame ID,2=Form 1,3=Form 2, 4=Element 1 ,5=Element 2
	if (Check_Uniquines($frame_links[0],7)==0)
	{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	echo "New Frame Link Row With ID :".$frame_links[0]."<br/>";
	}
	else
	{
	$ee=preg_replace('/\(\d*\,/', "('',", $statement);
	$Newid=getlastID ($ee);
	echo "Updating Frame Link ID :".$frame_links[0]." to ".$Newid."<br/>";
	formIdMap(1,"Framelinks",$frame_links[0],$NewID);
	}
	//Post Process// To Update the Fields
     $id = (null == $Newid) ? $frame_links[0] : $Newid;//To Decide if ID has been Updated or Not to Avoid Redundancy Above 
	 if (formIdMap(2,"Frame",$frame_links[1])!=null){ //Updates the Frame ID
		$FrID=formIdMap(2,"Frame",$frame_links[1]);
		Post_Process ($id,$FrID,null,2,6);
		echo "Updating Frame ID :".$frame_links[1]." to ".$FrID."<br/>";
	 }
	  if (formIdMap(2,"Form",$frame_links[2])!=null){ // Updates Form 1
		$ForID1=formIdMap(2,"Form",$frame_links[2]);
		Post_Process ($id,$ForID1,null,2,7);
		echo "Updating Form 1 ID :".$frame_links[2]." to ".$ForID1."<br/>";
	 }
	   if (formIdMap(2,"Form",$frame_links[3])!=null){ // Updates Form 2
		$ForID2=formIdMap(2,"Form",$frame_links[3]);
		Post_Process ($id,$ForID2,null,2,8);
		echo "Updating Form 2 ID :".$frame_links[3]." to ".$ForID2."<br/>";
	 }
	   if (formIdMap(2,"Element",$frame_links[4])!=null){ // Updates Elements 1
		$Element1=formIdMap(2,"Element",$frame_links[4]);
		Post_Process ($id,$Element1,null,2,9);
		echo "Updating Element 1 ID :".$frame_links[4]." to ".$Element1."<br/>";
	 }
	 	if (formIdMap(2,"Element",$frame_links[5])!=null){ // Updates Elements 2
		$Element2=formIdMap(2,"Element",$frame_links[5]);
		Post_Process ($id,$Element2,null,2,10);
		echo "Updating Element 2 ID :".$frame_links[5]." to ".$Element2."<br/>";
	 }

	 //Alert The User If Form,Elements Are not Part of the App 
	 if (formIdMap(2,"FormOri",$frame_links[2])==null || formIdMap(2,"FormOri",$frame_links[3])==null ){ echo "Warning Form Doesn't Exist in Export <br/>";} //Alert For Form
	 if (formIdMap(2,"ElementOri",$frame_links[4])==null || formIdMap(2,"ElementOri",$frame_links[5])==null ){ echo "Warning Element Doesn't Exist in Export <br/>";} //Alert For Element
	}
	if(strstr($statement, "_groups")){

	//The Only ID it will Insert is the One that's in Group_Insert Array//It checks that's Included in Array and then Inserts it
	preg_match('/\(\d*\,/', $statement, $matches);
	$groups_ID=explode('(',$matches[0]);
	$groups_ID=explode(',',$groups_ID[1]);
	/*
	if (formIdMap(2,"Group_Map",$groups_ID[0])!=null){
	echo formIdMap(2,"Group_Map",$groups_ID[0])."    :  In Groups <br/>";}
		if (formIdMap(2,"Group_Ignore",$groups_ID[0])!=null){
	echo formIdMap(2,"Group_Ignore",$groups_ID[0])."   :  In Ignore <br/>";}
		if (formIdMap(2,"Group_Insert",$groups_ID[0])!=null){
	echo formIdMap(2,"Group_Insert",$groups_ID[0])." :    In Insert <br/>";}
	*/

	if (formIdMap(2,"Group_Insert",$groups_ID[0])!=null){
	
	if (Check_Uniquines($groups_ID[0],9)==0)
	{
	echo "New Groups Has Been Inserted  <br/>";
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	}
	else 
	{
	$ee=preg_replace('/\(\d*\,/', "('',", $statement);
	$NewID=getlastID ($ee);
	formIdMap(1,"Group_Map_Auto",$groups_ID[0],$NewID);
	echo "Updating the Group ID  in Groups from = ".$groups_ID[0]." to ".$NewID."<br/>";
	}
	}
	
	}
	ser($Element_ID); //To Update The Ele_Values
	}
	}
	Function Post_Process ($ID,$Update,$Flag=null,$switch,$table=null)
	{
	//Table Array Holds the Fields and table name that Will be Updated
	$tables=array (2=>'_formulize_application_form_link/`appid`/linkid',3=>'_formulize_application_form_link/`fid`/`linkid`',4=>'_formulize/`id_form`/`ele_id`'
	,5=>'_formulize/`ele_value`/`ele_id`',6=>'_formulize_framework_links/`fl_frame_id`/`fl_id`',7=>'_formulize_framework_links/`fl_form1_id`/`fl_id`',
	8=>'_formulize_framework_links/`fl_form2_id`/`fl_id`',9=>'_formulize_framework_links/`fl_key1`/`fl_id`',10=>'_formulize_framework_links/`fl_key2`/`fl_id`');
	$fields=explode ('/',$tables[$table]);
	$conn=new Connection ();
	switch ($switch) {
	case 1:
	if ($Flag==1){
		$Query=$conn->connect()->prepare("UPDATE ".Prefix."_formulize SET `ele_handle`= :Update where `ele_id`= :id");	
		}else {
		if (Check_Uniquines($Update,5)==0){
		$Query=$conn->connect()->prepare("UPDATE ".Prefix."_formulize SET `ele_handle`=:Update where `ele_id`= :id");
	}else {
		$Update="Imp_".$Update;
		$Query=$conn->connect()->prepare("UPDATE ".Prefix."_formulize SET `ele_handle`=:Update where `ele_id`= :id");
	}
		}
		break;
	case 2:
	$sql="UPDATE ".Prefix."".$fields[0]." SET ".$fields[1]."= :Update where ".$fields[2]."= :id";
		$Query=$conn->connect()->prepare($sql);
		break;
	}
		$Query->bindValue(':Update',$Update);
		$Query->bindValue(':id',$ID);
		if ($fields[1]=='fl_form2_id'){echo $sql;}
		$Query->execute();	
	}
	Function Import ()
	{				//Handles all the Functions
	Creat_Applications();
	}
	Function Check_Uniquines ($ID,$field)
	{
	/*
	This Function Checks if the ID are Unique or not .If Unique then it will return 0.
	*/
	//Add Table Name/Field to array 
	$Check;
	$tables=array (1=>'_formulize_application_form_link/`linkid` ',2=>'_formulize_applications/`appid`',3=>'_formulize_id/`id_form`'
	,4=>'_formulize/ele_id',5=>'_formulize/`ele_handle`',6=>'_formulize_frameworks/`frame_id`',7=>'_formulize_framework_links/`fl_id`',8=>'_formulize_id/`desc_form`',9=>'_groups/`groupid`');
	$fields=explode ('/',$tables[$field]);
	$conn=new Connection ();
	//echo "SELECT COUNT( * ) AS num from ".Prefix."".$fields[0]." where ".$fields[1]."= ".$ID."";
	$Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".Prefix."".$fields[0]." where ".$fields[1]."= :id") ;
	$Query->bindValue(":id",$ID);
    $Query->execute();
	$Check=$Query->fetch(\PDO::FETCH_ASSOC);
	return $Check['num'];
	}

	Function getlastID ($statment)
	{ //This Is used because the default getlastID syntax doesn't work
	$sel=";select last_insert_id() as ID;";
	$statment.=$sel;
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statment);
	$Query->execute();
	$Query->nextRowset();
	$result=$Query->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $key) { $NewID=$key['ID'];}
	return $NewID;
	}
	function formIdMap($a,$t=null,$b=null,$c=null) 
{
	
STATIC $id_map; // Static to hold the array 
// formIdMap used to maintain the $id_map array
// Parameters: $a is the access type (0 for new,1 for add, 2 for query)
// $b = old id
// $c = new id
// $t = type
// if called with a=1, system expects  $b = old id & $c = new id
// if called with a=2 system expects $b = old id to search for
switch ($a)
   { 	
		case 1: // Add
		  {  if (isset($id_map[$t][$b]))
			    {
				 $ret="Rejected";
				 break;
				}
            else
                {			
				$id_map[$t][$b]=$c;
				break;
				}}
			break; 	
		case 2: // Search
			if (isset($id_map[$t][$b]))
				{
					$ret=$id_map[$t][$b]; 
				}
				break;
				
	} 
	return $ret; 
}
	function ser($AllElements)
	{
	$result=array();
	$conn=new Connection ();
	foreach ($AllElements as $keyid) { 
    $Query=$conn->connect()->prepare("select ele_type,ele_value from ".Prefix."_formulize where ele_id=:ID") ;
	$Query->bindValue(':ID',$keyid);
	$Query->execute();
	$result=$Query->fetch(\PDO::FETCH_ASSOC);
	$x=unserialize($result['ele_value']); 
	switch ($result['ele_type']){
	case 'text':
	case 'grid':
	if (array_key_exists(4, $x)){
	if (formIdMap(2,'Element',$x[4])!=null){
	$x[4]=formIdMap(2,'Element',$x[4]);
	Post_Process($keyid,serialize($x),null,2,5);
	}}
	break;
	case 'textarea':
	if (array_key_exists(3, $x)){
	if (formIdMap(2,'Element',$x[3])!=null){
	$x[3]=formIdMap(2,'Element',$x[3]);
	Post_Process($keyid,serialize($x),null,2,5);}}
	break;
	}
	}
	}
	function get_Groups()
	{
	
		$newGroups= $_SERVER["QUERY_STRING"];
		$Ignore=Array();
		$Insert=Array();
		$map=Array();
		$break=explode('&',$newGroups);
		foreach ($break as $k)
		{
		if(preg_match('/\d*=Ignore/',$k))  { preg_match('/\d*=Ignore/',$k,$ign); $ign=explode('=',$ign[0]);formIdMap(1,"Group_Ignore",$ign[0],22);}
		if (strpos($k, 'Ignore') == false && strpos($k,'New') == false){ preg_match('/\d*=\d*/',$k,$ign); $ign=explode('=',$ign[0]);formIdMap(1,"Group_Map",$ign[0],$ign[1]); } //Array Map
		if (preg_match(' /\d*=New/',$k)) {preg_match('/\d*=New/',$k,$map1); $map1=explode('=',$map1[0]);echo $map1[0]; formIdMap(1,"Group_Insert",$map1[0],11);} //It Might Be Array Map
		}
	}