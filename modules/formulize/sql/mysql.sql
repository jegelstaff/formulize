CREATE TABLE `formulize_screen_calendar` (
  `calendar_id` int(11) unsigned NOT NULL auto_increment,
  `sid` int(11) DEFAULT NULL,
  `caltype` varchar(50) DEFAULT NULL,
  `datasets` text DEFAULT NULL,
  PRIMARY KEY (`calendar_id`),
  INDEX i_sid (`sid`)
) ENGINE=InnoDB;

CREATE TABLE `formulize_digest_data` (
  `digest_id` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) DEFAULT NULL,
  `fid` int(11) DEFAULT NULL,
  `event` varchar(50) DEFAULT NULL,
  `extra_tags` text DEFAULT NULL,
  `mailSubject` text DEFAULT NULL,
  `mailTemplate` text DEFAULT NULL,
  PRIMARY KEY (`digest_id`),
  INDEX i_email (`email`),
  INDEX i_fid (`fid`)
) ENGINE=InnoDB;

CREATE TABLE `formulize_passcodes` (
    `passcode_id` int(11) unsigned NOT NULL auto_increment,
    `passcode` text default null,
    `screen` int(11) NOT NULL default '0',
    `expiry` date default NULL,
    `notes` text default NULL,
    PRIMARY KEY (`passcode_id`),
    INDEX i_passcode (passcode(50)),
    INDEX i_screen (screen),
    INDEX i_expiry (expiry)
) ENGINE=MyISAM;
CREATE TABLE `formulize_apikeys` (
    `key_id` int(11) unsigned NOT NULL auto_increment,
    `uid` int(11) NOT NULL default '0',
    `apikey` varchar(255) NOT NULL default '',
    `expiry` datetime default NULL,
    PRIMARY KEY (`key_id`),
    INDEX i_uid (uid),
    INDEX i_apikey (apikey),
    INDEX i_expiry (expiry)
) ENGINE=MyISAM;

CREATE TABLE `formulize_tokens` (
    `key_id` int(11) unsigned NOT NULL auto_increment,
    `groups` varchar(255) NOT NULL default '',
    `tokenkey` varchar(255) NOT NULL default '',
    `expiry` datetime default NULL,
    `maxuses` int(11) NOT NULL default '0',
    `currentuses` int(11) NOT NULL default '0',
    PRIMARY KEY (`key_id`),
    INDEX i_groups (`groups`),
    INDEX i_tokenkey (tokenkey),
    INDEX i_expiry (expiry),
    INDEX i_maxuses (maxuses),
    INDEX i_currentuses (currentuses)
) ENGINE=MyISAM;

CREATE TABLE `formulize_menu_links` (
    `menu_id` int(11) unsigned NOT NULL auto_increment,
    `appid` int(11) unsigned NOT NULL,
    `screen` varchar(11),
    `rank` int(11),
    `url` varchar(255),
    `link_text` varchar(255),
    `note` text,
    PRIMARY KEY (`menu_id`),
    INDEX i_menus_appid (appid)
) ENGINE=MyISAM;
    
CREATE TABLE `formulize_menu_permissions` (
    `permission_id` int(11) unsigned NOT NULL auto_increment,
    `menu_id` int(11) unsigned NOT NULL,
    `group_id` int(11) unsigned NOT NULL,
    `default_screen` tinyint(1) NOT NULL default '0',
    PRIMARY KEY (`permission_id`),
    INDEX i_menu_permissions (menu_id)
) ENGINE=MyISAM;

CREATE TABLE `formulize_resource_mapping` (
    mapping_id int(11) NOT NULL auto_increment,
    internal_id int(11) NOT NULL,
    external_id int(11) NULL default NULL,
    resource_type int(4) NOT NULL,
    mapping_active tinyint(1) NOT NULL,
    external_id_string text NULL default NULL,
    PRIMARY KEY (mapping_id),
    INDEX i_internal_id (internal_id),
    INDEX i_external_id (external_id),
    INDEX i_resource_type (resource_type),
    INDEX i_external_id_string (external_id_string(10))
) ENGINE=MyISAM;

