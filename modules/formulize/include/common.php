<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

if(!defined('FORMULIZE_COMMON_INCLUDED')) {
	define('FORMULIZE_COMMON_INCLUDED', 1);
}

include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/formulize.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/frameworks.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elementrenderer.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/constants.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/entriesdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/graphdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/calendardisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/elementdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/griddisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/customCodeForApplications.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/data.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/screen.php';

// verify that we're on the required version of PHP
$module_handler = xoops_gethandler('module');
$formulizeModule = $module_handler->getByDirname("formulize");
if(PHP_VERSION_ID < $formulizeModule->modinfo['min_php_version_id']) {
	error_log('Fatal Error: PHP '.$formulizeModule->modinfo['min_php_version'].' or higher is required for Formulize to work correctly. This web server is currently running PHP '.PHP_VERSION);
	exit('Upgrade PHP to run Formulize. Advise the webmaster to check the error logs for more information.');
}

//Add the language constants
global $xoopsConfig;
if (file_exists(XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/admin.php";
		include_once XOOPS_ROOT_PATH . "/language/".$xoopsConfig['language']."/global.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/english/main.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/english/admin.php";
		include_once XOOPS_ROOT_PATH . "/language/english/global.php";
}

formulize_handleHtaccessRewriteRule();

$GLOBALS['formulize_subformInstance'] = 100;

function formulize_exception_handler($exception) {
	// log everything
	error_log($exception->getMessage());
	writeToFormulizeLog(array(
		'PHP_error_number' => $exception->getCode(),
		'PHP_error_string' => $exception->getMessage(),
		'PHP_error_file' => $exception->getFile(),
		'PHP_error_errline' => $exception->getLine()
	));
	// if this is an MCP request, return a JSON-RPC error response through the MCP server
	if(defined('FORMULIZE_MCP_REQUEST') && FORMULIZE_MCP_REQUEST==1) {
		FormulizeMCP::sendResponse([
			'jsonrpc' => '2.0',
			'error' => [
				'code' => $exception->getCode(),
				'message' => $exception->getMessage(),
				'timestamp' => date('Y-m-d H:i:s'),
				'type' => 'internal_formulize_error'
			]
		], 500);
		exit;
	}
	global $xoopsConfig, $xoopsUser;
	$stackTrace = "";
	$errorMessage = "";
	// stacktrace included for webmasters
	if($xoopsUser AND in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
		$stackTrace = "<ul id='formulize-stacktrace'><li>".str_replace("\n", "</li><li>", str_replace(XOOPS_ROOT_PATH, "", $exception->getTraceAsString()))."</li></ul>";
		$errorMessage = sprintf(_formulize_ERRORMSGONSCREEN, $exception->getMessage(), $exception->getLine(), str_replace(XOOPS_ROOT_PATH, "", $exception->getFile()));
		$notifyWebmaster = '';
		$token = '';
	} else {
		$token = $GLOBALS['xoopsSecurity']->createToken(0, 'formulize_error_token');
		$mailTemplateFolder = XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template";
		$reportFileName = "error_report_$token.tpl";
		formulize_scandirAndClean($mailTemplateFolder, "error_report_");
		$errorReportContents = sprintf(_formulize_ERRORREPORT, $exception->getMessage(), $exception->getLine(), str_replace(XOOPS_ROOT_PATH, "", $exception->getFile()), str_replace(XOOPS_ROOT_PATH, "", $exception->getTraceAsString()));
		file_put_contents($mailTemplateFolder."/".$reportFileName, $errorReportContents);
		$notifyWebmaster = "
			<script>
				function notifyWebmaster() {
					jQuery('#notifyWebmasterLink').hide(200);
					jQuery('#notifyWebmasterForm').show(200);
				}
				function submitNotifyForm() {
					jQuery('#notifyWebmasterForm form').attr('action', '".XOOPS_URL."/notify-webmaster.php');
					jQuery('#notifyWebmasterForm form').submit();
				}
			</script>
			<p id='notifyWebmasterLink'><a href='' onclick='notifyWebmaster();return false;'>Notify the webmaster</a><p>
			<div id='notifyWebmasterForm'>
			<form method='post' action=''>
			<p>Please describe what led up to this error:<br>
			<textarea name='details' rows=5 cols=50></textarea></p>
			<p>Include as many details as possible about what you were trying to do in the system,<br>the forms you were using, the data you were adding or updating, etc.</p>
			<p><input type='button' onclick='submitNotifyForm();' value='Notify'></p>
			<input type='hidden' name='errorToken' value='$token'>
			</form>
			</div>
		";
	}
	ob_get_clean();
	print "<style>
		#notifyWebmasterForm {
			display: none;
		}
		h1,blockquote,p {
			margin-bottom: 1em;
		}
		blockquote {
			padding-left: 1.5em;
			line-height: 1.5em;
		}
		#formulize-stacktrace li {
			list-style: disc;
			list-style-position: outside;
		}
		#formulize-stacktrace {
			padding-left: 2em;
		}
	</style>";
	print "<h1>"._formulize_ERRORTITLE."</h1>
	$errorMessage
	<p>"._formulize_ERRORLOGGED."</p>
	$notifyWebmaster
	$stackTrace";
	include XOOPS_ROOT_PATH.'/footer.php';
}

set_exception_handler('formulize_exception_handler');
