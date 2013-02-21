<?php
	// No direct access to this file
	defined('_JEXEC') or die('Restricted access');
 
	// To be commented
 	JToolBarHelper::title(JText::_('Formulize-Joomla'), 'formulize');
	JToolBarHelper::preferences('com_formulize');

	$document = JFactory::getDocument();
	$document->setTitle(JText::_('COM_FORMULIZE_ADMINISTRATION'));

	$params = JComponentHelper::getParams( 'com_formulize' );
	print "Formulize path: ".$params->get('formulize_path');
	