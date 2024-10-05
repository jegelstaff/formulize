---
layout: default
permalink: developers/API/functions/viewEntryLink/
---

# viewEntryLink( <span style='font-size: 14pt;'>(string) $clickable_text, (int | array) $entry_id_or_dataset_record, (int) $override_screen_id </span> ) 

## Location

/modules/formulize/include/entriesdisplay.php

## Description

This function is used to generate the links that users can click in a list of entries, to drill down into a single entry. This function is available in the List Template in List of Entries screens, and in the custom code for Template screens. It relies on certain apparatus within the page that those two screen types provide.

## Parameters

__$clickable_text__ - Optional. The text that should constitute the link. You could also use HTML for an image, etc. If empty or not specified, then the default clickable icon for editing entries will be used.<br>
__$entry_id_or_dataset_record__ - Optional. The entry id that the link should take the user to, or a single record from a dataset gathered by the getData function. If a dataset record, then the first entry found in the record will be used. If this parameter is empty or not specified, then the current record being displayed in the List Template will be used. If the function is being called from Template screen code, then this parameter is required.<br>
__$override_screen_id__ - Optional. By default, the entry will be displayed using the form specified in the settings for the List of Entries screen or Template screen. If you want an alternate screen to be used, then specify it here.

## Return Values

Returns the HTML markup for the link.

## Examples

~~~
// Inside a List Template, control where the link for editing the entry appears
print display($entry, 'title');
print display($entry, 'summary');
print viewEntryLink('Edit this record');
~~~

~~~
// Make a link that uses a different screen to edit the entry than the standard screen
print viewEntryLink('Edit this record', override_screen_id: 993);
~~~

~~~
// Inside Template screen code, make links to edit entries that have been gathered
// The second parameter is required in order to identify which entry the link should go to
$relationship_id = 0;
$form_id = 12;
$data = getData($relationship_id, $form_id);
foreach($data as $entry) {
    print viewEntryLink(display($entry, 'title'), $entry);
    print display($entry, 'summary');
}
~~~
