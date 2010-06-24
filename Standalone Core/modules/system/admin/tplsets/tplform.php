<?php
/**
* Administration of template sets, form file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: tplform.php 8626 2009-04-21 03:56:17Z skenow $
*/

include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';
if ($tform['tpl_tplset'] != 'default') {
	$form = new XoopsThemeForm(_MD_EDITTEMPLATE, 'template_form', 'admin.php', 'post', true);
}else{
	$form = new XoopsThemeForm(_MD_VIEWTEMPLATE, 'template_form', 'admin.php', 'post', true);
}
$form->addElement(new XoopsFormLabel(_MD_FILENAME, $tform['tpl_file']));
$form->addElement(new XoopsFormLabel(_MD_FILEDESC, $tform['tpl_desc']));
$form->addElement(new XoopsFormLabel(_MD_LASTMOD, formatTimestamp($tform['tpl_lastmodified'], 'l')));
$tpl_src = new XoopsFormTextArea(_MD_FILEHTML, 'html', $tform['tpl_source'], 25, 70);
if ($tform['tpl_tplset'] == 'default'){
	$tpl_src->setExtra('readonly');
}
$form->addElement($tpl_src);
$form->addElement(new XoopsFormHidden('id', $tform['tpl_id']));
$form->addElement(new XoopsFormHidden('op', 'edittpl_go'));
$form->addElement(new XoopsFormHidden('redirect', 'edittpl'));
$form->addElement(new XoopsFormHidden('fct', 'tplsets'));
$form->addElement(new XoopsFormHidden('moddir', $tform['tpl_module']));
if ($tform['tpl_tplset'] != 'default') {
	$button_tray = new XoopsFormElementTray('');
	$button_tray->addElement(new XoopsFormButton('', 'previewtpl', _PREVIEW, 'submit'));
	$button_tray->addElement(new XoopsFormButton('', 'submittpl', _SUBMIT, 'submit'));
	$form->addElement($button_tray);
} else {
	$form->addElement(new XoopsFormButton('', 'previewtpl', _MD_VIEW, 'submit'));
}
?>