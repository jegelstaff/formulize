<?php
/**
* Manage of imagesets baseclass
* Image sets - the image directory within a module - are part of templates 
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: imageset.php 19775 2010-07-11 18:54:25Z malanciault $
*/

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 *
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * An imageset
 *
 * These sets are managed through a {@link icms_image_set_Handler} object
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class icms_image_set_Object extends XoopsObject
{
    /**
     * Constructor
     *
     */
  	function __construct() {
  		$this->XoopsObject();
  		$this->initVar('imgset_id', XOBJ_DTYPE_INT, null, false);
  		$this->initVar('imgset_name', XOBJ_DTYPE_TXTBOX, null, true, 50);
  		$this->initVar('imgset_refid', XOBJ_DTYPE_INT, 0, false);
  	}
}