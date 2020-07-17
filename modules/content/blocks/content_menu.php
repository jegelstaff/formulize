<?php
/**
 * Display Content menu block file
 *
 * This file holds the functions needed for the display content menu block
 *
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		TheRplima aka Rodrigo Pereira Lima <therplima@impresscms.org>
 * @version		$Id: content_menu.php 20561 2010-12-19 18:24:19Z phoenyx $
 */

/**
 * Shows content menu
 *
 * @param array $options The Array of options
 * @return array $block The generated block content with its options
 */
function content_content_menu_show($options) {
	global $xoTheme;

	include_once ICMS_ROOT_PATH.'/modules/' . basename(dirname(dirname(__FILE__))) . '/include/common.php';

	$block = array();
	$block['showsubs'] = $options[2];
	$block['selcolor'] = $options[3];
	$block['menu'] = getPages($options[2],$options[0],$options[1],$options[4]);

	$xoTheme->addScript(CONTENT_URL . 'include/menu.js', array('type' => 'text/javascript'),'');
	$xoTheme->addStylesheet(CONTENT_URL . "include/menu" . ((defined('_ADM_USE_RTL') && _ADM_USE_RTL ) ? "_rtl" : "") . ".css", array("media" => "screen"));

	return $block;
}

/**
 * Shows edit options for content menu
 *
 * @param array $options The array of options
 * @return string $form The generated form HTML string
 */
function content_content_menu_edit($options){
	include_once ICMS_ROOT_PATH . '/modules/' . basename(dirname(dirname(__FILE__))) . '/include/common.php';
	$content_content_handler = icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), 'content');

	$sort = array('content_weight' => _CO_CONTENT_CONTENT_CONTENT_WEIGHT, 'content_title' => _CO_CONTENT_CONTENT_CONTENT_TITLE);
	$selsort = new icms_form_elements_Select('', 'options[0]', $options[0]);
	$selsort->addOptionArray($sort);
	$order = array('ASC' => 'ASC' , 'DESC' => 'DESC');
	$selorder = new icms_form_elements_Select('', 'options[1]', $options[1]);
	$selorder->addOptionArray($order);
	$showsubs = new icms_form_elements_Radioyn('', 'options[2]', $options[2]);
	$selcolor = new icms_form_elements_Text('', 'options[3]', 10, 255, $options[3]);
	$selpages = new icms_form_elements_Select('', 'options[4]', $options[4]);
	$selpages->addOptionArray($content_content_handler->getContentList());

	$form = '<table width="100%">';
	$form .= '<tr>';
	$form .= '<td width="30%">' . _MB_CONTENT_CONTENT_CONTID . '</td>';
	$form .= '<td>' . $selpages->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SHOWSUBS . '</td>';
	$form .= '<td>' . $showsubs->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SORT . '</td>';
	$form .= '<td>' . $selsort->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_ORDER . '</td>';
	$form .= '<td>' . $selorder->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SELCOLOR . '</td>';
	$form .= '<td>' . $selcolor->render() . '</td>';
	$form .= '</tr>';
	$form .= '</table>';

	return $form;
}

/**
 * Gets the content pages
 *
 * @param bool $showsubs Show subitems related to this item (recursive!)
 * @param string $sort Order the pages by weight
 * @param string $order The sort direction
 * @param int $content_id The content ID
 * @param int $relateds Show related items
 * @return array $pages The array with pages in a certain weight, order and with related id's
 */
function getPages($showsubs = true, $sort='content_weight', $order='ASC', $content_id = 0, $relateds = 0 ) {
	$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
	$uid = is_object(icms::$user) ? icms::$user->getVar('uid') : 0;
	$content_handler =& icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), 'content');
	$module = icms::handler('icms_module')->getByDirname(basename(dirname(dirname(__FILE__))));
	$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('content_status', 1));
	if (!$relateds){
		$criteria->add(new icms_db_criteria_Item('content_pid', $content_id));
	}else{
		$criteria->add(new icms_db_criteria_Item('short_url', $content_id, 'LIKE'));
		$criteria->add(new icms_db_criteria_Item('content_id', $content_id), 'OR');
	}
	$crit = new icms_db_criteria_Compo(new icms_db_criteria_Item('content_visibility', 1));
	$crit->add(new icms_db_criteria_Item('content_visibility', 3),'OR');
	$criteria->add($crit);
	$criteria->setSort($sort);
	$criteria->setOrder($order);
	$impress_content = $content_handler->getObjects($criteria);
	$i = 0;
	$pages = array();
	foreach ($impress_content as $content){
		if (icms::handler('icms_member_groupperm')->checkRight('content_read', $content->getVar('content_id'), $groups, $module->getVar('mid'))){
			$pages[$i]['title'] = $content->getVar('content_title');
			$pages[$i]['menu'] = $content_handler->makeLink($content);
			if ($showsubs){
				$subs = getPages($showsubs, $sort, $order, $content->getVar('content_id'));
				if (count($subs) > 0){
					$pages[$i]['hassubs'] = 1;
					$pages[$i]['subs'] = $subs;
				}else{
					$pages[$i]['hassubs'] = 0;
				}
			}
			$i++;
		}
	}

	return $pages;
}