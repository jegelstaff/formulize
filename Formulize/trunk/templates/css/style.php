<?php
$xoopsOption = array('nocommon'=>true); // will cause mainfile to simply load constants, skip bootstrapping the system
include "../../../../mainfile.php";
header("Content-Type: text/css");
?>

.icms_checkboxoption {
    clear: both;
}

#floating-list-of-entries-save-button {
	padding: 1em 1.5em;
	margin: 1em 0;
	background-color: #FFF;
}

#floating-list-of-entries-save-button.save_button_fixed {
	position: fixed; 
	bottom: 0;
	z-index: 100;
	margin-top: 0;
	margin-bottom: 0;
	border: solid 1px #1D65A5;
}

.floating-column {
    position: fixed;
    border: 1px solid white;
    display: none;
    padding: 0px !important;
}
.floating-column .main-cell-div {
    float: left;
    border: 1px solid white;
    padding: 5px;
    
}

#currentview {
    width: 500px;
    font-size: 1.2em;
}

/*=== Formulize Boutons by Francois T ===*/
button, input[type='submit'], input[type='button'], input[type='reset'] {
	background-color: 			#F4F9FE;
	-webkit-border-radius: 5px;
	color: 						#2F85DC;
	border:						1px solid;
	border-color: 				#ccc;
}
input:hover, button:hover, .xo-formbuttons:hover, .formButton:hover {
    transform: scale(1.2);  
    box-shadow: 0 0 65px #F4F9FE inset, 0 0 20px #F4F9FE inset, 0 0 15px #333;  
}
button:focus, input[type='submit']:focus, input[type='button']:focus, input[type='reset']:focus, .xo-formbuttons:focus, .formButton:focus {
	background-color:			#f4f4f4;
	border-color :				#333;
}

.formulize_button {
    text-indent:30px;
    border-color: #0076a3;
    color: #fff !important;
    text-shadow: 0 -1px 0 rgba(0,0,0,.4);    
    font-size: 15px;
    font-weight: bold;
    height: 36px;
    width: 185px !important;
}

/*=== Formulize Boutons by Francois T ===*/
#formulize_addButton {
    text-indent:45px;	
    background:#2F85DC url('<?php print XOOPS_URL; ?>/modules/formulize/images/contact-new.png') no-repeat 10px;
    width: 210px !important;
}
#formulize_addMultiButton {
    text-indent:45px;
    background:#2F85DC url('<?php print XOOPS_URL; ?>/modules/formulize/images/multi.png') no-repeat 10px;
    width: 210px !important;
}
#formulize_changeColsButton {
    background:#61A6EE url('<?php print XOOPS_URL; ?>/modules/formulize/images/table.png') no-repeat 10px;
    
}
#formulize_exportButton {
    background:#2F85DC url('<?php print XOOPS_URL; ?>/modules/formulize/images/math.png') no-repeat 10px;
}
#formulize_exportCalcsButton {
    background:#2F85DC url('<?php print XOOPS_URL; ?>/modules/formulize/images/math.png') no-repeat 10px;
}
#formulize_importButton {
    background:#2F85DC url('<?php print XOOPS_URL; ?>/modules/formulize/images/import.png') no-repeat 10px;
}
#formulize_calcButton, #formulize_advCalcButton, #formulize_advSearchButton  {
    border-color: #8E8E8E;
    background:#A3A3A3 url('<?php print XOOPS_URL; ?>/modules/formulize/images/calculator.png') no-repeat 10px;
}
#formulize_selectAllButton {
    background:#3CA683 url('<?php print XOOPS_URL; ?>/modules/formulize/images/select.png') no-repeat 10px;
}
#formulize_clearSelectButton {
    background:#3CA683 url('<?php print XOOPS_URL; ?>/modules/formulize/images/unselect.png') no-repeat 10px;
}
#formulize_deleteButton {
    background:#3CA683 url('<?php print XOOPS_URL; ?>/modules/formulize/images/delete.png') no-repeat 10px;
}
#formulize_cloneButton {
    background:#3CA683 url('<?php print XOOPS_URL; ?>/modules/formulize/images/clone.png') no-repeat 10px;
}
#formulize_notifButton {
    background:#5450A5 url('<?php print XOOPS_URL; ?>/modules/formulize/images/notif.png') no-repeat 10px;
}
#formulize_resetViewButton {
    background:#61A6EE url('<?php print XOOPS_URL; ?>/modules/formulize/images/find.png') no-repeat 10px;
}
#formulize_saveViewButton {
    background:#61A6EE url('<?php print XOOPS_URL; ?>/modules/formulize/images/findsave.png') no-repeat 10px;
}
#formulize_deleteViewButton {
    background:#61A6EE url('<?php print XOOPS_URL; ?>/modules/formulize/images/finddel.png') no-repeat 10px;
}


/*=== End of Formulize Boutons ===*/


