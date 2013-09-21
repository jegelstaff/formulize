<?php
/**
 * Images Manager - Image Browser
 *
 * Used to create an instance of the image manager in a popup window to use with the dhmtl textarea object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		XoopsForms
 * @since		1.2
 * @author		Rodrigo Pereira Lima (aka TheRplima) <therplima@impresscms.org>
 * @version		$Id: formimage_browse.php 10948 2011-01-04 03:04:52Z skenow $
 */

include_once '../../mainfile.php';
icms_core_Debug::setDeprecated('modules/system/admin/images/browser.php', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));

include ICMS_ROOT_PATH . '/modules/system/admin/images/browser.php';
