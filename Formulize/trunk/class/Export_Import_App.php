<?php
include 'PDO_Conn.php';//Include the Connection File
	
	
	//Import();
	//echo Check_Uniquines (3,1);
	//Application Runs From Here:
	Check_Post_Parameter();
	//Function To check if the Button Export or Import is Pressed and Loads the Function
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
	{//Add prefix
	$getfield=Get_FieldNames($table);
	$appid = intval($_GET['aid']);
	//$table='_formulize_applications';
	
	if ($table=='_formulize_application_form_link') {
	$k1=1;
	 $Links=Application_Fourm_Links($appid);
	 	  
	  $Insert="Insert INTO Prefix".$table." VALUES (linkid,appid,fid) (";
	 foreach ($Links as $key => $column) {
	
	 foreach($getfield as $k => $cur)
	{
	
	//Fixed
	if ($k1==count($getfield)){
	$Insert.=$column[$cur['Field']];
	$Insert.=');';
	array_push($SQLStatments,$Insert);
	echo $Insert;
	  $Insert="Insert INTO Prefix".$table." VALUES (linkid,appid,fid) (";
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

	if ($k1==count($getfield)){
	$Insert.=$column[$cur['Field']];
	$Insert.=');';
	array_push($SQLStatments,$Insert);
	echo $Insert;
	  $Insert="Insert INTO Prefix".$table." (appid, name,description) VALUES (";
	}else {
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	++$k1;
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
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	//echo "<br/>";
	++$k1;
	}
	}
	}
	if ($k1==11){$k1=1;}
	//echo $Insert;
	 }}
}
	//Function to Write the Insert Statements
	Function Export_All()
	{
		//First Create Applications,Fourms and The Link them
		Create_Insert_Statments("_formulize_applications");
		Create_Insert_Statments("_formulize_id");
		Create_Insert_Statments("_formulize_application_form_link");
		Write_To_File();
	
	}
	
	
	//Function to get the Fourms that's Linked with the requested AppID from URL
	 function Application_Fourm_Links($appid, $Uniq=null)
    {
	
		if ($Uniq==null){
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
        $Query=$conn->connect()->prepare("SELECT COUNT( * ) AS num from ".$table." where fid= :id") ;
        $Query->bindValue(":id",$appid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		}endif;
		
		
		
		} return $result; }
		
	//Function to get the App by ID \\\\
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
	//Function to get the Application Rows by AppID
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
	echo "Hello";
	$APP_ID_Replace;//This will store the New App ID if Needed
	$Form_ID_Replace=array ();
	//Some Error Handling will need to take place here and make sure not to overwrite anything in the DB
	$file = fopen(".$filename.", "r");
	while (!feof($file)) {
	$getlines = fgets($file);
	$get_line=explode(";",$getline);
	foreach ($get_line as $statement)
   {
   
	if(strstr($subject, "_formulize_applications")) {
	
	preg_match('/\(\d\d|\(\d/', $statement, $matches);
	$x1=explode('(',$matches[0]);
	if (Check_Uniquines ($x1[1],2)==0)
	{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	
	}else {
	//Do this $APP_ID_Replace =$matches1[0].New[];
	$Se=preg_replace('/\(\d\d|\(\d/', "(", $statement);
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($Se);
	$Query->execute();
	$Query=$conn->connect()->prepare("SELECT max(appid) FROM ".Prefix."_formulize_applications");
	$Query->execute();
	$result=$Query->fetch(\PDO::FETCH_ASSOC);
	$APP_ID_Replace=$matches1[0].":".$result['appid'];
	
    }}
	if(strstr($subject, "_formulize_id")) {
	 preg_match('/\(\d\d|\(\d/', $statement, $matches);
	$x1=explode('(',$matches[0]);
	if (Check_Uniquines ($x1[1],3)==0)
	{
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($statement);
	$Query->execute();
	
	}else {
	//Do this 
	$Se=preg_replace('/\(\d\d|\(\d/', "(", $statement);
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($Se);
	$Query->execute();
	$Query=$conn->connect()->prepare("SELECT max(id_form) FROM ".Prefix."_formulize_id");
	$Query->execute();
	$result=$Query->fetch(\PDO::FETCH_ASSOC);
	$matches1[0].":".$result['appid'];
	$Query=$conn->connect()->prepare("INSERT INTO form_mapping VALUES (:id,id2)");
	$Query->bindValue(":id",$matches1[0]);
	$Query->bindValue(":id2",$result['id_form']);
	$Query->execute();
    }
	}
	}
	//Here is Different than the Above Statments//Because we need to link the New ID if the fourms/App has been updated
	foreach ($get_line as $statement){
	if(strstr($subject, "_formulize_application_form_link")) {
	preg_match('/\(\d\d|\(\d/', $statement, $matches);
	$x1=explode('(',$matches[0]);
	if (Check_Uniquines ($x1[1],1)==0)
	{ /////////
	$APP_ID_Replace;//This will store the New App ID if Needed
	$Form_ID_Replace=array ();
	if (empty($APP_ID_Replace))
	{ 
		if (empty ($Form_ID_Replace)){
		$conn=new Connection ();
		$Query=$conn->connect()->prepare($statement);
		$Query->execute();}
		else{  
		$result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Fid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss2=preg_replace('/(,\d\d)\)|(,\d)\)/', ",".$result['N_FormID'].")",$statement );
		$Query=$conn->connect()->prepare($ss2);
		$Query->execute();
	}
	}else {
	$aPP_id_2=explode (":",$APP_ID_Replace);
	$ss2=preg_replace('/(,\d\d,)|(,\d,)/', ",".$aPP_id_2[1].",", $statement);
	if (empty ($Form_ID_Replace)){
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($ss2);
	$Query->execute();
	}else
	{
	$result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Fid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss2=preg_replace('/(,\d\d)\)|(,\d)\)/', ",".$result['N_FormID'].")",$statement );
		$Query=$conn->connect()->prepare($ss2);
		$Query->execute();
	}
	}
	}else {
	//Use the Auto Increment  
	$ee=preg_replace('/\(\d\d|\(\d/', "(", $statement);
		if (empty($APP_ID_Replace))
	{ 
		if (empty ($Form_ID_Replace)){
		$conn=new Connection ();
		$Query=$conn->connect()->prepare($ee);
		$Query->execute();}
		else{  
		$result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Fid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss2=preg_replace('/(,\d\d)\)|(,\d)\)/', ",".$result['N_FormID'].")",$ee );
		$Query=$conn->connect()->prepare($ss2);
		$Query->execute();
	}
	}else {
	$aPP_id_2=explode (":",$APP_ID_Replace);
	$epa=preg_replace('/(,\d\d,)|(,\d,)/', ",".$aPP_id_2[1].",", $ee);
	if (empty ($Form_ID_Replace)){
	$conn=new Connection ();
	$Query=$conn->connect()->prepare($epa);
	$Query->execute();
	}else
	{
	$result =array();
		$conn=new Connection ();
        $Query=$conn->connect()->prepare("SELECT N_FormID from form_mapping where O_FomID= :id") ;
        $Query->bindValue(":id",$Fid);
        $Query->execute();
		$result=$Query->fetch(\PDO::FETCH_ASSOC);
		$ss22=preg_replace('/(,\d\d)\)|(,\d)\)/', ",".$result['N_FormID'].")",$epa );
		$Query=$conn->connect()->prepare($ss22);
		$Query->execute();
	}
	}
	}
	
    }
	
	}
	fclose($file);
	}
	}
	

	Function Import ()
	{
				
				require_once "upload.php";
				$filename = '/Upload/'.UploadFile;
				//As soon it loads the file it changes the PREFIX word in the file to the current DB Prefix 
				replaces_Prefix_in_file ($filename);
				Creat_Applications($filename);
	
	}
	Function Check_Uniquines ($ID,$field)
	{
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
	}
	return $Check['num'];
	}


	//Table Names
	//_formulize_id 
	//_formulize_applications
	//_formulize_application_form_link
