<?php
/**
 * Configuration as code synchronization
 */

// Operations may require additional memory and time to perform
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '600');
ini_set('display_errors', 1);

include_once '../include/formulizeConfigSync.php';

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Configuration Synchronization";

$configSync = new FormulizeConfigSync('/config');
$diff = $configSync->compareConfigurations();

$adminPage['template'] = "db:admin/config-sync.html";
$adminPage['success'] = [];
$adminPage['failure'] = [];

if (isset($_POST['action']) && $_POST['action'] == 'export') {
	$export = $configSync->exportConfiguration();
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename="forms.json"');
	echo $export;
	exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'apply') {
	$changes = $_POST['handles'] ?? [];
	$result = $configSync->applyChanges($changes);
	$adminPage['success'] = $result['success'];
	$adminPage['failure'] = $result['failure'];
	// Compare the config again if we've applied changes so the results are up to date
	$diff = $configSync->compareConfigurations();
}

$adminPage['changes'] = $diff['changes'];
$adminPage['log'] = $diff['log'];
$adminPage['errors'] = $diff['errors'];
