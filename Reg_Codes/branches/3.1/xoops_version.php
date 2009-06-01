<?php
// ------------------------------------------------------------------------- 
//	Registration Codes
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

$modversion['name'] = _MI_REG_CODES_NAME;
$modversion['version'] = 3.0;
$modversion['description'] = _MI_REG_CODES_DESC;
$modversion['credits'] = "Thomas Hill http://www.worldware.com";
$modversion['author'] = "Freeform Solutions";
$modversion['help'] = "docs/reg_codes_admin.html";
$modversion['license'] = "GPL";
$modversion['official'] = 0;
$modversion['image'] = "images/reg_codes.gif";
$modversion['dirname'] = "reg_codes";

// SQL file 
// This is preprocessed by xoops. The format must be constistant with
// output produced by PHPMYADMIN
// All tables should not have any prefix!
$modversion['sqlfile']['mysql'] = "sql/mysql.sql";

// Tables created by sql (without prefix!)
$modversion['tables'][] = "reg_codes";
$modversion['tables'][] = "reg_codes_confirm_user";
$modversion['tables'][] = "reg_codes_preapproved_users";

// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

// Main contents
$modversion['hasMain'] = 1;

// Templates
$modversion['templates'][0]['file'] = 'reg_codes_index.html';
$modversion['templates'][0]['description'] = 'Registration Codes Template Page';
$modversion['templates'][1]['file'] = 'reg_codes_new.html';
$modversion['templates'][1]['description'] = 'Registration Codes Template Page - New Code';
$modversion['templates'][2]['file'] = 'reg_codes_edit.html';
$modversion['templates'][2]['description'] = 'Registration Codes Template Page - Edit Code';
$modversion['templates'][3]['file'] = 'reg_codes_pa_main.html';
$modversion['templates'][3]['description'] = 'Pre-approved Users Template - Main';
$modversion['templates'][4]['file'] = 'reg_codes_pa_edit.html';
$modversion['templates'][4]['description'] = 'Pre-approved Users Template - Edit';
$modversion['templates'][5]['file'] = 'reg_codes_pa_suspend.html';
$modversion['templates'][5]['description'] = 'Pre-approved Users Template - Suspend';
$modversion['templates'][6]['file'] = 'reg_codes_pa_add.html';
$modversion['templates'][6]['description'] = 'Pre-approved Users Template - Add New User';


// Config
$modversion['config'][0]['name'] = 'anons_view_profiles';
$modversion['config'][0]['title'] = '_MI_REG_CODES_AVP';
$modversion['config'][0]['description'] = '_MI_REG_CODES_AVP_DESC';
$modversion['config'][0]['formtype'] = 'yesno';
$modversion['config'][0]['valuetype'] = 'int';
$modversion['config'][0]['default'] = 0;

$modversion['config'][1]['name'] = 'notification_default';
$modversion['config'][1]['title'] = '_MI_REG_CODES_NOTDEF';
$modversion['config'][1]['description'] = '_MI_REG_CODES_NOTDEF_DESC';
$modversion['config'][1]['formtype'] = 'select';
$modversion['config'][1]['valuetype'] = 'int';
$modversion['config'][1]['options'] = array(_MI_REG_CODES_NOTDEF_EMAIL=>1, _MI_REG_CODES_NOTDEF_PM=>2);
$modversion['config'][1]['default'] = 1;

$modversion['config'][2]['name'] = 'limit_by_groups';
$modversion['config'][2]['title'] = '_MI_REG_CODES_LIMITBYGROUPS';
$modversion['config'][2]['description'] = '';
$modversion['config'][2]['formtype'] = 'yesno';
$modversion['config'][2]['valuetype'] = 'int';
$modversion['config'][2]['default'] = 0;

$modversion['config'][3]['name'] = 'email_as_username';
$modversion['config'][3]['title'] = '_MI_REG_CODES_EMAILASUSERNAME';
$modversion['config'][3]['description'] = '';
$modversion['config'][3]['formtype'] = 'yesno';
$modversion['config'][3]['valuetype'] = 'int';
$modversion['config'][3]['default'] = 0;
// no blocks
/*// Blocks (Start indexes with 1, not 0!)
// This is a simple block that just displays a fixed list.
$modversion['blocks'][1]['file'] = "blocks.php";
$modversion['blocks'][1]['name'] = _MI_REG_CODES_BLOCK_ONE_TITLE;
$modversion['blocks'][1]['description'] = _MI_REG_CODES_BLOCK_ONE_DESC;
$modversion['blocks'][1]['show_func'] = "b_reg_codes_do_block";
$modversion['blocks'][1]['template'] = 'reg_codes_block_one.html';
$modversion['blocks'][1]['options']	= 1 | "two";

// This block displays a selection from the database, controlled by the configuration, which is set in 
// module admin administration for Registration Codes
$modversion['blocks'][2]['file'] = "blocks_db.php";
$modversion['blocks'][2]['name'] = _MI_REG_CODES_BLOCK_TWO_TITLE;
$modversion['blocks'][2]['description'] = _MI_REG_CODES_BLOCK_TWO_DESC;
$modversion['blocks'][2]['show_func'] = "b_reg_codes_do_db_block";
$modversion['blocks'][2]['template'] = 'reg_codes_block_two.html';
$modversion['blocks'][2]['options']	= 1 | "two";
*/