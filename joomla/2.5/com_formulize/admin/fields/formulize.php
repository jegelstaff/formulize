<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
	 
	// Import the list field type
	jimport('joomla.form.helper');
	JFormHelper::loadFieldClass('list');

	// Get the path to Formulize stored as a component parameters
	$params = JComponentHelper::getParams( 'com_formulize' );
	$formulize_path = $params->get('formulize_path');
	// Include the Formulize API
	require_once $formulize_path."/integration_api.php";

	// Display a message in the menu item selection
	print "Choose a Formulize Screen";

	/**
	 * Formulize Form Field class
	 */
	class JFormFieldFormulize extends JFormFieldList
	{
		protected $type = 'Formulize';

		/**
		 * Method to get a list of options for a Formulize input.
		 *
		 * @return an array of Formulize Screens (ids and names).
		 */
		protected function getOptions() 
		{		
			// Get the array of Formulize Screens using the Formulize API 
			$options = Formulize::getScreens(true);
			return $options;
		}
	}