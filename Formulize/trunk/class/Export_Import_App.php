<?php

include 'PDO_Conn.php';//Include the Connection File
	

	$SQLStatments=array();
	Check_Post_Parameter();
	//Global Array to hold Strings
	//$Upload= new Upload();
	//$Upload.load();
	//$filename = '/Upload/'.UploadFile;
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
				echo "Import";
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
	 	  
	  $Insert="Insert INTO Prefix".$table." VALUES (";
	 foreach ($Links as $key => $column) {
	
	 foreach($getfield as $k => $cur)
	{
	
	//Fixed
	if ($k1==count($getfield)){
	$Insert.=$column[$cur['Field']];
	$Insert.=');';
	array_push($SQLStatments,$Insert);
	echo $Insert;
	  $Insert="Insert INTO Prefix".$table." VALUES (";
	}else {
	$Insert.=$column[$cur['Field']];
	$Insert.=',';
	++$k1;
	
	}
	
	}
	if ($k1==3){$k1=1;}
	
	}
	 
	// echo $Insert."<br/>";
	//$Insert="Insert Values INTO Prefix".$table."(";
	 }else if ($table=='_formulize_applications'){
	 $k1=1;
	 $Links=Application_Fourm_Links($appid);
	  $Insert="Insert INTO Prefix".$table." VALUES (";
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
	  $Insert="Insert INTO Prefix".$table." VALUES (";
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
	  $Insert="Insert INTO Prefix".$table." VALUES (";
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
	  $Insert="Insert INTO Prefix".$table." VALUES (";
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
	 function Application_Fourm_Links($appid)
    {
	
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
        return $result;
       
        $a= $Query->rowCount();
		//echo $a;
        //return $result;
    }
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
	Function Get_App($appID)
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
	function Get_Form($formID)
	
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


	//Table Names
	//_formulize_id 
	//_formulize_applications
	//_formulize_application_form_link
