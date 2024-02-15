<?php

print "

$submitButton 

<div class='card list-of-entries list-of-entries-data'>
    $procedureResults
    <div class='card__header'>
        <div class='list-of-entries list-of-entries-title-and-currentview'>
            <h1>$title</h1>$quickSearchactivities_activity_name
            $currentViewList
        </div>
        <div class='list-of-entries list-of-entries-controls'>
            $addButton
            $addMultiButton
            $addProxyButton
            $changeColsButton
            $moreActionsButton
        </div>
    </div>
    <div class='card__body'>
    
        <div id='more-action-buttons' class='list-of-entries list-of-entries-controls'>
            <div>
                $saveViewButton
                $deleteViewButton
                $resetViewButton
            </div>
            <div>
                $selectAllButton
                $clearSelectButton
                $cloneButton
                $deleteButton
            </div>
            <div>
                $calcButton
                $proceduresButton
                $exportButton
                $importButton
                $notifButton
            </div>
        </div>

";