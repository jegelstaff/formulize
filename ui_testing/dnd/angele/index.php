<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../../../mainfile.php";
include "../../../modules/formulize/admin/application.php";

print_r($_POST); 
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8" />

	<link href="https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
	<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
	<script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>

	<!--temporary: for icons-->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
	<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="js/jsfile.js"></script>
</head>

<body>

	<div class="Formlist">
		<h2 style="margin-left: 4px;">Forms</h2>
		<ul id="form-container">
			<!--Add forms to side menu-->
			<?php
            foreach($allForms as $form) {
				print '<li class="form" id='.$form['id'].'><span><i class="fas fa-grip-vertical"></i>'.$form['name'].'</li>';
            }
            ?>
		</ul>
	</div>
	<div class="container">
		<input type="hidden" name="formulize_admin_handler" value="newUI">
		<input type="hidden" name="formulize_admin_key" value="4">
		
		<div >
		
		<h2>Relationships</h2>
    		<input type="button" value="Collapse all" onclick="closeAll()"  style="float:right;"/>  
    		<input type="button" value="Expand all" onclick="openAll()"  style="float:right;"/>    
    		<input type="button" class="savebutton" id="save" value="Save Changes"/>   
									
		</div>  
		<ul class="tree" id="root">

			<li class="addNewRel"><span><i class="fa fa-plus"></i> Add new relationship</span></li>
			<!--populate relationships-->
			<script type='text/javascript'>
				$(document).ready(function() {
					/*Create forms*/
					<?php 
					foreach($relationships as $rel) {
						print "addRel('".$rel['content']['frid']."','".$rel['name']."',[";
						
						$start = true;
						foreach($rel['content']['links'] as $link) {
							if(!$start) { print ", "; }
							print "['".$link['form1']."','".$link['form2']."','".$link['relationship']."']";
							$start = false;
						}
						print "]);";
					}?>
					refreshCounters();
					
					// <input type="hidden" name="formulize_admin_handler" value="application_settings"> // handler name indicates the file that will read the data and save it
					// you can write your own handler file and put it in modules/formulize/admin/save/newUI_save.php <input type="hidden" name="formulize_admin_handler" value="newUI">
					// <input type="hidden" name="formulize_admin_key" value="4"> // admin key indicates the primary key of the thing you're saving...probably not applicable in this case
					//$.post("save.php, $(thisformdata).serialize(), function() { // save.php will hand off to the declared handler based on the hidden field packaged up with the data being posted
					//});
					//

				});
			</script>
		</ul>

		<span class="removeRel"><i class="fas fa-trash"></i> Remove </span>
		<a href='index.php?debug=true'>debug</a>
		<?php 
				function debug(){
					//print form data
							$forms_handler = xoops_getmodulehandler('forms', 'formulize');
							$forms = $forms_handler->getAllForms();


							$formdata = array();
							foreach($forms as $form) {
								$form_title = $form->getVar('title');
								$form_handle = $form->getVar('form_handle');
								$fid = $form->getVar('id_form');

								array_push($formdata, array("fid" => $fid, "title" => $form_title, "form_handle" => $form_handle));
							}
							print_r('<pre>');
							print_r($formdata);
							print_r('</pre>');
				}
				if (isset($_GET['debug'])) {
			debug();
		  }	?>
	</div>

	<!--Relationship settings popular for newly created relationships-->
	<div id="RelationshipPopup" class="popup">
		<!-- Modal content -->
		<div class="popup-content">
			<span class="close">&times;</span>
			<h3>New Relationship</h3>
			<p>Creating a <span><select name="Relationship1" class="popup-selector">
						<option>one to one</option>
						<option>one to many</option>
					</select></span> relationship. How would you like them to be linked?</p>
			<div style="margin-bottom: 24px;">
				<p>Link between these forms:</p>
				<select name="linkbetweenforms" class="popup-selector">
					<option>User ID of the person who filled them in</option>
					<option>Common value in two elements [pick elements]</option>
				</select>
			</div>
			
			<input type="checkbox" name="c1">Display as a single form<br>
			<input type="checkbox" name="c2">Delete linked entries<br>
			<br>
			<div style="display:inline-block; margin-right: 40px;">
				<p>Relationship 1</p>
				<select name="Relationship1" class="popup-selector">
					<option>val1</option>
				</select>
			</div>
			<div style="display:inline-block">
				<p>Relationship 2</p>
				<select name="Relationship2" class="popup-selector">
					<option>val1</option>
				</select>
			</div>
			<br><br>
			<input type="submit" class="popup-submit">
		</div>
	</div>
</body>

</html>