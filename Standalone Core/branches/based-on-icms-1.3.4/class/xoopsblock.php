<?php
/**
 * XOOPS Block Class File
 *
 * @since 		XOOPS
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @copyright 	The XOOPS Project <http://www.xoops.org>
 * @author 		The XOOPS Project Community <http://www.xoops.org>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 *
 * @version		$Id: xoopsblock.php 19425 2010-06-14 23:03:14Z skenow $
 *
 * @deprecated	use icms_core_Block class, instead - the file will be autoloaded
 * @todo		Remove from this file from the core on ImpressCMS 1.4
 */

if (!defined('ICMS_ROOT_PATH')) { exit(); }
icms_core_Debug::setDeprecated( 'class icms_core_Block', 'this file will be removed in ImpressCMS 1.4 - the classes are automatically loaded when instantiated' );
require_once ICMS_ROOT_PATH . '/kernel/block.php' ;
