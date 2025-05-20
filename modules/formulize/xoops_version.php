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

$modversion = array(
	'name' => _MI_formulize_NAME,
	'version' => "8.0-beta",
	'description' => _MI_formulize_DESC,
	'author' => "The Formulize Project - formulize.org",
	'credits' => "",
	'help' => "",
	'license' => "GPL",
	'official' => 0,
	'image' => "images/formulize.gif",
	'dirname' => "formulize",
);

$modversion['sqlfile']['mysql'] = "sql/mysql.sql";
$modversion['sqlfile']['pdo.mysql'] = "sql/mysql.sql";

// Tables created by sql file (without prefix!)
$modversion['tables'] = array(
	"formulize",
	"formulize_id",
	"formulize_menu",
	"formulize_menu_links",
	"formulize_menu_permissions",
	"formulize_resource_mapping",
	"formulize_frameworks",
	"formulize_framework_forms",
	"formulize_framework_elements",
	"formulize_framework_links",
	"formulize_menu_cats",
	"formulize_saved_views",
	"group_lists",
	"formulize_other",
	"formulize_notification_conditions",
	"formulize_valid_imports",
	"formulize_screen",
	"formulize_screen_multipage",
	"formulize_screen_listofentries",
	"formulize_screen_template",
  "formulize_screen_calendar",
	"formulize_entry_owner_groups",
	"formulize_application_form_link",
	"formulize_applications",
	"formulize_screen_form",
	"formulize_advanced_calculations",
	"formulize_group_filters",
	"formulize_groupscope_settings",
	"formulize_procedure_logs",
	"formulize_procedure_logs_params",
	"formulize_deletion_logs",
  "formulize_apikeys",
	"formulize_tokens",
  "formulize_digest_data",
  "formulize_passcodes",
  "tfa_codes"
);

$modversion['formulize_exportable_tables'] = array(
	"formulize",
	"formulize_id",
	"formulize_menu",
	"formulize_menu_links",
	"formulize_menu_permissions",
	"formulize_frameworks",
	"formulize_framework_forms",
	"formulize_framework_elements",
	"formulize_framework_links",
	"formulize_menu_cats",
	"formulize_saved_views",
	"formulize_notification_conditions",
	"formulize_screen",
	"formulize_screen_multipage",
	"formulize_screen_listofentries",
	"formulize_screen_template",
    "formulize_screen_calendar",
    "formulize_entry_owner_groups",
	"formulize_application_form_link",
	"formulize_applications",
	"formulize_screen_form",
	"formulize_advanced_calculations",
	"formulize_group_filters",
	"formulize_groupscope_settings",
    "formulize_apikeys",
    "formulize_tokens",
    "formulize_passcodes"
);

// tables that we skip when only dealing with groups in common
// formulize_tokens is presumed to be managed independently in different site instances with different groups
$modversion['formulize_group_tables'] = array(
    "groups",
    "group_lists",
    "formulize_tokens"
);

// fields that indicate a critical group that the record in the table is about
// so we would not sync records related to this group when only syncing groups in common
$modversion['formulize_group_id_fields'] = array(
    "group_permission" => array('groupid'),
    "formulize_entry_owner_groups" => array('groupid'),
    "formulize_groupscope_settings" => array('groupid', 'view_groupid'),
    "formulize_group_filters" => array('groupid'),
    "formulize_notification_conditions" => array('not_cons_groupid'),
    "formulize_menu_permissions" => array('group_id')
);

// fields that are foreign keys to groups, but the record in the table is not about the group
// so we would sync changes in this record, except for the group-related info, when we're only syncing groups in common
// PROBLEM IS THERE ARE DIFFERENT METHODS FOR UNEARTHING THE DAMNED GROUP ID!
// REPURPOSE THE MANAGEPERMISSIONS LOGIC FOR GETTING AT THE VALUES??
$modversion['formulize_group_id_embedded'] = array(
    "formulize"=>array("ele_value","ele_display","ele_disabled"),
    "formulize_screen_listofentries"=>array("customactions"),
    "formulize_saved_views"=>array("sv_pubgroups")
);

