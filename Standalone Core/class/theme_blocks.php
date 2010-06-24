<?php
/**
 * xos_logos_PageBuilder component class file
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license     http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package     core
 * @subpackage	template
 * 
 * @since       XOOPS
 * @version		$Id: theme_blocks.php 8565 2009-04-11 12:44:10Z icmsunderdog $
 * 
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */

/**
 * This file cannot be requested directly
 */
if ( !defined ( 'ICMS_ROOT_PATH' )) exit ();

include_once ICMS_ROOT_PATH . '/class/template.php';

/**
 * xos_logos_PageBuilder main class
 *
 * @package     core
 * @subpackage  template
 * @author      Skalpa Keo <skalpa@xoops.org>
 */
class xos_logos_PageBuilder {

	public $theme = false;

	public $blocks = array ( );

	public function xoInit($options = array()) {
		$this->retrieveBlocks ();
		if ($this->theme) {
			$this->theme->template->assign_by_ref ( 'xoBlocks', $this->blocks );
		}
		return true;
	}

	/**
	 * Called before a specific zone is rendered
	 * 
	 * @param string $zone
	 */
	public function preRender($zone = '') { /* Empty! */ }
	
	/**
	 * Called after a specific zone is rendered
	 *
	 * @param string $zone
	 */
	public function postRender($zone = '') { /* Empty! */ }
	
	/**
	 * Retrieve Blocks
	 *
	 */
	public function retrieveBlocks() {
		global $xoops, $icmsUser, $icmsModule, $icmsConfig;

		$groups = @is_object ( $icmsUser ) ? $icmsUser->getGroups () : array (XOOPS_GROUP_ANONYMOUS );
		
		//Getting the start module and page configured in the admin panel
		if (is_array ( $icmsConfig ['startpage'] )) {
			$member_handler = & xoops_gethandler ( 'member' );
			$group = $member_handler->getUserBestGroup ( (@is_object ( $icmsUser ) ? $icmsUser->uid () : 0) );
			$icmsConfig ['startpage'] = $icmsConfig ['startpage'] [$group];
		}
		
		$startMod = ( $icmsConfig['startpage'] == '--' ) ? 'system' : $icmsConfig ['startpage'];		

		//Setting the full and relative url of the actual page
		$fullurl = urldecode ( "http://" . $_SERVER ["SERVER_NAME"] . $_SERVER ["REQUEST_URI"] );
		$url = urldecode ( substr ( str_replace ( ICMS_URL, '', $fullurl ), 1 ) );

		$icms_page_handler =& xoops_gethandler ( 'page' );
		$criteria = new CriteriaCompo( new Criteria( 'page_url', $fullurl ) );
		if (! empty ( $url ))
			$criteria->add( new Criteria( 'page_url', $url ), 'OR' );
		$pages = $icms_page_handler->getCount ( $criteria );

		if ($pages > 0) { //We have a sym-link defined for this page
			$pages = $icms_page_handler->getObjects( $criteria );
			$page = $pages [0];
			$purl = $page->getVar ( 'page_url' );
			$mid = $page->getVar ( 'page_moduleid' );
			$pid = $page->getVar ( 'page_id' );
			$module_handler = & xoops_gethandler ( 'module' );
			$module =& $module_handler->get ( $mid );
			$dirname = $module->getVar ( 'dirname' );
			$isStart = ($startMod == $mid.'-'.$pid);
		} else { //Don't have a sym-link for this page
			if (@is_object ( $icmsModule )) {
				list ( $mid, $dirname ) = array ($icmsModule->getVar ( 'mid' ), $icmsModule->getVar ( 'dirname' ) );
				$isStart = (substr ( $_SERVER ['PHP_SELF'], - 9 ) == 'index.php' && $startMod == $dirname);
			} else {
				list ( $mid, $dirname ) = array (1, 'system' );
				$isStart = ! @empty ( $GLOBALS ['xoopsOption'] ['show_cblock'] );
			}
			$pid = 0;
		}

		if ($isStart) {
			$modid = '0-1';
		} else {
			$criteria = new CriteriaCompo ( new Criteria ( 'page_status', 1 ) );
			$pages = $icms_page_handler->getObjects ( $criteria );
			$pid = 0;
			foreach ( $pages as $page ) {
				$purl = $page->getVar ( 'page_url' );
				if (substr ( $purl, - 1 ) == '*') {
					$purl = substr ( $purl, 0, - 1 );
					if (substr ( $url, 0, strlen ( $purl ) ) == $purl || substr ( $fullurl, 0, strlen ( $purl ) ) == $purl) {
						$pid = $page->getVar ( 'page_id' );
						break;
					}
				} else {
					if ($purl == $url || $purl == $fullurl) {
						$pid = $page->getVar ( 'page_id' );
						break;
					}
				}
			}
			$modid = $mid . '-' . $pid;
		}

		$icms_block_handler = xoops_gethandler('block');
		$oldzones = $icms_block_handler->getBlockPositions();

		foreach ( $oldzones as $zone ) {
			$this->blocks [$zone] = array ( );
		}
		if ( $this->theme ) {
			$template = & $this->theme->template;
			$backup = array ($template->caching, $template->cache_lifetime );
		} else {
			$template = new XoopsTpl();
		}
		$block_arr = $icms_block_handler->getAllByGroupModule ( $groups, $modid, $isStart, XOOPS_BLOCK_VISIBLE );
		foreach ( $block_arr as $block ) {
			$side = $oldzones[$block->getVar ( 'side', 'n' )];
			if ($var = $this->buildBlock ( $block, $template )) {
				$this->blocks [$side] [$var ["id"]] = $var;
			}
		}
		if ( $this->theme ) {
			list ( $template->caching, $template->cache_lifetime ) = $backup;
		}
	}

