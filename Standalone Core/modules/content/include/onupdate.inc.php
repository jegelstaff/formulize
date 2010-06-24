<?php
/**
 * File containing onUpdate and onInstall functions for the module
 *
 * This file is included by the core in order to trigger onInstall or onUpdate functions when needed.
 * Of course, onUpdate function will be triggered when the module is updated, and onInstall when
 * the module is originally installed. The name of this file needs to be defined in the
 * icms_version.php
 *
 * <code>
 * $modversion['onInstall'] = "include/onupdate.inc.php";
 * $modversion['onUpdate'] = "include/onupdate.inc.php";
 * </code>
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id$
 */

if (! defined ( "ICMS_ROOT_PATH" ))
	die ( "ICMS root path not defined" );
	
// this needs to be the latest db version
define ( 'CONTENT_DB_VERSION', 1 );

/**
 * it is possible to define custom functions which will be call when the module is updating at the
 * correct time in update incrementation. Simpy define a function named <direname_db_upgrade_db_version>
 */
/*function content_db_upgrade_1() {
}
function content_db_upgrade_2() {
}*/

function icms_module_update_content($module) {
	$table = new IcmsDatabasetable ( 'icmscontent' );
	$db = $GLOBALS ['xoopsDB'];
	
	$content_handler = xoops_getmodulehandler ( 'content', 'content' );
	$gperm_handler = & xoops_gethandler ( 'groupperm' );
	
	if ($table->exists ()) {
		
		echo '<code><b>Importing data from the core content manager</b></code><br />';
		
		$sql = "SELECT * FROM " . $db->prefix ( 'icmscontent' );
		$result = $db->query ( $sql );
		echo '<code>';
		while ( $row = $db->fetchArray ( $result ) ) {
			$obj = $content_handler->create ( true );
			$obj->setVar ( 'content_pid', $row ['content_supid'] );
			$obj->setVar ( 'content_uid', $row ['content_uid'] );
			$obj->setVar ( 'content_title', $row ['content_title'] );
			$obj->setVar ( 'content_body', $row ['content_body'] );
			$obj->setVar ( 'content_css', $row ['content_css'] );
			$obj->setVar ( 'content_tags', $row ['content_tags'] );
			$obj->setVar ( 'content_visibility', $row ['content_visibility'] );
			$obj->setVar ( 'content_published_date', ( int ) $row ['content_created'] );
			$obj->setVar ( 'content_updated_date', time () );
			$obj->setVar ( 'content_weight', $row ['content_weight'] );
			$obj->setVar ( 'short_url', $row ['content_menu'] );
			$obj->setVar ( 'counter', $row ['content_reads'] );
			$obj->setVar ( 'content_status', $row ['content_status'] );
			
			$obj->setVar ( 'content_makesymlink', 1 );
			$obj->setVar ( 'content_showsubs', 1 );
			$obj->setVar ( 'content_cancomment', 1 );
			$obj->setVar ( 'dohtml', 1 );
			$obj->setVar ( 'dobr', 1 );
			$obj->setVar ( 'doimage', 1 );
			$obj->setVar ( 'dosmiley', 1 );
			$obj->setVar ( 'doxcode', 1 );
			
			$content_handler->insert ( $obj, true );
			
			/**
			 * Importing the permissions from the old page to add to the new one and deleting the old permissions
			 */
			//Getting the groups that have read permission to this content page
			$groups = $gperm_handler->getGroupIds ( 'content_read', $row ['content_id'] );
			//Deleting the permissions to the old page
			$criteria = new CriteriaCompo ( );
			$crit = new CriteriaCompo ( new Criteria ( 'gperm_name', 'content_read' ) );
			$crit->add ( new Criteria ( 'gperm_name', 'content_admin' ), 'OR' );
			$criteria->add ( $crit );
			$criteria->add ( new Criteria ( 'gperm_itemid', $row ['content_id'] ) );
			$criteria->add ( new Criteria ( 'gperm_modid', 1 ) );
			$gperm_handler->deleteAll ( $criteria );
			//Adding permissions to allow the groups allowed to read the old content page to read the new content page
			foreach ( $groups as $group ) {
				$gperm_handler->addRight ( 'content_read', $obj->getVar ( 'content_id' ), $group, $module->mid () );
			}
			
			/**
			 * Deleting the symlinks from the old page
			 */
			$seo = $obj->handler->makelink ( $obj );
			$url = str_replace ( ICMS_URL . '/', '', $obj->handler->_moduleUrl . $obj->handler->_itemname . '.php?page=' . $seo );
			$old_seo = str_replace ( "-", "_", $seo );
			
			$symlink_handler = xoops_getmodulehandler ( 'pages', 'system' );
			$criteria = new CriteriaCompo ( new Criteria ( 'page_url', '%' . $old_seo, 'LIKE' ) );
			$criteria->add ( new Criteria ( 'page_moduleid', 1 ) );
			$symlinks_remove = $symlink_handler->getObjects ( $criteria );
			
			$criteria = new CriteriaCompo ( new Criteria ( 'page_url', '%' . $url, 'LIKE' ) );
			$criteria->add ( new Criteria ( 'page_moduleid', $module->mid () ) );
			$symlinks_added = $symlink_handler->getObjects ( $criteria );
			foreach ( $symlinks_added as $symlink ) {
				$symlinks_added = $symlink;
				break;
			}
			
			/**
			 * If needed, changing the startpage to use the new symlink created
			 */
			if (count ( $symlinks_remove ) > 0) {
				foreach ( $symlinks_remove as $symlink ) {
					$sql_preferences = 'SELECT * FROM ' . $db->prefix ( 'config' ) . ' WHERE conf_name = "startpage" AND conf_value LIKE \'%"1-' . $symlink->getVar ( 'page_id' ) . '"%\'';
					$result_preferences = $db->query ( $sql_preferences );
					if ($db->getRowsNum ( $result_preferences ) > 0) {
						$row_preferences = $db->fetchArray ( $result_preferences );
						$arr = unserialize ( $row_preferences ['conf_value'] );
						foreach ( $arr as $k => $v ) {
							if ($v == '1-' . $symlink->getVar ( 'page_id' )) {
								$arr [$k] = $module->mid () . '-' . $symlinks_added->getVar ( 'page_id' );
							}
						}
						$value = serialize ( $arr );
						$db->queryF ( 'UPDATE ' . $db->prefix ( 'config' ) . ' SET conf_value = \'' . $value . '\' WHERE conf_name = "startpage"' );
					}
					$symlink_handler->delete ( $symlink, true );
				}
			}
			echo '&nbsp;&nbsp;-- <b>' . $row ['content_title'] . '</b> successfully imported!<br />';
		}
		echo '</code>';
		echo '<code><b>Core Content Manager table successfully dropped.</b></code><br />';
		$table->dropTable ();
		
		/**
		 * Importing the core content manager blocks
		 */
		echo '<code><b>Importing the core content manager blocks and configurations.</b></code><br />';
		$icms_block_handler = xoops_getmodulehandler ( 'blocksadmin' );
		//Content Block
		$criteria = new CriteriaCompo ( new Criteria ( 'show_func', 'content_content_display_show' ) );
		$content_content_block = $icms_block_handler->getObjects ( $criteria, false, true );
		$content_content_block = $content_content_block [0] ? $content_content_block [0] : false;
		
		$criteria = new CriteriaCompo ( new Criteria ( 'show_func', 'b_content_show' ) );
		$content_blocks = $icms_block_handler->getObjects ( $criteria, true, true );
		foreach ( $content_blocks as $block ) {
			$nb = $content_content_block;
			$nb->setVar ( 'visiblein', $block->getVar ( 'visiblein', 'e' ) );
			$nb->setVar ( 'options', $block->getVar ( 'options', 'e' ) );
			$nb->setVar ( 'title', $block->getVar ( 'title', 'e' ) );
			$nb->setVar ( 'side', $block->getVar ( 'side', 'e' ) );
			$nb->setVar ( 'weight', $block->getVar ( 'weight', 'e' ) );
			$nb->setVar ( 'visible', $block->getVar ( 'visible', 'e' ) );
			$nb->setVar ( 'isactive', $block->getVar ( 'isactive', 'e' ) );
			$nb->setVar ( 'last_modified', time () );
			if ($block->getVar ( 'block_type', 'D' )) { //Cloned Version of the Block
				$nb->setVar ( 'block_type', 'K' );
				$nb->setVar ( 'bid', 0 );
				$nb->setNew ();
			}
			if ($icms_block_handler->insert ( $nb, true )) {
				$criteria = new CriteriaCompo ( new Criteria ( 'gperm_name', 'block_read' ) );
				$criteria->add ( new Criteria ( 'gperm_itemid', $block->getVar ( 'bid', 'e' ) ) );
				$criteria->add ( new Criteria ( 'gperm_modid', 1 ) );
				$perms = $gperm_handler->getObjects ( $criteria, true );
				foreach ( $perms as $perm ) {
					$gperm_handler->addRight ( 'block_read', $nb->getVar ( 'bid' ), $perm->getVar ( 'gperm_groupid' ), $module->mid () );
					$gperm_handler->delete ( $perm );
				}
				echo '<code>Block <b>' . $block->getVar ( 'title', 'e' ) . '</b> successfully imported.</code><br />';
			} else {
				echo '<code>Error while importing block <b>' . $block->getVar ( 'title', 'e' ) . '</b>.</code><br />';
			}
			$icms_block_handler->delete ( $block, true );
		}
		
		//Content Menu Block
		$criteria = new CriteriaCompo ( new Criteria ( 'show_func', 'content_content_menu_show' ) );
		$content_menu_block = $icms_block_handler->getObjects ( $criteria, false, true );
		$content_menu_block = $content_menu_block [0] ? $content_menu_block [0] : false;
		
		$criteria = new CriteriaCompo ( new Criteria ( 'show_func', 'b_content_menu_show' ) );
		$criteria->add ( new Criteria ( 'show_func', 'b_content_relmenu_show' ), 'OR' );
		$content_menu_blocks = $icms_block_handler->getObjects ( $criteria, true, true );
		
		foreach ( $content_menu_blocks as $block ) {
			$nb = $content_menu_block;
			$nb->setVar ( 'visiblein', $block->getVar ( 'visiblein', 'e' ) );
			$nb->setVar ( 'options', $block->getVar ( 'options', 'e' ) . '|0' );
			$nb->setVar ( 'title', $block->getVar ( 'title', 'e' ) );
			$nb->setVar ( 'side', $block->getVar ( 'side', 'e' ) );
			$nb->setVar ( 'weight', $block->getVar ( 'weight', 'e' ) );
			$nb->setVar ( 'visible', $block->getVar ( 'visible', 'e' ) );
			$nb->setVar ( 'isactive', $block->getVar ( 'isactive', 'e' ) );
			$nb->setVar ( 'last_modified', time () );
			if ($block->getVar ( 'block_type', 'D' )) { //Cloned Version of the Block
				$nb->setVar ( 'block_type', 'K' );
				$nb->setVar ( 'bid', 0 );
				$nb->setNew ();
			}
			if ($icms_block_handler->insert ( $nb, true )) {
				$criteria = new CriteriaCompo ( new Criteria ( 'gperm_name', 'block_read' ) );
				$criteria->add ( new Criteria ( 'gperm_itemid', $block->getVar ( 'bid', 'e' ) ) );
				$criteria->add ( new Criteria ( 'gperm_modid', 1 ) );
				$perms = $gperm_handler->getObjects ( $criteria, true );
				foreach ( $perms as $perm ) {
					$gperm_handler->addRight ( 'block_read', $nb->getVar ( 'bid' ), $perm->getVar ( 'gperm_groupid' ), $module->mid () );
					$gperm_handler->delete ( $perm );
				}
				echo '<code>Block <b>' . $block->getVar ( 'title', 'e' ) . '</b> successfully imported.</code><br />';
			} else {
				echo '<code>Error while importing block <b>' . $block->getVar ( 'title', 'e' ) . '</b>.</code><br />';
			}
			$icms_block_handler->delete ( $block, true );
		}
		
		if (is_dir ( ICMS_ROOT_PATH . '/modules/system/admin/content' )) {
			icms_unlinkRecursive ( ICMS_ROOT_PATH . '/modules/system/admin/content' );
			echo '<code>Folder removed successfully.</code><br />';
		}
		if (is_file ( ICMS_ROOT_PATH . '/kernel/content.php' )) {
			icms_deleteFile ( ICMS_ROOT_PATH . '/kernel/content.php' );
			echo '<code>File removed successfully.</code><br />';
		}
		/*if (is_file ( ICMS_ROOT_PATH . '/content.php' )) {
			icms_deleteFile ( ICMS_ROOT_PATH . '/content.php' );
			echo '<code>File removed successfully.</code><br />';
		}*/
		if (is_file ( ICMS_ROOT_PATH . '/modules/system/templates/system_content.html' )) {
			icms_deleteFile ( ICMS_ROOT_PATH . '/modules/system/templates/system_content.html' );
			echo '<code>File removed successfully.</code><br />';
		}
		if (is_file ( ICMS_ROOT_PATH . '/modules/system/templates/system_content_list.html' )) {
			icms_deleteFile ( ICMS_ROOT_PATH . '/modules/system/templates/system_content_list.html' );
			echo '<code>File removed successfully.</code><br />';
		}
		icms_clean_folders ( array ('templates_c' => ICMS_ROOT_PATH . "/templates_c/", 'cache' => ICMS_ROOT_PATH . "/cache/" ), true );
		$sql = sprintf ( "DELETE FROM %s WHERE confcat_name= '_MD_AM_CONTMANAGER'", $db->prefix ( 'configcategory' ) );
		if (! $result = $db->queryF ( $sql )) {
			echo 'Error while removing category.';
		}
		$sql = sprintf ( "DELETE FROM %s WHERE (conf_modid = '0' AND conf_catid = '9')", $db->prefix ( 'config' ) );
		if (! $result = $db->queryF ( $sql )) {
			echo 'Error while removing category items.';
		}
		
		echo '<code>Data from Core Content Manager successfully imported.</code><br />';
	}
	unset ( $table );
	
	$feedback = ob_get_clean();
	if (method_exists($module, "setMessage")) {
		$module->messages = $module->setMessage($feedback);
	} else {
		echo $feedback;
	}
	
	return true;
}

function icms_module_install_content($module) {
	
	return true;
}

?>