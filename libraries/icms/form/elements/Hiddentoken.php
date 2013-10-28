<?php
/**
* Creates a hidden token form attribute
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @category		ICMS
* @package		Form
* @subpackage	Elements
* @version		$Id: Hiddentoken.php 20423 2010-11-20 17:09:38Z phoenyx $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * A hidden token field
 *
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 */
class icms_form_elements_Hiddentoken extends icms_form_elements_Hidden {

  /**
   * Constructor
   *
   * @param   string  $name       "name" attribute
   * @param   int     $timeout    timeout variable for the createToken function
   */
  public function icms_form_elements_Hiddentoken($name = _CORE_TOKEN, $timeout = 0) {
      parent::__construct($name . '_REQUEST', icms::$security->createToken($timeout, $name));
  }
}

