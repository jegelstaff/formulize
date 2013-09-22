<?php
/**
 * ImpressCMS Customtags
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @subpackage	Custom Tags
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: customtag.php 12083 2012-10-21 23:24:06Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

define('ICMS_CUSTOMTAG_TYPE_XCODES', 1);
define('ICMS_CUSTOMTAG_TYPE_HTML', 2);
define('ICMS_CUSTOMTAG_TYPE_PHP', 3);

/**
 * Custom tags
 * @package		Administration
 * @subpackage	Custom Tags
 */
class SystemCustomtag extends icms_ipf_Object {
	public $content = FALSE;
	public $evaluated = FALSE;

	/**
	 * Constructor
	 * @param object $handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('customtagid', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('name', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_CUSTOMTAG_NAME, _CO_ICMS_CUSTOMTAG_NAME_DSC);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTAREA, FALSE, _CO_ICMS_CUSTOMTAG_DESCRIPTION, _CO_ICMS_CUSTOMTAG_DESCRIPTION_DSC);
		$this->quickInitVar('customtag_content', XOBJ_DTYPE_TXTAREA, TRUE, _CO_ICMS_CUSTOMTAG_CONTENT, _CO_ICMS_CUSTOMTAG_CONTENT_DSC);
		$this->quickInitVar('language', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_CUSTOMTAG_LANGUAGE, _CO_ICMS_CUSTOMTAG_LANGUAGE_DSC);
		$this->quickInitVar('customtag_type', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_CUSTOMTAG_TYPE, _CO_ICMS_CUSTOMTAG_TYPE_DSC, ICMS_CUSTOMTAG_TYPE_XCODES);
		$this->initNonPersistableVar('dohtml', XOBJ_DTYPE_INT, 'class', 'dohtml', '', TRUE);
		$this->initNonPersistableVar('doimage', XOBJ_DTYPE_INT, 'class', 'doimage', '', TRUE);
		$this->initNonPersistableVar('doxcode', XOBJ_DTYPE_INT, 'class', 'doxcode', '', TRUE);
		$this->initNonPersistableVar('dosmiley', XOBJ_DTYPE_INT, 'class', 'dosmiley', '', TRUE);

		$this->setControl('customtag_content', array('name' => 'textarea', 'form_editor' => 'textarea', 'form_rows' => 25));
		$this->setControl('language', array('name' => 'language', 'all' => TRUE));
		$this->setControl('customtag_type', array('itemHandler' => 'customtag', 'method' => 'getCustomtag_types', 'module' => 'system', "onSelect" => "submit"));
	}

	/**
	 * Override accessors for properties
	 * @see htdocs/libraries/icms/ipf/icms_ipf_Object::getVar()
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this, $key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * Render and output the custom tag
	 */
	public function render() {
		$myts = icms_core_Textsanitizer::getInstance();
		if (!$this->content) {
			switch ($this->getVar('customtag_type')) {
				case ICMS_CUSTOMTAG_TYPE_XCODES:
					$ret = $this->getVar('customtag_content', 'N');
					$ret = $myts->displayTarea($ret, 1, 1, 1, 1, 1);
					break;
					
				case ICMS_CUSTOMTAG_TYPE_HTML:
					$ret = $this->getVar('customtag_content', 'N');
					$ret = $myts->displayTarea($ret, 1, 1, 1, 1, 0);
					break;

				case ICMS_CUSTOMTAG_TYPE_PHP:
					$ret = $this->renderWithPhp();
					break;
					
				default:
					break;
			}
			$this->content = $ret;
		}
		return $this->content;
	}

	/**
	 * Rendering a custom tag that contains PHP
	 */
	public function renderWithPhp() {
		if (!$this->content && !$this->evaluated) {
			$ret = $this->getVar('customtag_content', 'e');
			$ret = icms_core_DataFilter::undoHtmlSpecialChars($ret);

			// check for PHP if we are not on admin side
			if (!defined('XOOPS_CPFUNC_LOADED' ) && $this->getVar('customtag_type') == ICMS_CUSTOMTAG_TYPE_PHP) {
				// we have PHP code, let's evaluate
				ob_start();
				echo eval($ret);
				$ret = ob_get_contents();
				ob_end_clean();
				$this->evaluated = TRUE;
			}
			$this->content = $ret;
		}
		return $this->content;
	}


	/**
	 * Generate a bbcode for the custom tag
	 */
	public function getXoopsCode() {
		$ret = '[customtag]' . $this->getVar('name', 'n') . '[/customtag]';
		return $ret;
	}

	/**
	 * Generate link and graphic for cloning a custom tag
	 */
	public function getCloneLink() {
		$ret = '<a href="' . ICMS_URL . '/modules/system/admin.php?fct=customtag&amp;op=clone&amp;customtagid=' . $this->id() . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/editcopy.png" style="vertical-align: middle;" alt="' . _CO_ICMS_CUSTOMTAG_CLONE . '" title="' . _CO_ICMS_CUSTOMTAG_CLONE . '" /></a>';
		return $ret;
	}


	/**
	 * Determine if the string is empty
	 */
	public function emptyString($var) {
		return strlen($var) > 0;
	}

	/**
	 * Accessor for the name property
	 */
	public function getCustomtagName() {
		$ret = $this->getVar('name');
		return $ret;
	}
}

/**
 * Handler for the custom tag object
 */
class SystemCustomtagHandler extends icms_ipf_Handler {
	private $_objects = FALSE;

	/**
	 * Constructor
	 * @param object $db
	 */
	public function __construct($db) {
		parent::__construct($db, 'customtag', 'customtagid', 'name', 'description', 'system');
		$this->addPermission('view_customtag', _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW, _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW_DSC);
	}

	/**
	 * Return an array of custom tag types
	 */
	public function getCustomtag_types() {
		$ret = array(ICMS_CUSTOMTAG_TYPE_XCODES => 'BB-Codes', ICMS_CUSTOMTAG_TYPE_HTML => 'HTML', ICMS_CUSTOMTAG_TYPE_PHP => 'PHP');
		return $ret;
	}

	/**
	 * Return an array of custom tags, indexed by name
	 */
	public function getCustomtagsByName() {
		if (!$this->_objects) {
			global $icmsConfig;

			$ret = array();

			$criteria = new icms_db_criteria_Compo();

			$criteria_language = new icms_db_criteria_Compo();
			$criteria_language->add(new icms_db_criteria_Item('language', $icmsConfig['language']));
			$criteria_language->add(new icms_db_criteria_Item('language', 'all'), 'OR');
			$criteria->add($criteria_language);

			$icms_permissions_handler = new icms_ipf_permission_Handler($this);
			$granted_ids = $icms_permissions_handler->getGrantedItems('view_customtag');

			if ($granted_ids && count($granted_ids) > 0) {
				$criteria->add(new icms_db_criteria_Item('customtagid', '(' . implode(', ', $granted_ids) . ')', 'IN'));
				$customtagsObj = $this->getObjects($criteria, TRUE);
				foreach ($customtagsObj as $customtagObj) {
					$ret[$customtagObj->getVar('name')] = $customtagObj;
				}
			}
			$this->_objects = $ret;
		}
		return $this->_objects;
	}
}