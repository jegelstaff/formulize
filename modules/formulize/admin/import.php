<?php

	// display breadcrumb trail
		$breadcrumbtrail[1]['url'] = 'page=home';
		$breadcrumbtrail[1]['text'] = 'Home';
		$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
		$breadcrumbtrail[3]['text'] = 'Import';
		$_GET['select'] = 'Import';

	// output buffering to make sure that everything is in the right place on the page
	ob_start();

	(isset($_GET['next_import']))?  include '../class/Import_Backend.php' : include '../class/Import_Frontend.php' ;

	$htmlContents = ob_get_clean();
	$adminPage['htmlContents'] = $htmlContents;
	$adminPage['template'] = "db:admin/import_template.html";
?>