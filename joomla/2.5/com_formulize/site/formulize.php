<?php

// No direct access to this file
defined( '_JEXEC' ) or die( 'Restricted access' );

// Get the path to Formulize stored as a component parameters
$params = JComponentHelper::getParams( 'com_formulize' );
$formulize_path = $params->get( 'formulize_path' );
// Include API
require_once $formulize_path."/integration_api.php";


// Get the selected formId
// Get the menuitemid number
$input = JFactory::getApplication()->input;
$menuitemid = $input->getInt( 'Itemid' );  

if ($menuitemid) {
	// Querying the db necessary in order to have any change reflected "fast enough"
	// Get a reference to the database
	$db = JFactory::getDbo();
	// Query the database for the link's url
	$query = $db->getQuery(true);      
	$query->select('link')
		->from('#__menu ')
		->where('id = ' .  "'". $menuitemid . "'" );            
	$db->setQuery($query);    
	if (!$db->query()) {
		$this->setError($this->_db->getErrorMsg());
		return -1;
	}  
	// Get the result 
	$rows = $db->loadObjectList();  
	$link = $rows[0]->link;
	// Get the very last number (the selected formId)
	$parts = explode('=', $link);
	$formId = end($parts);
}
// If a form was selected
if($formId!=0) {
	// Add a style sheet for Formulize screens general styling
	$document = JFactory::getDocument();
	$document->addStyleSheet( JURI::base() . 'components/com_formulize/formulize.css' );
	
	// If no user is currently logged in
	// set $GLOBALS so Formulize know no user is currently logged in
	$user =& JFactory::getUser();
	if ($user->guest) {
		$GLOBALS['formulizeHostSystemUserId'] = 0;
	}
	
	// Inject the selected form into the screen
	include_once $formulize_path."/mainfile.php";
	Formulize::renderScreen( $formId );
}
else {
	echo '<br> No Formulize form was selected. </br>';

}
