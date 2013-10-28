<?php
/**
 * Display Content block file
 *
 * This file holds the functions needed for the display content block
 *
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		TheRplima aka Rodrigo Pereira Lima <therplima@impresscms.org>
 * @version		$Id: content_display.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

defined("ICMS_ROOT_PATH" ) or die("ICMS root path not defined");

function content_content_display_show($options) {
	global $xoTheme;

	$block = array();

	$xoTheme->addStylesheet(ICMS_URL . '/modules/' . basename(dirname(dirname(__FILE__))) . '/module.css');
	$xoTheme->addStylesheet(ICMS_URL . '/modules/' . basename(dirname(dirname(__FILE__))) . '/include/content.css');

	include_once ICMS_ROOT_PATH . '/modules/' . basename(dirname(dirname(__FILE__))) . '/include/common.php';

	$content_content_handler = icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), 'content');

	if ($options[0] == 0) {
		$options[0] = $content_content_handler->getLastestCreated(false);
	}

	$contentObj = $content_content_handler->get($options[0]);
	if ($contentObj && ! $contentObj->isNew() && $contentObj->accessGranted()) {
		$block['content_content'] = $contentObj->toArray();
		$block['showSubs'] = $options[1];
		$block['showInfo'] = $options[3];

		if ($options[2]) {
			$block['content_category_path'] = $content_content_handler->getBreadcrumbForPid($contentObj->getVar('content_id', 'e'), 1) ;
		} else {
			$block['content_category_path'] = false ;
		}
	}

	return $block;
}

function content_content_display_edit($options) {
	include_once ICMS_ROOT_PATH . '/modules/' . basename(dirname(dirname(__FILE__))) . '/include/common.php';

	$content_content_handler = icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), 'content');

	$selpages = new icms_form_elements_Select('', 'options[0]', $options[0]);
	$selpages->addOptionArray($content_content_handler->getContentList());
	$showsubs = new icms_form_elements_Radioyn('', 'options[1]', $options[1]);
	$showbreadc = new icms_form_elements_Radioyn('', 'options[2]', $options[2]);
	$showinfo = new icms_form_elements_Radioyn('', 'options[3]', $options[3]);

	$form = '<table width="100%">';
	$form .= '<tr>';
	$form .= '<td width="30%">' . _MB_CONTENT_CONTENT_SELPAGE . '</td>';
	$form .= '<td>' . $selpages->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SHOWSUBS . '</td>';
	$form .= '<td>' . $showsubs->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SHOWBREADCRUMB . '</td>';
	$form .= '<td>' . $showbreadc->render() . '</td>';
	$form .= '</tr>';
	$form .= '<tr>';
	$form .= '<td>' . _MB_CONTENT_CONTENT_SHOWINFO . '</td>';
	$form .= '<td>' . $showinfo->render() . '</td>';
	$form .= '</tr>';
	$form .= '</table>';

	return $form;
}