<?php
/**
 * Creates a simple form
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @version		SVN: $Id: Simple.php 20058 2010-08-28 21:30:27Z skenow $
 * @todo		this class is not used by the core; we will probably remove it in 1.4
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Form that will output as a simple HTML form with minimum formatting
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 *
 */
class icms_form_Simple extends icms_form_Base {
	/**
	 * This method is required - this method in the parent (abstract) class is also abstract
	 * @param string $extra
	 */
	public function insertBreak($extra = NULL) {
	}
	/**
	 * create HTML to output the form with minimal formatting
	 *
	 * @return	string
	 */
	public function render() {
		$ret = $this->getTitle() . "\n<form name='" . $this->getName()
			. "' id='" . $this->getName()
			. "' action='" . $this->getAction()
			. "' method='" . $this->getMethod() . "'" . $this->getExtra()
			. ">\n";
		foreach ($this->getElements() as $ele) {
			if (!$ele->isHidden()) {
				$ret .= "<strong>" . $ele->getCaption() . "</strong><br />" . $ele->render() . "<br />\n";
			} else {
				$ret .= $ele->render() . "\n";
			}
		}
		$ret .= "</form>\n";
		return $ret;
	}
}
