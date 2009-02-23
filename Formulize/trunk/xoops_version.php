<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################

$modversion['name'] = _MI_formulize_NAME;
$modversion['version'] = "3.1";
$modversion['description'] = _MI_formulize_DESC;
$modversion['author'] = "Freeform Solutions";                                            
$modversion['credits'] = "";
$modversion['help'] = "";
$modversion['license'] = "GPL";
$modversion['official'] = 0;
$modversion['image'] = "images/formulize.gif";
$modversion['dirname'] = "formulize";

$modversion['sqlfile']['mysql'] = "sql/mysql.sql";

// Tables created by sql file (without prefix!)
$modversion['tables'][0] = "formulize";
$modversion['tables'][1] = "formulize_id";
$modversion['tables'][2] = "formulize_menu";
$modversion['tables'][3] = "formulize_reports";
$modversion['tables'][4] = "formulize_frameworks";
$modversion['tables'][5] = "formulize_framework_forms";
$modversion['tables'][6] = "formulize_framework_elements";
$modversion['tables'][7] = "formulize_framework_links";
$modversion['tables'][8] = "formulize_menu_cats";
$modversion['tables'][9] = "formulize_saved_views";
$modversion['tables'][10] = "group_lists";
$modversion['tables'][11] = "formulize_other";
$modversion['tables'][12] = "formulize_notification_conditions";
$modversion['tables'][13] = "formulize_valid_imports";
$modversion['tables'][14] = "formulize_screen";
$modversion['tables'][15] = "formulize_screen_multipage";
$modversion['tables'][16] = "formulize_screen_listofentries";
$modversion['tables'][17] = "formulize_entry_owner_groups";


// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/formindex.php";
$modversion['adminmenu'] = "admin/menu.php";

// Menu -- content in main menu block
$modversion['hasMain'] = 1;


// Templates
$modversion['templates'][1]['file'] = 'formulize_cat.html';
$modversion['templates'][1]['description'] = '';
$modversion['templates'][2]['file'] = 'calendar_month.html';
$modversion['templates'][2]['description'] = '';
$modversion['templates'][3]['file'] = 'calendar_mini_month.html';
$modversion['templates'][3]['description'] = '';
$modversion['templates'][4]['file'] = 'calendar_micro_month.html';
$modversion['templates'][4]['description'] = '';


//	Module Configs
// $xoopsModuleConfig['t_width']
$modversion['config'][1]['name'] = 't_width';
$modversion['config'][1]['title'] = '_MI_formulize_TEXT_WIDTH';
$modversion['config'][1]['description'] = '';
$modversion['config'][1]['formtype'] = 'textbox';
$modversion['config'][1]['valuetype'] = 'int';
$modversion['config'][1]['default'] = '30';

// $xoopsModuleConfig['t_max']
$modversion['config'][2]['name'] = 't_max';
$modversion['config'][2]['title'] = '_MI_formulize_TEXT_MAX';
$modversion['config'][2]['description'] = '';
$modversion['config'][2]['formtype'] = 'textbox';
$modversion['config'][2]['valuetype'] = 'int';
$modversion['config'][2]['default'] = '255';

// $xoopsModuleConfig['ta_rows']
$modversion['config'][3]['name'] = 'ta_rows';
$modversion['config'][3]['title'] = '_MI_formulize_TAREA_ROWS';
$modversion['config'][3]['description'] = '';
$modversion['config'][3]['formtype'] = 'textbox';
$modversion['config'][3]['valuetype'] = 'int';
$modversion['config'][3]['default'] = '5';

// $xoopsModuleConfig['ta_cols']
$modversion['config'][4]['name'] = 'ta_cols';
$modversion['config'][4]['title'] = '_MI_formulize_TAREA_COLS';
$modversion['config'][4]['description'] = '';
$modversion['config'][4]['formtype'] = 'textbox';
$modversion['config'][4]['valuetype'] = 'int';
$modversion['config'][4]['default'] = '35';

// $xoopsModuleConfig['delimeter'] 
$modversion['config'][5]['name'] = 'delimeter';
$modversion['config'][5]['title'] = '_MI_formulize_DELIMETER';
$modversion['config'][5]['description'] = '';
$modversion['config'][5]['formtype'] = 'select';
$modversion['config'][5]['valuetype'] = 'text';
$modversion['config'][5]['default'] = 'br';
$modversion['config'][5]['options'] = array(_MI_formulize_DELIMETER_BR=>'br', _MI_formulize_DELIMETER_SPACE=>'space');

