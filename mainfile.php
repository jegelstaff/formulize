<?php
/**
* All information in order to connect to database are going through here.
*
* Be careful if you are changing data's in this file.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Core
* @version		$Id: mainfile.php 20902 2011-02-27 02:34:57Z skenow $
*/

// ImpressCMS is not installed yet.
if(! defined('XOOPS_INSTALL')){
    header('Location: install/index.php');
    exit();
}
