<?php
/**
 * Creates form attribute which shows match possibilities for search form
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Matchoption.php 20014 2010-08-25 01:54:34Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */

/**
 * A selection box with options for matching search terms.
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_select_Matchoption extends icms_form_elements_Select {
	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	$value	Pre-selected value (or array of them).
	 * 							Legal values are {@link XOOPS_MATCH_START}, {@link XOOPS_MATCH_END},
	 * 							{@link XOOPS_MATCH_EQUAL}, and {@link XOOPS_MATCH_CONTAIN}
	 * @param	int		$size	Number of rows. "1" makes a drop-down-list
	 */
	public function __construct($caption, $name, $value = null, $size = 1) {
		parent::__construct($caption, $name, $value, $size, false);
		$this->addOption(XOOPS_MATCH_START, _STARTSWITH);
		$this->addOption(XOOPS_MATCH_END, _ENDSWITH);
		$this->addOption(XOOPS_MATCH_EQUAL, _MATCHES);
		$this->addOption(XOOPS_MATCH_CONTAIN, _CONTAINS);
	}
}

