<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

// we're in the global space, don't need to reference 'global'
require_once "mainfile.php";
include "header.php";

if(isset($_POST['errorToken']) AND $xoopsSecurity->validateToken($_POST['errorToken'], name: 'formulize_error_token')) {

	include_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";
	$member_handler = xoops_gethandler('member');
	$reportFileName = "error_report_".$_POST['errorToken'].".tpl";
	$mailTemplateFolder = XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template";
	$userName = $xoopsUser ? $xoopsUser->getVar('uname') : 'An anonymous user';

	file_put_contents($mailTemplateFolder."/".$reportFileName, $_POST['details']."\n--$userName", FILE_APPEND);

	$event = 'new_entry'; // doesn't matter since we're sending direct mail with its own subject and template
	$extra_tags = array();
	$fid = null; // when null, will use the first form in the DB to determine the available notification events, which will match the 'new_entry' event above
	$uids_to_notify = $member_handler->getUsersByGroup(XOOPS_GROUP_ADMIN);
	$mid = getFormulizeModId();
	$omit_user = 0;
	$subject = "Formulize error report in '".$xoopsConfig['sitename']."'";
	$template = $reportFileName;

	formulize_processNotification($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject, $template);

	print "<h1>"._formulize_ERRORSENT1."</h1><p>"._formulize_ERRORSENT2."</p>";
}
include "footer.php";
