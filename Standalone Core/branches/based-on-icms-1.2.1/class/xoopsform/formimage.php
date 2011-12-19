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
* @version	$Id: formimage.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/


if (!defined('ICMS_ROOT_PATH')) {
	die("Oooops!!");
}

include_once ICMS_ROOT_PATH."/class/xoopsform/formselect.php";

class MastopFormSelectImage extends XoopsFormSelect
{
	/**
   * OptGroup
	 * @var array
	 * @access	private
	 */
	var $_optgroups = array();
	var $_optgroupsID = array();

	/**
	 * Construtor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	  $value	Value for the Select attribute
	 * @param	string	$cat    Name of the Category
	 */
	function MastopFormSelectImage($caption, $name, $value=null, $cat = null)
	{
		$this->XoopsFormSelect($caption, $name, $value);
		$this->addOptGroupArray($this->getImageList($cat));
	}

	/**
	 * Adiciona um Optgroup
   *
	 * @param	string  $value  opções do Grupo
   * @param	string  $name   Nome do Grupo de opções
	 */
	function addOptGroup($value=array(), $name="&nbsp;"){
		$this->_optgroups[$name] = $value;
	}

	/**
	 * Adiciona m�ltiplos Optgroups
	 *
   * @param	array   $options    Array com nome->opções
	 */
	function addOptGroupArray($options){
		if ( is_array($options) ) {
			foreach ( $options as $k=>$v ) {
				$this->addOptGroup($v,$k);
			}
		}
	}

  /**
  * Gets the image list
  *
  * @param    mixed     $cat    category number or array of categories
  * @return   string    $ret    The imagelist string
  */
	function getImageList($cat = null)
	{
		global $icmsUser;
		$ret = array();
		if (!is_object($icmsUser)) {
			$group = array(XOOPS_GROUP_ANONYMOUS);
		} else {
			$group =& $icmsUser->getGroups();
		}
		$imgcat_handler =& xoops_gethandler('imagecategory');
		$catlist =& $imgcat_handler->getList($group, 'imgcat_read', 1);
		if (is_array($cat) && count($catlist) > 0) {
			foreach ($catlist as $k=>$v) {
				if (!in_array($k, $cat)) {
					unset($catlist[$k]);
				}
			}
		}elseif (is_int($cat)){
			$catlist = array_key_exists($cat, $catlist) ? array($cat=>$catlist[$cat]) : array();
		}

  	$image_handler = xoops_gethandler('image');
  	foreach ($catlist as $k=>$v) {
  		$this->_optgroupsID[$v] = $k;
  		$criteria = new CriteriaCompo(new Criteria('imgcat_id', $k));
  		$criteria->add(new Criteria('image_display', 1));
  		$total = $image_handler->getCount($criteria);
  		if ($total > 0) {
  			$imgcat =& $imgcat_handler->get($k);
  			$storetype = $imgcat->getVar('imgcat_storetype');
  			if ($storetype == 'db') {
  				$images =& $image_handler->getObjects($criteria, false, true);
  			} else {
  				$images =& $image_handler->getObjects($criteria, false, false);
  			}
  			foreach ($images as $i) {
  				if($storetype == "db"){
  					$ret[$v]["/image.php?id=".$i->getVar('image_id')] = $i->getVar('image_nicename');
  				}else{
  					$categ_path = $imgcat_handler->getCategFolder($imgcat);
  					$categ_path = str_replace(ICMS_ROOT_PATH,'',$categ_path);
  					$path = (substr($categ_path,-1) != '/')?$categ_path.'/':$categ_path;
  					$ret[$v][$path.$i->getVar('image_name')] = $i->getVar('image_nicename');
  				}
  			}
  		}else{
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
	function getOptGroups(){
		return $this->_optgroups;
	}

	/**
	 * Get OptgroupIDs
	 *
   * @return	array   Array of optgroupids
   */
	function getOptGroupsID(){
		return $this->_optgroupsID;
	}

  /**
  * Renders the HTML for the select form attribute
  * @return   string    $ret    the constructed select form attribute HTML
  */
	function render(){
		global $icmsUser;
		if (!is_object($icmsUser)) {
			$group = array(XOOPS_GROUP_ANONYMOUS);
		} else {
			$group =& $icmsUser->getGroups();
		}
		$imgcat_handler =& xoops_gethandler('imagecategory');
		$catlist =& $imgcat_handler->getList($group, 'imgcat_write', 1);
		$catlist_total = count($catlist);
		$optIds = $this->getOptGroupsID();
		$ret = "<select onchange='if(this.options[this.selectedIndex].value != \"\"){ document.getElementById(\"".$this->getName()."_img\").src=\"".ICMS_URL."\"+this.options[this.selectedIndex].value;}else{document.getElementById(\"".$this->getName()."_img\").src=\"".ICMS_URL."/images/blank.gif\";}'  size='".$this->getSize()."'".$this->getExtra()."";
		if ($this->isMultiple() != false) {
			$ret .= " name='".$this->getName()."[]' id='".$this->getName()."[]' multiple='multiple'>\n";
		} else {
			$ret .= " name='".$this->getName()."' id='".$this->getName()."'>\n";
		}
		$ret .= "<option value=''>"._SELECT."</option>\n";
		foreach ( $this->getOptGroups() as $nome => $valores ){
			$ret .= '\n<optgroup id="img_cat_'.$optIds[$nome].'" label="'.$nome.'">';
			if (is_array($valores)) {
				foreach ( $valores as $value => $name ) {
					$ret .= "<option value='".htmlspecialchars($value, ENT_QUOTES)."'";
					if (count($this->getValue()) > 0 && in_array($value, $this->getValue())) {
						$ret .= " selected='selected'";
						$imagem = $value;
					}
					$ret .= ">".$name."</option>\n";
				}
			}
			$ret .= '</optgroup>\n';
		}
		$browse_url = ICMS_URL."/class/xoopsform/formimage_browse.php";
		$ret .= "</select>";
		$ret .= ($catlist_total > 0) ? " <input type='button' value='"._ADDIMAGE."' onclick=\"window.open('$browse_url?target=".$this->getName()."','formImage','resizable=yes,scrollbars=yes,width=985,height=470,left='+(screen.availWidth/2-492)+',top='+(screen.availHeight/2-235)+'');return false;\">":"" ;
		$ret .= "<br /><img id='".$this->getName()."_img' src='".((!empty($imagem)) ? ICMS_URL.$imagem : ICMS_URL."/images/blank.gif")."'>";
		return $ret;
	}
}

?>