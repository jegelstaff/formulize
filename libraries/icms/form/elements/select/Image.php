<?PHP
/**
 * Creates a form attribute which is able to select an image
 *
 ### =============================================================
 ### Mastop InfoDigital - Paixão por Internet
 ### =============================================================
 ### Classe para Colocar as imagens da biblioteca em um Select
 ### =============================================================
 ### @author Developer: Fernando Santos (topet05), fernando@mastop.com.br
 ### @Copyright: Mastop InfoDigital � 2003-2007
 ### -------------------------------------------------------------
 ### www.mastop.com.br
 ### =============================================================
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: Image.php 20610 2010-12-21 21:13:37Z skenow $
 */

defined('ICMS_ROOT_PATH') or die();

class icms_form_elements_select_Image extends icms_form_elements_Select {
	/**
	 * OptGroup
	 * @var array
	 * @access	private
	 */
	private $_optgroups = array();
	private $_optgroupsID = array();

	/**
	 * Construtor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	  $value	Value for the Select attribute
	 * @param	string	$cat    Name of the Category
	 */
	public function __construct($caption, $name, $value = NULL, $cat = NULL) {
		parent::__construct($caption, $name, $value);
		$this->addOptGroupArray($this->getImageList($cat));
	}

	/**
	 * Adiciona um Optgroup
	 *
	 * @param	string  $value  opções do Grupo
	 * @param	string  $name   Nome do Grupo de opções
	 */
	public function addOptGroup($value = array(), $name = "&nbsp;") {
		$this->_optgroups[$name] = $value;
	}

	/**
	 * Adiciona m�ltiplos Optgroups
	 *
	 * @param	array   $options    Array com nome->opções
	 */
	public function addOptGroupArray($options) {
		if (is_array($options)) {
			foreach ($options as $k=>$v) {
				$this->addOptGroup($v, $k);
			}
		}
	}

	/**
	 * Gets the image list
	 *
	 * @param    mixed     $cat    category number or array of categories
	 * @return   string    $ret    The imagelist string
	 */
	public function getImageList($cat = NULL) {
		$ret = array();
		if (!is_object(icms::$user)) {
			$group = array(XOOPS_GROUP_ANONYMOUS);
		} else {
			$group =& icms::$user->getGroups();
		}
		$imgcat_handler = icms::handler('icms_image_category');
		$catlist =& $imgcat_handler->getList($group, 'imgcat_read', 1);
		if (is_array($cat) && count($catlist) > 0) {
			foreach ($catlist as $k=>$v) {
				if (!in_array($k, $cat)) {
					unset($catlist[$k]);
				}
			}
		} elseif (is_int($cat)) {
			$catlist = array_key_exists($cat, $catlist) ? array($cat=>$catlist[$cat]) : array();
		}

		$image_handler = icms::handler('icms_image');
		foreach ($catlist as $k=>$v) {
			$this->_optgroupsID[$v] = $k;
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('imgcat_id', $k));
			$criteria->add(new icms_db_criteria_Item('image_display', 1));
			$total = $image_handler->getCount($criteria);
			if ($total > 0) {
				$imgcat =& $imgcat_handler->get($k);
				$storetype = $imgcat->getVar('imgcat_storetype');
				if ($storetype == 'db') {
					$images =& $image_handler->getObjects($criteria, FALSE, TRUE);
				} else {
					$images =& $image_handler->getObjects($criteria, FALSE, FALSE);
				}
				foreach ($images as $i) {
					if ($storetype == "db"){
						$ret[$v]["/image.php?id=" . $i->getVar('image_id')] = $i->getVar('image_nicename');
					} else {
						$categ_path = $imgcat_handler->getCategFolder($imgcat);
						$categ_path = str_replace(ICMS_ROOT_PATH, '', $categ_path);
						$path = (substr($categ_path,-1) != '/') ? $categ_path . '/' : $categ_path;
						$ret[$v][$path . $i->getVar('image_name')] = $i->getVar('image_nicename');
					}
				}
			} else {
				$ret[$v] = "";
			}
		}
		return $ret;
	}

	/**
	 * Get Optgroups
	 *
	 * @return	array   Array of optgroups
	 */
	public function getOptGroups() {
		return $this->_optgroups;
	}

	/**
	 * Get OptgroupIDs
	 *
	 * @return	array   Array of optgroupids
	 */
	public function getOptGroupsID() {
		return $this->_optgroupsID;
	}

	/**
	 * Renders the HTML for the select form attribute
	 * @return   string    $ret    the constructed select form attribute HTML
	 */
	public function render(){
		if (!is_object(icms::$user)) {
			$group = array(XOOPS_GROUP_ANONYMOUS);
		} else {
			$group =& icms::$user->getGroups();
		}
		$imgcat_handler = icms::handler('icms_image_category');
		$catlist =& $imgcat_handler->getList($group, 'imgcat_write', 1);
		$catlist_total = count($catlist);
		$optIds = $this->getOptGroupsID();
		$ret = "<select onchange='if(this.options[this.selectedIndex].value != \"\"){ document.getElementById(\""
			. $this->getName() . "_img\").src=\"" . ICMS_URL . "\"+this.options[this.selectedIndex].value;}else{document.getElementById(\"" . $this->getName() . "_img\").src=\""
			. ICMS_URL . "/images/blank.gif\";}'  size='" . $this->getSize() . "'" . $this->getExtra() . "";
		if ($this->isMultiple() != false) {
			$ret .= " name='" . $this->getName() . "[]' id='" . $this->getName() . "[]' multiple='multiple'>\n";
		} else {
			$ret .= " name='" . $this->getName() . "' id='" . $this->getName() . "'>\n";
		}
		$ret .= "<option value=''>" . _SELECT . "</option>\n";
		foreach ($this->getOptGroups() as $nome => $valores) {
			$ret .= '\n<optgroup id="img_cat_' . $optIds[$nome] . '" label="' . $nome . '">';
			if (is_array($valores)) {
				foreach ($valores as $value => $name) {
					$ret .= "<option value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
					if (count($this->getValue()) > 0 && in_array($value, $this->getValue())) {
						$ret .= " selected='selected'";
						$imagem = $value;
					}
					$ret .= ">" . $name . "</option>\n";
				}
			}
			$ret .= '</optgroup>\n';
		}
		$browse_url = ICMS_URL."/modules/system/admin/images/browser.php";
		$ret .= "</select>";
		$ret .= ($catlist_total > 0)
			? " <input type='button' value='" . _ADDIMAGE . "' onclick=\"window.open('$browse_url?target=" . $this->getName() . "','formImage','resizable=yes,scrollbars=yes,width=985,height=470,left='+(screen.availWidth/2-492)+',top='+(screen.availHeight/2-235)+'');return false;\">"
			: "" ;
		$ret .= "<br /><img id='" . $this->getName() . "_img' src='" . ((!empty($imagem)) ? ICMS_URL.$imagem : ICMS_URL . "/images/blank.gif") . "'>";
		return $ret;
	}
}

