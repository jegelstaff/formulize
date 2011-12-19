<?php
/**
 * Helper forms available in the ImpressCMS process
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @author		The XOOPS Project Community <http://www.xoops.org>
 * @author	   	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author	   	Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @version		$Id: xoopsformloader.php 9071 2009-07-24 20:44:27Z nekro $
 *
 * @todo		Implement a way to change use autoload.
 */

if (!defined('ICMS_ROOT_PATH')) exit();

include_once ICMS_ROOT_PATH."/class/xoopsform/formelement.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/form.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formlabel.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselect.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formpassword.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formbutton.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formcheckbox.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formhidden.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formfile.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formradio.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formradioyn.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselectcountry.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselecttimezone.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselectlang.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselectgroup.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselectuser.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselecttheme.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselectmatchoption.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formtext.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formtextarea.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formdhtmltextarea.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formelementtray.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/themeform.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/simpleform.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formtextdateselect.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formdatetime.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formhiddentoken.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formcolorpicker.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formselecteditor.php";
include_once ICMS_ROOT_PATH."/class/xoopsform/formcaptcha.php";

?>