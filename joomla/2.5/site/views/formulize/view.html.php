<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML View class for the Formulize Component
 */
class FormulizeViewFormulize extends JView
{
    // Overwriting JView display method
    function display($tpl = null)
    {
         // Assign data to the view
        $this->msg = 'Formulize Front-end!';

        // Display the view
        parent::display($tpl);
    }
}