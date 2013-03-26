<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
 
	// Set the administration interface
	// Set the Toolbar
 	JToolBarHelper::title(JText::_('COM_FORMULIZE_ADMINISTRATION'), 'formulize');
	JToolBarHelper::preferences('com_formulize', '300', '700', 'Configure', ' ');
	
	// Add an image to the backend
	$document = JFactory::getDocument();
	$document->addStyleDeclaration('.icon-48-formulize {background-image: url(../media/com_formulize/images/logo-48x48.png);}');
	
	// Set the main body
	$params = JComponentHelper::getParams( 'com_formulize' );
	print "Path to Formulize: ".$params->get('formulize_path');
	// this path is wrong
	print "<br /><br /><a href='index.php?option=com_formulize&view=formulize&sync=true'>Initial Sync - Only run after installing formulize and joomla and configuring formulize_path</a>";
	