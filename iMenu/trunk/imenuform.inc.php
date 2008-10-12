<?php
	global $xoopsDB;
	$myts =& MyTextSanitizer::getInstance();
	$title = $myts->makeTboxData4Edit($title);
	$link = $myts->makeTboxData4Edit($link);
	echo "<h4>"._IM_IMENUADMIN."</h4>";
	$formtitle = new XoopsFormText(_IM_TITLE, "title", 50, 150, $title);
	$formlink = new XoopsFormText(_IM_LINK, "link", 50, 255, $link);

	// added by Freeform Solutions May 19, 2005
	$resultParent=$xoopsDB->query("SELECT id, title FROM ".$xoopsDB->prefix("imenu")." WHERE parent = '0' ORDER BY weight ASC");
	$formparent = new XoopsFormSelect(_IM_PARENT, "parent", $parent);
	$formparent->addOption("", _IM_NOPARENT);
	while ($resP = $xoopsDB->fetchArray($resultParent)) {
		$formparent->addOption($resP['id'], $resP['title']);
	}

	$formhide = new XoopsFormSelect(_IM_HIDE, "hide", $hide);
	$formhide->addOption("0", _NO);
	$formhide->addOption("1", _YES);
	$formtarget  = new XoopsFormSelect(_IM_TARGET, "target", $target);
	$formtarget->addOption("_self", _IM_TARG_SELF);
	$formtarget->addOption("_blank", _IM_TARG_BLANK);
	$formtarget->addOption("_parent", _IM_TARG_PARENT);
	$formtarget->addOption("_top", _IM_TARG_TOP);
	$formgroups = new XoopsFormSelectGroup(_IM_GROUPS, "groups", true, $groups, 5, true);
	$formgroups->setDescription(_IM_GROUPS_HELP);
	$submit_button = new XoopsFormButton("", "submit", _IM_SUBMIT, "submit");

	$form->addElement($formtitle, true);
	$form->addElement($formlink, false);
	$form->addElement($formparent);
	$form->addElement($formhide);
	$form->addElement($formtarget);
	$form->addElement($formgroups);
	$form->addElement(new XoopsFormHidden("id", $id));
	$form->addElement(new XoopsFormHidden("op", "update"));
	$form->addElement($submit_button);
?>