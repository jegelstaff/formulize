<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */

$sync = array();

$sync[1]['name'] = "Import Database for Synchronization";
$sync[1]['content']['type'] = "import";
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";

$adminPage['sync'] = $sync;
$adminPage['template'] = "db:admin/synchronize.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Synchronize";