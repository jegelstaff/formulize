---
layout: default
permalink: documentation/changing_columns/
redirect_from:
 - changing_columns/
title: Changing Columns
---

## Changing Columns
In lists of entries, you often want to change which columns are showing. You can do this with the _Change columns_ button. A pop-up window that appears, where you can check off the columns you want to see.

### All forms included

All available columns from all connected forms are generally available, unless the screen has been tailored to only include certain forms. So you can easily show related data from multiple parts of the system and look at it at once.

### Selecting multiple columns at once

The columns are listed with checkboxes. If you click on one checkbox, hold down the SHIFT key, and click on another checkbox, all the columns between the two checkboxes will be selected at once (this is the same behaviour as SHIFT-click in normal lists in other software).

### Metadata
Beside the columns that are created by Users, there are metadata columns: Entry ID, User who made entry, Creation date, User who last modified entry, Last modification date, Creator's email address, and Creator's Groups.

The _Entry ID_ is an automatically generated number that is created whenever an entry is made. The entry ids are handed out sequentially so they indicate the order entries were made in. Entry ids are unique within a form; each form can only have one entry number 6. But if you have many entries in many forms, each form could have an entry number 6 of its own. Entry ids are not reused after an entry is deleted. The next entry created will get the next id in sequence, it will not get an id that belonged to an old entry.

The _Creator's Groups_ are the groups that the creator of the entry was a member of __at the time the entry was created__. Users don't normally change groups, but if they did, their entries do _not_ change groups with them. The entries always have the groups of their creator at creation time.

If someone reassigns the owner/creator of an entry, the groups will be updated at that moment based on the current groups of the new owner/creator.


