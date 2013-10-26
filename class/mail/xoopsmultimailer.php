<?php
/**
 * Functions to extend PHPMailer to email the users
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	MultiMailer
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopsmultimailer.php 20312 2010-11-03 03:08:54Z skenow $
 */

if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

/**
 * Mailer Class.
 *
 * @deprecated	Use icms_messaging_EmailHandler, instead
 * @todo		Remove in version 1.4
 * @author		Jochen Buennagel	<job@buennagel.com>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 * @version		$Revision: 1083 $ - changed by $Author$ on $Date: 2007-10-16 12:42:51 -0400 (mar., 16 oct. 2007) $
 */
class XoopsMultiMailer extends icms_messaging_EmailHandler {

	private $_deprecated;

	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_messaging_EmailHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>