<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
 
	// Set the administration interface
	
	// Set the title
	$document = JFactory::getDocument();
	$document->setTitle(JText::_('Formulize administration'));
	$document->addStyleDeclaration('.icon-48-formulize {background-image: url(../media/com_formulize/images/logo-48x48.png);}');
	// Set the toolbar
 	JToolBarHelper::title(JText::_('COM_FORMULIZE_ADMINISTRATION'), 'formulize');
	JToolBarHelper::preferences('com_formulize', '300', '700', 'Configure', ' ');

	// Set the main body
	$params = JComponentHelper::getParams( 'com_formulize' );
	print "Formulize path: ".$params->get('formulize_path');
	