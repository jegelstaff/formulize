<?php
/**
* Administration of smilies, form file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: smileform.php 8717 2009-05-03 17:02:23Z sato $
*/

include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';
$smile_form = new XoopsThemeForm($smiles['smile_form'], 'smileform', 'admin.php', 'post', true);
$smile_form->setExtra('enctype="multipart/form-data"');
$smile_form->addElement(new XoopsFormText(_AM_SMILECODE, 'smile_code', 26, 25, $smiles['smile_code']), true);
$smile_form->addElement(new XoopsFormText(_AM_SMILEEMOTION, 'smile_desc', 26, 25, $smiles['smile_desc']), true);
$smile_select = new XoopsFormFile('', 'smile_url', 5000000);
$smile_label = new XoopsFormLabel('', '<img src="'.XOOPS_UPLOAD_URL.'/'.$smiles['smile_url'].'" alt="" />');
$smile_tray = new XoopsFormElementTray(_IMAGEFILE.'&nbsp;');
$smile_tray->addElement($smile_select);
$smile_tray->addElement($smile_label);
$smile_form->addElement($smile_tray);
$smile_form->addElement(new XoopsFormRadioYN(_AM_DISPLAYF, 'smile_display', $smiles['smile_display']));
$smile_form->addElement(new XoopsFormHidden('id', $smiles['id']));
$smile_form->addElement(new XoopsFormHidden('op', $smiles['op']));
$smile_form->addElement(new XoopsFormHidden('fct', 'smilies'));
$smile_form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
?>