CREATE TABLE `formulize_advanced_calculations` (
  `acid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `input` text NOT NULL,
  `output` text NOT NULL,
  `steps` text NOT NULL,
  `steptitles` text NOT NULL,
  `fltr_grps` text NOT NULL,
  `fltr_grptitles` text NOT NULL,
  PRIMARY KEY  (`acid`),
  KEY `i_fid` (`fid`)
) ENGINE=MyISAM; 

CREATE TABLE `formulize_applications` (
  `appid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `custom_code` mediumtext,
  PRIMARY KEY (`appid`)
) ENGINE=MyISAM;

CREATE TABLE `formulize_application_form_link` (
  `linkid` int(11) NOT NULL auto_increment,
  `appid` int(11) NOT NULL default 0,
  `fid` int(11) NOT NULL default 0,
  PRIMARY KEY (`linkid`),
  INDEX i_fid (`fid`),
  INDEX i_appid (`appid`)
) ENGINE=MyISAM;


CREATE TABLE `formulize_group_filters` (
  `filterid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default 0,
  `groupid` int(11) NOT NULL default 0,
  `filter` text NOT NULL,
  PRIMARY KEY (`filterid`),
  INDEX i_fid (`fid`),
  INDEX i_groupid (`groupid`)
) ENGINE=MyISAM;

CREATE TABLE `formulize_groupscope_settings` (
  `groupscope_id` int(11) NOT NULL auto_increment,
  `groupid` int(11) NOT NULL default 0,
  `fid` int(11) NOT NULL default 0,
  `view_groupid` int(11) NOT NULL default 0,
  PRIMARY KEY (`groupscope_id`),
  INDEX i_groupid (`groupid`),
	INDEX i_fid (`fid`),
  INDEX i_view_groupid (`view_groupid`)
) ENGINE=MyISAM;


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
  `defaultview` text NOT NULL,
  `advanceview` text NOT NULL, 
  `usechangecols` varchar(255) NOT NULL default '',
  `usecalcs` varchar(255) NOT NULL default '',
  `useadvcalcs` varchar(255) NOT NULL default '',
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
  `dedisplay` int(1) NOT NULL,
  `desavetext` varchar(255) NOT NULL default '',
  `columnwidth` int(1) NOT NULL,
  `textwidth` int(1) NOT NULL,
  `customactions` text NOT NULL, 
  `toptemplate` text NOT NULL,
  `listtemplate` text NOT NULL,
  `bottomtemplate` text NOT NULL,
  `entriesperpage` int(1) NOT NULL,
  `viewentryscreen` varchar(10) NOT NULL DEFAULT '',
  `fundamental_filters` text NOT NULL,
  PRIMARY KEY (`listofentriesid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;

CREATE TABLE `formulize_screen_multipage` (
  `multipageid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `introtext` text NOT NULL,
  `thankstext` text NOT NULL,
  `toptemplate` text NOT NULL,       
  `elementtemplate` text NOT NULL,   
  `bottomtemplate` text NOT NULL,	
  `donedest` varchar(255) NOT NULL default '',
  `buttontext` varchar(255) NOT NULL default '',
  `finishisdone` tinyint(1) NOT NULL default 0,
  `navstyle` tinyint(1) NOT NULL default 0,
  `pages` text NOT NULL,
  `pagetitles` text NOT NULL,
  `conditions` text NOT NULL,
  `printall` tinyint(1) NOT NULL,
  `paraentryform` int(11) NOT NULL default 0,
  `paraentryrelationship` tinyint(1) NOT NULL default 0,
  `displaycolumns` tinyint(1) NOT NULL default 2,
  `column1width` varchar(255) NULL default NULL,
  `column2width` varchar(255) NULL default NULL,
  PRIMARY KEY (`multipageid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;

CREATE TABLE `formulize_screen_form` (
  `formid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `donedest` varchar(255) NOT NULL default '',
  `savebuttontext` varchar(255) NOT NULL default '',
  `saveandleavebuttontext` varchar(255) NOT NULL default '',
  `printableviewbuttontext` varchar(255) NOT NULL default '',
  `alldonebuttontext` varchar(255) NOT NULL default '',
  `displayheading` tinyint(1) NOT NULL default 0,
  `reloadblank` tinyint(1) NOT NULL default 0,
  `formelements` text,
  `elementdefaults` text NOT NULL,
  `displaycolumns` tinyint(1) NOT NULL default 2,
  `column1width` varchar(255) NULL default NULL,
  `column2width` varchar(255) NULL default NULL,
  PRIMARY KEY (`formid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;

CREATE TABLE `formulize_screen` (
  `sid` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `fid` int(11) NOT NULL default 0,
  `frid` int(11) NOT NULL default 0,
  `type` varchar(100) NOT NULL default '',
  `useToken` tinyint(1) NOT NULL,
  `anonNeedsPasscode` tinyint(1) NOT NULL,
  PRIMARY KEY  (`sid`)
) ENGINE=MyISAM;

CREATE TABLE formulize_valid_imports (
  import_id smallint(5) NOT NULL auto_increment,
  file varchar(255) NOT NULL default '',
  id_reqs text NOT NULL,
  fid int(5),
  PRIMARY KEY (`import_id`)
) ENGINE=MyISAM;

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
  not_cons_elementemail smallint(5) NOT NULL default 0,
  not_cons_con text NOT NULL,
  not_cons_template varchar(255) default '',
  not_cons_subject varchar(255) default '',
  PRIMARY KEY (`not_cons_id`),
  INDEX i_not_cons_fid (not_cons_fid),
  INDEX i_not_cons_uid (not_cons_uid),
  INDEX i_not_cons_groupid (not_cons_groupid),
  INDEX i_not_cons_fidevent (not_cons_fid, not_cons_event(1))
) ENGINE=MyISAM;

CREATE TABLE formulize_other (
  other_id int(5) NOT NULL auto_increment,
  id_req int(5),
  ele_id int(5),
  other_text varchar(255) default NULL,
  PRIMARY KEY (`other_id`),
  INDEX i_ele_id (ele_id),
  INDEX i_id_req (id_req)
) ENGINE=MyISAM;

CREATE TABLE formulize_saved_views (
  sv_id smallint(5) NOT NULL auto_increment,
  sv_name varchar(255) default NULL,
  sv_pubgroups text,
  sv_owner_uid int(5),
  sv_mod_uid int(5),
  sv_formframe varchar(255) default NULL,
  sv_mainform varchar(255) default NULL,
  sv_lockcontrols tinyint(1),
  sv_hidelist tinyint(1),
  sv_hidecalc tinyint(1),
  sv_asearch text,
  sv_sort varchar(255) default NULL,
  sv_order varchar(30) default NULL,
  sv_oldcols text,
  sv_currentview text,
  sv_calc_cols text,
  sv_calc_calcs text,
  sv_calc_blanks text,
  sv_calc_grouping text,
  sv_quicksearches text,
  sv_global_search text,
  sv_pubfilters text,
  PRIMARY KEY (sv_id)
) ENGINE=MyISAM;

CREATE TABLE group_lists (
  gl_id smallint(5) unsigned NOT NULL auto_increment,
  gl_name varchar(255) NOT NULL default '',
  gl_groups text NOT NULL,
  PRIMARY KEY (gl_id),
  UNIQUE gl_name_id (gl_name)
) ENGINE=MyISAM;

CREATE TABLE formulize_frameworks (
  frame_id smallint(5) NOT NULL auto_increment,
  frame_name varchar(255) default NULL,
  PRIMARY KEY (`frame_id`)
) ENGINE=MyISAM;

CREATE TABLE formulize_framework_links (
  fl_id smallint(5) NOT NULL auto_increment,
  fl_frame_id smallint(5),
  fl_form1_id smallint(5),
  fl_form2_id smallint(5),
  fl_key1 smallint(5),
  fl_key2 smallint(5),
  fl_relationship smallint(5),
  fl_unified_display smallint(5),
  fl_unified_delete smallint(5),
  fl_common_value tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`fl_id`)
) ENGINE=MyISAM;

CREATE TABLE formulize_id (
  id_form smallint(5) NOT NULL auto_increment,
  desc_form varchar(255) NOT NULL default '',
  singleentry varchar(5) default NULL,
  headerlist text,
  tableform varchar(255) default NULL,
  lockedform tinyint(1) NULL default NULL,
  defaultform int(11) NOT NULL default 0,
  defaultlist int(11) NOT NULL default 0,
  menutext varchar(255) default NULL,
  form_handle varchar(255) NOT NULL default '',
  store_revisions tinyint(1) NOT NULL default '0',
  on_before_save text,
  on_after_save text,
  custom_edit_check text,
  note text,
  send_digests tinyint(1) NOT NULL default 0,
  PRIMARY KEY  (`id_form`)
) ENGINE=MyISAM;

CREATE TABLE formulize (
  id_form int(5) NOT NULL default '0',
  ele_id smallint(5) unsigned NOT NULL auto_increment,
  ele_type varchar(100) NOT NULL default '',
  ele_caption text NOT NULL,
  ele_desc text NULL,
  ele_colhead varchar(255) NULL default '',
  ele_handle varchar(255) NOT NULL default '',
  ele_order smallint(2) NOT NULL default '0',
  ele_req tinyint(1) NOT NULL default '1',
  ele_encrypt tinyint(1) NOT NULL default '0',
  ele_value text NOT NULL,
  ele_uitext text NOT NULL,
  ele_uitextshow tinyint(1) NOT NULL default 0,
  ele_delim varchar(255) NOT NULL default '',
  ele_display text NOT NULL,
  ele_disabled text NOT NULL,
  ele_filtersettings text NOT NULL,
  ele_forcehidden tinyint(1) NOT NULL default '0',
  ele_private tinyint(1) NOT NULL default '0',
  ele_use_default_when_blank tinyint(1) NOT NULL default '0',
  ele_exportoptions text NOT NULL,
  PRIMARY KEY  (`ele_id`),
  KEY `ele_display` (`ele_display` ( 255 ) ),
  KEY `ele_order` (`ele_order`)
) ENGINE=MyISAM;

CREATE TABLE formulize_entry_owner_groups (
  owner_id int(5) unsigned NOT NULL auto_increment,
  fid int(5) NOT NULL default '0',
  entry_id int(7) NOT NULL default '0',
  groupid int(5) NOT NULL default '0',
  PRIMARY KEY (`owner_id`),
  INDEX i_fid (fid),
  INDEX i_entry_id (entry_id),
  INDEX i_groupid (groupid)
) ENGINE=MyISAM;

CREATE TABLE `formulize_procedure_logs` (
  `proc_log_id` int(11) unsigned NOT NULL auto_increment,
  `proc_id` int(11) NOT NULL,
  `proc_datetime` datetime NOT NULL,
  `proc_uid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`proc_log_id`),
  INDEX i_proc_id (proc_id),
  INDEX i_proc_uid (proc_uid)
) ENGINE=MyISAM;

CREATE TABLE `formulize_procedure_logs_params` (
  `proc_log_param_id` int(11) unsigned NOT NULL auto_increment,
  `proc_log_id` int(11) unsigned NOT NULL,
  `proc_log_param` varchar(255),
  `proc_log_value` varchar(255),
  PRIMARY KEY (`proc_log_param_id`),
  INDEX i_proc_log_id (proc_log_id)
) ENGINE=MyISAM;

CREATE TABLE formulize_deletion_logs (
  del_log_id int(11) unsigned NOT NULL auto_increment,
  form_id int(11) NOT NULL,
  entry_id int(7) NOT NULL,
  user_id mediumint(8) NOT NULL,
  context text,
  deletion_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (del_log_id),
  INDEX i_del_id (del_log_id)
) ENGINE=MyISAM;

CREATE TABLE formulize_screen_template (
  templateid int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  custom_code text NOT NULL,
  donedest varchar(255) NOT NULL default '',
  savebuttontext varchar(255) NOT NULL default '',
  donebuttontext varchar(255) NOT NULL default '',
  template text NOT NULL,
  PRIMARY KEY (`templateid`),
  INDEX i_sid (`sid`)
) ENGINE=MyISAM;

