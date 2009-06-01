# --------------------------------------------------------
#
# Schema for Registration Codes
#
# --------------------------------------------------------
# Patch 2.3: 
#   Added reg_codes_confirm_user to record user details
#	for subsequent admin authorisation email. 
#   nmc 2007.03.22
# --------------------------------------------------------


# Table reg_codes
CREATE TABLE reg_codes (
	reg_codes_key int not null auto_increment,
	reg_codes_code varchar(100),
	reg_codes_groups text,
	reg_codes_owner smallint,
	reg_codes_expiry date,
	reg_codes_maxuses smallint,
	reg_codes_curuses smallint,
	reg_codes_instant tinyint(1),
	reg_codes_redirect varchar(255),
	reg_codes_approval varchar(255),
# keys
	primary key (reg_codes_key)
) TYPE=MyISAM;

# Table reg_codes_confirm_user
CREATE TABLE reg_codes_confirm_user (
	reg_codes_conf_id smallint,
	reg_codes_conf_actkey varchar(100),
	reg_codes_conf_name varchar(255),
	reg_codes_conf_email varchar(255),
	reg_codes_conf_reg_code varchar(255),
# keys
	primary key (reg_codes_conf_id)
) TYPE=MyISAM;

# Table reg_codes_pre_approved_users
CREATE TABLE reg_codes_preapproved_users (
	reg_codes_preapproved_id int not null auto_increment,
	reg_codes_key int not null,
	reg_codes_preapproved varchar(255),
# keys
	primary key (reg_codes_preapproved_id),
	UNIQUE KEY `preapproved_key` (`reg_codes_key`,`reg_codes_preapproved`)	
) TYPE=MyISAM;


