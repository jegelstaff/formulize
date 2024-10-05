<?php

ini_set('memory_limit', '1024M'); 
ini_set('max_execution_time', '600');

include_once '../include/synchronization.php';

$syncimport = array();

$syncimport['name'] = "Synchronize Import Review";
$syncimport['content'] = array();
$syncimport['content']['elements'] = array();

if (isset($_POST['syncimport'])) {
    error_log("sync import -ing");
    // if this post was sent then load the cached comparison data and commit it to the database
    $catalog = new SyncCompareCatalog();
    if ($catalog->loadCachedChanges()) {
        // commit database changes
        
        $syncimport['content']['result'] = $catalog->commitChanges("formulize_id"); // make tables first
        $syncimport['content']['result'] = $catalog->commitChanges("formulize"); // then make elements
        $syncimport['content']['result'] = $catalog->commitChanges(); // do the rest of the tables

        // export archive files
        $csvFilepath = getCachedExportFilepath();
        //extractTemplateFiles($csvFilepath); // UNCOMMENT WHEN WE'RE READY TO INCLUDE FILES WITH THE SYNCHRONIZATION!!!

        $syncimport['content']['result']['success'] = true; // TODO catch and display errors from above
    }
    else {
        // failed to load cached catalog comparison data
        $syncimport['content']['catalog_error'] = true;
    }
}
else {
    // load the cached sync compare changes and display the differences
    $catalog = new SyncCompareCatalog();
    if ($catalog->loadCachedChanges()) {
        $changes = $catalog->getChangeDescrs();

        // format the changes as sections for ui-accordion
        $formattedChanges = array();
        foreach ($changes as $changeTable => $change) {
            $section = array();
            $section['name'] = $changeTable;
            $section['content'] = $change;
            $formattedChanges[] = $section;
        }
        $syncimport['content']['elements'] = $formattedChanges;
    } else {
        // failed to load cached catalog comparison data
        $syncimport['content']['catalog_error'] = true;
        error_log(print_r($syncimport['content'], true));

    }
}



$adminPage['syncimport'] = $syncimport;
$adminPage['template'] = "db:admin/sync_import.html";

$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[2]['text'] = "Synchronize";
$breadcrumbtrail[2]['url'] = "page=synchronize";
$breadcrumbtrail[3]['text'] = "Synchronize Import";

$xoopsTpl->assign('content', $syncimport['content']);
$xoopsTpl->assign('name', $syncimport['name']);
