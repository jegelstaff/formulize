<?php
/**
 * xos_logos_PageBuilder component class file
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license     http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package     core
 * @subpackage	template
 *
 * @since       XOOPS
 * @version		$Id: theme_blocks.php 8565 2009-04-11 12:44:10Z icmsunderdog $
 *
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */

/**
 * This file cannot be requested directly
 */
defined('ICMS_ROOT_PATH') or exit();

/**
 * xos_logos_PageBuilder main class
 *
 * @package     core
 * @subpackage  template
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @deprecated	Use icms_view_PageBuilder, instead
 * @todo		Remove in version 1.4 - there are no other occurrences in the core
 */
class xos_logos_PageBuilder extends icms_view_PageBuilder {
	private $_deprecated;
	public function xoInit($options = array()) {
		parent::xoInit($options);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_PageBuilder', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}