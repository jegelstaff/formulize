<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     resource.db.php
 * Type:     resource
 * Name:     db
 * Purpose:  Fetches templates from a database
 * -------------------------------------------------------------
 */
function smarty_resource_db_source($tpl_name, &$tpl_source, &$smarty) {
	if ( !$tpl = smarty_resource_db_tplinfo( $tpl_name ) ) {
		return false;
	}
	if ( is_object( $tpl ) ) {
		$tpl_source = $tpl->getVar( 'tpl_source', 'n' );
	} else {
		$fp = fopen( $tpl, 'r' );
		$tpl_source = fread( $fp, filesize( $tpl ) );
		fclose( $fp );
	}
	return true;
}

function smarty_resource_db_timestamp($tpl_name, &$tpl_timestamp, &$smarty) {
	if ( !$tpl = smarty_resource_db_tplinfo( $tpl_name ) ) {
		return false;
	}
	if ( is_object( $tpl ) ) {
		$tpl_timestamp = $tpl->getVar( 'tpl_lastmodified', 'n' );
	} else {
		$tpl_timestamp = filemtime( $tpl );
	}
	return true;
}

function smarty_resource_db_secure($tpl_name, &$smarty)
{
    // assume all templates are secure
    return true;
}

function smarty_resource_db_trusted($tpl_name, &$smarty)
{
    // not used for templates
}

function smarty_resource_db_tplinfo( $tpl_name ) {
	global $icmsConfig;

	static $cache = array();

	if ( isset( $cache[$tpl_name] ) ) {
		return $cache[$tpl_name];
	}
	$tplset = $icmsConfig['template_set'];
	$theme = isset( $icmsConfig['theme_set'] ) ? $icmsConfig['theme_set'] : 'default';

	$tplfile_handler = icms::handler('icms_view_template_file');
	// If we're not using the "default" template set, then get the templates from the DB
	if ( $tplset != "default" ) {
		$tplobj = $tplfile_handler->getPrefetchedBlock($tplset, $tpl_name);
		if ( count( $tplobj ) ) {
			return $cache[$tpl_name] = $tplobj[0];
		}
	}
	// If we'using the default tplset, get the template from the filesystem
	$tplobj = $tplfile_handler->getPrefetchedBlock("default", $tpl_name);

	if ( !count( $tplobj ) ) {
		return $cache[$tpl_name] = false;
	}
	$tplobj = $tplobj[0];
	$module = $tplobj->getVar( 'tpl_module', 'n' );
	$type = $tplobj->getVar( 'tpl_type', 'n' );
	$blockpath = ( $type == 'block' ) ? 'blocks/' : '';
	// First, check for an overloaded version within the theme folder
	$filepath = ICMS_THEME_PATH . "/$theme/modules/$module/$blockpath$tpl_name";
	if ( !file_exists( $filepath ) ) {
		// If no custom version exists, get the tpl from its default location
		$filepath = ICMS_ROOT_PATH . "/modules/$module/templates/$blockpath$tpl_name";
		if ( !file_exists( $filepath ) ) {
			return $cache[$tpl_name] = $tplobj ;
		}
	}
	return $cache[$tpl_name] = $filepath;
}