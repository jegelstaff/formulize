<?php
// $Id: menu.php,v 1.2 2007/08/25 14:37:47 marcan Exp $
/**
* Contains links to admin options and images for those admin options
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package	Administration
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @version		$Id$
*/

include_once(XOOPS_ROOT_PATH."/modules/system/constants.php");

$i=0;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU1;
$adminmenu[$i]['link'] = "admin.php?fct=banners";
$adminmenu[$i]['icon'] = "admin/banners/images/banners.png";
$adminmenu[$i]['small'] = "admin/banners/images/banners_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_BANNER;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU2;
$adminmenu[$i]['link'] = "admin.php?fct=blocksadmin";
$adminmenu[$i]['icon'] = "admin/blocksadmin/images/blocksadmin.png";
$adminmenu[$i]['small'] = "admin/blocksadmin/images/blocksadmin_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_BLOCK;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU3;
$adminmenu[$i]['link'] = "admin.php?fct=groups";
$adminmenu[$i]['icon'] = "admin/groups/images/groups.png";
$adminmenu[$i]['small'] = "admin/groups/images/groups_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_GROUP;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU13;
$adminmenu[$i]['link'] = "admin.php?fct=images";
$adminmenu[$i]['icon'] = "admin/images/images/images.png";
$adminmenu[$i]['small'] = "admin/images/images/images_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_IMAGE;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU5;
$adminmenu[$i]['link'] = "admin.php?fct=modulesadmin";
$adminmenu[$i]['icon'] = "admin/modulesadmin/images/modulesadmin.png";
$adminmenu[$i]['small'] = "admin/modulesadmin/images/modulesadmin_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_MODULE;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU6;
$adminmenu[$i]['link'] = "admin.php?fct=preferences";
$adminmenu[$i]['icon'] = "admin/preferences/images/preferences.png";
$adminmenu[$i]['small'] = "admin/preferences/images/preferences_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_PREF;
//Getting categories of preferences to include in dropdownmenu
global $xoopsConfig;
icms_loadLanguageFile('system', 'preferences', true);
$confcat_handler = xoops_gethandler('configcategory');
$confcats = $confcat_handler->getObjects();
$catcount = count($confcats);
if ($catcount > 0){
	$adminmenu[$i]['hassubs'] = 1;
	for ($x = 0; $x < $catcount; $x++) {
		$subs[$x]['title'] = constant($confcats[$x]->getVar('confcat_name'));
		$subs[$x]['link'] = XOOPS_URL.'/modules/system/admin.php?fct=preferences&amp;op=show&amp;confcat_id='.$confcats[$x]->getVar('confcat_id');
	}
	$adminmenu[$i]['subs'] = $subs;
}
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU7;
$adminmenu[$i]['link'] = "admin.php?fct=smilies";
$adminmenu[$i]['icon'] = "admin/smilies/images/smilies.png";
$adminmenu[$i]['small'] = "admin/smilies/images/smilies_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_SMILE;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU9;
$adminmenu[$i]['link'] = "admin.php?fct=userrank";
$adminmenu[$i]['icon'] = "admin/userrank/images/userrank.png";
$adminmenu[$i]['small'] = "admin/userrank/images/userrank_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_URANK;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU10;
$adminmenu[$i]['link'] = "admin.php?fct=users";
$adminmenu[$i]['icon'] = "admin/users/images/users.png";
$adminmenu[$i]['small'] = "admin/users/images/users_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_USER;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU12;
$adminmenu[$i]['link'] = "admin.php?fct=findusers";
$adminmenu[$i]['icon'] = "admin/findusers/images/findusers.png";
$adminmenu[$i]['small'] = "admin/findusers/images/findusers_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_FINDU;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU11;
$adminmenu[$i]['link'] = "admin.php?fct=mailusers";
$adminmenu[$i]['icon'] = "admin/mailusers/images/mailusers.png";
$adminmenu[$i]['small'] = "admin/mailusers/images/mailusers_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_MAILU;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU14;
$adminmenu[$i]['link'] = "admin.php?fct=avatars";
$adminmenu[$i]['icon'] = "admin/avatars/images/avatars.png";
$adminmenu[$i]['small'] = "admin/avatars/images/avatars_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_AVATAR;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU15;
$adminmenu[$i]['link'] = "admin.php?fct=tplsets";
$adminmenu[$i]['icon'] = "admin/tplsets/images/tplsets.png";
$adminmenu[$i]['small'] = "admin/tplsets/images/tplsets_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_TPLSET;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU16;
$adminmenu[$i]['link'] = "admin.php?fct=comments";
$adminmenu[$i]['icon'] = "admin/comments/images/comments.png";
$adminmenu[$i]['small'] = "admin/comments/images/comments_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_COMMENT;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU17;
$adminmenu[$i]['link'] = "admin.php?fct=version";
$adminmenu[$i]['icon'] = "admin/version/images/version.png";
$adminmenu[$i]['small'] = "admin/version/images/version_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_VERSION;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU19;
$adminmenu[$i]['link'] = "admin.php?fct=blockspadmin";
$adminmenu[$i]['icon'] = "admin/blockspadmin/images/blockspadmin.png";
$adminmenu[$i]['small'] = "admin/blockspadmin/images/blockspadmin_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_BLOCKP;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU20;
$adminmenu[$i]['link'] = "admin.php?fct=pages";
$adminmenu[$i]['icon'] = "admin/pages/images/pages.png";
$adminmenu[$i]['small'] = "admin/pages/images/pages_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_PAGES;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU21;
$adminmenu[$i]['link'] = "admin.php?fct=customtag";
$adminmenu[$i]['icon'] = "admin/customtag/images/customtag.png";
$adminmenu[$i]['small'] = "admin/customtag/images/customtag_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_CUSTOMTAGS;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU22;
$adminmenu[$i]['link'] = "admin.php?fct=adsense";
$adminmenu[$i]['icon'] = "admin/adsense/images/adsense.png";
$adminmenu[$i]['small'] = "admin/adsense/images/adsense_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_ADSENSES;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU23;
$adminmenu[$i]['link'] = "admin.php?fct=rating";
$adminmenu[$i]['icon'] = "admin/rating/images/rating.png";
$adminmenu[$i]['small'] = "admin/rating/images/rating_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_RATINGS;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU24;
$adminmenu[$i]['link'] = "admin.php?fct=mimetype";
$adminmenu[$i]['icon'] = "admin/mimetype/images/mimetype.png";
$adminmenu[$i]['small'] = "admin/mimetype/images/mimetype_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_MIMETYPES;
$i++;
$adminmenu[$i]['title'] = _MI_SYSTEM_ADMENU25;
$adminmenu[$i]['link'] = "admin.php?fct=autotasks";
$adminmenu[$i]['icon'] = "admin/autotasks/images/autotasks.png";
$adminmenu[$i]['small'] = "admin/autotasks/images/autotasks_small.png";
$adminmenu[$i]['id'] = XOOPS_SYSTEM_AUTOTASKS;
?>