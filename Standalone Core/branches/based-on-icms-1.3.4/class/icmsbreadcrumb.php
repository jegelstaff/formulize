<?php
/**
* icmsbreadcrumb
*
*
*
* @copyright      http://www.impresscms.org/ The ImpressCMS Project
* @license         LICENSE.txt
* @package	breadcrumb
* @since            1.0
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmsbreadcrumb.php 10816 2010-12-02 00:25:05Z skenow $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * IcmsBreadcrumb
 * @deprecated	Use icms_view_Breadcrumb, instead
 * @todo		Remove in 1.4
 * 
 * Managing page breadcrumb
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id:icmspersistabletable.php 1948 2008-05-01 19:01:10Z malanciault $
 */
class IcmsBreadcrumb extends icms_view_Breadcrumb {

	private $_deprecated;

	/**
	 * Constructor
	 */
	public function __construct($items) {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_Breadcrumb', sprintf(_CORE_REMOVE_IN_VERSION	, '1.4'));
		parent::__construct($items);
	}
}
