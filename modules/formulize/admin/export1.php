<?php
	// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
	$aid = intval($_GET['aid']);
	$application_handler = xoops_getmodulehandler('applications','formulize');
	// get a list of all applications
	$allApps = $application_handler->getAllApplications();

	if($aid == 0) {
		$appName = _AM_APP_FORMWITHNOAPP; 
	} else {
		$appObject = $application_handler->get($aid);
		$appName = $appObject->getVar('name');
	}

	// display breadcrumb trail
	$breadcrumbtrail[1]['url'] = "page=home";
	$breadcrumbtrail[1]['text'] = "Home";
	$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
	$breadcrumbtrail[2]['text'] = $appName;
	$breadcrumbtrail[3]['text'] = "Export";
	
	$_GET['select'] = 'Export';

	// output buffering to make sure that everything is in the right place on the page
	ob_start();
	include "../class/Export.php";
	$htmlContents = ob_get_clean();
	$adminPage['htmlContents'] = $htmlContents;
	$adminPage['template'] = "db:admin/export_template.html";
?>