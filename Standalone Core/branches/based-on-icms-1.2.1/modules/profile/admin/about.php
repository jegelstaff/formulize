<?php
/**
 * Extended User Profile
 *
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.3
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

include_once 'admin_header.php';

include_once ICMS_ROOT_PATH . '/kernel/icmsmoduleabout.php';
$aboutObj = new IcmsModuleAbout();
$aboutObj -> render();
?>