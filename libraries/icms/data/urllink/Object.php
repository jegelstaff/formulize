<?php
/**
 * UrlLink Object
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	icms
 * @package		data
 * @subpackage	urllink
 * @since		1.3
 * @author		Phoenyx
 * @version		$Id: Object.php 20486 2010-12-05 18:46:02Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class icms_data_urllink_Object extends icms_ipf_Object {
	/**
	 * constructor
	 */
	public function __construct() {
		$this->quickInitVar("urllinkid", XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar("mid", XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar("caption", XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar("description", XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar("url", XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar("target", XOBJ_DTYPE_TXTBOX, TRUE);

		$this->setControl("target", array("options" => array("_self" => _CO_ICMS_URLLINK_SELF,
			"_blank" => _CO_ICMS_URLLINK_BLANK)));
	}

	/**
	 * get value for variable
	 *
	 * @param string $key field name
	 * @param string $format format
	 * @return mixed value
	 */
	public function getVar($key, $format = "e"){
		if (substr($key, 0, 4) == "url_") {
			return parent::getVar("url", $format);
		} elseif (substr($key, 0, 4) == "mid_") {
			return parent::getVar("mid", $format);
		} elseif(substr($key, 0, 8) == "caption_") {
			return parent::getVar("caption", $format);
		} elseif(substr($key, 0, 5) == "desc_") {
			return parent::getVar("description", $format);
		} else {
			return parent::getVar($key, $format);
		}
	}

	/**
	 * generate html for clickable link
	 *
	 * @return string html
	 */
	public function render() {
		$ret  = "<a href='" . $this->getVar("url") . "' target='" . $this->getVar("target") . "' ";
		$ret .= "title='" . $this->getVar("description") . "'>";
		$ret .= $this->getVar("caption") . "</a>";
		return $ret;
	}
}