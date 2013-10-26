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
 * @version	$Id: formimage.php 20509 2010-12-11 12:02:57Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("Oooops!!");

/**
 * Image select control for a form
 * @deprecated	Use icms_form_elements_select_Image, instead
 * @todo		Remove in 1.4
 */
class MastopFormSelectImage extends icms_form_elements_select_Image {
	private $_deprecated;
	
	/**
	 * Construtor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	  $value	Value for the Select attribute
	 * @param	string	$cat    Name of the Category
	 */
	function __construct($caption, $name, $value = null, $cat = null) {
		parent::__construct($caption, $name, $value, $cat);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_select_Image', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}