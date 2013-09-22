<?php
/**
 * Form control creating an autocomplete select box for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Autocomplete.php 10846 2010-12-05 11:04:09Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * To make the Autocomplete field work, the include file needs to look like the following
 * (in this example the Autocomplete field would use the conf_name field of the config table as a
 * lookup):
 *
 * include "mainfile.php";
 * icms::$logger->disableLogger();
 * $sql = "SELECT * FROM " . icms::$xoopsDB->prefix("config") . " WHERE conf_name LIKE '%" . $_GET['term'] . "%'";
 * $result = icms::$xoopsDB->query($sql);
 * while ($myrow = icms::$xoopsDB->fetchArray($result)) $ret[] = array("item" => $myrow["conf_name"]);
 * echo $_GET["callback"] . "(" . json_encode($ret) . ")";
 *
 * Important:
 *  - use control parameter "file" to specify the include file for the autocomplete
 *  - the include file must be specified without ICMS_URL or ICMS_ROOT_PATH
 *  - if the include file does not exist, the form field is displayed as a text field
 *  - use control parameter "minlength" to specify the minimum amount of characters before the
 *    autocomplete function starts to work (default: 3)
 *  - use control parameter "delay" to specify the delay before the autocomplete function starts
 *    to work. Use with caution since it can result in high server load! (default: 500)
 */
class icms_ipf_form_elements_Autocomplete extends icms_form_elements_Text {
	private $_file;

	/**
	 * Constructor
	 * @param	icms_ipf_Object	$object	reference to targetobject (@link icms_ipf_Object)
	 * @param	string			$key	the form name
	 */
	public function __construct($object, $key) {
		$var = $object->vars[$key];
		$control = $object->controls[$key];
		$form_maxlength = isset($control['maxlength']) ? $control['maxlength'] : (isset($var['maxlength']) ? $var['maxlength'] : 255);
		$form_size = isset($control['size']) ? $control['size'] : 50;
		$this->_file = $control['file'];

		parent::__construct($var['form_caption'], $key, $form_size, $form_maxlength, $object->getVar($key, 'e'));
	}

	/**
	 * Prepare HTML for output
	 *
	 * @global	icms_view_theme_Object	$xoTheme	theme object
	 * @return	string					$ret		the constructed HTML
	 */
	public function render() {
		global $xoTheme;

		if (!is_file(ICMS_ROOT_PATH . "/" . $this->_file)) return parent::render();

		$minlength = isset($control['minlength']) ? $control['minlength'] : 3;
		$delay = isset($control['delay']) ? $control['delay'] : 500;

		$js  = "jQuery(document).ready(function() {\n";
		$js .= " jQuery('#" . $this->getName() . "').autocomplete({\n";
		$js .= "  source: function(req, add){\n";
		$js .= "   jQuery.getJSON('" . ICMS_URL . "/" . $this->_file . "?callback=?', req, function(data) {\n";
		$js .= "    var suggestions = [];\n";
		$js .= "    jQuery.each(data, function(i, val){ suggestions.push(val.item); });\n";
		$js .= "    add(suggestions);\n";
		$js .= "   });\n";
		$js .= "  }\n";
		$js .= " }, {\n";
		$js .= "  minLength:" . $minlength . ",\n";
		$js .= "  delay:" . $delay . "\n";
		$js .= " });\n";
		$js .= "});";

		$xoTheme->addScript('', array('type' => 'text/javascript'), $js);

		return parent::render();
	}
}