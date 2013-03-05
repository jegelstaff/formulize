<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Script file of Formulize component
 */
class com_formulizeInstallerScript {
    /**
     * method to install the component
     *
     * @return void
     */

    function install($parent) {
		$application = JFactory::getApplication();
		$application->enqueueMessage(JText::_('Script is running in install'), 'message');
        // $parent is the class calling this method
        global $database;

        //get database
        if(defined('_JEXEC')) {
            //joomla 1.5
            $database = JFactory::getDBO();
        }

        $database->setQuery("DROP TABLE IF EXISTS `#__formulize`;");
        $database->query();
        $database->setQuery("CREATE TABLE `#__formulize` (
          `params` text not null
          );");

        $database->query();
    }

    /**
     * method to uninstall the component
     *
     * @return void
     */

    function uninstall($parent) {
        // $parent is the class calling this method
        echo '<p>'.JText::_('COM_HELLOWORLD_UNINSTALL_TEXT').'</p>';
    }

    /**
     * method to update the component
     *
     * @return void
     */

    function update($parent) {
		$application = JFactory::getApplication();
		$application->enqueueMessage(JText::_('Script is running in update'), 'message');
        // $parent is the class calling this method
        global $database;

        //get database
        if(defined('_JEXEC')) {
            //joomla 1.5
            $database = JFactory::getDBO();
        }

        $database->setQuery("DROP TABLE IF EXISTS `#__formulize`;");
        $database->query();
        $database->setQuery("CREATE TABLE `#__formulize` (
          `params` text not null
          );");

        $database->query();
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return void
     */

    function preflight($type, $parent) {
        // $parent is the class calling this method
        // $type is the type of change (install, update or discover_install)
        echo '<p>'.JText::_('COM_FORMULIZE_PREFLIGHT_'.$type.'_TEXT').'</p>';
    }

    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */

    function postflight($type, $parent) {
        // $parent is the class calling this method
        // $type is the type of change (install, update or discover_install)
        echo '<p>'.JText::_('COM_FORMULIZE_POSTFLIGHT_'.$type.'_TEXT').'</p>';
    }
}