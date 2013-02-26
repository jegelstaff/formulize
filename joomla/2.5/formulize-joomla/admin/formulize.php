<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
 
	// Set the administration interface
	// Set the toolbar
 	JToolBarHelper::title(JText::_('Formulize-Joomla'), 'formulize');
	JToolBarHelper::preferences('com_formulize');
	// Set the title
	$document = JFactory::getDocument();
	$document->setTitle(JText::_('Formulize administration'));
	// Set the main body
	$params = JComponentHelper::getParams( 'com_formulize' );
	print "Formulize path: ".$params->get('formulize_path');
	