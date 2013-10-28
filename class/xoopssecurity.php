<?php
// $Id: xoopssecurity.php 20317 2010-11-03 23:08:01Z skenow $
/**
 * Handles all security functions within ImpressCMS
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopssecurity.php 20317 2010-11-03 23:08:01Z skenow $
 */
/*
 * Class for managing security aspects such as checking referers, applying tokens and checking global variables for contamination
 *
 * @package        kernel
 * @subpackage    core
 *
 * @author        Jan Pedersen     <mithrandir@xoops.org>
 * @copyright    (c) 2000-2005 The Xoops Project - www.xoops.org
 */
class IcmsSecurity extends	icms_core_Security {

	private $_deprecated;

	/**
	 * Constructor
	 *
	 **/
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_Security', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

/**
 * XoopsSecurity
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @license		LICENSE.txt
 * @since		XOOPS
 * @author		The XOOPS Project Community <http://www.xoops.org>
 *
 * @deprecated	Use icms_core_Security instead
 * @todo		Remove this in version 1.4
 */
class XoopsSecurity extends icms_core_Security {

	private $_deprecated;

	/* For Backwards Compatibility */
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_Security', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