/*
 * Table metadata general structure
 *
 * table_name: {
 *      table_fields: {}
 *      table_joins: {
 *          { table_to_join,
 *          fields_table_is_joined_on: {table1field, table2field}
 *          fields_to_return }
 *      }
 * }
 *
 */
$modversion['table_metadata'] = array(
    "formulize" => array(
        "fields" => array("ele_caption", "ele_type"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("id_form", "id_form"),
                "field" => "desc_form"
            )
        )
    ),
    "formulize_id" => array(
        "fields" => array("desc_form"),
        "joins" => array()
    ),
    "formulize_menu" => array(),
    "formulize_menu_links" => array(
        "fields" => array("link_text"),
        "joins" => array()
    ),
    "formulize_menu_permissions" => array (
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_menu_links",
                "join_field" => array("menu_id", "menu_id"),
                "field" => "link_text"
            ),
			array(
				"join_table" => "groups",
				"join_field" => array("group_id", "groupid"),
				"field" => "name"
			)
        ),
    ),
    "formulize_frameworks" => array(
        "fields" => array("frame_name"),
        "joins" => array()
    ),
    "formulize_framework_forms" => array(),
    "formulize_framework_elements" => array(),
    "formulize_framework_links" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_frameworks",
                "join_field" => array("fl_frame_id", "frame_id"),
                "field" => "frame_name"
            ),
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fl_form1_id", "id_form"),
                "field" => "desc_form"
            ),
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fl_form2_id", "id_form"),
                "field" => "desc_form"
            )
        )
    ),
    "formulize_menu_cats" => array(),
    "formulize_saved_views" => array(
        "fields" => array("sv_name"),
        "joins" => array()
    ),
    "group_lists" => array(
        "fields" => array("gl_name"),
        "joins" => array()
    ),
    "formulize_other" => array(),
    "formulize_notification_conditions" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("not_cons_fid", "id_form"),
                "field" => "desc_form"
            )
        )
    ),
    "formulize_screen" => array(
        "fields" => array("title", "type"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "desc_form"
            )
        )
    ),
    "formulize_screen_multipage" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_listofentries" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_template" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_entry_owner_groups" => array(),
    "formulize_application_form_link" => array(
        "fields" => array(),
        "joins" => array(
            array(
                    "join_table" => "formulize_applications",
                    "join_field" => array("appid", "appid"),
                "field" => "name"
                ),
                array(
                    "join_table" => "formulize_id",
                    "join_field" => array("fid", "id_form"),
                    "field" => "desc_form"
                )
            )
    ),
    "formulize_applications" => array(
        "fields" => array("name"),
        "joins" => array()
    ),
    "formulize_screen_form" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_calendar" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_advanced_calculations" => array(
        "fields" => array("name"),
        "joins" => array()
    ),
    "formulize_group_filters" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "desc_form"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
    ),
    "formulize_groupscope_settings" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "desc_form"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
    ),
    "formulize_procedure_logs" => array(),
    "formulize_procedure_logs_params" => array(),
    "formulize_deletion_logs" => array(),
    "groups" => array(
        "fields" => array("name", "group_type"),
        "joins" => array()
    ),
	"group_permission" => array(
		"fields" => array("gperm_name"),
		"joins" => array(
			array(
				"join_table" => "groups",
				"join_field" => array("gperm_groupid", "groupid"),
				"field" => "name"
			),
			array(
				"join_table" => "formulize_id",
				"join_field" => array("gperm_itemid", "id_form"),
				"field" => "desc_form"
			)
		)
	),
    "formulize_entry_owner_groups" => array(
        "fields" => array("entry_id"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "desc_form"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
	),
    "formulize_apikeys" => array(
        "fields" => array("uid","apikey"),
        "joins" => array()
    ),
    "formulize_tokens" => array(
        "fields" => array("groups","tokenkey"),
        "joins" => array()
    ),
    "formulize_passcodes" => array(
        "fields" => array("passcode", "screen"),
        "joins" => array()
    )
);


// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/ui.php";
$modversion['adminmenu'] = "admin/menu.php";

// Menu -- content in main menu block
$modversion['hasMain'] = 1;


// Templates

// Need to include templates for any custom element types first
// custom element classes must contain "Element.php" as the final part of the filename
$classFiles = scandir(XOOPS_ROOT_PATH."/modules/formulize/class/");
$customElements = array();
foreach($classFiles as $thisFile) {
	if(substr($thisFile, -11)=="Element.php") {
		$customType = substr($thisFile, 0, strpos($thisFile, "Element.php"));
		$modversion['templates'][] = array('file' => 'admin/element_type_'.$customType.'.html',
                                                   'description'=>'');
	}
}

$modversion['templates'][] = array(
	'file' => 'formulize_cat.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'formulize_application.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_mini_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_micro_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui-tabs.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui-accordion.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_forms.html',
	'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_menu_entries.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_code.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_menu_entries_sections.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_screens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_listing.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_permissions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_screens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_elements.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_elements_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_advanced_calculations.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_relationships.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_relationships_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_common_values.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_relationships.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_names.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_display.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_advanced.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_checkbox.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_colorpick.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_date.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_derived.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_grid.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_areamodif.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_ib.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_radio.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_select.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_sep.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_subform.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_textarea.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_text.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_yn.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/home.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/home_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_entries.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_custom.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_custom_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_buttons.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_templates.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_headings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_text.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_templates.html',
	'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/screen_template_options.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/screen_template_templates.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_optionlist.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedoptionlist.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedfilter.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedsortoptions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedandusernames_scopefilter.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_input_output.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_steps.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_steps_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_fltr_grp.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_fltr_grp_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/import_template.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/export_template.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/synchronize.html',
	'description' => '');
	$modversion['templates'][] = array(
		'file' => 'admin/synchronize_sections.html',
		'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/config-sync.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/sync_import.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/sync_import_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managekeys.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managetokens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_options_delimiter_choice.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_data_sections.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_data.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_templates.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'passcode.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation2-above.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation2-below.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation3-above.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation3-below.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/alternate_fields_for_linked_elements.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_templates.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_template_boxes.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'blocks/menu.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'blocks/menu_controller.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/mailusers.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managepermissions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/variable_template_help.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_listing.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_pair.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_multi.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_common.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/primary_identifier_selection.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_connection_options.html',
	'description' => '');


//	Module Configs
// $xoopsModuleConfig['t_width']
$modversion['config'][1] = array(
	'name' => 't_width',
	'title' => '_MI_formulize_TEXT_WIDTH',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '30',
);

// $xoopsModuleConfig['t_max']
$modversion['config'][] = array(
	'name' => 't_max',
	'title' => '_MI_formulize_TEXT_MAX',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '255',
);

// $xoopsModuleConfig['ta_rows']
$modversion['config'][] = array(
	'name' => 'ta_rows',
	'title' => '_MI_formulize_TAREA_ROWS',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '5',
);

// $xoopsModuleConfig['ta_cols']
$modversion['config'][] = array(
	'name' => 'ta_cols',
	'title' => '_MI_formulize_TAREA_COLS',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '35',
);

$modversion['config'][] = array(
	'name' => 'delimeter',
	'title' => '_MI_formulize_DELIMETER',
	'description' => '',
	'formtype' => 'select',
	'valuetype' => 'text',
	'default' => 'br',
	'options' => array(_MI_formulize_DELIMETER_BR=>'br', _MI_formulize_DELIMETER_SPACE=>'space'),
);

