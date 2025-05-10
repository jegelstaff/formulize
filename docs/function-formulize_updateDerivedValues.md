---
layout: default
permalink: developers/API/functions/formulize_updateDerivedValues/
title: formulize_updateDerivedValues
---

# formulize_updateDerivedValues( <span style='font-size: 14pt;'>(mixed) $entry_id_or_filter, (int) $fid, (int) $frid = 0</span> )

## Location

/modules/formulize/include/functions.php

## Description

Updates all the derived values in the specified entry in the specified form. Optionally, the update can be performed in the context of the specified relationship, which will make the data in related form entries available to the derived value logic.

## Parameters

__$entry_id_or_filter__ - either an entry id number, or a valid filter for the getData function, as specified in [older Formulize documentation](../../../../files/Using_Formulize-Pageworks_to_Make_Custom_Applications.pdf). If a filter is specified, the derived values will be updated on all entries found by the filter.<br>
__$fid__ - the form id of the form the entry belongs to<br>
__$frid__ - Optional - the form relationship id that represents the dataset within which the derived values should be updated. If not specified, only data from the entry itself will be available and in scope when the derived values are updated. If a relationship is specified then all the data from connected forms will be in scope when the derived values are updated.

## Return Values

Returns __null__

## Examples

~~~
// update all the derived values in entry 7 in form 88
formulize_updateDerivedValues(7, 88);
~~~

~~~
// update all the derived values in entry 7 in form 88, and make sure all the data
// connected to that entry within relationship 12 is available when the derived
// values are updated
formulize_updateDerivedValues(7, 88, 12);
~~~

~~~
// update all the derived values in form 88 where the value of the
// full_name element contains 'John'
$filter = "full_name/**/John";
formulize_updateDerivedValues($filter, 88);
~~~
