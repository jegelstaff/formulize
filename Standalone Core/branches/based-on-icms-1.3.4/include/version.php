<?php
/**
 * Version information about ImpressCMS
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.0
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: version.php 12125 2012-12-03 08:28:43Z fiammy $
 */

define('ICMS_VERSION_NAME', 'Formulize 4 Standalone'); // ALTERED BY FREEFORM SOLUTIONS FOR THE FORMULIZE 4 STANDALONE VERSION

// For backward compatibility with XOOPS
define('XOOPS_VERSION', ICMS_VERSION_NAME);

/**
 * Version Status
 * 1  = Alpha
 * 2  = Beta
 * 3  = RC
 * 10 = Final
 */

define('ICMS_VERSION_STATUS', 10);

/**
 * Build number
 *
 * Every release has its own build number, incrementable by 1 everytime we make a release
 */
// impresscms_1.3.4 RC = 66
define('ICMS_VERSION_BUILD', 67);

/**
 * Latest dbversion of the System Module
 *
 * When installing ImpressCMS, the System Module's dbversion needs to be the latest dbversion found
 * in system/include/update.php
 *
 * So, developers, everytime you add an upgrade block in system/include/update.php to upgrade something in the DB,
 * please also change this constant
 */
define('ICMS_SYSTEM_DBVERSION', 43);
