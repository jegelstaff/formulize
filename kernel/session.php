<?php
/**
 * Session Management
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: session.php 19118 2010-03-27 17:46:23Z skenow $
 */
/*
 Based on SecureSession class
 Written by Vagharshak Tozalakyan <vagh@armdex.com>
 Released under GNU Public License
 */
/**
 * Handler for a session
 * @package     kernel
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_core_SessionHandler, instead
 * @todo		Remove in version 1.4
 */
class XoopsSessionHandler extends icms_core_SessionHandler
{
	private $_deprecated;

	/**
	 * Constructor
	 * @param object $db reference to the {@link XoopsDatabase} object
	 *
	 */
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_SessionHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
