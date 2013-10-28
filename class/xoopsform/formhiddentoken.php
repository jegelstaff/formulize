
<?php
/**
* Creates a hidden token form attribute
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formhiddentoken.php 20020 2010-08-25 14:25:59Z malanciault $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2005 XOOPS.org
 */
/**
 * A hidden token field
 *
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2005 XOOPS.org
 */
class XoopsFormHiddenToken extends icms_form_elements_Hiddentoken {
	private $_deprecated;
	public function __construct($name = _CORE_TOKEN, $timeout = 0) {
		parent::__construct($name, $timeout);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Hiddentoken', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>