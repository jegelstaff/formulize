---
layout: default
permalink: developers/API/functions/viewEntryButton/
---

# viewEntryButton( <span style='font-size: 14pt;'>(string) $clickable_text, (int | array) $entry_id_or_dataset_record, (int) $override_screen_id </span> ) 

## Location

/modules/formulize/include/entriesdisplay.php

## Description

This function behaves exactly the same as [viewEntryLink](../viewEntryLink/) except it results in a clickable button being generated instead of a clickable link.

## Parameters

__$clickable_text__ - Optional. The text that should constitute the link. You could also use HTML for an image, etc. If empty or not specified, then the default clickable icon for editing entries will be used.<br>
__$entry_id_or_dataset_record__ - Optional. The entry id that the link should take the user to, or a single record from a dataset gathered by the getData function. If a dataset record, then the first entry found in the record will be used. If this parameter is empty or not specified, then the current record being displayed in the List Template will be used. If the function is being called from Template screen code, then this parameter is required.<br>
__$override_screen_id__ - Optional. By default, the entry will be displayed using the form specified in the settings for the List of Entries screen or Template screen. If you want an alternate screen to be used, then specify it here.

## Return Values

Returns the HTML markup for the button.


