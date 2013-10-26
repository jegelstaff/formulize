<?php
/**
 * Contains the basis classes for managing any SEO-enabled objects derived from icms_ipf_Objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	SeoObject
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id:Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * icms_ipf_Object base SEO-enabled class
 *
 * Base class representing a single icms_ipf_Object with "search engine optimisation" capabilities
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 */
class icms_ipf_seo_Object extends icms_ipf_Object {

    public function __construct(&$handler) {
		parent::__construct($handler);
    }

	public function initiateSEO() {
		$this->initCommonVar('meta_keywords');
		$this->initCommonVar('meta_description');
		$this->initCommonVar('short_url');
		$this->seoEnabled = true;
	}

	/**
	 * Backward compat
	 *
	 * @todo to be removed in 1.4
	 */
    function IcmsPersistableSeoObject() {
		$this->initiateSEO();
    }

	/**
	 * Return the value of the short_url field of this object
	 *
	 * @return string
	 */
	public function short_url() {
		return $this->getVar('short_url');
	}

	/**
	 * Return the value of the meta_keywords field of this object
	 *
	 * @return string
	 */
	public function meta_keywords() {
		return $this->getVar('meta_keywords');
	}

	/**
	 * Return the value of the meta_description field of this object
	 *
	 * @return string
	 */
	public function meta_description() {
		return $this->getVar('meta_description');
	}
}

