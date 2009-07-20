CREATE TABLE `formulize_screen_listofentries` (
  `listofentriesid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `useworkingmsg` tinyint(1) NOT NULL,
  `repeatheaders` tinyint(1) NOT NULL,
  `useaddupdate` varchar(255) NOT NULL default '',
  `useaddmultiple` varchar(255) NOT NULL default '',
  `useaddproxy` varchar(255) NOT NULL default '',
  `usecurrentviewlist` varchar(255) NOT NULL default '',
  `limitviews` text NOT NULL, 
  `defaultview` varchar(20) NOT NULL default '',
  `usechangecols` varchar(255) NOT NULL default '',
  `usecalcs` varchar(255) NOT NULL default '',
  `useadvsearch` varchar(255) NOT NULL default '',
  `useexport` varchar(255) NOT NULL default '',
  `useexportcalcs` varchar(255) NOT NULL default '',
  `useimport` varchar(255) NOT NULL default '',
  `useclone` varchar(255) NOT NULL default '',
  `usedelete` varchar(255) NOT NULL default '',
  `useselectall` varchar(255) NOT NULL default '',
  `useclearall` varchar(255) NOT NULL default '',
  `usenotifications` varchar(255) NOT NULL default '',
  `usereset` varchar(255) NOT NULL default '',
  `usesave` varchar(255) NOT NULL default '',
  `usedeleteview` varchar(255) NOT NULL default '',
  `useheadings` tinyint(1) NOT NULL,
  `usesearch` tinyint(1) NOT NULL, 
  `usecheckboxes` tinyint(1) NOT NULL, 
  `useviewentrylinks` tinyint(1) NOT NULL,
  `usescrollbox` tinyint(1) NOT NULL,
  `usesearchcalcmsgs` tinyint(1) NOT NULL,
  `hiddencolumns` text NOT NULL,
  `decolumns` text NOT NULL,
  `desavetext` varchar(255) NOT NULL default '',
  `columnwidth` int(1) NOT NULL,
  `textwidth` int(1) NOT NULL,
  `customactions` text NOT NULL, 
  `toptemplate` text NOT NULL,
  `listtemplate` text NOT NULL,
  `bottomtemplate` text NOT NULL,
  `entriesperpage` int(1) NOT NULL,
  `viewentryscreen` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`listofentriesid`),
  INDEX i_sid (`sid`)
) TYPE=MyISAM;

CREATE TABLE `formulize_screen_multipage` (
  `multipageid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `introtext` text NOT NULL,
  `thankstext` text NOT NULL,
  `donedest` varchar(255) NOT NULL default '',
  `buttontext` varchar(255) NOT NULL default '',
  `pages` text NOT NULL,
  `pagetitles` text NOT NULL,
  `conditions` text NOT NULL,
  `printall` tinyint(1) NOT NULL,
  `paraentryform` int(11) NOT NULL default 0,
  `paraentryrelationship` tinyint(1) NOT NULL default 0,
  PRIMARY KEY (`multipageid`),
  INDEX i_sid (`sid`)
) TYPE=MyISAM;

CREATE TABLE `formulize_screen` (
  `sid` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `fid` int(11) NOT NULL default 0,
  `frid` int(11) NOT NULL default 0,
  `type` varchar(100) NOT NULL default '',
  `useToken` tinyint(1) NOT NULL,
  PRIMARY KEY  (`sid`)
) TYPE=MyISAM;