$modversion['config'][] = array(
	'name' => 'all_done_singles',
	'title' => '_MI_formulize_ALL_DONE_SINGLES',
	'description' => '_MI_formulize_SINGLESDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

$modversion['config'][] = array(
	'name' => 'LOE_limit',
	'title' => '_MI_formulize_LOE_limit',
	'description' => '_MI_formulize_LOE_limit_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '5000',
);

$modversion['config'][] = array(
	'name' => 'useToken',
	'title' => '_MI_formulize_USETOKEN',
	'description' => '_MI_formulize_USETOKENDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

// this preference is checked when save button is pressed on front end by user | 0 - False | 1 - True
$modversion['config'][] = array(
	'name' => 'isSaveLocked',
	'title' => '_MI_formulize_ISSAVELOCKED',
	'description' => '_MI_formulize_ISSAVELOCKEDDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0
);

$modversion['config'][] = array(
	'name' =>'number_decimals',
	'title' => '_MI_formulize_NUMBER_DECIMALS',
	'description' => '_MI_formulize_NUMBER_DECIMALS_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' =>'number_prefix',
	'title' => '_MI_formulize_NUMBER_PREFIX',
	'description' => '_MI_formulize_NUMBER_PREFIX_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => "",
);

$modversion['config'][] = array(
	'name' =>'number_suffix',
	'title' => '_MI_formulize_NUMBER_SUFFIX',
	'description' => '_MI_formulize_NUMBER_SUFFIX_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => "",
);

$modversion['config'][] = array(
	'name' =>'number_decimalsep',
	'title' => '_MI_formulize_NUMBER_DECIMALSEP',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => ".",
);

$modversion['config'][] = array(
	'name' =>'number_sep',
	'title' => '_MI_formulize_NUMBER_SEP',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => ",",
);

$modversion['config'][] = array(
	'name' =>'show_empty_elements_when_read_only',
	'title' => '_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY',
	'description' => '_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' =>'heading_help_link',
	'title' => '_MI_formulize_HEADING_HELP_LINK',
	'description' => '_MI_formulize_HEADING_HELP_LINK_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

$modversion['config'][] = array(
	'name' => 'useCache',
	'title' => '_MI_formulize_USECACHE',
	'description' => '_MI_formulize_USECACHEDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

$modversion['config'][] = array(
	'name' => 'downloadDefaultToExcel',
	'title' => '_MI_formulize_DOWNLOADDEFAULT',
	'description' => '_MI_formulize_DOWNLOADDEFAULT_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'logProcedure',
	'title' => '_MI_formulize_LOGPROCEDURE',
	'description' => '_MI_formulize_LOGPROCEDUREDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'printviewStylesheets',
	'title' => '_MI_formulize_PRINTVIEWSTYLESHEETS',
	'description' => '_MI_formulize_PRINTVIEWSTYLESHEETSDESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => '',
);

$modversion['config'][] = array(
	'name' => 'debugDerivedValues',
	'title' => '_MI_formulize_DEBUGDERIVEDVALUES',
	'description' => '_MI_formulize_DEBUGDERIVEDVALUESDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);

$modversion['config'][] = array(
	'name' => 'customScope',
	'title' => '_MI_formulize_CUSTOMSCOPE',
	'description' => '_MI_formulize_CUSTOMSCOPEDESC',
	'formtype' => 'textarea',
	'valuetype' => 'text',
	'default' => '',
);

$modversion['config'][] = array(
	'name' => 'exportIntroChar',
	'title' => '_MI_formulize_EXPORTINTROCHAR',
	'description' => '_MI_formulize_EXPORTINTROCHARDESC',
	'formtype' => 'select',
	'valuetype' => 'int',
	'default' => '1',
    'options' => array(
      _MI_formulize_EIC_BASIC=>1,
      _MI_formulize_EIC_ALWAYSAPOS=>2,
      _MI_formulize_EIC_ALWAYSTAB=>3,
      _MI_formulize_EIC_PLAIN=>4
        )
);

$modversion['config'][] = array(
	'name' => 'notifyByCron',
	'title' => '_MI_formulize_NOTIFYBYCRON',
	'description' => '_MI_formulize_NOTIFYBYCRONDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);

$modversion['config'][] = array(
	'name' => 'f7MenuTemplate',
	'title' => '_MI_formulize_F7MENUTEMPLATE',
	'description' => '_MI_formulize_F7MENUTEMPLATEDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '1',
);

$modversion['config'][] = array(
	'name' => 'useOldCustomButtonEffectWriting',
	'title' => '_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITING',
	'description' => '_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITINGDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);

$modversion['config'][] = array(
	'name' => 'formulizeLoggingOnOff',
	'title' => '_MI_formulize_FORMULIZELOGGINGONOFF',
	'description' => '_MI_formulize_FORMULIZELOGGINGONOFFDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'formulizeLogFileLocation',
	'title' => '_MI_formulize_FORMULIZELOGFILELOCATION',
	'description' => '_MI_formulize_FORMULIZELOGFILELOCATIONDESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => XOOPS_ROOT_PATH.'/logs',
);

$modversion['config'][] = array(
	'name' => 'formulizeLogFileStorageDurationHours',
	'title' => '_MI_formulize_formulizeLogFileStorageDurationHours',
	'description' => '_MI_formulize_formulizeLogFileStorageDurationHoursDESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '168',
);

$modversion['config'][] = array(
	'name' => 'formulizeRewriteRulesEnabled',
	'title' => '_MI_formulize_rewriteRulesEnabled',
	'description' => '_MI_formulize_rewriteRulesEnabledDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'validateCode',
	'title' => '_MI_formulize_VALIDATECODE',
	'description' => '_MI_formulize_VALIDATECODE_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

$modversion['config'][] = array(
	'name' => 'formulizePublicAPIEnabled',
	'title' => '_MI_formulize_PUBLICAPIENABLED',
	'description' => '_MI_formulize_PUBLICAPIENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'formulizeRevisionsForAllForms',
	'title' => '_MI_formulize_REVISIONSFORALLFORMS',
	'description' => '_MI_formulize_REVISIONSFORALLFORMS_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['blocks'][1] = array(
	'file' => "mymenu.php",
	'name' => _MI_formulizeMENU_BNAME,
	'description' => "",
	'show_func' => "block_formulizeMENU_show",
    'template' => 'menu_controller.html');

// Notifications -- added by jwe 10/10/04, removed for 2.0, reinstated for 2.2 with improved options
$modversion['hasNotification'] = 1;

$modversion['notification'] = array(
	'lookup_file' => 'include/notification.inc.php',
	'lookup_func' => 'form_item_info');

$modversion['notification']['category'][1] = array(
	'name' => 'form',
	'title' => _MI_formulize_NOTIFY_FORM,
	'description' => _MI_formulize_NOTIFY_FORM_DESC,
	'subscribe_from' => 'index.php',
	'item_name' => 'fid',
	'allow_bookmark' => 0,
);
$modversion['notification']['event'][1] = array(
	'name' => 'new_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_NEWENTRY,
	'caption' => _MI_formulize_NOTIFY_NEWENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_NEWENTRY_DESC,
	'mail_template' => 'form_newentry',
	'mail_subject' => _MI_formulize_NOTIFY_NEWENTRY_MAILSUB,
);
$modversion['notification']['event'][] = array(
	'name' => 'update_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_UPENTRY,
	'caption' => _MI_formulize_NOTIFY_UPENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_UPENTRY_DESC,
	'mail_template' => 'form_upentry',
	'mail_subject' => _MI_formulize_NOTIFY_UPENTRY_MAILSUB,
);
$modversion['notification']['event'][] = array(
	'name' => 'delete_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_DELENTRY,
	'caption' => _MI_formulize_NOTIFY_DELENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_DELENTRY_DESC,
	'mail_template' => 'form_delentry',
	'mail_subject' => _MI_formulize_NOTIFY_DELENTRY_MAILSUB,
);

// override mail_template and mail_subject if necessary
if(isset($GLOBALS['formulize_notificationTemplateOverride'])) {
	$modversion['notification']['event'][1]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
	$modversion['notification']['event'][2]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
	$modversion['notification']['event'][3]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
}
if(isset($GLOBALS['formulize_notificationSubjectOverride'])) {
	$modversion['notification']['event'][1]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
	$modversion['notification']['event'][2]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
	$modversion['notification']['event'][3]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
}
