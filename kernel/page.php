<?php
/**
 * Classes responsible for managing core page objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license	LICENSE.txt
 * @package	core
 * @since	ImpressCMS 1.1
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @version	$Id: page.php 19118 2010-03-27 17:46:23Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');


/**
 * ImpressCMS page class.
 *
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @deprecated	use icms_data_page_Object, insted
 * @todo		Remove in version 1.4
 */
class IcmsPage extends icms_data_page_Object {
	private $_deprecated;

	public function __construct( & $handler ){
		parent::__construct( $handler );
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_page_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));

	}

}

/**
 * ImpressCMS page handler class.
 *
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */
class IcmsPageHandler extends icms_data_page_Handler {
	private $_deprecated;

	public function __construct( & $db ){
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_page_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

/**
 * XOOPS page handler class.
 *
 * @deprecated	use icms_data_page_Object, insted
 * @todo		Remove in version 1.4
 */
class XoopsPage extends IcmsPage { /* For backwards compatibility */ }

/**
 * XOOPS page handler class.
 *
 * @todo 	Remove this class after ImpressCMS 1.5
 * @deprecated
 */
class XoopsPageHandler extends IcmsPageHandler { /* For backwards compatibility */ }

?>