// $xoopsModuleConfig['profileForm']
$modversion['config'][6]['name'] = 'profileForm';
$modversion['config'][6]['title'] = '_MI_formulize_PROFILEFORM';
$modversion['config'][6]['description'] = '';
$modversion['config'][6]['formtype'] = 'select';
$modversion['config'][6]['valuetype'] = 'int';
$modversion['config'][6]['default'] = '0';
// get all the available forms and populate the options array
// this is not permission controlled yet -- should make use of the edit_form permission perhaps
global $xoopsDB;
$getFormsSQL = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id");
$resFormsSQL = $xoopsDB->query($getFormsSQL);
$pformoptions["-------------"] = 0;
while($resArray = $xoopsDB->fetchArray($resFormsSQL)) {
	$pformoptions[$resArray['desc_form']] = $resArray['id_form'];
}
$modversion['config'][6]['options'] = $pformoptions;

$modversion['config'][7]['name'] = 'all_done_singles';
$modversion['config'][7]['title'] = '_MI_formulize_ALL_DONE_SINGLES';
$modversion['config'][7]['description'] = '_MI_formulize_SINGLESDESC';
$modversion['config'][7]['formtype'] = 'yesno';
$modversion['config'][7]['valuetype'] = 'int';
$modversion['config'][7]['default'] = 1;

// $xoopsModuleConfig['LOE_limit']
$modversion['config'][8]['name'] = 'LOE_limit';
$modversion['config'][8]['title'] = '_MI_formulize_LOE_limit';
$modversion['config'][8]['description'] = '_MI_formulize_LOE_limit_DESC';
$modversion['config'][8]['formtype'] = 'textbox';
$modversion['config'][8]['valuetype'] = 'int';
$modversion['config'][8]['default'] = '5000';

// $xoopsModuleConfig['useToken']
$modversion['config'][9]['name'] = 'useToken';
$modversion['config'][9]['title'] = '_MI_formulize_USETOKEN';
$modversion['config'][9]['description'] = '_MI_formulize_USETOKENDESC';
$modversion['config'][9]['formtype'] = 'yesno';
$modversion['config'][9]['valuetype'] = 'int';
$modversion['config'][9]['default'] = 1;

//bloc
$modversion['blocks'][1]['file'] = "mymenu.php";
$modversion['blocks'][1]['name'] = _MI_formulizeMENU_BNAME;
$modversion['blocks'][1]['description'] = "Zeigt individuelles Menu an";
$modversion['blocks'][1]['show_func'] = "block_formulizeMENU_show";

// Notifications -- added by jwe 10/10/04, removed for 2.0, reinstated for 2.2 with improved options
$modversion['hasNotification'] = 1;

$modversion['notification']['lookup_file'] = 'include/notification.inc.php';
$modversion['notification']['lookup_func'] = 'form_item_info';

$modversion['notification']['category'][1]['name'] = 'form';
$modversion['notification']['category'][1]['title'] = _MI_formulize_NOTIFY_FORM;
$modversion['notification']['category'][1]['description'] = _MI_formulize_NOTIFY_FORM_DESC;
$modversion['notification']['category'][1]['subscribe_from'] = 'index.php';
$modversion['notification']['category'][1]['item_name'] = 'fid';
$modversion['notification']['category'][1]['allow_bookmark'] = 0;

$modversion['notification']['event'][1]['name'] = 'new_entry';
$modversion['notification']['event'][1]['category'] = 'form';
$modversion['notification']['event'][1]['title'] = _MI_formulize_NOTIFY_NEWENTRY;
$modversion['notification']['event'][1]['caption'] = _MI_formulize_NOTIFY_NEWENTRY_CAP;
$modversion['notification']['event'][1]['description'] = _MI_formulize_NOTIFY_NEWENTRY_DESC;
$modversion['notification']['event'][1]['mail_template'] = 'form_newentry';
$modversion['notification']['event'][1]['mail_subject'] = _MI_formulize_NOTIFY_NEWENTRY_MAILSUB;

$modversion['notification']['event'][2]['name'] = 'update_entry';
$modversion['notification']['event'][2]['category'] = 'form';
$modversion['notification']['event'][2]['title'] = _MI_formulize_NOTIFY_UPENTRY;
$modversion['notification']['event'][2]['caption'] = _MI_formulize_NOTIFY_UPENTRY_CAP;
$modversion['notification']['event'][2]['description'] = _MI_formulize_NOTIFY_UPENTRY_DESC;
$modversion['notification']['event'][2]['mail_template'] = 'form_upentry';
$modversion['notification']['event'][2]['mail_subject'] = _MI_formulize_NOTIFY_UPENTRY_MAILSUB;

$modversion['notification']['event'][3]['name'] = 'delete_entry';
$modversion['notification']['event'][3]['category'] = 'form';
$modversion['notification']['event'][3]['title'] = _MI_formulize_NOTIFY_DELENTRY;
$modversion['notification']['event'][3]['caption'] = _MI_formulize_NOTIFY_DELENTRY_CAP;
$modversion['notification']['event'][3]['description'] = _MI_formulize_NOTIFY_DELENTRY_DESC;
$modversion['notification']['event'][3]['mail_template'] = 'form_delentry';
$modversion['notification']['event'][3]['mail_subject'] = _MI_formulize_NOTIFY_DELENTRY_MAILSUB;



?>
