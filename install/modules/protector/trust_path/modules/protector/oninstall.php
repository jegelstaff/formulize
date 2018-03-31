<?php

eval( ' function xoops_module_install_'.$mydirname.'( $module ) { return protector_oninstall_base( $module , "'.$mydirname.'" ) ; } ' ) ;


if( ! function_exists( 'protector_oninstall_base' ) ) {

function protector_oninstall_base( $module , $mydirname )
{
	// transations on module install

	global $ret ; // TODO :-D

	// for Cube 2.1
	if( defined( 'XOOPS_CUBE_LEGACY' ) ) {
		$root =& XCube_Root::getSingleton();
		$root->mDelegateManager->add( 'Legacy.Admin.Event.ModuleInstall.' . ucfirst($mydirname) . '.Success' , 'protector_message_append_oninstall' ) ;
		$ret = array() ;
	} else {
		if( ! is_array( $ret ) ) $ret = array() ;
	}

	$db =& icms_db_Factory::instance() ;
	$mid = $module->getVar('mid') ;

	// TABLES (loading mysql.sql)
	$sql_file_path = dirname(__FILE__).'/sql/mysql.sql' ;
	$prefix_mod = $db->prefix() . '_' . $mydirname ;
	if( file_exists( $sql_file_path ) ) {
		$ret[] = "SQL file found at <b>".htmlspecialchars($sql_file_path)."</b>.<br /> Creating tables...";

		$sqlutil = new icms_db_legacy_mysql_Utility();
		$sql_query = trim( file_get_contents( $sql_file_path ) ) ;
		$sqlutil->splitMySqlFile( $pieces , $sql_query ) ;
		$created_tables = array() ;
		foreach( $pieces as $piece ) {
			$prefixed_query = $sqlutil->prefixQuery( $piece , $prefix_mod ) ;
			if( ! $prefixed_query ) {
				$ret[] = "Invalid SQL <b>".htmlspecialchars($piece)."</b><br />";
				return false ;
			}
			if( ! $db->query( $prefixed_query[0] ) ) {
				$ret[] = '<b>'.htmlspecialchars( $db->error() ).'</b><br />' ;
				//var_dump( $db->error() ) ;
				return false ;
			} else {
				if( ! in_array( $prefixed_query[4] , $created_tables ) ) {
					$ret[] = 'Table <b>'.htmlspecialchars($prefix_mod.'_'.$prefixed_query[4]).'</b> created.<br />';
					$created_tables[] = $prefixed_query[4];
				} else {
					$ret[] = 'Data inserted to table <b>'.htmlspecialchars($prefix_mod.'_'.$prefixed_query[4]).'</b>.</br />';
				}
			}
		}
	}

	// TEMPLATES
	$tplfile_handler = new icms_view_template_file_Handler(icms::$xoopsDB);
	$tpl_path = dirname(__FILE__).'/templates' ;
	if (is_dir(($tpl_path . '/')) and $handler = @opendir($tpl_path . '/')) {
		while( ( $file = readdir( $handler ) ) !== false ) {
			if( substr( $file , 0 , 1 ) == '.' ) continue ;
			$file_path = $tpl_path . '/' . $file ;
			if( is_file( $file_path ) && in_array( strrchr( $file , '.' ) , array( '.html' , '.css' , '.js' ) ) ) {
				$mtime = intval( @filemtime( $file_path ) ) ;
				$tplfile =& $tplfile_handler->create() ;
				$tplfile->setVar( 'tpl_source' , file_get_contents( $file_path ) , true ) ;
				$tplfile->setVar( 'tpl_refid' , $mid ) ;
				$tplfile->setVar( 'tpl_tplset' , 'default' ) ;
				$tplfile->setVar( 'tpl_file' , $mydirname . '_' . $file ) ;
				$tplfile->setVar( 'tpl_desc' , '' , true ) ;
				$tplfile->setVar( 'tpl_module' , $mydirname ) ;
				$tplfile->setVar( 'tpl_lastmodified' , $mtime ) ;
				$tplfile->setVar( 'tpl_lastimported' , 0 ) ;
				$tplfile->setVar( 'tpl_type' , 'module' ) ;
				if( ! $tplfile_handler->insert( $tplfile ) ) {
					$ret[] = '<span style="color:#ff0000;">ERROR: Could not insert template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b> to the database.</span><br />';
				} else {
					$tplid = $tplfile->getVar( 'tpl_id' ) ;
					$ret[] = 'Template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b> added to the database. (ID: <b>'.$tplid.'</b>)<br />';
					// generate compiled file
					if( !icms_view_Tpl::template_touch( $tplid ) ) {
						$ret[] = '<span style="color:#ff0000;">ERROR: Failed compiling template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b>.</span><br />';
					} else {
						$ret[] = 'Template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b> compiled.</span><br />';
					}
				}
			}
		}
		closedir( $handler ) ;
	}

	/*
	Fixes Bug #619 : parse Error
	*/
	if((defined(ICMS_PRELOAD_PATH) && !file_exists(ICMS_PRELOAD_PATH.'/protector.php')) && (! defined( 'PROTECTOR_POSTCHECK_INCLUDED' )||! defined( 'PROTECTOR_PRECHECK_INCLUDED' )) && function_exists('icms_copyr')){
		icms_core_Filesystem::copyRecursive(ICMS_TRUST_PATH.'/modules/protector/patches/ImpressCMS1.1/preload_protector.php',ICMS_PRELOAD_PATH.'/protector.php');
	}

	icms_view_Tpl::template_clear_module_cache( $mid ) ;

	return true ;
}

function protector_message_append_oninstall( &$module_obj , &$log )
{
	if( is_array( @$GLOBALS['ret'] ) ) {
		foreach( $GLOBALS['ret'] as $message ) {
			$log->add( strip_tags( $message ) ) ;
		}
	}

	// use mLog->addWarning() or mLog->addError() if necessary
}

}

?>