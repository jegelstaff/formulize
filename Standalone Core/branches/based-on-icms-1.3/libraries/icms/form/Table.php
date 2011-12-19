<?php
/**
 * Creates a form styled by a table
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Form
 * @version		SVN: $Id: Table.php 20058 2010-08-28 21:30:27Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Form that will output formatted as a HTML table
 *
 * No styles and no JavaScript to check for required fields.
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 *
 */
class icms_form_Table extends icms_form_Base {
	/**
	 * Insert an empty row in the table to serve as a separator.
	 *
	 * @param	string  $extra  HTML to be displayed in the empty row.
	 * @param	string	$class	CSS class name for <td> tag
	 */
	public function insertBreak($extra = '', $class= '') {
		$class = ($class != '') ? " class='$class'" : '';
		//Fix for $extra tag not showing
		if ($extra) {
			$extra = "<tr><td colspan='2' $class>$extra</td></tr>";
			$this->addElement($extra);
		} else {
			$extra = "<tr><td colspan='2' $class>&nbsp;</td></tr>";
			$this->addElement($extra);
		}
	}

	/**
	 * create HTML to output the form as a table
	 *
	 * @return	string  $ret  the constructed HTML
	 */
	public function render() {
		$ret = $this->getTitle() . "\n<form name='" . $this->getName()
			. "' id='" . $this->getName()
			. "' action='" . $this->getAction()
			. "' method='" . $this->getMethod() . "'" . $this->getExtra()
			. ">\n<table border='0' width='100%'>\n";
		$hidden = '';
		foreach ($this->getElements() as $ele) {
			if (!$ele->isHidden()) {
				$ret .= "<tr valign='top' align='" . _GLOBAL_LEFT . "'><td>" . $ele->getCaption();
				if ($ele_desc = $ele->getDescription()) {
					$ret .= '<br /><br /><span style="font-weight: normal;">' . $ele_desc . '</span>';
				}
				$ret .= "</td><td>" . $ele->render() . "</td></tr>";
			} else {
				$hidden .= $ele->render() . "\n";
			}
		}
		$ret .= "</table>\n$hidden</form>\n";
		return $ret;
	}
}
