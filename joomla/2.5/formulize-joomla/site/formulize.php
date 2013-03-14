<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
	
	//$GLOBALS['joomla'] = 1;
	
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
	
	//echo '<meta http-equiv="Cache-control" content="no-cache">';
	//header("Cache-Control: no-cache, must-revalidate");
	
	
	// Get the selected form id
	/*alternative to query db, but less responsive...
	$jinput = JFactory::getApplication()->input;
	$formId = $jinput->get('id', '1', 'INT');
	*/

	
	// Get the menu item id number
	$input = JFactory::getApplication()->input;
    $menuitemid = $input->getInt( 'Itemid' );  
	
    if ($menuitemid) {
        // Get a reference to the database
		$db = JFactory::getDbo();
		// Query the database
        $query = $db->getQuery(true);      
        $query->select('link')
			->from('#__menu ')
			->where('id = ' .  "'". $menuitemid . "'" );            
        $db->setQuery($query);    
        if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return -1;
        }  
		// Get the result and return the userId
		$rows = $db->loadObjectList();  
		$link = $rows[0]->link;
		$parts = explode('=', $link);
		$formId = end($parts);
    }
	
	//echo '<script type="text/javascript">alert("' . $formId2 . '"); </script>';
	


	
	
	//echo '<script type="text/javascript">$('link[rel=stylesheet]').remove();</script>';
	
	// Add a style sheet for the icons and general styling
	$document = JFactory::getDocument();
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
	// Used to remove all style from the component
	//$document->_styleSheets= array();
	
	//Include the selected form
	echo '<div id="formulize-screen">';
	include_once $formulize_path."/mainfile.php";
	$formulize_screen_id = $formId; 
	include $formulize_path."/modules/formulize/index.php";
	echo '</div>';