CREATE TABLE formulize_valid_imports (
  import_id smallint(5) NOT NULL auto_increment,
  file varchar(255) NOT NULL default '',
  id_reqs text NOT NULL,
  fid int(5),
  PRIMARY KEY (`import_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_notification_conditions (
  not_cons_id smallint(5) NOT NULL auto_increment,
  not_cons_fid smallint(5) NOT NULL default 0,
  not_cons_event varchar(25) default '',
  not_cons_uid mediumint(8) NOT NULL default 0,
  not_cons_curuser tinyint(1),
  not_cons_groupid smallint(5) NOT NULL default 0,
  not_cons_creator tinyint(1),
  not_cons_elementuids smallint(5) NOT NULL default 0,
  not_cons_linkcreator smallint(5) NOT NULL default 0,
  not_cons_con text NOT NULL,
  not_cons_template varchar(255) default '',
  not_cons_subject varchar(255) default '',
  PRIMARY KEY (`not_cons_id`),
  INDEX i_not_cons_fid (not_cons_fid),
  INDEX i_not_cons_uid (not_cons_uid),
  INDEX i_not_cons_groupid (not_cons_groupid),
  INDEX i_not_cons_fidevent (not_cons_fid, not_cons_event(1))
) TYPE=MyISAM;

CREATE TABLE formulize_other (
  other_id int(5) NOT NULL auto_increment,
  id_req int(5),
  ele_id int(5),
  other_text varchar(255) default NULL,
  PRIMARY KEY (`other_id`),
  INDEX i_ele_id (ele_id),
  INDEX i_id_req (id_req)
) TYPE=MyISAM;

CREATE TABLE formulize_saved_views (
  sv_id smallint(5) NOT NULL auto_increment,
  sv_name varchar(255) default NULL,
  sv_pubgroups text default NULL,
  sv_owner_uid int(5),
  sv_mod_uid int(5),
  sv_formframe varchar(255) default NULL,
  sv_mainform varchar(255) default NULL,
  sv_lockcontrols tinyint(1),
  sv_hidelist tinyint(1),
  sv_hidecalc tinyint(1),
  sv_asearch text default NULL,
  sv_sort varchar(255) default NULL,
  sv_order varchar(30) default NULL,
  sv_oldcols text default NULL,
  sv_currentview text default NULL,
  sv_calc_cols text default NULL,
  sv_calc_calcs text default NULL,
  sv_calc_blanks text default NULL,
  sv_calc_grouping text default NULL,
  sv_quicksearches text default NULL,
  PRIMARY KEY (sv_id)
) TYPE=MyISAM;

CREATE TABLE group_lists (
  gl_id smallint(5) unsigned NOT NULL auto_increment,
  gl_name varchar(255) NOT NULL default '',
  gl_groups text NOT NULL,
  PRIMARY KEY (gl_id),
  UNIQUE gl_name_id (gl_name)
) TYPE=MyISAM;

CREATE TABLE formulize_menu_cats (
  cat_id smallint(5) NOT NULL auto_increment,
  cat_name varchar(255) default NULL,
  id_form_array varchar(255) default NULL,
  PRIMARY KEY (`cat_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_frameworks (
  frame_id smallint(5) NOT NULL auto_increment,
  frame_name varchar(255) default NULL,
  PRIMARY KEY (`frame_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_framework_links (
  fl_id smallint(5) NOT NULL auto_increment,
  fl_frame_id smallint(5),
  fl_form1_id smallint(5),
  fl_form2_id smallint(5),
  fl_key1 smallint(5),
  fl_key2 smallint(5),
  fl_relationship smallint(5),
  fl_unified_display smallint(5),
  fl_common_value tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`fl_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_framework_forms (
  ff_id smallint(5) NOT NULL auto_increment,
  ff_frame_id smallint(5),
  ff_form_id smallint(5),
  ff_handle varchar(255) default NULL,
  PRIMARY KEY (`ff_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_framework_elements (
  fe_id smallint(5) NOT NULL auto_increment,
  fe_frame_id smallint(5),
  fe_form_id smallint(5),
  fe_element_id smallint(5),
  fe_handle varchar(255) default NULL,
  PRIMARY KEY (`fe_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_reports (
  report_id smallint(5) NOT NULL auto_increment,
  report_name varchar(255) default NULL,
  report_id_form smallint(5),
  report_uid int(10),
  report_ispublished tinyint(1) NOT NULL default '0',
  report_groupids text NOT NULL,
  report_scope text NOT NULL,
  report_fields text NOT NULL,
  report_search_typeArray text NOT NULL,
  report_search_textArray text NOT NULL,
  report_andorArray text NOT NULL,
  report_calc_typeArray text NOT NULL,
  report_sort_orderArray text NOT NULL,
  report_ascdscArray text NOT NULL,
  report_globalandor varchar(10) default NULL,
  PRIMARY KEY (`report_id`)
) TYPE=MyISAM;

CREATE TABLE formulize_id (
  id_form smallint(5) NOT NULL auto_increment,
  desc_form varchar(255) NOT NULL default '',
  singleentry varchar(5) default NULL,
  headerlist text default NULL,
  tableform varchar(255) default NULL,
  lockedform tinyint(1) NULL default NULL,
  PRIMARY KEY  (`id_form`),
  UNIQUE `desc_form_id` (`desc_form`)
) TYPE=MyISAM;

CREATE TABLE formulize (
  id_form int(5) NOT NULL default '0',
  ele_id smallint(5) unsigned NOT NULL auto_increment,
  ele_type varchar(10) NOT NULL default '',
  ele_caption text NOT NULL default '',
  ele_desc text NULL,
  ele_colhead varchar(255) NULL default '',
  ele_handle varchar(255) NOT NULL default '',
  ele_order smallint(2) NOT NULL default '0',
  ele_req tinyint(1) NOT NULL default '1',
  ele_encrypt tinyint(1) NOT NULL default '0',
  ele_value text NOT NULL,
  ele_uitext text NOT NULL,
  ele_delim varchar(255) NOT NULL default '',
  ele_display varchar(255) NOT NULL default '1',
  ele_disabled varchar(255) NOT NULL default '0',
  ele_forcehidden tinyint(1) NOT NULL default '0',
  ele_private tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ele_id`),
  KEY `ele_display` (`ele_display`),
  KEY `ele_order` (`ele_order`)
) TYPE=MyISAM;

CREATE TABLE formulize_menu (
  menuid int(4) unsigned NOT NULL auto_increment,
  position int(4) unsigned NOT NULL,
  indent int(2) unsigned NOT NULL default '0',
  itemname varchar(255) NOT NULL default '',
  margintop varchar(12) NOT NULL default '0px',
  marginbottom varchar(12) NOT NULL default '0px',
  itemurl varchar(255) NOT NULL default '',
  bold tinyint(1) NOT NULL default '0',
  mainmenu tinyint(1) NOT NULL default '0',
  membersonly tinyint(1) NOT NULL default '1',
  status tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (menuid),
  KEY idxmymenustatus (status)
) TYPE=MyISAM;

CREATE TABLE formulize_entry_owner_groups (
  owner_id int(5) unsigned NOT NULL auto_increment,
  fid int(5) NOT NULL default '0',
  entry_id int(7) NOT NULL default '0',
  groupid int(5) NOT NULL default '0',
  PRIMARY KEY (`owner_id`),
  INDEX i_fid (fid),
  INDEX i_entry_id (entry_id),
  INDEX i_groupid (groupid)
) TYPE=MyISAM;