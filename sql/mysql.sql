CREATE TABLE formulize_saved_views (
  sv_id smallint(5) NOT NULL auto_increment,
  sv_name varchar(255) default NULL,
  sv_pubgroups varchar(255) default NULL,
  sv_owner_uid int(5),
  sv_mod_uid int(5),
  sv_formframe varchar(255) default NULL,
  sv_mainform varchar(255) default NULL,
  sv_lockcontrols tinyint(1),
  sv_hidelist tinyint(1),
  sv_hidecalc tinyint(1),
  sv_asearch varchar(255) default NULL,
  sv_sort varchar(255) default NULL,
  sv_order varchar(30) default NULL,
  sv_oldcols varchar(255) default NULL,
  sv_currentview varchar(255) default NULL,
  sv_calc_cols varchar(255) default NULL,
  sv_calc_calcs varchar(255) default NULL,
  sv_calc_blanks varchar(255) default NULL,
  sv_calc_grouping varchar(255) default NULL,
  sv_quicksearches varchar(255) default NULL,
  PRIMARY KEY (sv_id)
) TYPE=MyISAM;

CREATE TABLE group_lists (
  gl_id smallint(5) unsigned NOT NULL auto_increment,
  gl_name varchar(255) NOT NULL default '',
  gl_groups text default '',
  PRIMARY KEY (gl_id),
  UNIQUE gl_name_id (gl_name)
) TYPE=MyISAM;

CREATE TABLE formulize_onetoone_links (
  link_id smallint(5) NOT NULL auto_increment,
  main_form int(5),
  link_form int(5),
  PRIMARY KEY (`link_id`)
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

CREATE TABLE form_max_entries (
  max_ent_id smallint(5) NOT NULL auto_increment,
  max_ent_id_form smallint(5),
  max_ent_uid int(10),
  max_ent_entcount smallint(5),
  PRIMARY KEY (`max_ent_id`)
) TYPE=MyISAM;

CREATE TABLE form_reports (
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

CREATE TABLE form_chains (
  chain_id smallint(5) NOT NULL auto_increment,
  chain_name varchar(255) default NULL,
  chain_startform smallint(5),
  chain_allforms varchar(255) default NULL,
  PRIMARY KEY (`chain_id`)
) TYPE=MyISAM;

CREATE TABLE form_chains_entries (
  chain_entry_id smallint(5) NOT NULL auto_increment,
  chain_id smallint(5),
  chain_reqs varchar(255) default NULL,
  PRIMARY KEY (`chain_entry_id`)
) TYPE=MyISAM;

CREATE TABLE form_id (
  id_form smallint(5) NOT NULL auto_increment,
  desc_form varchar(255) NOT NULL default '',
  admin varchar(5) default NULL,
  groupe varchar(255) default NULL,
  email varchar(255) default NULL,
  expe varchar(5) default NULL,
  singleentry varchar(5) default NULL,
  groupscope varchar(5) default NULL,
  headerlist text NOT NULL,
  showviewentries varchar(5) default NULL,
  maxentries smallint(5) NOT NULL default '0',
  even varchar(255) default NULL,
  odd varchar(255) default NULL, 
  PRIMARY KEY  (`id_form`),
  UNIQUE `desc_form_id` (`desc_form`)
) TYPE=MyISAM;

CREATE TABLE form (
  id_form int(5) NOT NULL default '0',
  ele_id smallint(5) unsigned NOT NULL auto_increment,
  ele_type varchar(10) NOT NULL default '',
  ele_caption varchar(255) NOT NULL default '',
  ele_order smallint(2) NOT NULL default '0',
  ele_req tinyint(1) NOT NULL default '1',
  ele_value text NOT NULL,
  ele_display tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`ele_id`),
  KEY `ele_display` (`ele_display`),
  KEY `ele_order` (`ele_order`)
) TYPE=MyISAM;

CREATE TABLE form_menu (
  menuid int(4) unsigned NOT NULL auto_increment,
  position int(4) unsigned NOT NULL,
  indent int(2) unsigned NOT NULL default '0',
  itemname varchar(60) NOT NULL default '',
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

CREATE TABLE form_form (
  id_form int(5) NOT NULL default '0',
  id_req smallint(5) ,
  ele_id int(5) unsigned NOT NULL auto_increment,
  ele_type varchar(10) NOT NULL default '',
  ele_caption varchar(255) NOT NULL default '',
  ele_value text NOT NULL,
  date Date NOT NULL default '2004-06-03',
  uid int(10) default '0',
  proxyid int(10) NULL ,
  creation_date Date NOT NULL, 
  PRIMARY KEY  (`ele_id`),
  KEY `ele_id` (`ele_id`),
  INDEX i_id_req (id_req),
  INDEX i_id_form (id_form),
  INDEX i_ele_caption (ele_caption),
  INDEX i_ele_value (ele_value(20)),
  INDEX i_uid (uid)
) TYPE=MyISAM;