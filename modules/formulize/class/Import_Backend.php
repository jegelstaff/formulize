<?php

class Import_file_upload{

	public Function Import (){

		$fileName=$_FILES['file']['name'];
		$fileTmp=$_FILES['file']['tmp_name'];

		if (!file_exists('../upload/')) { mkdir('../upload/', 0777, true); }

		$filePath = '../upload/'.$fileName;


		move_uploaded_file ($fileTmp, $filePath);

		$this->replaces_Prefix_in_file ($filePath);

		$this->render_table($filePath);
	}

	private function replaces_Prefix_in_file ($fileName){
	
		$str=implode("\n",file($fileName));
		$fp=fopen($fileName,'w');
		
		//replace Prefix word file string with the current DB Prefix 
		$str=str_replace('Prefix',Prefix,$str);

		fwrite($fp,$str,strlen($str));
		fclose($fileName);
	}

	private function render_table ($fileName){

		$newGroups = $this->Getall(2);
		$oldGroups = $this->Getall(1, $fileName);
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

	private function Getall($Flag, $fileName = null){
		global $get;

		$get=array();
		if ($Flag==1){

			session_start();
			$_SESSION['file'] = $fileName;//Send the File Location to Import.php
		
			$getlines = file_get_contents($fileName);
			$get_line=explode(';',$getlines);//print_r($get_line);
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

			fclose($fileName);

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



}

/************************************END of File Upload Class*************************************************************/

class Import_Model{
	
	private $_fileName = '';
	private $_db = null;

	 function __construct() {

	 	$this->_db = new Connection ();
	 	session_start();
		$this->_fileName = $_SESSION['file'];
		
	 }

	public function Create_Applications()
	{
	
		$this->get_Groups();
		/*
		1)It Gets the Statement row ID.Checks if the ID is Unique or not .If not then removes the ID and let the row use the Auto-increment to assign an ID 
		2)The only process is done before the Import is to check if the row contains Table handle.If it's text ,also  Uniq or not in the DB ,so to avoid multiple duplicates after Insert.If it was ID then updated after we get the New ID 
		3)Usually post Process for each table is done after each insert .Unless the Post process involves a global process ,for example Ele_Value might contain groups ID so we need to wait till we are done with the Group Insert  
		*/
		//Those  variables will store the New AppID/Form ID  if Needed.
		global $Element_ID; //Every time ID is Inserted in the DB it will be pushed here to be used later on for the Ele_Filter settings and Ele_Value 
		global $Calc_ID; //To store the Calculation IDs
		$Calc_ID=array ();
		$Element_ID=array();
		echo $fileName;
		$getlines = file_get_contents($this->_fileName);
		$get_line=explode(';',$getlines);
	
	
		set_time_limit(0);
		foreach ($get_line as $statement){ 
			if(strstr($statement, 'Create table')) {
				$this->Create_table($statement);
			}
			if($this->Statement_ID($statement,null,null,1)=='_formulize_applications') { 
				$App_ID= $this->Statement_ID($statement);
				if ($this->Check_Uniquines($App_ID,2)==0) //If Unique then no need to Update the ID
				{
					echo 'New App <br/>';
					$this->Insert($statement);echo $statement;
				}else {
					echo 'App Exists Updating ID <br/>';
					$Auto_Incr= $this->Statement_ID($statement,1);
					$NewID= $this->getlastID($Auto_Incr);
					$this->formIdMap(1,'AppID',$App_ID,$NewID);

				}} 
			//The Formalize Updates to Fields in the INsert statement if needed.It Updates the ID and the Desc_From 
			if($this->Statement_ID($statement,null,null,1)=='_formulize_id') {//echo $statement;
				$Form_ID=$this->Statement_ID($statement);
				global $up;
				//Process Form Handle//
				preg_match('/\^.*\^/', $statement, $handle);//To get the Form Handle 
				$form_Handle_Text=explode('^',$handle[0]);//To Get the Form Handle As Text 
				$Flag;//This Flag will be Used to determine if the Form_Handle is Text or Integer 
				$Flag=is_numeric($form_Handle_Text[1]);//This Flag will be Used to determine if the Form_Handle is Text or Integer Flag=1 if Yes or Null
				if ($Flag==null)
				{
					if ( $this->Check_Uniquines($form_Handle_Text[1],10)!=0){
						$statement=preg_replace('/\^.*\^/','Imp_'.$form_Handle_Text[1].'', $statement);
						$this->formIdMap(1,'Form_Handle',$form_Handle_Text[1],'Imp_'.$form_Handle_Text[1].'');
					} else {$statement=preg_replace('/\^.*\^/',''.$form_Handle_Text[1].'', $statement);
					}}
				$this->formIdMap(1,'FormOri',$Form_ID,2); // Used For Warning
				if ( $this->Check_Uniquines($Form_ID,3)==0) //If Unique then no need to Update the ID
				{
					 $this->Insert($statement);
					echo 'New Form <br/>'; 
				}else {
					echo 'Form Exist Updating Row ID and Unique Field <br/>';
					$Auto_Incr=$this->Statement_ID($statement,1);
					$NewID= $this->getlastID($Auto_Incr);
					$this->formIdMap(1,'Form',$Form_ID,$NewID);
					$up=2;
					//$Form_ID=$NewID;
				}
				if ($Flag!=null) {
					if ($up==2){
						$Form_ID=$NewID;
						$this->formIdMap(1,'Form_Handle',$form_Handle_Text[1],$Form_ID);
					}
					$this->Post_Process($Form_ID,$Form_ID,null,2,13);}
			}
			//First, it checks if the Link Id is in use or not.If not then no need to update the Form ID.Second,It checks if the AppID has been updated by checking  the APP ID variable.If it's empty then it doesn't update the APP ID in the Link $this->Insert statement.Third,It checks if the Form ID that's in the link insert statement has been updated or not.If the ID is updated it will be included in the Form_ID_Replace array.After,if gets the Old ID it query the Form_Map table to check the new ID that corresponds to the OLD ID and then updates the Insert Statement and Insert the new Row. 
			if($this->Statement_ID($statement,null,null,1)=='_formulize_application_form_link') {
				$Link_ID=$this->Statement_ID($statement);
				if ( $this->Check_Uniquines($Link_ID,1)==0)
				{ 
					$this->Insert ($statement);
					echo 'New Link Form/App  <br/>';
				}
				else {
					//Use the Auto Increment 
					echo 'Updating the Link ID  <br/>';
					$Auto_Incr= $this->Statement_ID($statement,1);
					$NewID= $this->getlastID($Auto_Incr);
					$Link_ID=$NewID;
				}
				//Post Process To Update the Link with the Latest App and Form
				$result= $this->get_result('_formulize_application_form_link',$Link_ID);
				if ($this->formIdMap(2,'AppID',$result['appid'])!=null ) {//Update the App ID 
					$this->Post_Process ($Link_ID,$this->formIdMap(2,'AppID',$result['appid']),null,2,2);
				}
				if ($this->formIdMap(2,'Form',$result['fid'])!=null ) { //Update the Form ID
					$NewFormID=$this->formIdMap(2,'Form',$result['fid']);
					$this->Post_Process($Link_ID,$NewFormID,null,2,3);
				}
			}
			if( $this->Statement_ID($statement,null,null,1)=='_formulize') {
				///echo $statement."<br/>";
				preg_match('/\^.*\^/', $statement, $matches1);//To get the Ele Handle 
				$ele_Handle_Text=explode('^',$matches1[0]);//To Get the Ele Handle As Text 
				$Ele_ID= $this->Statement_ID($statement,null,2);///
				///echo $Ele_ID;
				///$NewID;
				global $up;
				$Flag;//This Flag will be Used to determine if the ee_Handle is Text or Integer 
				$Flag=is_numeric($ele_Handle_Text[1]);//This Flag will be Used to determine if the ele_Handle is Text or Integer Flag=1 if Yes or Null
				$statement=str_replace('&', ';',$statement);//This to bring back the ; to the Sterilized Array after we broke the statements with ; 
				///echo $statement;  
				$rem=array('\'');
				$Ele_ID=str_replace($rem,'', $Ele_ID);
				$this->formIdMap(1,'ElementOri',$Ele_ID,2); //Used For Warning
				
				if ($Flag==null) //Ele_Handle Needs to be Done before Insert if it was a Text due to conflict might appear after the Insert
				{
					if ( $this->Check_Uniquines($ele_Handle_Text[1],5)!=0){
						$statement=preg_replace('/\^.*\^/','Imp_'.$ele_Handle_Text[1].'', $statement);
						$this->formIdMap(1,'Element_Handle',$ele_Handle_Text[1],'Imp_'.$ele_Handle_Text[1].'');
					} else {$statement=preg_replace('/\^.*\^/',''.$ele_Handle_Text[1].'', $statement);}//To Remove The ^^ if Unique
				}
				//echo $Ele_ID."Here";

				if ( $this->Check_Uniquines($Ele_ID,4)==0)//Check IF Ele_ID is Unique or not 
				{
					$this->Insert ($statement);
					array_push($Element_ID,$Ele_ID);
					echo 'New Formulize row <br/>';  //cho $statement;
				}
				else {
					//echo "Updating the Ele_ID in Formulize  <br/>";
					$Auto_Incr= $this->Statement_ID($statement,1,1);
					////echo $Auto_Incr;
					$NewID_1= $this->getlastID($Auto_Incr);
					///echo $NewID_1;
					array_push($Element_ID,$NewID_1);
					$this->formIdMap(1,'Element',$Ele_ID,$NewID_1);
					echo 'Updating the Ele_ID in Formulize from : '.$Ele_ID.' to '.$NewID_1.' <br/>';
					$up=1;
				}
				////echo "Inserted";
				if ($Flag!=null) {
					if ($up==1){//This to check if THe ID has been Updated or not
						$Ele_ID=$NewID_1;//We initialize the ELE_ID here with the latest ID  to be able to keep track of the Element_Handle the New with OLD
						$this->formIdMap(1,'Element_Handle',$ele_Handle_Text[1],$Ele_ID);}
					$this->Post_Process($Ele_ID,$Ele_ID,null,2,14);
				} 
				if (!empty($NewID_1)){$Ele_ID=$NewID_1;} //This is used as a precaution if the ELe_Handle is not a Number then the Ele_ID will always contain the OLD ID   
				$result= $this->get_result('_formulize',$Ele_ID);
				if ($this->formIdMap(2,'Form',$result['id_form'])!=null ) {
					$this->Post_Process($Ele_ID,$this->formIdMap(2,'Form',$result['id_form']),null,2,4);
				}
			}
			if( $this->Statement_ID($statement,null,null,1)=='_formulize_frameworks') {
				$frame_ID= $this->Statement_ID($statement);
				if ( $this->Check_Uniquines($frame_ID,6)==0)
				{
					echo 'New Frameworks <br/>';
					$this->Insert ($statement);
				}
				else 
				{
					$Auto_Incr= $this->Statement_ID($statement,1);
					$NewID=getlastID ($Auto_Incr);
					$this->formIdMap(1,'Frame',$frame_ID,$NewID);
					echo 'Updating the Frame ID  in Frameworks from = '.$frame_ID.' to '.$NewID.'<br/>';
				}
			}
			if( $this->Statement_ID($statement,null,null,1)=='_formulize_framework_links') {
				$frame_links_ID= $this->Statement_ID($statement);
				if ( $this->Check_Uniquines($frame_links_ID,7)==0)
				{
					$this->Insert ($statement);
					echo 'New Frame Link Row With ID <br/>';
					$this->formIdMap(1,'Framelinks',$frame_links[0],$frame_links[0]);
				}
				else
				{
					$Auto_Incr= $this->Statement_ID($statement,1);
					$NEWID= $this->getlastID ($Auto_Incr);
					echo 'Updating Frame Link ID <br/>';
					$this->formIdMap(1,'Framelinks',$frame_links_ID,$NEWID);
					$frame_links_ID=$NEWID;
				}
				///echo $frame_links_ID;
				//Post Process// To Update the Fields
				$result= $this->get_result('_formulize_framework_links',$frame_links_ID);
				///echo "Here".$result;
				//if (is_numeric($result['fl_form1_id'])){echo "Tre";}
				if ($this->formIdMap(2,'Frame',$result['fl_frame_id'])!=null){ //Updates the Frame ID
					$FrID=$this->formIdMap(2,'Frame',$result['fl_frame_id']);
					$this->Post_Process($frame_links_ID,$FrID,null,2,6);
				}
				if ($this->formIdMap(2,'Form',$result['fl_form1_id'])!=null){ // Updates Form 1
					$ForID1=$this->formIdMap(2,'Form',$result['fl_form1_id']);
					$this->Post_Process($frame_links_ID,$ForID1,null,2,7);
				}
				if ($this->formIdMap(2,'Form',$result['fl_form2_id'])!=null){ // Updates Form 2
					$ForID2=$this->formIdMap(2,'Form',$result['fl_form2_id']);
					$this->Post_Process($frame_links_ID,$ForID2,null,2,8);
				}
				if ($this->formIdMap(2,'Element',$result['fl_key1'])!=null){ // Updates Elements 1
					$Element1=$this->formIdMap(2,'Element',$result['fl_key1']);
					$this->Post_Process($frame_links_ID,$Element1,null,2,9);
				}
				if ($this->formIdMap(2,'Element',$result['fl_key2'])!=null){ // Updates Elements 2
					$Element2=$this->formIdMap(2,'Element',$result['fl_key2']);
					$this->Post_Process($frame_links_ID,$Element2,null,2,10);
				}
				//Alert The User If Form,Elements Are not Part of the App 
				if ($this->formIdMap(2,'FormOri',$result['fl_form1_id'])==null || $this->formIdMap(2,'FormOri',$result['fl_form2_id'])==null ){ echo 'Warning Form Doesn\'t Exist in Export <br/>';} //Alert For Form
				if ($this->formIdMap(2,'ElementOri',$result['fl_key1'])==null || $this->formIdMap(2,'ElementOri',$result['fl_key2'])==null ){ echo 'Warning Element Doesn\'t Exist in Export <br/>';} //Alert For Element
			}
			if( $this->Statement_ID($statement,null,null,1)=='_groups'){
				//The Only ID it will Insert is the One that's in Group_Insert Array//It checks that's Included in Array and then Inserts it
				////echo "Here in groups";
				$groups_ID= $this->Statement_ID($statement);
				////echo $groups_ID."<br/>";
				if ($this->formIdMap(2,'Group_Insert',$groups_ID)!=null){
					
					if ( $this->Check_Uniquines($groups_ID,9)==0)
					{
						echo 'New Groups Has Been Inserted  <br/>';
						$this->Insert ($statement);
						$this->formIdMap(1,'Group_Map_Auto',$groups_ID,11);
					}
					else 
					{
						$Auto_Incr= $this->Statement_ID($statement,1);
						$NewID= $this->getlastID ($Auto_Incr);
						$this->formIdMap(1,'Group_Map_Auto',$groups_ID,$NewID);
						echo 'Updating the Group ID  in Groups from = '.$groups_ID.' to '.$NewID.'<br/>';
					}}}
			if( $this->Statement_ID($statement,null,null,1)=='_formulize_entry_owner_groups'|| $this->Statement_ID($statement,null,null,1)=='_formulize_group_filters'
					|| $this->Statement_ID($statement,null,null,1)=='_formulize_groupscope_settings'|| $this->Statement_ID($statement,null,null,1)=='_group_lists'
					|| $this->Statement_ID($statement,null,null,1)=='_group_permission')
			{
				preg_match('/VALUES \(.*\)/', $statement, $values);//To get the Values
				$rem=array('VALUES','`','(',')');
				$table= $this->Statement_ID($statement,null,null,1);
				$values=str_replace($rem,'',$values[0]);
				$values=explode(',',$values);
				$tt=$values[1];
				///print_r($values);
				//$values=str_replace('&',' ',$values);
				if ($table=='_formulize_group_filters'){
					$statement=str_replace('&', ';',$statement);}
				if ($table=='_group_lists'){
					$values[1]=explode('\'',$values[1]);//Add a check only to Group_Lists
					$values[1]=$values[1][1];}
				$ID=$values[0]; //Statement ID
				$ID=$values[0];
				///print_r($values);
				///echo "Here".Check_Uniquines($values[1],15);
				///print_r($values);
				if ($table=='_group_lists'&&  $this->Check_Uniquines($values[1],15)!=0)
				{  ///echo "Not Ui";
					$gl_name='Imp_'.$values[1];
					///echo $gl_name;
				}
				///echo "Here";
				//To get the table name \\
				//***********************************
				//***********************************
				//This is Needed because the Tables in the DB are poorly designed .FID should have been consistent in the column number in all tables,also the group ID 
				$map_fid=array('_formulize_entry_owner_groups'=>'1/17','_formulize_group_filters'=>'1/19','_formulize_groupscope_settings'=>'2/23','_group_permission'=>'2/56');
				$map_grpid=array('_formulize_entry_owner_groups'=>3,'_formulize_group_filters'=>2,'_formulize_groupscope_settings'=>1,'_group_permission'=>1);
				$map_check=array('_formulize_entry_owner_groups'=>11,'_formulize_group_filters'=>12,'_formulize_groupscope_settings'=>13,'_group_lists'=>14,'_group_permission'=>24);
				$map_fid=explode('/',$map_fid[$table]);
				//echo"FID <br/>";
				//print_r($map_fid);
				//echo"FID <br/>";
				$rem=array('\'');
				$ID=str_replace($rem,'',$ID);
				///echo $ID;
				if ( $this->Check_Uniquines($ID,$map_check[$table])==0)
				{
					$this->Insert($statement);
					echo 'Inserting New Row in '.$table.'<br/>';
				}
				else
				{
					///echo "Not Unique <br/>";
					if ($table=='_group_lists') { 
						$ee=preg_replace('/\(\'\d*\'\,/','(\'\',', $statement);
						///echo $ee;
						if (!empty($gl_name)){// A fix added ,because if the Field is Unique then it will always get a null value
							$ee=str_replace($tt,"'$gl_name'",$ee);}//echo "here";
						//echo $ee;
					} else{
						$ee=preg_replace('/\(\'\d*\'\,/','(\'\',', $statement);
					}
					///	echo $ID;
					$ID= $this->getlastID($ee);
					echo 'Updating Row in '.$table.'<br/>';
					//Post Process
				}
				if ($table=='_formulize_entry_owner_groups'){
					$this->Insert('UPDATE '.Prefix.'_formulize_entry_owner_groups SET `entry_id`='.SID.' WHERE `owner_id` ='.$ID);
				}
				if ($table=='_group_permission'){
					$this->Insert('UPDATE '.Prefix.'_group_permission SET `gperm_modid`='.MOD_ID.' WHERE gperm_id = '.$ID);}
				foreach ($values as $k=>$v)
				{
					$rem=array('\'');
					$v=str_replace($rem,'',$v);
					if ($map_fid[0]==$k && $table!='_group_lists')
					{ ////echo $v;
						if ($this->formIdMap(2,'Form',$v)!=null){ // Updates Form 1
							$ForID1=$this->formIdMap(2,'Form',$v);
							$this->Post_Process($ID,$ForID1,null,2,$map_fid[1]);
						}}
					if ($map_grpid[$table]==$k &&$table!='_formulize_groupscope_settings')
					{
						 $this->update_groups($table,$ID); //This function will take care of retrieving the Group ID using the ID and check if needs update ,new or ignore 
					}
					if ($map_grpid[$table]==$k &&$table=='_formulize_groupscope_settings')
					{
						 $this->update_groups($table,$ID,1); //This function will take care of retrieving the Group ID using the ID and check if needs update ,new or ignore 
						 $this->update_groups($table,$ID,2);
					}
				}}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_advanced_calculations')
			{
				$statement=str_replace('&', ';',$statement);
				$ID= $this->Statement_ID($statement);
				if ( $this->Check_Uniquines($ID,16)==0)
				{
					$this->Insert($statement);
					echo ' New Row in formulize_advanced_calculations <br/>';
				}
				else
				{
					$Auto_Incr= $this->Statement_ID($statement,1);
					$ID= $this->getlastID($Auto_Incr);
					echo 'Updating Row in formulize_advanced_calculations <br/>';
				}
				//Post Process
				$result= $this->get_result('_formulize_advanced_calculations',$ID);
				if ($this->formIdMap(2,'Form',$result['fid'])!=null){ // Updates Form 1
					$ForID1=$this->formIdMap(2,'Form',$result['fid']);
					$this->Post_Process ($ID,$ForID1,null,2,26);
				}
				$a=array();
				array_push($a,$ID);//Because the Function Expects an Array of IDS
				 $this->ser($a,2,3);
			}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_other')
			{
				$ID= $this->Statement_ID($statement);
				if ( $this->Check_Uniquines($ID,17)==0)
				{
					$this->Insert ($statement);
					echo 'Inserting New Row in Formulize other<br/>';
				}
				else
				{
					$Auto_Incr= $this->Statement_ID($statement,1);
					$ID=getlastID($Auto_Incr);
					echo 'Updating Row in Formulize Other <br/>';
				}
				$result= $this->get_result('_formulize_other',$ID);
				if ($this->formIdMap(2,'Element',$result['ele_id'])!=null){ // Updates Form 1
					$ForID1=$this->formIdMap(2,'Element',$result['ele_id']);
					$this->Post_Process ($ID,$ForID1,null,2,27);
				}
			}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_notification_conditions')
			{
				preg_match('/\^.*\^/', $statement, $matches1);//To get the UID 
				$UID=explode('^',$matches1[0]);//
				$UID=$UID[1];
				$ID= $this->Statement_ID($statement);
				
				///echo $UID."   ".$ID;
				if ($UID==0)//To check if the UID is 0 or not .IF its not zero then this row won't be Inserted 
				{///	echo "I UID";
					$statement=str_replace('^','',$statement);
					$statement=str_replace('&',';',$statement);
					if ( $this->Check_Uniquines($ID,22)==0)
					{///echo "Uniq";echo $statement;
						$this->Insert($statement);
						echo 'Inserting New Row in _formulize_notification_conditions<br/>';
					}
					else
					{
						///echo "Not Uniq";
						$Auto_Incr= $this->Statement_ID($statement,1);
						$ID= $this->getlastID ($Auto_Incr);
						//echo $ID;
						echo 'Updating Row in  formulize_notification_conditions <br/>';
					}
					$result= $this->get_result('_formulize_notification_conditions',$ID);
					///Post Process\\\
					if ($this->formIdMap(2,'Form',$result['not_cons_fid'])!=null){ // Updates Form ID in the Table
						$ForID1=$this->formIdMap(2,'Form',$result['not_cons_fid']);
						$this->Post_Process($ID,$ForID1,null,2,28);
					}
					if ($result['not_cons_groupid']!=0)//Check if the GroupID is null or nut
					{
						 $this->update_groups('_formulize_notification_conditions',$ID);//To update the Group ID if needed 
					}
					if ($result['not_cons_elementuids']!=0)
					{
						if ($this->formIdMap(2,'Element',$result['not_cons_elementuids'])!=null){ // Updates Elements not_cons_elementuids
							$ForID1=$this->formIdMap(2,'Element',$result['not_cons_elementuids']);
							$this->Post_Process($ID,$ForID1,null,2,31);
						}
					}
					if ($result['not_cons_elementemail']!=0)
					{
						if ($this->formIdMap(2,'Element',$result['not_cons_elementemail'])!=null){ // Updates Elements not_cons_elementemail
							$ForID1=$this->formIdMap(2,'Element',$result['not_cons_elementemail']);
							$this->Post_Process($ID,$ForID1,null,2,32);
						}
					}
					$Data_Ser=@unserialize($result['not_cons_con']);//To check if its a serialized array or Not
					if ($Data_Ser!=false && $Data_Ser !='b:0;'){
						$Data_Ser=unserialize($result['not_cons_con']); //$Data_Ser[0] Elements Handle and $Data_Ser[2]
						$DS=unserialize($Data_Ser[0]);//For Array 0 Element Handle 
						$DS1=unserialize($Data_Ser[2]);//For Array 2 Element Handle between { } 
						if ($this->formIdMap(2,'Element_Handle',$DS[0])!=null){///echo 'Here';
							$NewIDSer=array();
							$NewIDSer0[0]=$this->formIdMap(2,'Element_Handle',$DS[0]);
							$Data_Ser[0]=serialize($NewIDSer0);
						}
						if (preg_match('/\{.*\}/',$DS1[0])) {
							$rem=array('{','}',' ');
							$DS1=str_replace($rem,'', $DS1[0]);
							if ($this->formIdMap(2,'Element_Handle',$DS1)!=null){
								$NewIDSer2=array();
								$NewIDSer2[0]='{'.$this->formIdMap(2,'Element_Handle',$DS1).'}';
								$Data_Ser[2]=serialize($NewIDSer2);
							}}
						$this->Post_Process($ID,serialize($Data_Ser),null,2,33);
					}
					
				}else {
					echo 'This User ID row won\'t be inserted With USER ID : '.$UID.'<br/>';
				}
			}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_saved_views')
			{
				$sv_ID= $this->Statement_ID($statement);echo $sv_ID;
				if ( $this->Check_Uniquines($sv_ID,18)==0)
				{///echo "Trying";
					$this->Insert($statement);
					echo 'Inserting New Row in Saved Views <br/>';
					$this->formIdMap(1,'SV_ID',$sv_ID,$sv_ID);//No Need to store it in the Map array but it won't hurt
				}else
				{
					$Auto_Incr= $this->Statement_ID($statement,1);
					$ID= $this->getlastID($Auto_Incr);///echo $Auto_Incr;
					$this->formIdMap(1,'SV_ID',$sv_ID,$ID);//No Need to store it in the Map array but it won't hurt
					$sv_ID=$ID;
					echo 'Updating Row in  Saved Views <br/>';
				}
				//By this point we carry the Post Process Procedure//
				$result= $this->get_result('_formulize_saved_views',$sv_ID);
				//print_r ($result);
				foreach ($result as $field_name=>$field_value)
				{
					switch ($field_name){
					case 'sv_pubgroups'://Actually the $field_vlaue is useless in here because the Function  $this->update_groups gets the values by it self
						 $this->update_groups('_formulize_saved_views',$sv_ID);
						break;
					case 'sv_owner_uid':
						$this->Post_Process($sv_ID,SID,null,2,35);
						break;
					case 'sv_mod_uid':
						$this->Post_Process($sv_ID,SID,null,2,36);
						break;
					case 'sv_formframe':
						if ($field_value!=null)
						{
							if ($result['sv_mainform']!=null){//If it's not Empty then this means the sv_fromframe is a Frame Links ID 
								$this->Post_Process($sv_ID,$this->formIdMap(2,'Framelinks',$field_value),null,2,37);}
							else {$this->Post_Process($sv_ID,$this->formIdMap(2,'Form',$field_value),null,2,37);}//Else the From Frame is a Form ID
						}
						break;
					case 'sv_mainform':
						if ($this->formIdMap(2,'Form',$field_value)!=null){
							$this->Post_Process($sv_ID,$this->formIdMap(2,'Form',$field_value),null,2,38);
						}
						break;
					case 'sv_sort':
						break;
					case 'sv_oldcols':
						$Temp_value=explode(',',$field_value);
						//print_r($Temp_value);
						foreach ($Temp_value as $v)
						{
							if ($this->formIdMap(2,'Element_Handle',$v)!=null){
								$field_value=str_replace($v, $this->formIdMap(2,'Element_Handle',$v), $field_value);
							}
							if (strstr($v,'hiddencolumn_')){
								$hdncolm=explode('hiddencolumn_',$v);
								if ($this->formIdMap(2,'Element_Handle',$hdncolm[1])!=null)
								{
									$field_value=str_replace($v,'hiddencolumn_'.$this->formIdMap(2,'Element_Handle',$hdncolm[1]), $field_value);
								}
				
							}
							$this->Post_Process($sv_ID,$field_value,null,2,40);
						}
						break;
					case 'sv_currentview':
						break;
					case 'sv_calc_cols':
						$Temp_value=explode('/',$field_value);
						foreach ($Temp_value as $v)
						{
							if (is_numeric($v))
							{
								if ($this->formIdMap(2,'Element',$v)!=null){
									$field_value=str_replace($v, $this->formIdMap(2,'Element',$v), $field_value);
								}
							}
							$this->Post_Process($sv_ID,$field_value,null,2,39);
						}
						break;
					case 'sv_calc_grouping':
						preg_match_all('!\d+!', $field_value, $numbers);//This will return all the numbers in the string
						///	print_r($numbers);
						foreach ($numbers[0] as $v)
						{

							if ($this->formIdMap(2,'Element',$v)!=null){
								$field_value=str_replace($v,$this->formIdMap(2,'Element',$v), $field_value);
							}
						}
						///echo $field_value;
						$this->Post_Process($sv_ID,$field_value,null,2,41);
						break;
					}
				}
				
			}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_screen'||  $this->Statement_ID($statement,null,null,1)=='_formulize_screen_form')
			{
				$ID= $this->Statement_ID($statement);
			
				$table= $this->Statement_ID($statement,null,null,1);
				$tables=array('_formulize_screen'=>19,'_formulize_screen_form'=>20);

				if ( $this->Check_Uniquines($ID,$tables[$table])==0)
				{
					$this->Insert($statement);
					echo 'Inserting New Row in $table with ID :'. $ID.' <br/>';
				}else
				{
					
					$Auto_Incr= $this->Statement_ID($statement,1);
					$ID1= $this->getlastID($Auto_Incr);

					if ($table=='_formulize_screen'){
						$this->formIdMap(1,'SID',$ID,$ID1);}//No Need to store it in the Map array but it won't hurt
					echo 'Updating Row in in '.$table.' with ID :'. $ID1 .'<br/>';
					$ID=$ID1;
				}
				
				//Post Process//
				$result= $this->get_result($table,$ID);

				//Update the FID 
				if ($table=='_formulize_screen'){
					if ($this->formIdMap(2,'Form',$result['fid'])!=null)
					{
						$this->Post_Process($ID,$this->formIdMap(2,'Form',$result['fid']),null,2,42);
					}
					if ($this->formIdMap(2,'Framelinks',$result['frid'])!=null)
					{
						$this->Post_Process($ID,$this->formIdMap(2,'Form',$result['frid']),null,2,43);
					}}
				if ($table=='_formulize_screen_form'){
					if ($this->formIdMap(2,'SID',$result['sid'])!=null)
					{
						$this->Post_Process($ID,$this->formIdMap(2,'SID',$result['sid']),null,2,44);
					}
				}
			}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_screen_multipage')
			{
				$statement=str_replace('&', ';',$statement);
				$Multi_ID= $this->Statement_ID($statement);
				//echo $Multi_ID;
				if ( $this->Check_Uniquines($Multi_ID,21)==0)
				{
					$this->Insert($statement);
					echo 'Inserting New Row in Screen Multipage with ID :'. $Multi_ID .' <br/>';
				}else
				{
					$Aut_Incr= $this->Statement_ID($statement,1);
					$ID1= $this->getlastID($Aut_Incr);
					//echo $ID1;
					$this->formIdMap(1,'multipageid',$Multi_ID,$ID1);//No Need to store it in the Map array but it won't hurt
					echo 'Updating Row in in Screen Multipage  with ID : '. $ID1 .' <br/>';
					$Multi_ID=$ID1;
				}
				//Post Process
				$result= $this->get_result('_formulize_screen_multipage',$Multi_ID);
				if ($this->formIdMap(2,'SID',$result['sid'])!=null)
				{
					$this->Post_Process($Multi_ID,$this->formIdMap(2,'SID',$result['sid']),null,2,45);
				}
				if ($result['paraentryform']!=0)//If not default Zero 
				{
					$this->Post_Process($Multi_ID,$this->formIdMap(2,'Form',$result['paraentryform']),null,2,48);
				}
				if (!empty($result['pages'][0]))
				{
					$data1=unserialize($result['pages']);
					foreach($data1[0] as $k=>$v)
					{
						if ($this->formIdMap(2,'Element',$v)!=null){ // Updates Elements not_cons_elementuids 
							$v=$this->formIdMap(2,'Element',$v);
							$data1[0][$k]=$v;
							//$this->Post_Process ($ID,$ForID1,null,2,31);
						}
					}
					$this->Post_Process($Multi_ID,serialize($data1),null,2,46);
				}
				//To Update the Condition Field ;
				if (!empty($result['conditions'])){//echo "Here";
					$data1=unserialize($result['conditions']);
					//$data1);
					foreach ($data1 as $K1=>$D){
						foreach ($D[0] as $k => $d) //To Change the Array of Elements Handle 
						{//echo "$d";
							//print_r($d);
							if ($this->formIdMap(2,'Element_Handle',$D[0][$k])!=null)
							{ //echo 'Here";
								$data1[$K1][0][$k]=$this->formIdMap(2,'Element_Handle',$D[0][$k]);
								//echo $
							}}
						foreach ($D[2] as $k => $d)
						{
							if (preg_match('/\{.*\}/',$d)) {
								$s=explode('{',$d);
								$s=explode('}',$s[1]);
								if ($this->formIdMap(2,'Element_Handle',$s[0])!=null){
									$v='{'.$this->formIdMap(2,'Element_Handle',$s[0]).'}';
									$data1[$K1][2][$k]=$v;
								}}}
					}
					$this->Post_Process($Multi_ID,serialize($data1),null,2,47);
				}}
			if ( $this->Statement_ID($statement,null,null,1)=='_formulize_screen_listofentries')
			{
				$statement=str_replace('&', ';',$statement);
				$ListEntries_ID= $this->Statement_ID($statement);
				if ( $this->Check_Uniquines($ListEntries_ID,23)==0)
				{
					$this->Insert($statement);
					echo 'Inserting New Row in Screen Screen List of Entries with ID :'. $ListEntries_ID.' <br/>';
				}else{
					$Aut_Incr= $this->Statement_ID($statement,1);
					$ID1= $this->getlastID($Aut_Incr);
					//echo $ID1;
					$this->formIdMap(1,'ListofEntriesID',$ListEntries_ID,$ID1);//No Need to store it in the Map array but it won't hurt
					echo 'Updating Row in in Screen List of Entries  with ID :'. $ID1 .'<br/>';
					$ListEntries_ID=$ID1;
				}
				$result= $this->get_result('_formulize_screen_listofentries',$ListEntries_ID);
				$Decolums=unserialize($result['decolumns']);
				$hiddencolumns=unserialize($result['hiddencolumns']);
				$limitviews=unserialize($result['limitviews']);
				if ($this->formIdMap(2,'SID',$result['sid'])!=null)
				{
					$this->Post_Process($ListEntries_ID,$this->formIdMap(2,'SID',$result['sid']),null,2,50);
				}
				if ($this->formIdMap(2,'SID',$result['defaultview'])!=null)
				{
					$this->Post_Process($ListEntries_ID,$this->formIdMap(2,'SID',$result['sid']),null,2,52);
				}
				foreach ($limitviews as $key=>$value)
				{
					if ($this->formIdMap(2,'SID',$value)!=null)
					{
						$limitviews[$key]=''.$this->formIdMap(2,'SID',$value).'';
					}
				}
				if ($result['decolumns']!='b:0;'|| $result['decolumns']!='a:0:{}')
				{
					foreach ($Decolums as $key=>$data)
					{
						if ($this->formIdMap(2,'Element_Handle',$data)!=null)
						{
							$Decolums[$key]=$this->formIdMap(2,'Element_Handle',$data);
						}
					}
					$this->Post_Process($ListEntries_ID,serialize($Decolums),null,2,54);
				}
				if ($result['hiddencolumns']!='b:0;'|| $result['hiddencolumns']!='a:0:{}')
				{
					foreach ($hiddencolumns as $key=>$data)
					{
						if ($this->formIdMap(2,'Element_Handle',$data)!=null)
						{
							$hiddencolumns[$key]=$this->formIdMap(2,'Element_Handle',$data);
						}
					}
					$this->Post_Process($ListEntries_ID,serialize($hiddencolumns),null,2,53);
				}
				if ($this->formIdMap(2,'SID',$result['viewentryscreen'])!=null)
				{
					$this->Post_Process($ListEntries_ID,$this->formIdMap(2,'SID',$result['viewentryscreen']),null,2,55);
				}
				$this->Post_Process($ListEntries_ID,serialize($limitviews),null,2,51);
			}
		}//Add New Table before this Curly Bracket_formulize_screen

		//$Element_ID is an array that Holds all the Inserted Element ID.If it was New ID or Auto-Incremented. 
		$this->ser($Element_ID,1);//To Update Ele_Value
		$this->ser($Element_ID,2,1);//To Update Ele_Filter settings 
		//This Process Needs to be done after all groups have been Updated It Updates the Ele_Disable/Ele_Display in Formulize Table
		$this->Post_Process($Element_ID,null,null,3,11,'ele_disabled');
		$this->Post_Process($Element_ID,null,null,3,12,'ele_display');
	}

	private Function Post_Process ($ID,$Update=null,$Flag=null,$switch,$table=null,$Flag1=null) {
		//Table Array Holds the Fields and table name that Will be Updated
		$tables = array (2=>'_formulize_application_form_link/`appid`/linkid',3=>'_formulize_application_form_link/`fid`/`linkid`',4=>'_formulize/`id_form`/`ele_id`'
		,5=>'_formulize/`ele_value`/`ele_id`',6=>'_formulize_framework_links/`fl_frame_id`/`fl_id`',7=>'_formulize_framework_links/`fl_form1_id`/`fl_id`',
		8=>'_formulize_framework_links/`fl_form2_id`/`fl_id`',9=>'_formulize_framework_links/`fl_key1`/`fl_id`',10=>'_formulize_framework_links/`fl_key2`/`fl_id`',
		11=>'_formulize/`ele_display`/`ele_id`',12=>'_formulize/`ele_disabled`/`ele_id`',13=>'_formulize_id/`form_handle`/`id_form`',14=>'_formulize/`ele_handle`/`ele_id`'
		,15=>'_formulize/`ele_filtersettings`/`ele_id`',16=>'_formulize_entry_owner_groups/`groupid`/`owner_id`',17=>'_formulize_entry_owner_groups/`fid`/`owner_id`',
		18=>'_formulize_group_filters/`groupid`/`filterid`',19=>'_formulize_group_filters/`fid`/`filterid`',20=>'_formulize_group_filters/filter/filterid',
		21=>'_formulize_groupscope_settings/`groupid`/`groupscope_id`',22=>'_formulize_groupscope_settings/view_groupid/`groupscope_id`',
		23=>'_formulize_groupscope_settings/fid/`groupscope_id`',24=>'_group_lists/gl_groups/`gl_id`',25=>'_formulize_advanced_calculations/fltr_grps/acid',
		26=>'_formulize_advanced_calculations/fid/acid',27=>'_formulize_other/ele_id/other_id',28=>'_formulize_notification_conditions/not_cons_fid/not_cons_id',
		29=>'_formulize_notification_conditions/not_cons_event/not_cons_id',30=>'_formulize_notification_conditions/not_cons_groupid/not_cons_id',31=>'_formulize_notification_conditions/not_cons_elementuids/not_cons_id'
		,32=>'_formulize_notification_conditions/not_cons_elementemail/not_cons_id',33=>'_formulize_notification_conditions/not_cons_con/not_cons_id'
		,34=>'_formulize_saved_views/sv_pubgroups/sv_id/',35=>'_formulize_saved_views/sv_owner_uid/sv_id/',36=>'_formulize_saved_views/sv_mod_uid/sv_id/',
		37=>'_formulize_saved_views/sv_formframe/sv_id/',38=>'_formulize_saved_views/sv_mainform/sv_id/',39=>'_formulize_saved_views/sv_calc_cols/sv_id/',
		40=>'_formulize_saved_views/sv_oldcols/sv_id/',41=>'_formulize_saved_views/sv_calc_grouping/sv_id/',42=>'_formulize_screen/fid/sid',
		43=>'_formulize_screen/frid/sid',44=>'_formulize_screen_form/sid/formid',45=>'_formulize_screen_multipage/sid/multipageid',46=>'_formulize_screen_multipage/pages/multipageid'
		,47=>'_formulize_screen_multipage/conditions/multipageid',48=>'_formulize_screen_multipage/paraentryform/multipageid',
		50=>'_formulize_screen_listofentries/sid/listofentriesid',51=>'_formulize_screen_listofentries/limitviews/listofentriesid',
		52=>'_formulize_screen_listofentries/defaultview /listofentriesid',53=>'_formulize_screen_listofentries/hiddencolumns/listofentriesid'
		,54=>'_formulize_screen_listofentries/decolumns/listofentriesid',55=>'_formulize_screen_listofentries/viewentryscreen/listofentriesid',
		56=>'_group_permission/gperm_itemid/gperm_id',57=>'_group_permission/gperm_groupid/gperm_id');


		$fields=explode ('/',$tables[$table]);

		switch ($switch) {
		case 1:
			//Removed this Case statement Part and changed it to a more efficient one //Will Remove this Case and Update the Function Parameters in the next code Cleaning  
			break;
		case 2:
			$sql='UPDATE '.Prefix.''.$fields[0].' SET '.$fields[1].'= :Update WHERE '.$fields[2].'= :id';
			$Query=$this->_db->connect()->prepare($sql);
			break;
		case 3:
			//This Is done because it's much easier to Get the Ele_Display/Ele_Disable from the DB after its inserted.Preg-match and Str_Pos won't be Reliable to get from Insert String
			foreach ( $ID as $ElementID){
				$result = $this->get_result('_formulize',$ElementID);
				if ($result["$Flag1"]!='1' && $result["$Flag1"]!='0'){ //To make sure the Result is not 1 or 0
					($Flag1=="ele_display" ? $this->update_groups ('_formulize',$ElementID,3) : $this->update_groups ('_formulize',$ElementID,4));}}
			break;
		}	

		if ($Flag1==null){//This iF The Post Process In Case 2: Which is the General Update Fields  for any Field ,but the Ele_display needs to be returned Broken Down and Processed to check each Group
			$Query->bindValue(':Update',$Update);
			$Query->bindValue(':id',$ID);
			$Query->execute();
		}
	
	}


	private Function Check_Uniquines ($ID,$field){/* This Function Checks if the ID are Unique or not .If Unique then it will return 0.*/

		$Check;
		$tables=array (1=>'_formulize_application_form_link/`linkid` ',2=>'_formulize_applications/`appid`',3=>'_formulize_id/`id_form`'
		,4=>'_formulize/ele_id',5=>'_formulize/`ele_handle`',6=>'_formulize_frameworks/`frame_id`',7=>'_formulize_framework_links/`fl_id`',8=>'_formulize_id/desc_form',9=>'_groups/`groupid`'
		,10=>'_formulize_id/`form_handle`',11=>'_formulize_entry_owner_groups/`owner_id`',12=>'_formulize_group_filters/filterid',13=>'_formulize_groupscope_settings/groupscope_id'
		,14=>'_group_lists/`gl_id`',15=>'_group_lists/gl_name',16=>'_formulize_advanced_calculations/acid',17=>'_formulize_other/other_id',18=>'_formulize_saved_views/sv_id'
		,19=>'_formulize_screen/sid',20=>'_formulize_screen_form/formid',21=>'_formulize_screen_multipage/multipageid',22=>'_formulize_notification_conditions/not_cons_id'
		,23=>'_formulize_screen_listofentries/listofentriesid',24=>'_group_permission/gperm_id');
		$fields=explode ('/',$tables[$field]);

		$Query=$this->_db->connect()->prepare('SELECT COUNT( * ) AS num FROM '.Prefix.''.$fields[0].' WHERE '.$fields[1].'= :id ;') ;
		$Query->bindValue(':id',$ID);
		$Query->execute();
		$Check=$Query->fetch(\PDO::FETCH_ASSOC);
		return $Check['num'];
	}

	private Function getlastID ($sql_st){ //This Is used because the default getlastID syntax doesn't work
		//echo $statment;
		$sel=';select last_insert_id() as ID;';
		$sql_st.=$sel;

		$Query=$this->_db->connect()->prepare($sql_st);
		$Query->execute();
		$Query->nextRowset();
		$result=$Query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as $key) { $NewID=$key['ID'];}
		return $NewID;
	}

	private function formIdMap($a,$t=null,$b=null,$c=null) {
		STATIC $id_map; // Static to hold the array until session ends //This is a Multi-Dimensional Array
		//To add a New Array formidMap (1,"New Array Name",OLD Map,NewMap);
		//To Retrieve results formidMap (2,"New Array Name",OLD Map);
		switch ($a)
		{ 	
		case 1: // Add
			{  if (isset($id_map[$t][$b]))
				{
					$ret='Rejected';
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
	private function ser($AllElements,$Flag,$table=null) {//Function used to Update the Ele_Value and Ele_settings. Flag=1: Ele_Value and Flag=2:Ele_filter settings

		$result=array();
		$type=array('text','grid','textarea','select');

		$fields=array (1=>'_formulize/ele_filtersettings/ele_id/15',2=>'_formulize_group_filters/filter/filterid/20',
		3=>'_formulize_advanced_calculations/fltr_grps/acid/25',4=>'_formulize_notification_conditions/not_cons_con/not_cons_id/33'
		,5=>'_formulize_screen_multipage/conditions/multipageid/47');//We Assume notification_conditions filter is same as before
		$fields=explode ('/',$fields[$table]);

		if ($Flag==1){
			foreach ($AllElements as $keyid) { 
				$Query=$this->_db->connect()->prepare('SELECT ele_type,ele_value FROM '.Prefix.'_formulize WHERE ele_id=:ID') ;
				$Query->bindValue(':ID',$keyid);
				$Query->execute();
				$result=$Query->fetch(\PDO::FETCH_ASSOC);
				$data1=unserialize($result['ele_value']);

				switch ($result['ele_type']){
				case 'text':
				case 'grid'://If Row was Text or Grid
					if (array_key_exists(4, $data1)){
						if ($this->formIdMap(2,'Element',$data1[4])!=null){
							$data1[4]= $this->formIdMap(2,'Element',$data1[4]);
							$this->Post_Process($keyid,serialize($data1),null,2,5);
						}}
					break;
				case 'textarea': //If Row was Text Area
					if (array_key_exists(3, $data1)){
						if ($this->formIdMap(2,'Element',$data1[3])!=null){
							$data1[3]=$this->formIdMap(2,'Element',$data1[3]);
							$this->Post_Process($keyid,serialize($data1),null,2,5);}}
					break;
					
				case 'select': //If it was Select// Case 2 :is the link between Form and Element,Case 3 :Is List of Group ID,Case 10:11:12,Case 5:Deals with Array similar to Filter settings
					foreach ($data1 as $k => $d)
					{
						switch ($k){
						case 2:
							if (strstr ($d,'#*=:*')){
								$s=explode ('#*=:*',$d);///print_r($s);echo $keyid."<br/>";
								if ($this->formIdMap(2,'Form',$s[0])!=null){ 
									$d=str_replace($s[0],$this->formIdMap(2,'Form',$s[0]),$d);}
								if ($this->formIdMap(2,'Element_Handle',$s[1])!=null)
								{
									$d=str_replace($s[1],$this->formIdMap(2,'Element_Handle',$s[1]),$d);
								}
								$data1[2]=$d;
							}
							//echo $data1[2];
							break;//End of Case 2
						case 3:
							if (strstr ($d,',') || is_numeric($d)){//If it was a List of Groups or Single ID\\\\
								$s=explode(',',$d);
								foreach ($s as $key=>$v){//Update the Code Instead of Using Str_Replace to replace the value ,I reassign the Key of the array.It won't make difference in the Front end ,but just to keep things cleaner in the back end.For example if we ignored a group the str_replace will remove the grp id but keep the comma,however,unset will remove the value and , because of how Implode works. 
									if ($this->formIdMap(2,'Group_Map_Auto',$v)!=null){//To Update The Create New Group
										$grpID_N=$this->formIdMap(2,'Group_Map_Auto',$v);
										$s[$key]=$grpID_N;}
									if ($this->formIdMap(2,'Group_Map',$v)!=null){ //To Update the Map Group
										$grpID_N=$this->formIdMap(2,'Group_Map',$v);
										$s[$key]=$grpID_N;}
									if ($this->formIdMap(2,'Group_Ignore',$v)!=null){ //To Remove the Group that's flagged as Ignore by replacing it with ''
										unset($s[$key]);}
								}
								$data1[3]=implode(',',$s);;
							}
							break;//End of Case 3
						case 5://To Update the Array of Ele_ID=>{ele_handle}
							foreach ($data1[5][0] as $k => $d) //To Change the Array of Elements Handle 
							{
								if ($this->formIdMap(2,'Element_Handle',$data1[5][0][$k])!=null)
								{$data1[5][0][$k]=$this->formIdMap(2,'Element_Handle',$data1[5][0][$k]);} 
							}
							foreach ($data1[5][2] as $k => $d)
							{
								if (preg_match('/\{.*\}/',$d)) {
									$s=explode('{',$d);
									$s=explode('}',$s[1]);
									$v='{'.$this->formIdMap(2,'Element_Handle',$s[0]).'}';
									$data1[5][2][$k]=$v;}}
							break;//End of Case 5:
						case 10:
						case 11:
						case 12:
							if ($d!='none'){
								if ($this->formIdMap(2,'Element',$d)!=null){ // Updates Elements 1
									$data[$k]=''.$this->formIdMap(2,'Element',$d).'';}}
							break;
						}
					}
				}

				$this->Post_Process($keyid,serialize($data1),null,2,5);

				if (!in_array($result['ele_type'],$type)){
					$this->Post_Process($keyid,$result['ele_value'],null,2,5);
				}
			}
		}

		if ($Flag==2)
		{

			$tf=Prefix.$fields[0];

			foreach ($AllElements as $keyid) { 

				$Query=$this->_db->connect()->prepare('SELECT '.$fields[1].' FROM '.$tf.' WHERE '.$fields[2].'=:ID') ;
				$Query->bindValue(':ID',$keyid);
				$Query->execute();
				$result=$Query->fetch(\PDO::FETCH_ASSOC);

				$data1=unserialize($result[$fields[1]]); 
				//print_r($data1);
				if ($fields[2]!='acid'){
					foreach ($data1[0] as $k => $d) //To Change the Array of Elements Handle 
					{//echo "$d";
						///print_r($d);
						if ($this->formIdMap(2,'Element_Handle',$data1[0][$k])!=null)
						{ //echo "Here";
							$data1[0][$k]=$this->formIdMap(2,'Element_Handle',$data1[0][$k]);
							//echo $
						}}
					foreach ($data1[2] as $k => $d)
					{
						if (preg_match('/\{.*\}/',$d)) {
							$s=explode('{',$d);
							$s=explode('}',$s[1]);
							if ($this->formIdMap(2,'Element_Handle',$s[0])!=null){
								$v='{'.$this->formIdMap(2,'Element_Handle',$s[0]).'}';
								$data1[2][$k]=$v;
							}
						}
					}
				}
				else 
				{
					if ($this->formIdMap(2,'Element_Handle',$data1[0]['form'])!=null){//To get the App cal ELement Handle we don't need to loop in it because it will always be one Element
						$data1[0]['form']=$this->formIdMap(2,'Element_Handle',$data1[0]['form']);
					}
				}
				//print_r($data1);
				$this->Post_Process($keyid,serialize($data1),null,2,$fields[3]);
			}}
	}
 
	private function get_Groups(){

		$newGroups= $_SERVER["QUERY_STRING"];
		$Ignore=array();
		$Insert=array();
		$map=array();
		$break=explode('&',$newGroups);
		foreach ($break as $k)
		{
			if(preg_match('/\d*=Ignore/',$k))  { preg_match('/\d*=Ignore/',$k,$ign); $ign=explode('=',$ign[0]); $this->formIdMap(1,"Group_Ignore",$ign[0],22);}
			if (strpos($k, 'Ignore') == false && strpos($k,'New') == false){ preg_match('/\d*=\d*/',$k,$ign); $ign=explode('=',$ign[0]); $this->formIdMap(1,"Group_Map",$ign[0],$ign[1]); } //Array Map
			if (preg_match(' /\d*=New/',$k)) {preg_match('/\d*=New/',$k,$map1); $map1=explode('=',$map1[0]); $this->formIdMap(1,"Group_Insert",$map1[0],11);} //Array Map
		}
	} 

	private function Insert ($sql_st){
		
		$Query=$this->_db->connect()->prepare($sql_st);
		$Query->execute();
	}

	private function Create_table($insert_sql ){

		$insert_sql=explode('^',$insert_sql);
		$AllHandles1=explode (',',$insert_sql[1]);
		$AllHandles2=explode ('\'',$AllHandles1[0]);
		if ($this->formIdMap(2,'Form_Handle',$AllHandles2[0])!=null)
		{
			$AllHandles2[0]=$this->formIdMap(2,'Form_Handle',$AllHandles2[0]);
		}
		$sql_1='CREATE TABLE IF NOT EXISTS `'.Prefix.'_formulize_'.$AllHandles2[0].'` (
			`entry_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
			`creation_datetime` datetime DEFAULT NULL,
			`mod_datetime` datetime DEFAULT NULL,
			`creation_uid` int(7) DEFAULT \'0\',
			`mod_uid` int(7) DEFAULT \'0\',';

		$sql_2= 'PRIMARY KEY (`entry_id`),
			KEY `i_creation_uid` (`creation_uid`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;';
		foreach ($AllHandles1 as $k=> $column)
		{
			if ($k!=0){
				if ( $this->formIdMap(2,'Element_Handle',$column) != null){
					$column = $this->formIdMap(2,"Element_Handle",$column);}
				$sql_1.="`$column` text,";
			}}
		$sql_1.=$sql_2;
		$sql_1.=$insert_sql[2];
		$sql_1.=';';
		$sql_1=preg_replace('/Table_Name/', '`'.Prefix.'_formulize_'.$AllHandles2[0].'`', $sql_1);
		$this->Insert($sql_1);
		$this->Insert ('UPDATE `'.Prefix."_formulize_$AllHandles2[0]` SET `creation_uid`=".SID.',`mod_uid`='.MOD_ID.'');//To Update Formulize_Handle Mod ID to the User who's carrying out the Impot
		echo "Creating Table Formulize_$AllHandles2[0] <br/>";
	}


	private function update_groups ($table,$ID,$flag=null){ 
		///echo $ID;
		$tables=array ('_formulize_entry_owner_groups'=>'groupid/owner_id/16','_formulize_group_filters'=>'groupid/filterid/18'
		,'_formulize_groupscope_settings'=>'groupid/groupscope_id/21','_2formulize_groupscope_settings'=>'view_groupid/groupscope_id/22','_group_lists'=>'gl_groups/gl_id/24'
		,'_formulize_notification_conditions'=>'not_cons_groupid/not_cons_id/30','_formulize_saved_views'=>'sv_pubgroups/sv_id/34'
		,'_formulize'=>'ele_display/ele_id/11','_2formulize'=>'ele_disabled/ele_id/12','_group_permission'=>'gperm_groupid/gperm_id/57');
		$f;
		//echo $tables[$table];
		switch ($flag){
		case null:
			$fields=explode ('/',$tables[$table]);
			break;
		case 1:
			$tb='_formulize_groupscope_settings';
			$fields=explode ('/',$tables[$tb]);
			break;
		case 2:
			$tb='_2formulize_groupscope_settings';
			$fields=explode ('/',$tables[$tb]);
			break;
		case 3:
			$tb='_formulize';
			$fields=explode ('/',$tables[$tb]);
			break;
		case 4:
			$tb='_2formulize';
			$fields=explode ('/',$tables[$tb]);
			break;
		}

		$table1=Prefix.$table;


		$Query = $this->_db->connect()->prepare("SELECT ".$fields[0]." from ".$table1." where ".$fields[1]." =:id") ;
		$Query->bindValue(":id",$ID);
		$Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);

		if (strstr($result[$fields[0]],',')){ $f=1;}

		$result=explode(',',$result[$fields[0]]);
		foreach ($result as $k=>$d){
			if ($this->formIdMap(2,'Group_Map_Auto',$d)!=null){//To Update The Create New Group
				$grpID_N=$this->formIdMap(2,'Group_Map_Auto',$d);
				if (empty($f)){
					$this->Post_Process($ID,$grpID_N,null,2,$fields[2]);}else {$result[$k]=$grpID_N; ///print_r($result);
				}
			}
			if ($this->formIdMap(2,'Group_Map',$d)!=null){ //To Update the Map Group
				$grpID_N=$this->formIdMap(2,'Group_Map',$d);
				if (empty($f)){
					$this->Post_Process($ID,$grpID_N,null,2,$fields[2]);}else {
					$result[$k]=$grpID_N;
				}
			}

			if ($this->formIdMap(2,'Group_Ignore',$d)!=null){
				if (empty($f)){	//To Remove the Group that's flagged as Ignore by replacing it with ID 999
				$this->Post_Process ($ID,999,null,2,$fields[2]);}else {
				$result[$k]=999;}
			}}
		if (!empty($f))
		{
			$final=implode (',',$result);
			$this->Post_Process($ID,$final,null,2,$fields[2]);
		}
		if ($table=='_formulize_group_filters')
		{
			$a=array();
			array_push($a,$ID);//Because the Function Expects an Array of IDS
			ser($a,2,2);
		}
	}

	private function get_result($table,$ID){
		//Adapted Juilan regarding Array Map Idea instead of Explode on /
		$tables = array('_formulize_saved_views'=>array('sv_pubgroups,sv_owner_uid,sv_mod_uid,sv_formframe,sv_mainform,sv_sort,sv_oldcols,sv_currentview,sv_calc_cols,sv_calc_grouping',Prefix.'_formulize_saved_views','sv_id')
		,'_formulize_screen'=>array('fid,frid',Prefix.'_formulize_screen','sid'),'_formulize_screen_form'=>array('sid',Prefix.'_formulize_screen_form','formid'),
		'_formulize_screen_multipage'=>array('sid,pages,conditions,paraentryform',Prefix.'_formulize_screen_multipage','multipageid'),
		'_formulize'=>array('id_form,ele_display,ele_disabled',Prefix.'_formulize','ele_id'),'_formulize_application_form_link'=>array('appid,fid',Prefix.'_formulize_application_form_link','linkid')
		,'_formulize_framework_links'=>array('fl_frame_id,fl_form1_id,fl_form2_id,fl_key1,fl_key2',Prefix.'_formulize_framework_links','fl_id')
		,'_formulize_advanced_calculations'=>array ('fid',Prefix.'_formulize_advanced_calculations','acid'),'_formulize_other'=>array ('ele_id',Prefix.'_formulize_other','other_id')
		,'_formulize_notification_conditions'=>array('not_cons_fid,not_cons_groupid,not_cons_elementuids,not_cons_elementemail,not_cons_con',Prefix.'_formulize_notification_conditions','not_cons_id')
		,'_formulize_screen_listofentries'=>array('sid,limitviews,defaultview,hiddencolumns,decolumns,viewentryscreen',Prefix.'_formulize_screen_listofentries','listofentriesid'));

		//echo "SELECT ".$tables[$table][0]." from ".$tables[$table][1]." where ".$tables[$table][2]." =$ID";
		$Query=$this->_db->connect()->prepare("SELECT ".$tables[$table][0]." from ".$tables[$table][1]." where ".$tables[$table][2]." =:id") ;
		$Query->bindValue(":id",$ID);
		$Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		return $result;	
	}


	private function Statement_ID ($statement,$Flag=null,$sec=null,$table=null){

		if ($Flag==null && empty($table))
		{
			if ($sec==null){
				preg_match('/\(\'\d*\'\,/', $statement, $matches);//To get Any Digit number .Not just 2 digit number as the old preg match did
				preg_match_all('!\d+!', $matches[0], $numbers);
				return $numbers[0][0];}
			if ($sec!=null)
			{
				preg_match('/\(\'\d*\'\,\'\d*\'/', $statement, $matches);
				$ID=explode(',',$matches[0]);
				//echo $ID[1];
				return $ID[1];
			}
		}
		else {
			if (empty($table)){ 
				if ($sec==null){
					$statement=preg_replace('/\(\'\d*\'\,/',"('',", $statement);
					///echo $statement;
					return $statement; 
				}
				if ($sec!=null)
				{
					preg_match('/\(\'\d*\'\,\'\d*\'/', $statement, $matches);
					$ID=explode(',',$matches[0]);
					///print_r($ID);
					$statement=preg_replace('/\w*VALUES \(\'\d*\'\,\'\d*\'/', "VALUES ".$ID[0].",''", $statement);
					//echo $statement;
					return $statement;
				}
			}}
		if ($table!=null)
		{
			preg_match('/_.*\(\`/', $statement, $table);//To get the Table name
			$rem=array('VALUES','`','(',' ',')');
			$table=str_replace($rem,'', $table[0]);
			return $table;
		}
	}



}

	

?>