	public function generateCacheId($cache_id) {
		if ($this->theme) {
			$cache_id = $this->theme->generateCacheId ( $cache_id );
		}
		return $cache_id;
	}
	
	/**
	 * The lame type workaround will change
	 * bid is added temporarily as workaround for specific block manipulation
	 *
	 * @param unknown_type $xobject
	 * @param unknown_type $template
	 * @return unknown
	 */
	public function buildBlock($xobject, &$template) {
		global $icmsUser, $icmsConfigPersona;
		$gperm =& xoops_gethandler ( 'groupperm' );
		$ugroups = @is_object ( $icmsUser ) ? $icmsUser->getGroups () : array(XOOPS_GROUP_ANONYMOUS );
		$agroups = $gperm->getGroupIds('system_admin',5); //XOOPS_SYSTEM_BLOCK constant not available?
		$uagroups = array_intersect($ugroups, $agroups);
		if ($icmsConfigPersona ['editre_block'] == true) {
			if ($icmsUser && count($uagroups) > 0) {
				$url = base64_encode( str_replace( ICMS_URL, '', "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] ) );
				$titlebtns = '<a href="#" onclick="$(\'#ed_block_' . $xobject->getVar ( 'bid' ) . '\').dialog(\'open\'); return false;"><img src="' . ICMS_IMAGES_SET_URL . '/actions/configure.png" title="' . _EDIT . '" alt="' . _EDIT . '"  /></a>';
				$titlebtns .= '<button style="display: none;"><div id="ed_block_' . $xobject->getVar ( 'bid' ) . '">';
				$titlebtns .= "<a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=visible&amp;bid=" . $xobject->getVar ( 'bid' ) . "&amp;rtn=$url'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/button_cancel.png' alt='" . _INVISIBLE . "'  /> " . _INVISIBLE . "</a><br />";
				$titlebtns .= "<a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=clone&amp;bid=" . $xobject->getVar ( 'bid' ) . "'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/editcopy.png' alt='" . _CLONE . "'  /> " . _CLONE . "</a><br />";
				$titlebtns .= "<a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=mod&amp;bid=" . $xobject->getVar ( 'bid' ) . "'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/edit.png' alt='" . _EDIT . "'  /> " . _EDIT . "</a><br />";
				$titlebtns .= "<a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=up&amp;bid=" . $xobject->getVar ( 'bid' ) . "&amp;rtn=$url'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/up.png' alt='" . _UP . "'  /> " . _UP . "</a><br />";
				$titlebtns .= "<a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=down&amp;bid=" . $xobject->getVar ( 'bid' ) . "&amp;rtn=$url'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/down.png' alt='" . _DOWN . "'  /> " . _DOWN . "</a>";
				if ($xobject->getVar ( 'dirname' ) == '') {
					$titlebtns .= "<br /><a href='" . ICMS_URL . "/modules/system/admin.php?fct=blocksadmin&amp;op=del&amp;bid=" . $xobject->getVar ( 'bid' ) . "'> <img src='" . ICMS_IMAGES_SET_URL . "/actions/editdelete.png' alt='" . _DELETE . "'  /> " . _DELETE . "</a>";
				}
				$titlebtns .= '</div></button>';
				$titlebtns .= '<script type="text/javascript">
					$(function() {
						$(\'#ed_block_' . $xobject->getVar ( 'bid' ) . '\').dialog({
							bgiframe: true,
							//height: 140,
							autoOpen: false,
							modal: true
						});
					});
					</script>
				';
			} else {
				$titlebtns = '';
			}
		} else {
			$titlebtns = '';
		}

		$block = array (
		    'id' => $xobject->getVar ( 'bid' ),
		    'module' => $xobject->getVar ( 'dirname' ),
		    'title' => $xobject->getVar ( 'title' ) . $titlebtns,
		    //'name' => strtolower( preg_replace( '/[^0-9a-zA-Z_]/', '', str_replace( ' ', '_', $xobject->getVar( 'name' ) ) ) ),
		    'weight' => $xobject->getVar ( 'weight' ),
		    'lastmod' => $xobject->getVar ( 'last_modified' )
		);

		$xoopsLogger = & XoopsLogger::instance ();

		$bcachetime = intval ( $xobject->getVar ( 'bcachetime' ) );
		//$template =& new XoopsTpl();
		if (empty ( $bcachetime )) {
			$template->caching = 0;
		} else {
			$template->caching = 2;
			$template->cache_lifetime = $bcachetime;
		}
		$tplName = ($tplName = $xobject->getVar ( 'template' )) ? "db:$tplName" : "db:system_block_dummy.html";
		$cacheid = $this->generateCacheId ( 'blk_' . $xobject->getVar ( 'dirname', 'n' ) . '_' . $xobject->getVar ( 'bid' )/*, $xobject->getVar( 'show_func', 'n' )*/ );

		if (! $bcachetime || ! $template->is_cached ( $tplName, $cacheid )) {
			$xoopsLogger->addBlock ( $xobject->getVar ( 'name' ) );
			if (! ($bresult = $xobject->buildBlock ())) {
				return false;
			}
			$template->assign ( 'block', $bresult );
			$block ['content'] = $template->fetch ( $tplName, $cacheid );
		} else {
			$xoopsLogger->addBlock ( $xobject->getVar ( 'name' ), true, $bcachetime );
			$block ['content'] = $template->fetch ( $tplName, $cacheid );
		}
		return $block;
	}

}

?>