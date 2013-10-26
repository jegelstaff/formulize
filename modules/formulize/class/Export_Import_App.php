<?php
/*
****************************************************************************************
****************************************************************************************
**************** This File checks the Query Strings and Directs the User ***************
**************** to the Correct File.Wether Export or Import			***************											
****************************************************************************************
****************************************************************************************
*/
Check_Post_Parameter();

Function Check_Post_Parameter()
	{
	if(isset($_GET['select']))
	{
			switch ($_GET['select'])
			{
				case 'Export':	
				 include 'Export.php';
				break;
				case'Import':
				include 'grp33.php';
				break;
			}
	}
	else
	{
	echo "Failed For Some Reason.Please Refresh The Page";
	}
	
	}