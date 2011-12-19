<?php
/**
 * Contains the basis classes for managing any SEO-enabled objects derived from IcmsPersistableObjects
 *
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmspersistableseoobject.php 8569 2009-04-11 13:34:58Z icmsunderdog $
 */

if (!defined("XOOPS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

include_once ICMS_ROOT_PATH . "/kernel/icmspersistableobject.php";
include_once ICMS_ROOT_PATH . "/kernel/icmsmetagen.php";

/**
 * IcmsPersistableObject base SEO-enabled class
 *
 * Base class representing a single IcmsPersistableObject with "search engine optimisation" capabilities
 *
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmspersistableseoobject.php 8569 2009-04-11 13:34:58Z icmsunderdog $
 */
class IcmsPersistableSeoObject extends IcmsPersistableObject {
    function IcmsPersistableSeoObject() {
        $this->initCommonVar("meta_keywords");
        $this->initCommonVar("meta_description");
        $this->initCommonVar("short_url");
        $this->seoEnabled = true;
    }

    /**
     * Return the value of the short_url field of this object
     *
     * @return string
     */
    function short_url()
    {
    	return $this->getVar('short_url');
    }

    /**
     * Return the value of the meta_keywords field of this object
     *
     * @return string
     */
    function meta_keywords()
    {
    	return $this->getVar('meta_keywords');
    }

    /**
     * Return the value of the meta_description field of this object
     *
     * @return string
     */
    function meta_description()
    {
    	return $this->getVar('meta_description');
    }
}

?>