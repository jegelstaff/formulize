<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// Import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

$params = JComponentHelper::getParams( 'com_formulize' );
$formulize_path = $params->get('formulize_path');
require_once $formulize_path."/integration_api.php";

print"Choose a Formulize screen:";

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
		$options = Formulize::getScreens();
		return $options;
	}
}