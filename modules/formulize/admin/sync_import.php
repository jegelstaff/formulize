<?php

include_once '../include/synchronization.php';

$syncimport = array();

$syncimport[1]['name'] = "Synchronize Import Details";
$syncimport[1]['content'] = array();
$syncimport[1]['content']['elements'] = array();

if (isset($_POST['syncimport'])) {
    // if this post was sent then load the cached comparison data and commit it to the database
    $catalog = new SyncCompareCatalog();
    $catalog->loadCachedChanges();

    // commit database changes
    $syncimport[1]['content']['result'] = $catalog->commitChanges();
    $syncimport[1]['content']['result']['success'] = true;
}
else {
    // load the cached sync compare changes and display the differences
    $catalog = new SyncCompareCatalog();
    $catalog->loadCachedChanges();

    $syncimport[1]['content']['elements'] = $catalog->getChanges();
}

$adminPage['syncimport'] = $syncimport;
$adminPage['template'] = "db:admin/sync_import.html";

$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[2]['text'] = "Synchronize";
$breadcrumbtrail[2]['url'] = "page=synchronize";
$breadcrumbtrail[3]['text'] = "Synchronize Import";
