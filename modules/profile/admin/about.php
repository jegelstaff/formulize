<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.3
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id: about.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

include_once 'admin_header.php';

$aboutObj = new icms_ipf_About();
$aboutObj->render();
?>