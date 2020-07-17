<?php
/**
 * Administration of smilies, form file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Smilies
 * @version		SVN: $Id: smileform.php 20851 2011-02-18 05:18:10Z skenow $
 */

$smile_form = new icms_form_Theme($smiles['smile_form'], 'smileform', 'admin.php', 'post', TRUE);
$smile_form->setExtra('enctype="multipart/form-data"');
$smile_form->addElement(new icms_form_elements_Text(_AM_SMILECODE, 'smile_code', 26, 25, $smiles['smile_code']), TRUE);
$smile_form->addElement(new icms_form_elements_Text(_AM_SMILEEMOTION, 'smile_desc', 26, 25, $smiles['smile_desc']), TRUE);
$smile_select = new icms_form_elements_File('', 'smile_url', 5000000);
$smile_label = new icms_form_elements_Label('', '<img src="' . ICMS_UPLOAD_URL . '/' . $smiles['smile_url'] . '" alt="" />');
$smile_tray = new icms_form_elements_Tray(_IMAGEFILE . '&nbsp;');
$smile_tray->addElement($smile_select);
$smile_tray->addElement($smile_label);
$smile_form->addElement($smile_tray);
$smile_form->addElement(new icms_form_elements_Radioyn(_AM_DISPLAYF, 'smile_display', $smiles['smile_display']));
$smile_form->addElement(new icms_form_elements_Hidden('id', $smiles['id']));
$smile_form->addElement(new icms_form_elements_Hidden('op', $smiles['op']));
$smile_form->addElement(new icms_form_elements_Hidden('fct', 'smilies'));
$smile_form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
