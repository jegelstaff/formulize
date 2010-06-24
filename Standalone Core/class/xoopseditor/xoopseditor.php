<?php
/**
* For backward compatibility of xoopseditors, some modules are still using this path.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		xoopseditor
* @since		  XOOPS
* @author		  http://www.xoops.org The XOOPS Project
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: xoopseditor.php 8566 2009-04-11 12:52:08Z icmsunderdog $
*/

/*
@todo   Should we tell the modules that use this path to look at the other path?
Added Ticket #44 to trac http://trac.impresscms.org/addons/ticket/44
*/

include_once str_replace("/class/xoopseditor/", "/class/", str_replace("\\", "/", __FILE__));
?>