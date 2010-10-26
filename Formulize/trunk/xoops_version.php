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
$modversion['version'] = "4.0";
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
$modversion['tables'][18] = "formulize_application_form_link";
$modversion['tables'][19] = "formulize_applications";
$modversion['tables'][20] = "formulize_screen_form";


// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/formindex.php";
$modversion['adminmenu'] = "admin/menu.php";

// Menu -- content in main menu block
$modversion['hasMain'] = 1;


// Templates
$tindex = 0;
$modversion['templates'][++$tindex]['file'] = 'formulize_cat.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'formulize_application.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'calendar_month.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'calendar_mini_month.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'calendar_micro_month.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/ui.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/ui-tabs.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/ui-accordion.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/application_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/application_forms.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_listing.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_permissions.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_screens.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_elements.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_elements_sections.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/form_advanced_calculations.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/application_relationships.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/application_relationships_sections.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/relationship_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/relationship_common_values.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_names.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_options.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_display.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_advanced.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_checkbox.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_date.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_derived.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_grid.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_areamodif.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_ib.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_radio.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_select.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_sep.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_subform.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_textarea.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_text.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_type_yn.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/home.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/home_sections.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_entries.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_custom.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_custom_sections.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_form_options.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_buttons.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_templates.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_list_headings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_multipage_options.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_multipage_text.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_multipage_pages.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_multipage_pages_sections.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/screen_multipage_pages_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/element_optionlist.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/advanced_calculation_settings.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/advanced_calculation_input_output.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/advanced_calculation_steps.html';
$modversion['templates'][$tindex]['description'] = '';
$modversion['templates'][++$tindex]['file'] = 'admin/advanced_calculation_steps_sections.html';
$modversion['templates'][$tindex]['description'] = '';


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

// number formatting options
$modversion['config'][10]['name'] = 'number_decimals';
$modversion['config'][10]['title'] = '_MI_formulize_NUMBER_DECIMALS';
$modversion['config'][10]['description'] = '_MI_formulize_NUMBER_DECIMALS_DESC';
$modversion['config'][10]['formtype'] = 'textbox';
$modversion['config'][10]['valuetype'] = 'int';
$modversion['config'][10]['default'] = 0;

$modversion['config'][11]['name'] = 'number_prefix';
$modversion['config'][11]['title'] = '_MI_formulize_NUMBER_PREFIX';
$modversion['config'][11]['description'] = '_MI_formulize_NUMBER_PREFIX_DESC';
$modversion['config'][11]['formtype'] = 'textbox';
$modversion['config'][11]['valuetype'] = 'text';
$modversion['config'][11]['default'] = "";

$modversion['config'][12]['name'] = 'number_decimalsep';
$modversion['config'][12]['title'] = '_MI_formulize_NUMBER_DECIMALSEP';
$modversion['config'][12]['description'] = '';
$modversion['config'][12]['formtype'] = 'textbox';
$modversion['config'][12]['valuetype'] = 'text';
$modversion['config'][12]['default'] = ".";

$modversion['config'][13]['name'] = 'number_sep';
$modversion['config'][13]['title'] = '_MI_formulize_NUMBER_SEP';
$modversion['config'][13]['description'] = '';
$modversion['config'][13]['formtype'] = 'textbox';
$modversion['config'][13]['valuetype'] = 'text';
$modversion['config'][13]['default'] = ",";

$modversion['config'][14]['name'] = 'heading_help_link';
$modversion['config'][14]['title'] = '_MI_formulize_HEADING_HELP_LINK';
$modversion['config'][14]['description'] = '_MI_formulize_HEADING_HELP_LINK_DESC';
$modversion['config'][14]['formtype'] = 'yesno';
$modversion['config'][14]['valuetype'] = 'int';
$modversion['config'][14]['default'] = 1;

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
