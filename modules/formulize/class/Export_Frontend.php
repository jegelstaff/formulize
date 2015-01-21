<?php

include_once ('Export_backend.php');
include_once('../admin/admin_header.php');
xoops_cp_header();
echo '<div style="margin-bottom: 10px;" id="formulize-logo"><img src="../images/formulize-logo.png" title="" align=""></div>';
?>
<h3 style="font-family:verdana;color:rgb(0,100,100)">Export Application</h3>
<p style="margin-bottom: 10px;font-family:arial;color:rgb(100,100,0);font-size:medium;">
    This utility will automatically export all basic tables of the selected application.</br>
    However, you need to a make decision with regard to the following <u>dynamic forms</u>, </br>
    forms selected will be exported with data and other names we will only export empty forms</br>
	Furthermore, you need also to select the group lists. 
</p>
<form action="<?php $_SERVER['PHP_SELF']; ?>" method="POST">
	<table class="formTable">
		
			<?php
				
				$Export = new Export();
			
				$appid =$_GET['aid'];

				if (is_null($appid) || !isset($appid) || empty($appid)){
					exit('Error [0] : Invalid Application Id Contact the Administrator ...No Export File is Created ... Exiting');
				}
				$row = $Export->sqlQuery("SELECT count(appid) as appid FROM ".Prefix."_formulize_applications WHERE appid = :id;","appid","$appid");
				
				$i = 0;
				if ($row[0]> 0)	
				{
					// Get Form id's to export
					$row = $Export->sqlQuery('SELECT fid FROM '.Prefix.'_formulize_application_form_link WHERE appid = :id;','fid',"$appid");

					$formsid = '('.(implode(',',$row)).')';
					session_start();
					$_SESSION['fid'] = $formsid;

					$rowset = $Export->sqlQuery('SELECT desc_form,form_handle FROM '.Prefix.'_formulize_id where id_form in '.$formsid.'order by desc_form');
					$rowset1= $Export->sqlQuery('SELECT gl_id,gl_name FROM '.Prefix.'_group_lists order by gl_name');
					if (!empty($rowset))
						{
							echo '<tr><td><label>Select Forms</label></td>';
							if (!empty($rowset1)) {echo '<td><label>Select Groups</label></td>';}
							echo '</tr>';
							echo '<td><select multiple=\"multiple\" name=FormIDS[] size=12>';
							foreach ($rowset as $key){echo "<option value=$key[form_handle]>$key[desc_form]</option>";}
						    echo '</select><br></td>';
						    if (!empty($rowset1)){
							echo '<td><select multiple="multiple" name=GroupID[] size=12>';
						    foreach ($rowset1 as $key){echo "<option value=$key[gl_id]>$key[gl_name]</option>";}
						    echo '</select><br></td></tr>';}
						}
				}else{

					exit('Error [1] : Invalid Application Id Contact the Administrator ...No Export File is Created ... Exiting');
				}

			?>
		<tr>
			<td><input type='submit' name='formSubmit' value='Export' id='export'></td>
			<td><input type="button" id='download' value="Download" onclick='myfunction()'></td>
		</tr>
	</table>
</form>

<!-- Progress bar holder -->
<div id="progress" style="margin-top: 10px; width:500px;border:3px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>
<script>
	 function hide(button){document.getElementById(button).hidden=true;}
	 function unhide(button){document.getElementById(button).hidden=false;}
	 function myfunction()
	 { var tmp =<?php echo $appid?>;
	   // link need to be updated
	   url ='download.php?aid='+tmp;
	   window.open(url);
	 }
</script>

<?php
echo "<script language='javascript'> hide('download');</script>";
if (isset($_POST['formSubmit'])) 
{
	global $i;
	echo "<script language='javascript'> hide('download');</script>";
	$Export->export();
	//output($text);
	echo "<script language='javascript'> hide('export');</script>";
	$Export->progress(100,$i);
	echo "<script language='javascript'> unhide('download');</script>";
}

include '../admin/footer.php';
xoops_cp_footer();
?>