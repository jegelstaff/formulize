<?php
/**
* Form control creating a closing section in a form for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformsectionclose.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
  * @todo	This is not functionnal yet.. it needs further integration
  */
  
class IcmsFormSectionClose extends XoopsFormElement {

	/**
   * Text
	 * @var	string
	 * @access	private
	 */
	var $_value;

	/**
	 * Constructor
	 * @param	string    $sectionname  name of the section to close
	 * @param	string    $value        value of the section to close
	 */
	function IcmsFormSectionClose($sectionname, $value=false){
		$this->setName($sectionname);
		$this->_value = $value;
	}

	/**
	 * Get the text
	 *
	 * @return	string
	 */
	function getValue(){
		return $this->_value;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string
	 */
	function render(){
		return $this->getValue();
	}
}

?>