<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
	
	// Create a lighter shade of a hex color:
	// Note: This function is not used currently.  
	// It will be useful when working on the customization of 
	// the screen appearance by the webmaster	
	function hexLighter($hex,$factor = 30) 
    { 
    $new_hex = ''; 
     
    $base['R'] = hexdec($hex{0}.$hex{1}); 
    $base['G'] = hexdec($hex{2}.$hex{3}); 
    $base['B'] = hexdec($hex{4}.$hex{5}); 
     
    foreach ($base as $k => $v) 
        { 
        $amount = 255 - $v; 
        $amount = $amount / 100; 
        $amount = round($amount * $factor); 
        $new_decimal = $v + $amount; 
     
        $new_hex_component = dechex($new_decimal); 
        if(strlen($new_hex_component) < 2) 
            { $new_hex_component = "0".$new_hex_component; } 
        $new_hex .= $new_hex_component; 
        } 
         
    return $new_hex;     
    } 

	// Get the path to Formulize stored as a component parameters
	$params = JComponentHelper::getParams( 'com_formulize' );
	$formulize_path = $params->get('formulize_path');
	// Include API
	require_once $formulize_path."/integration_api.php";
	
	// Get the selected form id 
	// Note: Need to fix menu item hiting twice here
	// See tutorial MVC last link, in the controller
	$jinput = JFactory::getApplication()->input;
	$formId = $jinput->get('id', '1', 'INT');
	
	// For debugging
	//echo '<script type="text/javascript">alert("' . $formId . '"); </script>';
	
	// Add a style sheet for the icons and general styling
	$document =& JFactory::getDocument();
	$document->addStyleSheet(JURI::base() . 'components/com_formulize/formulize.css');
	
	// Note: The following code is not used currently.
	// It will be usefull when working on the customization of 
	// the screen appearance by the webmaster
	// Add a style for the colors (determined using a param and parsing...)
	$style1 = '#listofentries {
    background-color: yellow;
	}';
	$style2 = '#formulizeform {
    background-color: brown;
	}';
	// Will overwrite formulize.css
	//$document->addStyleDeclaration($style1);
	//$document->addStyleDeclaration($style2);
	
	//Include the selected form
	echo '<div id="formulize-screen">';
	include_once $formulize_path."/mainfile.php";
	$formulize_screen_id = $formId; 
	include $formulize_path."/modules/formulize/index.php";
	echo '</div>';