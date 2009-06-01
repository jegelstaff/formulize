# --------------------------------------------------------
#
# Schema for pageworks
#
# --------------------------------------------------------


# Table pages
CREATE TABLE pageworks_pages (
  page_id smallint(5) NOT NULL auto_increment,
  page_name varchar(255),
  page_title varchar(255),
  page_template text NOT NULL,
  page_searchable smallint(5) default '0',
  page_html_from_db smallint(5) default '0',
  PRIMARY KEY (`page_id`)
) TYPE=MyISAM;


# Table  frameworks
CREATE TABLE pageworks_frameworks (
  pf_id smallint(5) NOT NULL auto_increment,
  pf_page_id smallint(5),
  pf_framework smallint(5),
  pf_mainform smallint(5),
  pf_filters text NOT NULL,
  pf_sort smallint(5),
  pf_output_name varchar(255),
  pf_search_title smallint(5),
  pf_sortable smallint(5) default '0',
  pf_sortdir smallint(5) default '0',
  PRIMARY KEY (`pf_id`)
) TYPE=MyISAM;

CREATE TABLE pageworks_log (
  log_id int(5) NOT NULL auto_increment,
  log_item varchar(255),
  log_uid int(5),
  log_date Date NOT NULL,
  log_time Time NOT NULL,
  PRIMARY KEY (`log_id`),
  INDEX i_log_item (log_item(255)),
  INDEX i_log_date (log_date),
  INDEX i_log_uid (log_uid)
) TYPE=MyISAM;
