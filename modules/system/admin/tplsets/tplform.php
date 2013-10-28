<?php
/**
 * Administration of template sets, form file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Template Sets
 * @version		SVN: $Id: tplform.php 20852 2011-02-18 05:19:56Z skenow $
 */

if ($tform['tpl_tplset'] != 'default') {
	$form = new icms_form_Theme(_MD_EDITTEMPLATE, 'template_form', 'admin.php', 'post', TRUE);
} else {
	$form = new icms_form_Theme(_MD_VIEWTEMPLATE, 'template_form', 'admin.php', 'post', TRUE);
}
$form->addElement(new icms_form_elements_Label(_MD_FILENAME, $tform['tpl_file']));
$form->addElement(new icms_form_elements_Label(_MD_FILEDESC, $tform['tpl_desc']));
$form->addElement(new icms_form_elements_Label(_MD_LASTMOD, formatTimestamp($tform['tpl_lastmodified'], 'l')));
$config = array(
	'name' => 'html',
	'value' => $tform['tpl_source'],
	'language' => _LANGCODE,
	'width' => '100%',
	'height' => '400px',
	'syntax' => 'html');
if ($tform['tpl_tplset'] == 'default') $config["is_editable"] = FALSE;
$tpl_src = icms_plugins_EditorHandler::getInstance('source')->get($icmsConfig['sourceeditor_default'], $config);
$tpl_src->setCaption(_MD_FILEHTML);
$form->addElement($tpl_src);
$form->addElement(new icms_form_elements_Hidden('id', $tform['tpl_id']));
$form->addElement(new icms_form_elements_Hidden('op', 'edittpl_go'));
$form->addElement(new icms_form_elements_Hidden('redirect', 'edittpl'));
$form->addElement(new icms_form_elements_Hidden('fct', 'tplsets'));
$form->addElement(new icms_form_elements_Hidden('moddir', $tform['tpl_module']));
if ($tform['tpl_tplset'] != 'default') {
	$button_tray = new icms_form_elements_Tray('');
	$button_tray->addElement(new icms_form_elements_Button('', 'previewtpl', _PREVIEW, 'submit'));
	$button_tray->addElement(new icms_form_elements_Button('', 'submittpl', _SUBMIT, 'submit'));
	$form->addElement($button_tray);
} else {
	$form->addElement(new icms_form_elements_Button('', 'previewtpl', _MD_VIEW, 'submit'));
}
