<?php

$listFid  = (isset($screen) AND is_object($screen)) ? intval($screen->getVar('fid')) : 0;
$listFrid = (isset($screen) AND is_object($screen) AND $screen->getVar('frid')) ? intval($screen->getVar('frid')) : 0;
$listSid  = (isset($screen) AND is_object($screen)) ? intval($screen->getVar('sid')) : 0;
// Where the edit icon opens the entry form: 'drawer' (right-side drawer) or 'screen'
// (navigate to the full edit form). Defaults to 'drawer' for existing lists.
$listEditDest = (isset($screen) AND is_object($screen) AND $screen->getVar('editdestination') === 'screen') ? 'screen' : 'drawer';

// total entries in the list across all pages, shown beside the title
$listTotalCount = isset($GLOBALS['formulize_countMasterResultsForPageNumbers']) ? intval($GLOBALS['formulize_countMasterResultsForPageNumbers']) : 0;
$listTotalCountMarkup = $listTotalCount > 0 ? "<span class='fz-list__count'>$listTotalCount entries</span>" : "";

print "

$submitButton
$procedureResults

<div class='fz-list-screen' data-fz-fid='$listFid' data-fz-frid='$listFrid' data-fz-sid='$listSid' data-fz-editdest='$listEditDest'>

  <div class='fz-list__titlebar'>
    <div class='fz-list__titlebar-start'>
      <h1 class='fz-list__title'>$title</h1>
      $listTotalCountMarkup
    </div>
    <div class='fz-list__titlebar-end'>
      $addButton
			$currentViewList";

if ($searchesShown) {
    print "
      <button id='fz-filter-toggle' type='button' class='fz-btn fz-btn--ghost fz-btn--icon' aria-label='Toggle filters' title='Toggle filters'>
        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='M3 5h18M6 12h12M10 19h4'/></svg>
      </button>";
}

// Only render the "…" trigger and the action panel when there is at least one
// actual item to show inside it. $moreActionsButton holds the trigger markup and
// is effectively always truthy here, so gate on the panel's section contents.
$hasEntriesSection = ($addMultiButton OR $addProxyButton OR $importButton OR $exportButton);
$hasViewSection    = ($changeColsButton OR $saveViewButton OR $resetViewButton OR $deleteViewButton);
$hasOtherSection   = ($calcButton OR $proceduresButton OR $notifButton);

if ($moreActionsButton AND ($hasEntriesSection OR $hasViewSection OR $hasOtherSection)) {
  print "
      <div class='fz-list__more-wrap'>
        $moreActionsButton
        <div id='more-action-buttons' class='fz-list__action-panel fz-panel'>";

  if ($hasEntriesSection) {
    print "
          <div class='fz-pop__group'>Entries</div>
          $addMultiButton
          $addProxyButton
          $importButton
          $exportButton";
  }

  if ($hasViewSection) {
    if ($hasEntriesSection) { print "\n          <div class='fz-pop__sep'></div>"; }
    print "
          <div class='fz-pop__group'>View</div>
          $changeColsButton
          $saveViewButton
          $resetViewButton
          $deleteViewButton";
  }

  if ($hasOtherSection) {
    if ($hasEntriesSection OR $hasViewSection) { print "\n          <div class='fz-pop__sep'></div>"; }
    print "
          $calcButton
          $proceduresButton
          $notifButton";
  }

  print "
        </div>
      </div>";
}

print "
    </div>
  </div>

  <div id='fz-selection-bar' class='fz-selection-bar'>
    <div class='fz-selection-bar__start'>
      $clearSelectButton
      <span class='js-selection-count'></span>
      $selectAllButton
    </div>
    <div class='fz-spacer'></div>
    <div class='fz-selection-bar__actions'>
      $cloneButton
      $changeOwnerButton
      $deleteButton
    </div>
  </div>

";
