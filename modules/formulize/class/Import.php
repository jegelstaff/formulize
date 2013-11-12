<?php
include  'PDO_Conn.php';//Include the Connection File
Import();
//$a =array ('Text',2,3,4,5);
//$b="Create table^'Text',2,3,4,5^Insert into Table_name values (1, '2013-09-22 09:48:07', '2013-09-22 09:48:07', 1, 1, '1', NULL),(2, '2013-09-22 09:48:07', '2013-09-22 09:48:07', 1, 1, '1', NULL);";
//Create_table($b);

//Import Functions Begins Below
function Creat_Applications()
{
	get_Groups();
	/*
	1)It Gets the Statement row ID.Checks if the ID is Unique or not .If not then removes the ID and let the row use the Auto-increment to assign an ID 
	2)The only process is done before the Import is to check if the row contains Table handle.If it's text ,also  Uniq or not in the DB ,so to avoid multiple duplicates after Insert.If it was ID then updated after we get the New ID 
	3)Usually post Process for each table is done after each insert .Unless the Post process involves a global process ,for example Ele_Value might contain groups ID so we need to wait till we are done with the Group Insert  
	*/
	//Those  variables will store the New AppID/Form ID  if Needed.
	global $APP_ID_Replace;//To store the old ID with the New one.The Format is OLDAPPID:NewAPPID.If AppID is not updated then the variable stay empty.
	global $Element_ID; //Every time ID is Inserted in the DB it will be pushed here to be used later on for the Ele_Filter settings and Ele_Value 
	global $Calc_ID; //To store the Calculation IDs
	$Calc_ID=array ();
	$Element_ID=array();
	session_start();
	$getlines=$_SESSION['file'];//Gets the String of inserts from grp33.php file 
	$get_line=explode(";",$getlines);
	foreach ($get_line as $statement)
	{
		if(strstr($statement, "Create table")) {
			Create_table($statement);
		}
		if(strstr($statement, "_formulize_applications")) {
			preg_match('/\(\d*\,/', $statement, $matches);//To get Any Digit number .Not just 2 digit number as the old preg match did
			$x1=explode('(',$matches[0]);
			$x2=explode(',',$x1[1]);
			if (Check_Uniquines ($x2[0],2)==0) //If Unique then no need to Update the ID
			{
				echo"New App <br/>";
				Insert ($statement);
			}else {
				echo"App Exists Updating ID <br/>";
				$Se=preg_replace('/\(\d*\,/', "('',", $statement);
				$NewID=getlastID ($Se);
				$APP_ID_Replace=$x2[0].":".$NewID; 
			}} 
		//The Formalize Updates to Fields in the INsert statement if needed.It Updates the ID and the Desc_From 
		if(strstr($statement, "_formulize_id")) {

			global $NewID;
			global $F3;
			preg_match('/\(\d*\,\"\&.*\&\"/', $statement, $matches);//Updated Preg_Match to get the Desc form that contains Space .
			$s=explode("(",$matches[0]);
			$s6=explode(",",$s[1]);
			$desc=explode("\"",$s6[1]);
			$desc=explode("&",$desc[1]);
			//Process Form Handle//
			preg_match('/\^.*\^/', $statement, $handle);//To get the Form Handle 
			$form_Handle_Text=explode("^",$handle[0]);//To Get the Ele Handle As Text 
			global $Flag;//This Flag will be Used to determine if the Form_Handle is Text or Integer 
			$Flag=is_numeric($form_Handle_Text[1]);//This Flag will be Used to determine if the Form_Handle is Text or Integer Flag=1 if Yes or Null
			if ($Flag==null)
			{
				if (Check_Uniquines($form_Handle_Text[1],10)!=0){
					$statement=preg_replace('/\^.*\^/',"Imp_".$form_Handle_Text[1]."", $statement);
					formIdMap(1,"Form_Handle",$form_Handle_Text[1],"Imp_".$form_Handle_Text[1]."");
				} else {$statement=preg_replace('/\^.*\^/',"".$form_Handle_Text[1]."", $statement);}//To Remove The ^^ if Unique
				$F3=12;
			}
			//Changes the desc If not Uniq. Once Instead Repeating the Code
			if (Check_Uniquines($desc[1],8)!=0){
				$st="VALUES (".$s6[0].",\"C_".$desc[1]."\"";
				$statement=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\"\&.*\&\"/', $st, $statement);
				echo "The Form Description is Not Unique Change the Name to C_".$desc[1]." <br/>";} 
			else  
			{$st="VALUES (".$s6[0].",\"".$desc[1]."\""; $statement=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\"\&.*\&\"/', $st, $statement);}//This Means Desc_Form is Uniq. so remove the && char from the Desc_from
			formIdMap(1,"FormOri",$s6[0],2); // Used For Warning
			if (Check_Uniquines($s6[0],3)==0) //If Unique then no need to Update the ID
			{
				//This Is a Special Case :If a New ID is Inserted but the desc_form is not unique in DB
				Insert ($statement);
				if ($F3==null){echo "Update";Post_Process ($s6[0],$s6[0],null,2,13);}//To remove the ^^ form the Field as when it was initially inserted it had ^id^
				//echo $statement;
				if ($x == null) { echo"New Form <br/>";} 
				//
			}else {
				echo"Froum Exist Updating Row ID and Unique Field <br/>";
				$st="VALUES ('',\"";
				$Se=preg_replace('/(\w*VALUES\w*)\s*\(\d*\,\"/', $st, $statement);
				echo $Se;
				$NewID=getlastID ($Se);
				//echo $NewID;
				formIdMap(1,"Form",$s6[0],$NewID);
				echo "ID Replaced =".$s6[0]. ":" .formIdMap(2,"Form",$s6[0])."<br/>";
				if ($Flag!=null) {
					formIdMap(1,"Form_Handle",$form_Handle_Text[1],$NewID);
					Post_Process ($NewID,"$NewID",null,2,13);}
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
			{ 
				Insert ($statement);
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
			//echo "Here";
			global $F2;
			preg_match('/\(\d*\,\d*/', $statement, $matches);
			preg_match('/\^.*\^/', $statement, $matches1);//To get the Ele Handle 
			$ele_Handle_Text=explode("^",$matches1[0]);//To Get the Ele Handle As Text 
			$ele_ID=explode(',',$matches[0]);
			global $Flag;//This Flag will be Used to determine if the ee_Handle is Text or Integer 
			$Flag=is_numeric($ele_Handle_Text[1]);//This Flag will be Used to determine if the ele_Handle is Text or Integer Flag=1 if Yes or Null
			$Form_ID_Check=explode('(',$ele_ID[0]);
			$statement=str_replace('&', ';',$statement);//This to bring back the ; to the Sterilized Array after we broke the statements with ; 
			formIdMap(1,"ElementOri",$ele_ID[1],2); //Used For Warning
			if ($Flag==null) //Ele_Handle Needs to be Done before Insert if it was a Text due to conflict might appear after the Insert
			{
				if (Check_Uniquines($ele_Handle_Text[1],5)!=0){
					$statement=preg_replace('/\^.*\^/',"\"Imp_".$ele_Handle_Text[1]."\"", $statement);
					formIdMap(1,"Element_Handle",$ele_Handle_Text[1],"Imp_".$ele_Handle_Text[1]."");
				} else {$statement=preg_replace('/\^.*\^/',"\"".$ele_Handle_Text[1]."\"", $statement);}//To Remove The ^^ if Unique
				$F2=2;//This is Used so not to Overwrite the above Handle text if the ID is Uniq.and ele _handle is a new Ele_ID
			}
			if (Check_Uniquines($ele_ID[1],4)==0)//Check IF Ele_ID is Unique or not 
			{
				Insert ($statement);
				if ($F2==null){echo "Update";Post_Process ($ele_ID[1],$ele_ID[1],null,2,14);}//To remove the ^^ form the Field as when it was initially inserted it had ^18^
				if (formIdMap(2,"Form",$Form_ID_Check[1])!=null ) {
					$NewFormID=formIdMap(2,"Form",$Form_ID_Check[1]);
					Post_Process ($ele_ID[1],$NewFormID,null,2,4);
					array_push($Element_ID,$ele_ID[1]);
					echo "Ele_ID Unique but Updating FID in Forumulize from =".$Form_ID_Check[1]." to ".$NewFormID."<br/>";
				}
				echo "New Formulize row <br/>";
			}else {
				echo "Updating the Ele_ID in Formulize  <br/>";
				$ee=preg_replace('/\w*VALUES \(\d*\,\d*/', "VALUES (".$Form_ID_Check[1].",''", $statement);
				echo $ee;
				$NewID=getlastID ($ee);
				array_push($Element_ID,$NewID);
				formIdMap(1,"Element",$ele_ID[1],$NewID);
				//AddMapp to Element;
				if (formIdMap(2,"Form",$Form_ID_Check[1])!=null ) {
					$NewFormID=formIdMap(2,"Form",$Form_ID_Check[1]);
					Post_Process ($NewID,$NewFormID,null,2,4);
				}
				if ($Flag!=null) {formIdMap(1,"Element_Handle",$ele_ID[1],$NewID); Post_Process ($NewID,"$NewID",null,2,14);} //Example Of Post Process,this Is Different Because we Update the Ele_Handle Field with New ID
			}
			
		}
		if(strstr($statement, "_formulize_framework")) {
			preg_match('/\(\d*\,/', $statement, $matches);
			$frame_ID=explode('(',$matches[0]);
			$frame_ID=explode(',',$frame_ID[1]);
			if (Check_Uniquines($frame_ID[0],6)==0)
			{
				echo "New Frameworks <br/>";
				Insert ($statement);
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
				Insert ($statement);
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
			if (formIdMap(2,"Group_Insert",$groups_ID[0])!=null){
				
				if (Check_Uniquines($groups_ID[0],9)==0)
				{
					echo "New Groups Has Been Inserted  <br/>";
					Insert ($statement);
					formIdMap(1,"Group_Map_Auto",$groups_ID[0],11);
				}
				else 
				{
					$ee=preg_replace('/\(\d*\,/', "('',", $statement);
					$NewID=getlastID ($ee);
					formIdMap(1,"Group_Map_Auto",$groups_ID[0],$NewID);
					echo "Updating the Group ID  in Groups from = ".$groups_ID[0]." to ".$NewID."<br/>";
				}}}
		if(strstr($statement, "_formulize_entry_owner_groups")||strstr($statement,"_formulize_group_filters")
				||strstr($statement, "_formulize_groupscope_settings") ||strstr($statement,"_1group_lists")
				){
			echo "Test1";
			$statement=str_replace('_1group_lists','_group_lists', $statement);
			//echo $statement;
			formIdMap(1,"Form",1,66);
			preg_match('/_.*\(\`/', $statement, $table);//To get the Table name
			preg_match('/VALUES \(.*\)/', $statement, $values);//To get the Values
			$rem=array('VALUES','`','(',' ',')');
			$table=str_replace($rem,'', $table[0]);
			$values=str_replace($rem,'',$values[0]);
			$values=explode(',',$values);
			$tt=$values[1];
			print_r($values);
			$values=str_replace('&',' ',$values);
			if ($table=='_formulize_group_filters'){
				$statement=str_replace('&', ';',$statement);}
			if ($table=='_group_lists'){
				$values[1]=explode('\'',$values[1]);//Add a check only to Group_Lists
				$values[1]=$values[1][1];}
			$ID=$values[0]; //Statement ID
			if ($table=='_group_lists'&& Check_Uniquines($values[1],15))
			{  
				$gl_name='Imp_'.$values[1];
			}
			
			//To get the table name \\
			//***********************************
			//***********************************
			//This is Needed because the Tables in the DB are poorly designed .FID should have been consistent in the column number in all tables,also the group ID 
			$map_fid=array ("_formulize_entry_owner_groups"=>"1/17","_formulize_group_filters"=>"1/19","_formulize_groupscope_settings"=>"2/23");
			$map_grpid=array ("_formulize_entry_owner_groups"=>3,"_formulize_group_filters"=>2,"_formulize_groupscope_settings"=>1);
			$map_check=array ('_formulize_entry_owner_groups'=>11,'_formulize_group_filters'=>12,'_formulize_groupscope_settings'=>13,'_group_lists'=>14);
			$map_fid=explode('/',$map_fid[$table]);
			//echo"FID <br/>";
			//print_r($map_fid);
			//echo"FID <br/>";
			if (Check_Uniquines($ID,$map_check[$table])==0)
			{
				Insert ($statement);
				echo "Inserting New Row in".$table[0]."<br/>";
			}
			else
			{
				echo "Not Unique <br/>";
				if ($table=='_group_lists') { 
					$ee=str_replace($values[0],"''",$statement);
					$ee=str_replace($tt,"'$gl_name'",$ee);echo "here";
				} else{
					$ee=preg_replace('/\(\d*\,/',"('',", $statement);
				}
				$ID=getlastID ($ee);
				echo $ID;
				echo "Updating Row in".$table."<br/>";
				//Post Process

				foreach ($values as $k=>$v)
				{
					if ($map_fid[0]==$k && $table!='_group_lists')
					{ 
						if (formIdMap(2,"Form",$v)!=null){ // Updates Form 1
							$ForID1=formIdMap(2,"Form",$v);
							//echo $ForID1."ID new <br/>";
							Post_Process ($ID,$ForID1,null,2,$map_fid[1]);
						}}
					if ($map_grpid[$table]==$k &&$table!='_formulize_groupscope_settings')
					{
						update_groups ($table,$ID); //This function will take care of retrieving the Group ID using the ID and check if needs update ,new or ignore 
					}
					if ($map_grpid[$table]==$k &&$table=='_formulize_groupscope_settings')
					{
						update_groups ($table,$ID,1); //This function will take care of retrieving the Group ID using the ID and check if needs update ,new or ignore 
						update_groups ($table,$ID,2);
					}
				}}}
		if (strstr($statement,'_formulize_advanced_calculations'))
		{
			preg_match('/VALUES \(.*\)/', $statement, $values);//To get the Values
			$statement=str_replace('&', ';',$statement);
			$rem=array('VALUES','`','(',' ',')');
			$values=str_replace($rem,'',$values[0]);
			$values=explode(',',$values);
			print_r($values);
			$ID=$values[0];
			if (Check_Uniquines($ID,16)==0)
			{
				Insert ($statement);
				echo "Inserting New Row in formulize_advanced_calculations<br/>";
			}
			else
			{
				$ee=preg_replace('/\(\d*\,/',"('',", $statement);
				$ID=getlastID ($ee);
				//echo $ID;
				echo "Updating Row in formulize_advanced_calculations <br/>";
			}
			//formIdMap(1,"Form",1,60);
			if (formIdMap(2,"Form",$values[1])!=null){ // Updates Form 1
				$ForID1=formIdMap(2,"Form",$values[1]);
				Post_Process ($ID,$ForID1,null,2,26);
			}
			//formIdMap(1,"Element_Handle",5,66);
			$a=array();
			array_push($a,$ID);//Because the Function Expects an Array of IDS
			ser($a,2,3);
		}
		if (strstr($statement,'_formulize_other'))
		{
			preg_match('/VALUES \(.*\)/', $statement, $values);
			$rem=array('VALUES','`','(',' ',')');
			$values=str_replace($rem,'',$values[0]);
			$values=explode(',',$values);
			$ID=$values[0];
			if (Check_Uniquines($ID,17)==0)
			{
				Insert ($statement);
				echo "Inserting New Row in Formulize other<br/>";
			}
			else
			{
				$ee=preg_replace('/\(\d*\,/',"('',", $statement);
				$ID=getlastID ($ee);
				//echo $ID;
				echo "Updating Row in formulize_advanced_calculations <br/>";
			}
			if (formIdMap(2,"Element",$values[2])!=null){ // Updates Form 1
				$ForID1=formIdMap(2,"Element",$values[2]);
				Post_Process ($ID,$ForID1,null,2,27);
			}
		}
		if (strstr($statement,'_formulize_notification_conditions'))
		{
			preg_match('/VALUES \(.*\)/', $statement, $values);
			$rem=array('VALUES','`','(',' ',')');
			$values=str_replace($rem,'',$values[0]);
			$statement=str_replace('&',';',$statement);
			if ($values[3]==0)//To check if the UID is 0 or not .IF its not zero then this row won't be Inserted 
			{
				if (Check_Uniquines($ID,16)==0)
				{
					Insert ($statement);
					echo "Inserting New Row in _formulize_notification_conditions<br/>";
				}
				else
				{
					$ee=preg_replace('/\(\d*\,/',"('',", $statement);
					$ID=getlastID ($ee);
					//echo $ID;
					echo "Updating Row in  formulize_notification_conditions <br/>";
				}
				///Post Process\\\
				if (formIdMap(2,"Form",$values[1])!=null){ // Updates Form ID in the Table
					$ForID1=formIdMap(2,"Form",$values[1]);
					Post_Process ($ID,$ForID1,null,2,28);
				}
				if ($values[5]!=0)//Check if the GroupID is null or nut
				{
					update_groups ('_formulize_notification_conditions',$ID);//To update the Group ID if needed 
				}
				if ($values[7]!=0)
				{
					if (formIdMap(2,"Element",$values[7])!=null){ // Updates Elements not_cons_elementuids 
						$ForID1=formIdMap(2,"Element",$values[7]);
						Post_Process ($ID,$ForID1,null,2,31);
					}
				}
				if ($values[9]!=0)
				{
					if (formIdMap(2,"Element",$values[9])!=null){ // Updates Elements not_cons_elementemail
						$ForID1=formIdMap(2,"Element",$values[9]);
						Post_Process ($ID,$ForID1,null,2,32);
					}
				}
				if ($values[10]!='None'){
					$a=array();
					array_push ($a,$ID);//Ser Expects Array 
					ser($ID,2,4);
				}
			}else {
				echo "This User ID row won't be inserted : ".$values[3]."<br/>";
			}
		}
	}//Add New Table before this Curly Bracket
	//$Element_ID is an array that Holds all the Inserted Element ID.If it was New ID or Auto-Incremented. 
	ser($Element_ID,1);//To Update Ele_Value
	ser($Element_ID,2,1);//To Update Ele_Filter settings 
	//This Process Needs to be done after all groups have been Updated It Updates the Ele_Disable/Ele_Display in Formulize Table
	Post_Process ($Element_ID,null,null,3,11,"ele_disabled");
	Post_Process ($Element_ID,null,null,3,12,"ele_display");
}
//
Function Post_Process ($ID,$Update=null,$Flag=null,$switch,$table=null,$Flag1=null)
{
	//Table Array Holds the Fields and table name that Will be Updated
	$tables=array (2=>'_formulize_application_form_link/`appid`/linkid',3=>'_formulize_application_form_link/`fid`/`linkid`',4=>'_formulize/`id_form`/`ele_id`'
	,5=>'_formulize/`ele_value`/`ele_id`',6=>'_formulize_framework_links/`fl_frame_id`/`fl_id`',7=>'_formulize_framework_links/`fl_form1_id`/`fl_id`',
	8=>'_formulize_framework_links/`fl_form2_id`/`fl_id`',9=>'_formulize_framework_links/`fl_key1`/`fl_id`',10=>'_formulize_framework_links/`fl_key2`/`fl_id`',
	11=>'_formulize/`ele_display`/`ele_id`',12=>'_formulize/`ele_disabled`/`ele_id`',13=>'_formulize_id/`form_handle`/`id_form`',14=>'_formulize/`ele_handle`/`ele_id`'
	,15=>'_formulize/`ele_filtersettings`/`ele_id`',16=>'_formulize_entry_owner_groups/`groupid`/`owner_id`',17=>'_formulize_entry_owner_groups/`fid`/`owner_id`',
	18=>'_formulize_group_filters/`groupid`/`filterid`',19=>'_formulize_group_filters/`fid`/`filterid`',20=>'_formulize_group_filters/filter/filterid',
	21=>'_formulize_groupscope_settings/`groupid`/`groupscope_id`',22=>'_formulize_groupscope_settings/view_groupid/`groupscope_id`',
	23=>'_formulize_groupscope_settings/fid/`groupscope_id`',24=>'_group_lists/gl_groups/`gl_id`',25=>'_formulize_advanced_calculations/fltr_grps/acid',
	26=>'_formulize_advanced_calculations/fid/acid',27=>'_formulize_other/ele_id/other_id',28=>'_formulize_notification_conditions/not_cons_fid/not_cons_id',
	29=>'_formulize_notification_conditions/not_cons_event/not_cons_id',30=>'_formulize_notification_conditions/not_cons_groupid/not_cons_id',31=>'_formulize_notification_conditions/not_cons_elementuids/not_cons_id'
	,32=>'_formulize_notification_conditions/not_cons_elementemail/not_cons_id',33=>'_formulize_notification_conditions/not_cons_con/not_cons_id');


	$fields=explode ('/',$tables[$table]);
	$conn=new Connection ();
	switch ($switch) {
	case 1:
		//Removed this Case statement Part and changed it to a more efficient one //Will Remove this Case and Update the Function Parameters in the next code Cleaning  
		break;
	case 2:
		$sql="UPDATE ".Prefix."".$fields[0]." SET ".$fields[1]."= :Update where ".$fields[2]."= :id";
		$Query=$conn->connect()->prepare($sql);
		break;
	case 3:
		//This Is done because it's much easier to Get the Ele_Display/Ele_Disable from the DB after its inserted.Preg-match and Str_Pos won't be Reliable to get from Insert String
		foreach ( $ID as $ElementID){
			$sql="select ".$Flag1." from  ".Prefix."".$fields[0]." where ".$fields[2]."= :id";
			$Query=$conn->connect()->prepare($sql);
			$Query->bindValue(':id',$ElementID);
			$Query->execute();
			$result=$Query->fetch(\PDO::FETCH_ASSOC);
			$result=$result["$Flag1"];
			//echo $Flag1;
			if ($result!='1' && $result!='0'){ //To make sure the Result is not 1 or 0
				$eledispl=explode (",",$result);
				foreach ( $eledispl as $grpID){
					if (formIdMap(2,"Group_Map_Auto",$grpID)!=null){//To Update The Create New Group
						$grpID_N=formIdMap(2,"Group_Map_Auto",$grpID);
						$result=str_replace($grpID,$grpID_N,$result);}
					if (formIdMap(2,"Group_Map",$grpID)!=null){ //To Update the Map Group
						$grpID_N=formIdMap(2,"Group_Map",$grpID);
						$result=str_replace($grpID,$grpID_N,$result);}
					if (formIdMap(2,"Group_Ignore",$grpID)!=null){ //To Remove the Group that's flagged as Ignore by replacing it with ''
						$result=str_replace($grpID,null,$result);}
				}
				($Flag1=="ele_display" ? Post_Process ($ElementID,$result,null,2,11) :Post_Process ($ElementID,$result,null,2,12));
			}}
		break;
	}	
	if ($Flag1==null){//This iF The Post Process In Case 2: Which is the General Update Fields  for any Field ,but the Ele_display needs to be returned Broken Down and Processed to check each Group
		$Query->bindValue(':Update',$Update);
		$Query->bindValue(':id',$ID);
		$Query->execute();}
	
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
	$Check;
	$tables=array (1=>'_formulize_application_form_link/`linkid` ',2=>'_formulize_applications/`appid`',3=>'_formulize_id/`id_form`'
	,4=>'_formulize/ele_id',5=>'_formulize/`ele_handle`',6=>'_formulize_frameworks/`frame_id`',7=>'_formulize_framework_links/`fl_id`',8=>'_formulize_id/desc_form',9=>'_groups/`groupid`'
	,10=>'_formulize_id/`form_handle`',11=>'_formulize_entry_owner_groups/`owner_id`',12=>'_formulize_group_filters/filterid',13=>'_formulize_groupscope_settings/groupscope_id'
	,14=>'_group_lists/`gl_id`',15=>'_group_lists/gl_name',16=>'_formulize_advanced_calculations/acid',17=>'_formulize_other/other_id');
	$fields=explode ('/',$tables[$field]);
	$conn=new Connection ();
	$Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".Prefix."".$fields[0]." where ".$fields[1]."= :id ;") ;
	$Query->bindValue(":id",$ID);
	$Query->execute();
	$Check=$Query->fetch(\PDO::FETCH_ASSOC);
	return $Check['num'];
}

Function getlastID ($statment)
{ //This Is used because the default getlastID syntax doesn't work
	//echo $statment;
	$sel=";select last_insert_id() as ID;";
	$statment.=$sel;
	//echo $statment;
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
	STATIC $id_map; // Static to hold the array until session ends //This is a Multi-Dimensional Array
	//To add a New Array formidMap (1,"New Array Name",OLD Map,NewMap);
	//To Retrieve results formidMap (2,"New Array Name",OLD Map);
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
function ser($AllElements,$Flag,$table=null)
{//Function used to Update the Ele_Value and Ele_settings. Flag=1: Ele_Value and Flag=2:Ele_filter settings
	$result=array();
	$conn=new Connection ();
	$fields=array (1=>"_formulize/ele_filtersettings/ele_id/15",2=>"_formulize_group_filters/filter/filterid/20",
	3=>"_formulize_advanced_calculations/fltr_grps/acid/25",4=>"_formulize_notification_conditions/not_cons_con/not_cons_id/33");//We Assume notification_conditions filter is same as before
	$fields=explode ('/',$fields[$table]);
	if ($Flag==1){
		foreach ($AllElements as $keyid) { 
			$Query=$conn->connect()->prepare("select ele_type,ele_value from ".Prefix."_formulize where ele_id=:ID") ;
			$Query->bindValue(':ID',$keyid);
			$Query->execute();
			$result=$Query->fetch(\PDO::FETCH_ASSOC);
			$data1=unserialize($result['ele_value']); 
			switch ($result['ele_type']){
			case 'text':
			case 'grid'://If Row was Text or Grid
				if (array_key_exists(4, $data1)){
					if (formIdMap(2,'Element',$data1[4])!=null){
						$data1[4]=formIdMap(2,'Element',$data1[4]);
						Post_Process($keyid,serialize($data1),null,2,5);
					}}
				break;
			case 'textarea': //If Row was Text Area
				if (array_key_exists(3, $data1)){
					if (formIdMap(2,'Element',$data1[3])!=null){
						$data1[3]=formIdMap(2,'Element',$data1[3]);
						Post_Process($keyid,serialize($data1),null,2,5);}}
				break;
				
			case 'select': //If it was Select// Case 2 :is the link between Form and Element,Case 3 :Is List of Group ID,Case 10:11:12,Case 5:Deals with Array similar to Filter settings
				foreach ($data1 as $k => $d)
				{
					switch ($k){
					case 2:
						if (strstr ($d,"#*=:*")){
							$s=explode ("#*=:*",$d);
							if (formIdMap(2,"Form",$s[0])!=null){ 
								$d=str_replace($s[0],formIdMap(2,"Form",$s[0]),$d);}
							if (formIdMap(2,"Element_Handle",$s[1])!=null)
							{
								$d=str_replace($s[1],formIdMap(2,"Element_Handle",$s[1]),$d);
							}
							$data1[2]=$d;
						}
						break;//End of Case 2
					case 3:
						if (strstr ($d,",") || is_numeric($d)){//If it was a List of Groups or Single ID\\\\
							$s=explode(',',$d);
							foreach ($s as $v){
								if (formIdMap(2,"Group_Map_Auto",$v)!=null){//To Update The Create New Group
									$grpID_N=formIdMap(2,"Group_Map_Auto",$v);
									$d=str_replace($v,$grpID_N,$d);}
								if (formIdMap(2,"Group_Map",$v)!=null){ //To Update the Map Group
									$grpID_N=formIdMap(2,"Group_Map",$v);
									$d=str_replace($v,$grpID_N,$d);}
								if (formIdMap(2,"Group_Ignore",$v)!=null){ //To Remove the Group that's flagged as Ignore by replacing it with ''
									$d=str_replace($v,null,$d);}
							}
							$data1[3]=$d;
						}
						break;//End of Case 3
					case 5://To Update the Array of Ele_ID=>{ele_handle}
						foreach ($data1[5][0] as $k => $d) //To Change the Array of Elements Handle 
						{
							if (formIdMap(2,"Element_Handle",$data1[5][0][$k])!=null)
							{$data1[5][0][$k]=formIdMap(2,"Element_Handle",$data1[5][0][$k]);} 
						}
						foreach ($data1[5][2] as $k => $d)
						{
							if (preg_match('/\{.*\}/',$d)) {
								$s=explode("{",$d);
								$s=explode("}",$s[1]);
								$v="{".formIdMap(2,"Element_Handle",$s[0])."}";
								$data1[5][2][$k]=$v;}}
						break;//End of Case 5:
					case 10:
					case 11:
					case 12:
						if ($d!='none'){
							if (formIdMap(2,"Element",$d)!=null){ // Updates Elements 1
								$data[$k]="".formIdMap(2,"Element",$d)."";}}
						break;
					}}}Post_Process($keyid,serialize($data1),null,2,5);}}
	if ($Flag==2)
	{
		//echo "Here Serilzed";
		$tf=Prefix.$fields[0];
		//"_formulize/ele_filtersettings/ele_id"
		foreach ($AllElements as $keyid) { 
			//echo "select ".$fields[1]." from ".$tf." where ".$fields[2]."=$keyid";
			$Query=$conn->connect()->prepare("select ".$fields[1]." from ".$tf." where ".$fields[2]."=:ID") ;
			$Query->bindValue(':ID',$keyid);
			$Query->execute();
			$result=$Query->fetch(\PDO::FETCH_ASSOC);
			$data1=unserialize($result[$fields[1]]); 
			//print_r($data1);
			if ($fields[2]!='acid'){
				foreach ($data1[0] as $k => $d) //To Change the Array of Elements Handle 
				{
					if (formIdMap(2,"Element_Handle",$data1[0][$k])!=null)
					{
						$data1[0][$k]=formIdMap(2,"Element_Handle",$data1[0][$k]);
					}}
				foreach ($data1[2] as $k => $d)
				{
					if (preg_match('/\{.*\}/',$d)) {
						$s=explode("{",$d);
						$s=explode("}",$s[1]);
						if (formIdMap(2,"Element_Handle",$s[0])!=null){
							$v="{".formIdMap(2,"Element_Handle",$s[0])."}";
							$data1[2][$k]=$v;
						}}}}
			else 
			{
				if (formIdMap(2,"Element_Handle",$data1[0]['form'])!=null){//To get the App cal ELement Handle we don't need to loop in it because it will always be one Element
					$data1[0]['form']=formIdMap(2,"Element_Handle",$data1[0]['form']);
				}
			}
			Post_Process($keyid,serialize($data1),null,2,$fields[3]);
		}}
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
		if (preg_match(' /\d*=New/',$k)) {preg_match('/\d*=New/',$k,$map1); $map1=explode('=',$map1[0]); formIdMap(1,"Group_Insert",$map1[0],11);} //Array Map
	}}
function Insert ($st)
{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($st);
	$Query->execute();
}
function Create_table($Insert)
{
	$Insert=explode('^',$Insert);
	$AllHandles1=explode (',',$Insert[1]);
	$AllHandles2=explode ('\'',$AllHandles1[0]);
	if (formIdMap(2,"Form_Handle",$AllHandles2[0])!=null)
	{
		$AllHandles2[0]=formIdMap(2,"Form_Handle",$AllHandles2[0]);
	}
	$s1="CREATE TABLE IF NOT EXISTS `".Prefix."_formulize_$AllHandles2[0]` (
`entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
`creation_datetime` datetime DEFAULT NULL,
`mod_datetime` datetime DEFAULT NULL,
`creation_uid` int(7) DEFAULT '0',
`mod_uid` int(7) DEFAULT '0',";
	$s2=" PRIMARY KEY (`entry_id`),
	KEY `i_creation_uid` (`creation_uid`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;";
	foreach ($AllHandles1 as $k=> $column)
	{
		if ($k!=0){
			if (formIdMap(2,"Element",$column)!=null){
				$column=formIdMap(2,"Element",$column);}
			$s1.="`$column` text,";
		}}
	$s1.=$s2;
	$s1.=$Insert[2];
	$s1.=";";
	$s1=preg_replace('/Table_Name/', "`".Prefix."_formulize_$AllHandles2[0]`", $s1);
	Insert ($s1);
	echo "Creating Table Formulize_$AllHandles2[0] <br/>";
}
function update_groups ($table,$ID,$flag=null)
{ 
	///echo $ID;
	$tables=array ('_formulize_entry_owner_groups'=>'groupid/owner_id/16','_formulize_group_filters'=>'groupid/filterid/18'
	,'_formulize_groupscope_settings'=>'groupid/groupscope_id/21','_2formulize_groupscope_settings'=>'view_groupid/groupscope_id/22','_group_lists'=>'gl_groups/gl_id/24'
	,'_formulize_notification_conditions'=>'not_cons_groupid/not_cons_id/30');
	$f;
	//echo $tables[$table];
	switch ($flag){
	case null:
		$fields=explode ('/',$tables[$table]);
		print_r($fields);
		break;
	case 1:
		$tb="_formulize_groupscope_settings";
		$fields=explode ('/',$tables[$tb]);
		print_r($fields);
		break;
	case 2:
		$tb="_2formulize_groupscope_settings";
		$fields=explode ('/',$tables[$tb]);
		print_r($fields);
		break;
	}
	$table1=Prefix.$table;
	//	echo $table;
	$conn=new Connection ();
	$Query=$conn->connect()->prepare("SELECT ".$fields[0]." from ".$table1." where ".$fields[1]." =:id") ;
	$Query->bindValue(":id",$ID);
	$Query->execute();
	$result=$Query->fetch(\PDO::FETCH_ASSOC);
	print_r ($result);
	if (strstr($result[$fields[0]],',')){echo "Here in F 1";$f=1;}
	$result=explode(',',$result[$fields[0]]);
	///print_r($result);
	foreach ($result as $k=>$d){
		if (formIdMap(2,"Group_Map_Auto",$d)!=null){//To Update The Create New Group
			$grpID_N=formIdMap(2,"Group_Map_Auto",$d);
			if (empty($f)){
				Post_Process ($ID,$grpID_N,null,2,$fields[2]);}else {$result[$k]=$grpID_N;}
		}
		if (formIdMap(2,"Group_Map",$d)!=null){ //To Update the Map Group
			$grpID_N=formIdMap(2,"Group_Map",$d);
			if (empty($f)){
				Post_Process ($ID,$grpID_N,null,2,$fields[2]);}else {$result[$k]=$grpID_N;}
		}
		if (formIdMap(2,"Group_Ignore",$d)!=null){
			if (empty($f)){	//To Remove the Group that's flagged as Ignore by replacing it with ''
				Post_Process ($ID,$grpID_N,null,2,0);} else {unset($result[$k]);} 
		}}
	if (!empty($f))
	{
		$final=implode (',',$result);
		Post_Process ($ID,$final,null,2,$fields[2]);
	}
	if ($table=='_formulize_group_filters')
	{
		$a=array();
		array_push($a,$ID);//Because the Function Expects an Array of IDS
		ser($a,2,2);
	}
}
