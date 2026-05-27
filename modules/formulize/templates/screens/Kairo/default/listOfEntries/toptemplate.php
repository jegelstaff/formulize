<?php

print "

$submitButton
$procedureResults

<div class='fz-list-screen'>

  <div class='fz-list__titlebar'>
    <div class='fz-list__titlebar-start'>
      <h1 class='fz-list__title'>$title</h1>
      $currentViewList
    </div>
    <div class='fz-list__titlebar-end'>
      $addButton";

if ($searchesShown) {
    print "
      <button id='fz-filter-toggle' class='fz-btn fz-btn--ghost fz-btn--icon' aria-label='Toggle filters' title='Toggle filters'>
        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'><path d='M3 5h18M6 12h12M10 19h4'/></svg>
      </button>";
}

print "
      <div class='fz-list__more-wrap'>
        $moreActionsButton
        <div id='more-action-buttons' class='fz-list__action-panel'>
          <div class='fz-pop__group'>Entries</div>
          $addMultiButton
          $addProxyButton
          $importButton
          $exportButton
          <div class='fz-pop__sep'></div>
          <div class='fz-pop__group'>View</div>
          $changeColsButton
          $saveViewButton
          $resetViewButton
          $deleteViewButton
          <div class='fz-pop__sep'></div>
          $calcButton
          $proceduresButton
          $notifButton
        </div>
      </div>
    </div>
  </div>

  <div id='fz-selection-bar' class='fz-selection-bar' hidden>
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
