<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// Import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

// Get the path to Formulize stored as a component parameters
$params = JComponentHelper::getParams( 'com_formulize' );
$formulize_path = $params->get('formulize_path');
// Include API
require_once $formulize_path."/integration_api.php";

// Display a message in the menu item selection
print"Choose a Formulize Screen";

/**
 * Formulize Form Field class
 */
class JFormFieldFormulize extends JFormFieldList
{
    protected $type = 'Formulize';

	/**
	 * Method to get a list of options for a list input.
	 * @return An array of JHtml options.
	 */
	protected function getOptions() 
	{		
		// Need to use the formulize API to populate the 
		// array with the available forms(ids and names)
		// Note: Will get the current user here and use the new function
		$options = Formulize::getScreens();
		return $options;
	}
}