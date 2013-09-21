<?php
/**
 * ImpressCMS AUTOTASKSs action
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 * @internal	This file is used for executing sheduled tasks
 * @version		$Id: autotasks.php 10020 2010-06-12 06:23:03Z skenow $
 */

ob_start();

global $xoopsOption;
$xoopsOption['ignore_closed_site'] = true;
define('ICMS_AUTOTASKS_EXECMODE', true);

chdir(dirname(__FILE__));
require_once '../mainfile.php';

ob_end_clean();
