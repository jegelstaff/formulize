<?php

include "../../../mainfile.php";
include "../../../modules/formulize/admin/application.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

	<link rel="stylesheet" type="text/css" href="style.css">
	<script src="js/jsfile.js"></script>

	</head>

<body>
    
    <script type='text/javascript'>
    $(document).ready(function () {
        /*Create forms*/
	var $drop2 = "<div class='drop-container2'>Add new relationship</div>";
    <?php
    foreach($relationships as $rel) {
        print "addRel('".$rel['name']."',[";
        $start = true;
        foreach($rel['content']['links'] as $link) {
            if(!$start) { print ", "; }
            print "'".$link['form1']."', '".$link['form2']."'";
            $start = false;
        }
        print "]);";
    }
    ?>
    
    // <input type="hidden" name="formulize_admin_handler" value="application_settings"> // handler name indicates the file that will read the data and save it
    // you can write your own handler file and put it in modules/formulize/admin/save/newUI_save.php <input type="hidden" name="formulize_admin_handler" value="newUI">
    // <input type="hidden" name="formulize_admin_key" value="4"> // admin key indicates the primary key of the thing you're saving...probably not applicable in this case
    //$.post("save.php, $(thisformdata).serialize(), function() { // save.php will hand off to the declared handler based on the hidden field packaged up with the data being posted
    //});
    //
    
	$('#form-container').append($drop2);

	var toggler = document.getElementsByClassName("list");
	var i;
	for (i = 0; i < toggler.length; i++) {
		(toggler[i]).addEventListener("click", function () {
			addTogglers(this)
		});
	}
    });
    </script>
    
    
	<!-- The sidebar -->
	<div class="sidebar">
		<p>Forms</p>
		<div>
            <?php
            foreach($allForms as $form) {
                print '<div class="sidebar-form">
				<p><i class="fas fa-file-alt" source="'.$form['id'].'"></i>'.$form['name'].'</p>
                </div>';
            }
            ?>
		</div>

		<div class="form form-main">
			<p style="font-weight: bold;">Relationships</p>
			<div id="form-container" class="form-layout">


			</div>
		</div>
	</div>
	<div id="RelationshipPopup" class="popup">`

		<!-- Modal content -->
		<div class="popup-content">
			<span class="close">&times;</span>
			<h3>New Relationship</h3>
			<p>Creating a <span><select name="Relationship1" class="popup-selector">
						<option value="volvo">one to one</option>
						<option value="saab">one to many</option>
					</select></span> relationship. How would you like them to be linked?</p>
			<input type="checkbox" name="c1" value="Bike">Use ID of person who filled them in<br>
			<input type="checkbox" name="c2" value="Bike">Use common value in 2 elements<br>
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