<?php
/**
* Installer language selection page
*
* See the enclosed file license.txt for licensing information.
* If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
*
* @copyright    The XOOPS project http://www.xoops.org/
* @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
* @package		installer
* @since        Xoops 2.3.0
* @author		Haruki Setoyama  <haruki@planewave.org>
* @author 		Kazumi Ono <webmaster@myweb.ne.jp>
* @author		Skalpa Keo <skalpa@xoops.org>
* @version		$Id: page_langselect.php 9577 2009-11-19 21:33:56Z mrtheme $
*/
/**
 *
 */ 
require_once 'common.inc.php';
if ( !defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'langselect' );

/*
 * gets list of name of directories inside a directory
 */
function getDirList($dirname) {
    $dirlist = array();
    if ( $handle = opendir($dirname) ) {
        while ( $file = readdir($handle) ) {
        	if ( $file{0} != '.' && is_dir( $dirname . $file ) ) {
        		$dirlist[] = $file;
        	}
        }
        closedir($handle);
        asort($dirlist);
        reset($dirlist);
    }
    return $dirlist;
}


if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	$lang = $_REQUEST['lang'];

	$languages = getDirList( "./language/" );
	if (!in_array($lang, $languages)) {
		$lang = 'english';
	}
	setcookie( 'xo_install_lang', $lang, null, null, null );

	$wizard->redirectToPage( '+1' );
	exit();
}
	$_SESSION = array();
	$pageHasForm = true;
	$pageHasHelp = true;
	$title = LANGUAGE_SELECTION;
	$content = "";

	$languages = getDirList( "./language/" );
	foreach ( $languages as $lang ) {
		$sel = ( $lang == $wizard->language ) ? ' checked="checked"' : '';
		$content .= "<div class=\"langselect\" style=\"text-decoration: none;\"><a href=\"javascript:void(0);\" style=\"text-decoration: none;\"><img src=\"../images/flags/$lang.gif\" alt=\"$lang\" /><br />$lang<br /> <input type=\"checkbox\" name=\"lang\" value=\"$lang\"$sel /></a></div><br /><br />\n";
	}
	$content .= '<fieldset style="text-align: center;">';
	$content .= '<legend>Select an Alternative Language Pack to Download</legend>';
	$content .= '<div class="xoform-help">Languages may or may not be available if this is a development release.</div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=241" target="_blank" style="text-decoration: none;"><img src="../images/flags/portuguesebr.gif" alt="Portuguese/BR Language Pack" /><br />Portug.<br />&lt;BRAZIL&gt;<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=243" target="_blank" style="text-decoration: none;"><img src="../images/flags/nederlands.gif" alt="Dutch Language Pack" /><br />Dutch<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=236" target="_blank" style="text-decoration: none;"><img src="../images/flags/french.gif" alt="French Language Pack" /><br />French<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=239" target="_blank" style="text-decoration: none;"><img src="../images/flags/german.gif" alt="German Language Pack" /><br />German<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=247" target="_blank" style="text-decoration: none;"><img src="../images/flags/italian.gif" alt="Italian Language Pack" /><br />Italian<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=237" target="_blank" style="text-decoration: none;"><img src="../images/flags/persian.gif" alt="Persian Language Pack" /><br />Persian<br /></a></div>';
	$content .= '<div class="langselect" style="text-decoration: none;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=240" target="_blank" style="text-decoration: none;"><img src="../images/flags/spanish.gif" alt="Spanish Language Pack" /><br />Spanish<br /></a></div>';
	$content .= '<div style="text-align: center; margin-top: 5px;"><a href="http://addons.impresscms.org/modules/wfdownloads/viewcat.php?cid=11" target="_blank">Select another language not listed here.</a></div>';
	$content .= '</fieldset>';
       
	include 'install_tpl.php';
?>