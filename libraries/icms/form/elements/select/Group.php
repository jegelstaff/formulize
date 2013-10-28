<?php
/**
 * Creates a form field for selecting a user group or groups
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Group.php 22532 2011-09-02 20:16:01Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A field with a choice of available groups
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_select_Group extends icms_form_elements_Select {
	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	bool	$include_anon	Include group "anonymous"?
	 * @param	mixed	$value	    	Pre-selected value (or array of them).
	 * @param	int		$size	        Number or rows. "1" makes a drop-down-list.
	 * @param	bool    $multiple       Allow multiple selections?
	 */
	public function __construct($caption, $name, $include_anon = false, $value = null, $size = 1, $multiple = false) {
		parent::__construct($caption, $name, $value, $size, $multiple);
		$member_handler = icms::handler('icms_member');
		if (!$include_anon) {
			$this->addOptionArray($member_handler->getGroupList(new icms_db_criteria_Item('groupid', ICMS_GROUP_ANONYMOUS, '!=')));
		} else {
			$this->addOptionArray($member_handler->getGroupList());
		}
	}
}

