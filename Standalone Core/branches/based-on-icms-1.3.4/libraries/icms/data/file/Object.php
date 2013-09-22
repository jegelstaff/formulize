<?php
/**
 * Richfile Object
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	icms
 * @package		data
 * @subpackage	richfile
 * @since		1.3
 * @author		Phoenyx
 * @version		$Id: Object.php 10851 2010-12-05 19:15:30Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class icms_data_file_Object extends icms_ipf_Object {
	/**
	 * constructor
	 */
    public function __construct() {
        $this->quickInitVar("fileid", XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar("mid", XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar("caption", XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar("description", XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar("url", XOBJ_DTYPE_TXTBOX, FALSE);
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

	public function render() {
		$url = str_replace("{ICMS_URL}", ICMS_URL , $this->getVar("url"));
		$caption = $this->getVar("caption") != "" ? $this->getVar("caption") : $url;
		return "<a href='" . $url . "' title='" . $this->getVar("description") . "' target='_blank'>" . $caption . "</a>";
	}
}