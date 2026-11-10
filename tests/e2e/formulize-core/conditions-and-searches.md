# Conditions and searches

Every row here is something a person can set up, and what they should see when they look at the page. The specs read this file, so a row is a test. Every row runs in `03-validate-sequential/045-conditions-on-the-page.spec.js`, which checks them all and only then fails, listing every row that disagrees, so one run shows them all. To check a behaviour, read its row. To add a case, add a row.

The museum's data is in `01-setup/020-data-entry.spec.js`, `01-setup/022-locations-and-loan-requests.spec.js` and `01-setup/023-visits-and-offers.spec.js`, and there is a summary at the end of this file.

## Writing a row

Every row is written and read the same way, in every section. A row's id is its section's letter and a number, and the letter says how the row is checked, in the next table. The specs find a column by its heading.

- **All of** and **Any of** are the two sets of conditions: all of the first set, and at least one of the second. Conditions in a set are separated by `;`.
- A condition is the element's caption, an operator, and a term: `Era = BCE`. The operators are `=`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, `NOT LIKE` and `IN`.
- `(empty)` is a condition with nothing in the term. `{BLANK}` is the keyword for blank.
- `{handle}` refers to a field in the entry that's open.
- **Should show** is `Everything`, `Nothing`, `Everything except` some names, or a list of names, separated by commas. What the names are out of is in the next table.
- A row can say where to look, in a column named for what it holds:
  - **Loan Request**, **Artifact**, **Visit** and **List** name the one to open. `(new)` in Loan Request is a new one.
  - **Open** is several to open, by name, separated by commas, and Should show is out of those.
  - **On screen** is what to fill in on the page first: `Caption: value`, with ` + ` between boxes to tick, and `(blank)` for filling it in and then clearing it.
  - **Typed in** is what to type in a list's search boxes: ``Caption: `term` ``, with `<br>` between boxes.
  - **In the address** is added to the page's address, ie: `place=Cairo`.
- **Saved** is in place of Should show in section 12. It's what the new entry holds afterwards, as `Caption: value`, separated by `;`, with `(blank)` for nothing.
- **Where what a row should show is open to question, it's what master does now.** For now, the aim is to change as little of master's behaviour as possible, so master's result is the answer for anything the rules below don't already settle. Each such row is checked by comparing runs on master and on the branch.

## How each section is checked

A row says what should be on the page. This table says where to look for it, and the specs read their selectors and sets of names from here, so if the markup changes, this is the one place to change.

| Rows | Open, as curator1 | Look at | Names | A row passes when |
|---|---|---|---|---|
| S | Main Vault | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| R | The row's saved Loan Request | `.form-row:has(.formulize-label-loans_artifact) select option` | Artifact | The options are exactly the row's names |
| N | A new Loan Request, then what the row puts on screen | `.form-row:has(.formulize-label-loans_artifact) select option` | Artifact | The options are exactly the row's names, once the redraw has finished |
| L | The row's artifact, on its Location and loans tab | `.formulize-subform td` | Loan Request | The names in those cells are exactly the row's names, out of that artifact's requests |
| O | The row's visit | `.form-row:has(.formulize-label-visits_follow_up) select option` | Offer | The options are exactly the row's names |
| Q | Main Vault, with the row's values in the page's address | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| D | A new Loan Request | `.form-row:has(.formulize-label-loans_donor) select option` | Donor | The options are exactly the row's names |
| C | A new Loan Request | `.form-row:has(.formulize-label-loans_collections) label` | Collection | The boxes are exactly the row's names |
| V | Each artifact in the row's Open, on its Location and loans tab | `.form-row:has(.formulize-label-artifacts_curator_note) input[type="text"]` | Artifact | The box is on the page for exactly the row's names, out of the artifacts it opens |
| F | The row's list | `[id^="celladdress_"]` | By list | The names in those cells are exactly the row's names |
| B | The row's list, then what the row types in the search boxes, or picks from a date range | `[id^="celladdress_"]` | By list | The names in those cells are exactly the row's names, and each box still holds what was typed or picked |
| A | Each Loan Request in the row's Open | `.form-row:has(.formulize-label-loans_end_date) input:not([type="hidden"]):not([disabled])` | Loan Request | End date can be edited in exactly the row's names, out of the Loan Requests it opens |
| E | The row's Loan Request | `.form-row:has(.formulize-label-loans_donor) select option:checked` | Donor | The donor selected is exactly the row's names |
| M | The Weapons collection | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names, out of the artifacts in Weapons |
| T | Main Vault | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| W | Each artifact in the row's Open, on its Location and loans tab | `.form-row:has(.formulize-label-artifacts_curator_note) input[type="text"]` | Artifact | The box is on the page for exactly the row's names, out of the artifacts it opens |
| U | The Catalogue list, then what the row types in the search boxes | `[id^="celladdress_"]` | Artifact | The names in those cells are exactly the row's names, and each box still holds what was typed |
| G | Main Vault | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| J | The Catalogue list, as auditor1, the one member of the Auditors, which the row's filter is on, instead of curator1 | `[id^="celladdress_"]` | Artifact | The names in those cells are exactly the row's names |
| K | Main Vault, as a webmaster, instead of curator1, then Add in its Artifacts subform | What the new artifact holds | Artifact | The new artifact holds what the row's Saved says |

- **Names** is one of the tables at the end of this file, by its first column. Only text that is exactly one of those names counts, so other text in the same cells can't be mistaken for a result. By list means Artifact on the Catalogue and Artifacts lists, and Donor on the Donor artifacts list. Out of means a row's Everything is only those, ie: a row in section 5 is out of the artifacts it opens.
- **The match is exact.** An entry that shouldn't be there fails a row as much as one that's missing.
- **`Everything`** means every name in the set, `Everything except` means the rest of the set, and anything else is the list of names. A name that isn't in the set stops the spec, so a typo here can't pass.
- **`Nothing` has to be proved.** A query that fails shows no entries, the same as one that finds none. So rows that find nothing in sections 1, 3 and 4 are loaded again with their conditions in the "one or more" set, alongside one that must match, and that entry must appear.

## What the operators mean

- `=` and `LIKE` on an element with several values mean any of them. `!=` and `NOT LIKE` mean none of them, whichever order they were saved in.
- A `{ }` that holds several values, ie: checkboxes, is the same. With `LIKE`, any of them can be part of a value, so Iron finds Cast iron, the same as a term that's typed. Linked values are entry ids, so they're compared whole, and so are the stored values of an element with text people see that's different from what's stored, because 1 isn't part of 12.
- A blank field is not equal to anything, so `!=` and `NOT LIKE` find it. Nothing is before or after a blank, so `>` and `<` don't.
- NULL and an empty string are both blank.
- A year or month in a date condition means all of that period: `= 2002` is any day in 2002, `> 2002` is after the end of it, and `< 2002` is before the start of it.
- A term that can't be a date finds nothing with `=`, `>`, `<` and `LIKE`, and everything with `!=` and `NOT LIKE`.
- `LIKE` and `NOT LIKE` on an element with text people see that's different from what's stored look in that text, and then compare whole stored values.
- A number in a condition on a linked element is an entry id, if no entry has that number as its text, whether the element holds one value or several. Display conditions don't do this, so section 5 doesn't have rows for it. On an element that isn't linked, a number is a value like any other, ie: `Handling = 1` is what's stored for Gloves, in S144.
- A condition on a connected form keeps the main entries with at least one connected entry that meets it, and shows only the connected entries that do. See [Conditions on connected forms](../../../docs/filters.md#conditions-on-connected-forms).
- `{ }` that refers to a blank field: `=` and `LIKE` find blanks, `!=` and `NOT LIKE` find anything that isn't blank, and `>` and `<` find nothing.
- The exception is a condition whose element lists entries from the form the `{ }` refers to, where the `{ }` names a linked element in that form, ie: `Visit = {visits_donor}` in section 4. With that linked element blank, `=` finds nothing, because master finds nothing. `!=` finds everything, since nothing is the same.
- `{TODAY}` is today's date, `{USER}` is curator1, who is Curator One, and `{ID}` is the entry that's open. A `{ }` that isn't an element or one of these is read from a parameter of that name in the page's address, and with no such parameter the condition is ignored.
- On when an entry was made or modified, which have a time as well, a day is all of that day, as the person sees it. `<= {TODAY}` is everything made today, and `> {TODAY}` is after the end of today. A term with a time in it, ie: `2000-01-01 00:00`, is that moment.
- A list of users matches the name people see in it, ie: Curator One, as well as the user's login.
- In a new entry, a `{ }` with nothing on screen has no value yet, so a list that depends on it is empty until there's one. On a linked element that's true of every operator. On any other element it's true of `=`, and the other operators ignore the condition. If the `{ }` element has a default value, an element that isn't linked uses that, whatever the operator. Any other conditions still apply.
- In a new entry, a `{ }` whose value was taken out on screen has no value either, but it's not the same as nothing yet: `=` finds nothing, and the other operators ignore the condition, on a linked element too. That's what master does.
- On a saved entry, a linked element always offers the value that's selected, whatever the condition says, so it isn't lost when the entry is saved. Rows for saved Loan Requests expect that.

## 1. The Artifacts subform in Main Vault

Set with `sourceEntryFilter` on `locations_subform_artifacts`, then open Main Vault as curator1. All 16 artifacts are connected to Main Vault, so this can show any of them. Each row has its own id.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| S1 | Provenance = Bought in Cairo |  | Egyptian Chariot | text |
| S2 | Provenance != Bought in Cairo |  | Everything except Egyptian Chariot | text, != finds blanks |
| S3 | Provenance LIKE Bought |  | Egyptian Chariot, Florentine Book, French Musket | text, part of the value |
| S4 | Provenance NOT LIKE Bought |  | Everything except Egyptian Chariot, Florentine Book, French Musket | text, NOT LIKE finds blanks |
| S5 | Provenance NOT LIKE Cairo; Provenance NOT LIKE Paris |  | Everything except Egyptian Chariot, French Musket | two negatives, both must hold |
| S6 |  | Provenance != a; Provenance != b | Everything | two negatives, one must hold |
| S7 |  | Provenance = zzz; Provenance != yyy | Everything | a negative that holds, beside one that does not |
| S8 | Provenance = (empty) |  | Everything except Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | no term means blank |
| S9 | Provenance != (empty) |  | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | no term, negated |
| S10 | Provenance LIKE (empty) |  | Everything except Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | no term with LIKE means blank |
| S11 | Provenance NOT LIKE (empty) |  | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | no term with NOT LIKE |
| S12 | Provenance = {BLANK} |  | Everything except Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | {BLANK} |
| S13 | Provenance != {BLANK} |  | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | not {BLANK} |
| S14 | Provenance < Z |  | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | nothing is before a blank |
| S15 | Provenance > A |  | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | nothing is after a blank |
| S16 | Provenance = Bought at Sotheby's |  | Florentine Book | a quote in the term |
| S17 | Provenance LIKE Sotheby's |  | Florentine Book | a quote in part of the value |
| S18 | Appraiser email = {BLANK} |  | Everything except Roman Coin, Japanese Coin | blank is NULL in some entries and '' in others, both are found |
| S19 | Appraiser email != {BLANK} |  | Roman Coin, Japanese Coin | not blank, neither NULL nor empty |
| S20 | Appraiser email = (empty) |  | Everything except Roman Coin, Japanese Coin | no term, on NULL and empty |
| S21 | Appraiser email != appraiser@museum.example |  | Everything except Roman Coin, Japanese Coin | != finds NULL and '' |
| S22 | Appraiser email LIKE museum |  | Roman Coin, Japanese Coin | LIKE |
| S23 | Appraiser email NOT LIKE museum |  | Everything except Roman Coin, Japanese Coin | NOT LIKE finds NULL and '' |
| S24 | Latitude != 0 |  | Everything | number, a blank is not 0 |
| S25 | Latitude < 50 |  | Everything except Viking Silver Armband, Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | number, a blank is not less than anything |
| S26 | Latitude >= -90 |  | Everything except Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | number, a blank is not more than anything |
| S27 | Latitude = (empty) |  | Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | number, blank is not 0 |
| S28 | Latitude != {BLANK} |  | Everything except Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | number, not blank |
| S29 |  | Latitude < 0; Era = BCE | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Polynesian Canoe, Persian necklace - number 2, Unknown Shard, Bronze Mirror | one or the other |
| S30 | Era = BCE |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | radio |
| S31 | Era != CE |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | radio, != |
| S32 | Era LIKE CE |  | Everything | radio, LIKE matches part of the value |
| S33 | Era LIKE (empty) |  | Nothing | radio, no entry has it blank |
| S34 | On loan = Yes |  | Roman Coin, Florentine Book | Yes/No, by what people see |
| S35 | On loan = No |  | Chinese Sword, French Musket, Japanese Coin | Yes/No |
| S36 | On loan != Yes |  | Everything except Roman Coin, Florentine Book | Yes/No, != finds blanks |
| S37 | On loan != No |  | Everything except Chinese Sword, French Musket, Japanese Coin | Yes/No, != finds blanks |
| S38 | On loan = {BLANK} |  | Everything except Roman Coin, Chinese Sword, Florentine Book, French Musket, Japanese Coin | Yes/No, blank |
| S39 | On loan != {BLANK} |  | Roman Coin, Chinese Sword, Florentine Book, French Musket, Japanese Coin | Yes/No, not blank |
| S40 | On loan = (empty) |  | Everything except Roman Coin, Chinese Sword, Florentine Book, French Musket, Japanese Coin | Yes/No, no term |
| S41 | On loan = maybe |  | Nothing | Yes/No, not a value it can have |
| S42 | On loan != maybe |  | Everything | Yes/No, every entry is not that |
| S43 | Materials = Iron |  | Chinese Sword, French Musket | checkboxes, a whole value, so not Cast iron |
| S44 | Materials LIKE Iron |  | Chinese Sword, French Musket, American Bicycle | checkboxes, part of a value, so Cast iron too |
| S45 | Materials != Iron |  | Everything except Chinese Sword, French Musket | checkboxes, none of the values is Iron, and blanks |
| S46 | Materials NOT LIKE Iron |  | Everything except Chinese Sword, French Musket, American Bicycle | checkboxes, no value has Iron in it, and blanks |
| S47 | Materials = {BLANK} |  | Japanese Coin, Persian necklace - number 2, Unknown Shard | checkboxes, blank |
| S48 | Materials != {BLANK} |  | Everything except Japanese Coin, Persian necklace - number 2, Unknown Shard | checkboxes, not blank |
| S49 | Materials = Silver; Materials = Gold |  | Persian necklace | checkboxes, both |
| S50 |  | Materials = Silver; Materials = Gold | Roman Coin, Persian necklace, Viking Silver Armband | checkboxes, either |
| S51 | Materials = Wood; Materials NOT LIKE Bronze |  | French Musket, Polynesian Canoe, Silk Scroll | checkboxes, has one and not the other |
| S52 | Donor = Freeform Solutions |  | Chinese Sword, Japanese Coin | linked, one value |
| S53 | Donor != Freeform Solutions |  | Everything except Chinese Sword, Japanese Coin | linked, != finds blanks |
| S54 | Donor LIKE Solutions |  | Chinese Sword, Japanese Coin | linked, part of the name |
| S55 | Donor NOT LIKE Solutions |  | Everything except Chinese Sword, Japanese Coin | linked, NOT LIKE finds blanks |
| S56 | Donor = 3 |  | Chinese Sword, Japanese Coin | linked, the entry id of Freeform Solutions |
| S57 | Donor != 3 |  | Everything except Chinese Sword, Japanese Coin | linked, not that entry id |
| S58 | Donor = {BLANK} |  | Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | linked, blank |
| S59 | Donor != {BLANK} |  | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | linked, not blank |
| S60 | Donor = Aoife O'Brien |  | Bronze Mirror | linked, a quote in the name |
| S61 | Donor LIKE O'Brien |  | Bronze Mirror | linked, a quote in part of the name |
| S62 | Donor = Hart & Sons Antiques |  | Silk Scroll | linked, an ampersand in the name |
| S63 | Donor != Hart & Sons Antiques |  | Everything except Silk Scroll | linked, not that name |
| S64 | Donor LIKE & Sons |  | Silk Scroll | linked, an ampersand in part of the name |
| S65 | Donor != Freeform Solutions; Era != CE |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | all of a linked and a radio |
| S66 |  | Donor != Freeform Solutions; Era != CE | Everything except Chinese Sword, Japanese Coin | one of a linked and a radio |
| S67 | Collections = Weapons |  | Chinese Sword, Egyptian Chariot, French Musket | linked, several values |
| S68 | Collections != Weapons |  | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, none is Weapons, and blanks |
| S69 | Collections LIKE ns |  | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | linked, several values, part of a name, Coins and Weapons |
| S70 | Collections NOT LIKE ns |  | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | linked, several values, no name has it, and blanks |
| S71 | Collections = 4 |  | Chinese Sword, Egyptian Chariot, French Musket | linked, several values, the entry id of Weapons. On checkboxes that aren't linked, a number is a value, see S144 |
| S72 | Collections != 4 |  | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, not that entry id |
| S73 | Collections = {BLANK} |  | Unknown Shard | linked, several values, blank |
| S74 | Collections != {BLANK} |  | Everything except Unknown Shard | linked, several values, not blank |
| S75 | Collections = Ancient History; Collections != Weapons |  | Roman Coin, Persian necklace, Babylonian Spoon, Persian necklace - number 2, Bronze Mirror | has one and not the other |
| S76 |  | Collections != Weapons; Donor NOT LIKE Solutions | Everything except Chinese Sword | one of two negatives on linked elements |
| S77 | Date of acquisition = 2002 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | a year, including its last day |
| S78 | Date of acquisition = 2003 |  | Silk Scroll | a year, including its first day |
| S79 | Date of acquisition != 2002 |  | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not in a year, and blanks |
| S80 | Date of acquisition > 2002 |  | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | after the end of a year, so the first day of 2003 |
| S81 | Date of acquisition >= 2003 |  | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | from the start of a year |
| S82 | Date of acquisition < 2003 |  | Persian necklace, Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe, Persian necklace - number 2, Bronze Mirror | before the start of a year, so the last day of 2002 |
| S83 | Date of acquisition <= 2002 |  | Persian necklace, Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe, Persian necklace - number 2, Bronze Mirror | up to the end of a year |
| S84 | Date of acquisition > 2020 |  | Viking Silver Armband, Candide - first edition | after a year |
| S85 | Date of acquisition >= 2020 |  | Roman Coin, Viking Silver Armband, Candide - first edition | from a year |
| S86 | Date of acquisition < 2000 |  | Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe | before a year, not blanks |
| S87 | Date of acquisition = December 2002 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | a month written out |
| S88 | Date of acquisition = January 2003 |  | Silk Scroll | the next month |
| S89 | Date of acquisition = 2002-12-31 |  | Bronze Mirror | a day |
| S90 | Date of acquisition = December 31 2002 |  | Bronze Mirror | a day written out |
| S91 | Date of acquisition = August 21 2020 |  | Roman Coin | a day written out |
| S92 | Date of acquisition LIKE 2002-12 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | part of a date |
| S93 | Date of acquisition NOT LIKE 2002 |  | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not part of a date, and blanks |
| S94 | Date of acquisition LIKE 05 |  | American Bicycle | any part of a date |
| S95 | Date of acquisition >= 1950; Date of acquisition <= 2002-12-02 |  | Persian necklace, Chinese Sword, Florentine Book, Japanese Coin, Polynesian Canoe, Persian necklace - number 2 | a range |
| S96 | Date of acquisition = {BLANK} |  | Unknown Shard | date, blank |
| S97 | Date of acquisition != {BLANK} |  | Everything except Unknown Shard | date, not blank |
| S98 | Date of acquisition = (empty) |  | Unknown Shard | date, no term |
| S99 | Date of acquisition LIKE (empty) |  | Unknown Shard | date, no term with LIKE |
| S100 | Date of acquisition NOT LIKE (empty) |  | Everything except Unknown Shard | date, no term with NOT LIKE |
| S101 | Date of acquisition = bread |  | Nothing | not a date |
| S102 | Date of acquisition != bread |  | Everything | not a date, so every entry is not it |
| S103 | Date of acquisition > bread |  | Nothing | not a date, nothing is after it |
| S104 | Date of acquisition <= bread |  | Nothing | not a date, nothing is before it |
| S105 | Date of acquisition = 2026-13-45 |  | Nothing | looks like a date, but is not one |
| S106 | Date of acquisition LIKE bread |  | Nothing | not a date, with LIKE |
| S107 | Date of acquisition NOT LIKE bread |  | Everything | not a date, with NOT LIKE |
| S108 | Date of acquisition IN bread |  | Nothing | a list with no dates in it |
| S109 | Date of acquisition IN 2020-08-21, bread, 2002-12-02 |  | Roman Coin, Persian necklace, Persian necklace - number 2 | a list, the term that is not a date is ignored |
| S110 | Era = BCE | Date of acquisition = bread; Date of acquisition > 2020 | Nothing | all and any, on terms that cannot match |
| S111 | creation_datetime = {BLANK} |  | Nothing | when an entry was made, never blank |
| S112 | creation_datetime != {BLANK} |  | Everything | when an entry was made |
| S113 | creation_datetime = bread |  | Nothing | not a date |
| S114 | creation_datetime != bread |  | Everything | not a date |
| S115 | creation_datetime LIKE bread |  | Nothing | not a date |
| S116 | creation_datetime >= 2000 |  | Everything | a year |
| S117 | creation_datetime = August 21 2020 |  | Nothing | a day when nothing was made |
| S118 | Era IN BCE |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | IN, one term |
| S119 | Short name IN Roman Coin, Chinese Sword |  | Roman Coin, Chinese Sword | IN, a list |
| S120 | Year IN 150, 198 |  | Roman Coin, Chinese Sword | IN, numbers |
| S121 | Short name IN Roman Coin, Chinese Sword; Era = BCE |  | Roman Coin | IN with another condition |
| S122 | Era = BCE; Donor != Freeform Solutions | Collections != Weapons; Date of acquisition = 2002; Latitude = {BLANK} | Roman Coin, Persian necklace, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | all and any together |
| S123 | Date of acquisition >= 1950; Date of acquisition <= 2002-12-02; Era != CE | Donor = {BLANK}; Donor NOT LIKE Solutions | Persian necklace, Persian necklace - number 2 | a range with a set of either |
| S124 | Collections NOT LIKE ns; Latitude != {BLANK} | Era = CE; Donor = {BLANK} | Florentine Book, American Bicycle, Polynesian Canoe, Viking Silver Armband | negatives and blanks in both sets |
| S125 | Date of acquisition != 2002; Collections = Weapons | Era != BCE; Date of acquisition < 2000 | Chinese Sword, French Musket | dates and linked in both sets |
| S126 | On loan != Yes; Provenance = {BLANK} | Materials = {BLANK}; Appraiser email = {BLANK} | Everything except Roman Coin, Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | blanks of every kind |
| S127 | Date of acquisition < {TODAY} |  | Everything except Unknown Shard | {TODAY}, and nothing is before a blank |
| S128 | Date of acquisition > {TODAY} |  | Nothing | nothing was acquired after today |
| S129 | creation_datetime <= {TODAY} |  | Everything | on or before a day is all of that day, so everything made today |
| S130 | creation_datetime > {TODAY} |  | Nothing | after a day is after the end of it |
| S131 | mod_datetime <= {TODAY} |  | Everything | when an entry was modified, all of today |
| S132 | mod_datetime > {TODAY} |  | Nothing | after the end of today |
| S133 | Location = {ID} |  | Everything | {ID} is the entry that's open, Main Vault |
| S134 | Location != {ID} |  | Nothing | not the entry that's open |
| S135 | Appraiser email < z |  | Roman Coin, Japanese Coin | text, '' is blank, and nothing is before a blank |
| S136 | Provenance > (empty) |  | Nothing | nothing is after a blank |
| S137 | Provenance > {BLANK} |  | Nothing | the same, with {BLANK} |
| S138 | Storage = Climate controlled |  | Roman Coin | text people see that's different from what's stored |
| S139 | Storage = 11 |  | Roman Coin | what's stored still works |
| S140 | Storage LIKE climate |  | Roman Coin | part of the text people see |
| S141 | Storage NOT LIKE climate |  | Everything except Roman Coin | not part of it, and blanks |
| S142 | Storage LIKE standard |  | Nothing | Standard is stored as 1, and the Roman Coin's value, 11, doesn't count |
| S143 | Handling = Two people to lift |  | Egyptian Chariot, Polynesian Canoe | checkboxes with text people see, a whole value |
| S144 | Handling = 1 |  | Roman Coin, Florentine Book, Silk Scroll | what's stored, so not Keep flat, which is 12 |
| S145 | Handling LIKE glove |  | Roman Coin, Florentine Book, Silk Scroll | part of the text people see |
| S146 | Handling NOT LIKE flat |  | Everything except Florentine Book, Candide - first edition, Silk Scroll | not part of it, and blanks |
| S147 | Catalogued by = Curator Two |  | Florentine Book, French Musket | a list of users, by the name people see |
| S148 | Catalogued by = {USER} |  | Roman Coin, Persian necklace, Babylonian Spoon | {USER} is curator1, Curator One |
| S149 | Catalogued by != {USER} |  | Everything except Roman Coin, Persian necklace, Babylonian Spoon | not curator1, and blanks |
| S150 | Handling LIKE 1 |  | Roman Coin, Florentine Book, Silk Scroll | a stored value, which is whole, so not Keep flat, which is stored as 12 |
| S151 | creation_datetime > 2000-01-01 00:00 |  | Everything | a term with a time is that moment |
| S152 | creation_datetime < 2000-01-01 00:00 |  | Nothing | the same, before it |

## 2. `{ }` in the Artifact list of a saved Loan Request

Set with `sourceEntryFilter` on `loans_artifact`, then open the Loan Request as curator1 and read the options offered. The borrower wants these, so the terms refer to the Loan Request. The artifact already selected is always offered.

| Row | Loan Request | All of | Any of | Should show | What it tests |
|---|---|---|---|---|---|
| R1 | City Museum | Era = {loans_era} |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | a radio, with a value |
| R2 | Harbour Gallery | Era = {loans_era} |  | Everything except Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | a radio, the other value |
| R3 | University Library | Era = {loans_era} |  | Florentine Book | a radio, blank, and no artifact has a blank era |
| R4 | City Museum | Era != {loans_era} |  | Everything except Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | != on a radio |
| R5 | University Library | Era != {loans_era} |  | Everything | != with a blank reference finds what is not blank |
| R6 | City Museum | Year > {loans_made_after} |  | Everything except Chinese Sword, Bronze Mirror | a number |
| R7 | Harbour Gallery | Year > {loans_made_after} |  | Roman Coin, Egyptian Chariot, Florentine Book, French Musket, Japanese Coin, American Bicycle, Polynesian Canoe, Candide - first edition | a number, a higher one |
| R8 | University Library | Year > {loans_made_after} |  | Florentine Book | nothing is after a blank |
| R9 | University Library | Latitude = {loans_made_after} |  | Florentine Book, Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | a blank number finds blanks, and not 0 |
| R10 | University Library | Latitude != {loans_made_after} |  | Everything except Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | a blank number with != |
| R11 | City Museum | Date of acquisition < {loans_start_date} |  | Everything except Unknown Shard | a date, not blanks |
| R12 | Harbour Gallery | Date of acquisition < {loans_start_date} |  | Everything except Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Unknown Shard, Silk Scroll | a date, the day before it counts |
| R13 | Harbour Gallery | Date of acquisition = {loans_start_date} |  | Roman Coin, Silk Scroll | a date, the same day |
| R14 | Harbour Gallery | Date of acquisition != {loans_start_date} |  | Everything except Silk Scroll | != on a date, and blanks |
| R15 | University Library | Date of acquisition = {loans_start_date} |  | Florentine Book, Unknown Shard | a blank date finds blanks |
| R16 | University Library | Date of acquisition != {loans_start_date} |  | Everything except Unknown Shard | a blank date with != |
| R17 | University Library | Date of acquisition > {loans_start_date} |  | Florentine Book | nothing is after a blank date |
| R18 | City Museum | Materials = {loans_materials} |  | Roman Coin, Persian necklace, Viking Silver Armband | checkboxes, one value |
| R19 | Harbour Gallery | Materials = {loans_materials} |  | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | checkboxes, any of several |
| R20 | Harbour Gallery | Materials != {loans_materials} |  | Everything except Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | checkboxes, none of several, and blanks |
| R21 | Harbour Gallery | Materials LIKE {loans_materials} |  | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | checkboxes with LIKE, so Cast iron too |
| R22 | Harbour Gallery | Materials NOT LIKE {loans_materials} |  | Everything except Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | checkboxes with NOT LIKE |
| R23 | University Library | Materials = {loans_materials} |  | Florentine Book, Japanese Coin, Persian necklace - number 2, Unknown Shard | blank checkboxes find blanks |
| R24 | University Library | Materials != {loans_materials} |  | Everything except Japanese Coin, Persian necklace - number 2, Unknown Shard | blank checkboxes with != |
| R25 | City Museum | Collections = {loans_collections} |  | Roman Coin, Japanese Coin | linked, several values, one to look for |
| R26 | Harbour Gallery | Collections = {loans_collections} |  | Everything except Persian necklace, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | linked, several values, any of several |
| R27 | Harbour Gallery | Collections != {loans_collections} |  | Roman Coin, Persian necklace, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | linked, none of several, and blanks |
| R28 | Harbour Gallery | Collections LIKE {loans_collections} |  | Everything except Persian necklace, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | linked, several values, LIKE |
| R29 | Harbour Gallery | Collections NOT LIKE {loans_collections} |  | Roman Coin, Persian necklace, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | linked, several values, NOT LIKE |
| R30 | University Library | Collections = {loans_collections} |  | Florentine Book, Unknown Shard | blank linked values find blanks |
| R31 | University Library | Collections != {loans_collections} |  | Everything except Unknown Shard | blank linked values with != |
| R32 | City Museum | Donor = {loans_donor} |  | Roman Coin, Babylonian Spoon, Polynesian Canoe, Candide - first edition | linked, one value |
| R33 | City Museum | Donor != {loans_donor} |  | Everything except Babylonian Spoon, Polynesian Canoe, Candide - first edition | linked, one value, and blanks |
| R34 | Harbour Gallery | Donor = {loans_donor} |  | Roman Coin, Silk Scroll | linked, an ampersand in the name |
| R35 | University Library | Donor = {loans_donor} |  | Egyptian Chariot, Florentine Book, French Musket, American Bicycle, Unknown Shard | a blank linked value finds blanks |
| R36 | University Library | Donor != {loans_donor} |  | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | a blank linked value with != |
| R37 | City Museum | Provenance = {loans_borrower} |  | Roman Coin, Babylonian Spoon | text |
| R38 | City Museum | Provenance != {loans_borrower} |  | Everything except Babylonian Spoon | text, and blanks |
| R39 | City Museum | Provenance LIKE {loans_borrower} |  | Roman Coin, Babylonian Spoon | text with LIKE |
| R40 | City Museum | Provenance NOT LIKE {loans_borrower} |  | Everything except Babylonian Spoon | text with NOT LIKE |
| R41 | Harbour Gallery | Provenance = {loans_borrower} |  | Roman Coin | text that matches nothing |
| R42 | City Museum | Donated to museum = {loans_approved} |  | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | Yes/No, a value that is Yes |
| R43 | Harbour Gallery | Donated to museum = {loans_approved} |  | Roman Coin, Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | Yes/No, a value that is No |
| R44 | City Museum | On loan != {loans_approved} |  | Everything except Florentine Book | Yes/No, !=, and blanks |
| R45 | University Library | On loan = {loans_approved} |  | Everything except Roman Coin, Chinese Sword, French Musket, Japanese Coin | a blank Yes/No finds blanks |
| R46 | University Library | On loan != {loans_approved} |  | Roman Coin, Chinese Sword, Florentine Book, French Musket, Japanese Coin | a blank Yes/No with != |
| R47 | Harbour Gallery | Era = {loans_era} | Collections = {loans_collections}; Short name = Roman Coin | Everything except Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | all and any, with references in both |
| R48 | Harbour Gallery | Collections = {loans_collections} | Collections != {loans_collections}; Era = BCE | Roman Coin, Egyptian Chariot | a reference and its opposite |
| R49 | City Museum | Era != {loans_era}; Donor != Freeform Solutions |  | Roman Coin, Florentine Book, French Musket, American Bicycle, Polynesian Canoe, Viking Silver Armband, Candide - first edition, Silk Scroll | a reference with a literal term |
| R50 | City Museum |  | Era != {loans_era}; Short name = Babylonian Spoon | Everything except Persian necklace, Egyptian Chariot, Persian necklace - number 2, Unknown Shard, Bronze Mirror | one of a reference and a literal term |
| R51 | University Library | On loan = {loans_approved} | Era = {loans_era}; Short name = Silk Scroll | Florentine Book, Silk Scroll | blank references, in both sets |
| R52 | University Library | Storage = {loans_storage} |  | Roman Coin, Florentine Book | text people see that's different from what's stored, and the value behind it is compared |
| R53 | University Library | Storage != {loans_storage} |  | Everything except Roman Coin | the same, with != |

## 3. `{ }` in a new Loan Request

The same setting, on a new entry. With nothing on screen, a list that depends on the `{ }` is empty, or the condition is ignored, as it says in [What the operators mean](#what-the-operators-mean). Then a person fills in what the row says, and the options change while the page is open, with nothing saved. A term in **On screen** like `Materials they can display: Iron + Wood` means those boxes, in that order. `(blank)` means clearing it again after a value.

| Row | All of | Any of | On screen | Should show | What it tests |
|---|---|---|---|---|---|
| N1 | Era = {loans_era} |  |  | Nothing | = finds nothing until there's a value |
| N2 | Era != {loans_era} |  |  | Everything | ignored with any other operator |
| N3 | Year > {loans_made_after} |  |  | Everything | ignored on a number |
| N4 | Date of acquisition < {loans_start_date} |  |  | Everything | ignored on a date |
| N5 | Materials = {loans_materials} |  |  | Nothing | = on checkboxes finds nothing until there's a value |
| N6 | Materials != {loans_materials} |  |  | Everything | ignored on checkboxes, with != |
| N7 | Collections = {loans_collections} |  |  | Nothing | empty until a choice is made, linked, several values |
| N8 | Collections != {loans_collections} |  |  | Nothing | empty until a choice is made, with != |
| N9 | Donor = {loans_donor} |  |  | Nothing | empty until a choice is made, linked, one value |
| N10 | Donor != {loans_donor} |  |  | Nothing | empty until a choice is made, with != |
| N11 | Era != {loans_era}; Collections = Weapons |  |  | Chinese Sword, Egyptian Chariot, French Musket | ignored, and the other condition still applies |
| N12 |  | Era = {loans_era}; Collections = Weapons |  | Chinese Sword, Egyptian Chariot, French Musket | finds nothing in the set of either, and the other still applies |
| N13 | Era = {loans_era} |  | Era wanted: CE | Everything except Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | a radio on screen |
| N14 | Era != {loans_era} |  | Era wanted: CE | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | != on screen |
| N15 | Year > {loans_made_after} |  | Made after year: 1500 | Egyptian Chariot, Florentine Book, French Musket, Japanese Coin, American Bicycle, Polynesian Canoe, Candide - first edition | a number on screen |
| N16 | Date of acquisition < {loans_start_date} |  | Start date: 2003-01-01 | Persian necklace, Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe, Persian necklace - number 2, Bronze Mirror | a date on screen |
| N17 | Date of acquisition = {loans_start_date} |  | Start date: 2003-01-01 | Silk Scroll | the same date on screen |
| N18 | Date of acquisition != {loans_start_date} |  | Start date: 2003-01-01 | Everything except Silk Scroll | != with a date on screen |
| N19 | Date of acquisition = {loans_start_date} |  | Start date: (blank) | Nothing | a date cleared on screen has no value, so = finds nothing, as master does |
| N20 | Date of acquisition != {loans_start_date} |  | Start date: (blank) | Everything | != with a date cleared on screen ignores the condition |
| N21 | Materials = {loans_materials} |  | Materials they can display: Iron + Wood | Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | checkboxes on screen |
| N22 | Materials != {loans_materials} |  | Materials they can display: Iron + Wood | Everything except Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | != with checkboxes on screen |
| N23 | Materials LIKE {loans_materials} |  | Materials they can display: Iron + Wood | Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | LIKE with checkboxes on screen |
| N24 | Materials NOT LIKE {loans_materials} |  | Materials they can display: Iron + Wood | Everything except Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | NOT LIKE with checkboxes on screen |
| N25 | Materials = {loans_materials} |  | Materials they can display: (blank) | Nothing | checkboxes cleared on screen have no value, as master does |
| N26 | Collections = {loans_collections} |  | Collections wanted: Weapons + Coins | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | linked checkboxes on screen |
| N27 | Collections = {loans_collections} |  | Collections wanted: Coins + Weapons | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | the other order finds the same |
| N28 | Collections != {loans_collections} |  | Collections wanted: Weapons + Coins | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | != with linked checkboxes on screen |
| N29 | Collections LIKE {loans_collections} |  | Collections wanted: Weapons + Coins | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | LIKE with linked checkboxes on screen |
| N30 | Collections NOT LIKE {loans_collections} |  | Collections wanted: Weapons + Coins | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | NOT LIKE with linked checkboxes on screen |
| N31 | Collections = {loans_collections} |  | Collections wanted: (blank) | Nothing | linked checkboxes cleared on screen have no value, as master does |
| N32 | Collections != {loans_collections} |  | Collections wanted: (blank) | Everything | != with linked checkboxes cleared ignores the condition, unlike N8, where nothing was chosen yet |
| N33 | Donor != {loans_donor} |  | Donor contact: Freeform Solutions | Everything except Chinese Sword, Japanese Coin | linked on screen |
| N34 | Donor = {loans_donor} |  | Donor contact: Hart & Sons Antiques | Silk Scroll | an ampersand on screen |
| N35 | Provenance != {loans_borrower} |  | Borrower: Bought in Cairo | Everything except Egyptian Chariot | text on screen |
| N36 |  | Provenance != {loans_borrower}; Era = BCE | Borrower: Bought in Cairo | Everything | one of a reference on screen and a literal term |
| N37 | Donated to museum = {loans_approved} |  | Approved: Yes | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | Yes on screen |
| N38 | Donated to museum != {loans_approved} |  | Approved: Yes | Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | != with Yes on screen |
| N39 | Donor = {loans_borrower} |  |  | Nothing | linked, one value, and the `{ }` isn't linked |
| N40 | Donor != {loans_borrower} |  |  | Nothing | the same, with != |
| N41 | Storage = {loans_storage} |  |  | Roman Coin | the default, Climate controlled, before anything is chosen |
| N42 | Storage != {loans_storage} |  |  | Everything except Roman Coin | the default, with != |
| N43 | Storage = {loans_storage} |  | Storage they have: Standard | Nothing | what's chosen on screen, rather than the default |
| N44 | Collections = {loans_borrower} |  |  | Nothing | linked, several values, and the `{ }` isn't linked |
| N45 | Collections != {loans_borrower} |  |  | Nothing | the same, with != |
| N46 | Era IN {loans_era} |  |  | Everything | IN is ignored too, until there's a value |
| N47 | Era IN {loans_era} |  | Era wanted: CE | Everything except Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | IN with a value on screen |

## 4. Other surfaces

**The Loan requests subform in an Artifact.** Set with `sourceEntryFilter` on `artifacts_subform_loan_requests`, and read on the artifact's Location and loans tab. A subform only shows entries connected to the entry that's open, so these rows are about the Loan Requests for that artifact. Donor contact and the artifact's Donor are both lists of donors, so L14 to L19 find the loan requests whose contact is the artifact's own donor.

| Row | Artifact | All of | Any of | Should show | What it tests |
|---|---|---|---|---|---|
| L1 | Roman Coin | Approved = {artifacts_on_loan} |  | City Museum | Yes/No, a reference to the artifact |
| L2 | Roman Coin | Approved != {artifacts_on_loan} |  | Harbour Gallery | the opposite |
| L3 | Florentine Book | Approved = {artifacts_on_loan} |  | Nothing | a blank field is not Yes |
| L4 | Florentine Book | Approved != {artifacts_on_loan} |  | Everything | a blank field is not Yes, with != |
| L5 | Unknown Shard | Approved = {artifacts_on_loan} |  | Regional Archive | a blank reference finds blanks |
| L6 | Unknown Shard | Approved != {artifacts_on_loan} |  | Town Hall | a blank reference with != |
| L7 | Roman Coin | Start date > {artifacts_date_of_acquisition} |  | City Museum | a date reference |
| L8 | Roman Coin | Start date <= {artifacts_date_of_acquisition} |  | Harbour Gallery | a date reference, the other way |
| L9 | Florentine Book | Start date > {artifacts_date_of_acquisition} |  | Nothing | a blank field is not after a date |
| L10 | Unknown Shard | Start date > {artifacts_date_of_acquisition} |  | Nothing | nothing is after a blank reference |
| L11 | Roman Coin | Approved = Yes |  | City Museum | a literal term |
| L12 | Unknown Shard | Approved != Yes |  | Everything | a literal term, with !=, and blanks |
| L13 | Unknown Shard | Approved = {BLANK} |  | Regional Archive | {BLANK} |
| L14 | Roman Coin | Donor contact = {artifacts_donor} |  | City Museum | linked, and the reference is linked to the same form |
| L15 | Roman Coin | Donor contact != {artifacts_donor} |  | Harbour Gallery | the opposite |
| L16 | Florentine Book | Donor contact = {artifacts_donor} |  | Nothing | a blank field isn't the artifact's donor |
| L17 | Florentine Book | Donor contact != {artifacts_donor} |  | Everything | a blank field isn't the artifact's donor, with != |
| L18 | Unknown Shard | Donor contact = {artifacts_donor} |  | Everything | a blank linked reference finds blanks |
| L19 | Unknown Shard | Donor contact != {artifacts_donor} |  | Nothing | a blank linked reference, with != |

**The Donor contact list in a new Loan Request.** Set with `sourceEntryFilter` on `loans_donor`.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| D1 | First name != Emilie; Email Address != nobody; Is the donor anonymous? != Yes |  | Everything except Emilie Du Châtelet | three negatives, with blanks of each kind |
| D2 | First name != Emilie; Is the donor anonymous? != Yes; Email Address = formulize@freeform.ca |  | Freeform Solutions | negatives with one that must match |
| D3 |  | First name != Emilie; First name != François-Marie | Everything | one of two negatives |
| D4 | First name != Emilie; First name != François-Marie |  | Everything except François-Marie Arouet, Emilie Du Châtelet | both of two negatives |
| D5 |  | First name = {BLANK}; Email Address != {BLANK} | Everything except Aoife O'Brien | blank and not blank |
| D6 |  | First name = {BLANK}; Email Address = {BLANK} | Everything except François-Marie Arouet, Emilie Du Châtelet | blank, NULL in one and '' in the other |
| D7 | First name != {BLANK}; Email Address != {BLANK} |  | François-Marie Arouet, Emilie Du Châtelet | not blank, NULL in one and '' in the other |
| D8 | First name != {BLANK}; Is the donor anonymous? != Yes | Email Address = {BLANK}; First name != Emilie | François-Marie Arouet, Aoife O'Brien | all and any |
| D9 | Last name = {BLANK} | Email Address != {BLANK}; Is the donor anonymous? = No | Freeform Solutions, L. Warner Foundation | all and any, with blanks |
| D10 | Type of donor = Organization |  | Freeform Solutions, L. Warner Foundation, Hart & Sons Antiques | a radio |
| D11 | Name = Aoife O'Brien |  | Aoife O'Brien | a quote in the term |
| D12 | Name = Hart & Sons Antiques |  | Hart & Sons Antiques | an ampersand in the term |
| D13 | Name LIKE & |  | Hart & Sons Antiques | just the ampersand |
| D14 | Name != Hart & Sons Antiques |  | Everything except Hart & Sons Antiques | not that name |
| D15 | Province = Ontario |  | Freeform Solutions | a province by its name, which is stored as a code |
| D16 | Province != Quebec |  | Everything except François-Marie Arouet, Emilie Du Châtelet | not that province, and blanks |

**The Collections wanted boxes in a new Loan Request.** Set with `sourceEntryFilter` on `loans_collections`.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| C1 | Suitable audience != Children |  | Coins | checkboxes |
| C2 | Suitable audience != Children; Name != Coins |  | Nothing | nothing left |
| C3 |  | Suitable audience != Children; Name != Coins | Everything | one of two |
| C4 | Suitable audience = Adults; Suitable audience NOT LIKE Child |  | Coins | has one and not the other |
| C5 | Suitable audience != Children | Name LIKE History; Name = Coins; Suitable audience = {BLANK} | Coins | all and any |

**The Follow up on list in a Visit.** Set with `sourceEntryFilter` on `visits_follow_up`, and read on the visit. Visits are donors' visits to the museum, and Offers are things they offer to give. When a donor comes back, Follow up on lists the offers that can be followed up. An offer's Visit is a list of visits, and the `{ }` refers to the visit that's open, so a condition compares the offer's visit with this one. `Visit = {visits_donor}` is an offer made at any visit by the same donor as this one. `Visit = {visits_name}` is an offer made at a visit with this visit's name, which means this visit. Nothing is chosen in Follow up on, so the list holds exactly what the condition finds. Visits and Offers are the only forms in the museum with a link each way, and nothing else uses them.

| Row | Visit | All of | Any of | Should show | What it tests |
|---|---|---|---|---|---|
| O1 | Arouet in April | Visit = {visits_donor} |  | Letters, Snuff box | linked, through the visit to its donor |
| O2 | Arouet in April | Visit != {visits_donor} |  | Telescope, Unlabelled box, Sketchbook | the opposite, and an offer with no visit. Master reads != as = here, so it shows what O1 does |
| O3 | Arouet in March | Visit = {visits_name} |  | Letters | not linked, the field the Visit list shows |
| O4 | Arouet in March | Visit != {visits_name} |  | Everything except Letters | the opposite, and an offer with no visit |
| O5 | Walk-in in April | Visit = {visits_donor} |  | Nothing | a visit with no donor has no one to match, which is what master does |
| O6 | Walk-in in April | Visit != {visits_donor} |  | Everything | the opposite. Master finds nothing, since it reads != as = here, as in O2 |
| O7 | Arouet in March |  | Visit = {visits_name}; Item = Sketchbook | Letters, Sketchbook | the field the Visit list shows, in Any of, beside a literal term |
| O8 | Arouet in March |  | Visit != {visits_name}; Item = Letters | Everything | the opposite, in Any of |

**Values in the page's address.** Set with `sourceEntryFilter` on `locations_subform_artifacts`, then open Main Vault with the row's values on the end of the address.

| Row | In the address | All of | Any of | Should show | What it tests |
|---|---|---|---|---|---|
| Q1 | place=Cairo | Provenance LIKE {place} |  | Egyptian Chariot | a value from the address |
| Q2 | place=Cairo | Provenance NOT LIKE {place} |  | Everything except Egyptian Chariot | the same, with NOT LIKE, and blanks |
| Q3 | place= | Provenance LIKE {place} |  | Everything except Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | an empty value is a search for blanks |
| Q4 |  | Provenance LIKE {place} |  | Everything | no such value, so the condition is ignored |
| Q5 | era=BCE | Era = {era} |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | = with a value from the address |

## 5. Display conditions

Set with `display_conditions` on `artifacts_curator_note`, then open each artifact in Open on its Location and loans tab as curator1. Should show is the ones Curator note is on. Blanks are the ones that go wrong, so Unknown Shard is used wherever it can be.

| Row | All of | Any of | Open | Should show | What it tests |
|---|---|---|---|---|---|
| V1 | Provenance = Bought in Cairo |  | Egyptian Chariot, Unknown Shard | Egyptian Chariot | text |
| V2 | Provenance != Bought in Cairo |  | Unknown Shard, Egyptian Chariot | Unknown Shard | != on a blank |
| V3 | Provenance NOT LIKE Cairo |  | Unknown Shard, Egyptian Chariot | Unknown Shard | NOT LIKE on a blank |
| V4 | Provenance = {BLANK} |  | Unknown Shard, French Musket | Unknown Shard | blank |
| V5 | Provenance != {BLANK} |  | French Musket, Unknown Shard | French Musket | not blank |
| V6 | Provenance = (empty) |  | Unknown Shard, French Musket | Unknown Shard | no term |
| V7 | Appraiser email = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | blank, NULL and '' |
| V8 | Appraiser email != {BLANK} |  | Roman Coin, Unknown Shard | Roman Coin | not blank, NULL and '' |
| V9 | Latitude != 0 |  | Unknown Shard | Unknown Shard | number |
| V10 | Latitude < 50 |  | Roman Coin, Unknown Shard | Roman Coin | a blank is not less |
| V11 | Latitude = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | a blank number |
| V12 | Era = BCE |  | Unknown Shard, Chinese Sword | Unknown Shard | radio |
| V13 | Era != CE |  | Unknown Shard, Chinese Sword | Unknown Shard | radio, != |
| V14 | On loan = Yes |  | Roman Coin, Unknown Shard | Roman Coin | Yes/No, by what people see |
| V15 | On loan != Yes |  | Unknown Shard, Roman Coin | Unknown Shard | Yes/No, != |
| V16 | On loan = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | Yes/No, blank |
| V17 | Materials = Iron |  | Chinese Sword, Unknown Shard | Chinese Sword | checkboxes, a whole value |
| V18 | Materials LIKE Iron |  | Chinese Sword, Unknown Shard | Chinese Sword | checkboxes, part of a value |
| V19 | Materials != Iron |  | Unknown Shard, Chinese Sword | Unknown Shard | checkboxes, none is Iron |
| V20 | Materials = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | checkboxes, blank |
| V21 | Materials != {BLANK} |  | Roman Coin, Unknown Shard | Roman Coin | checkboxes, not blank |
| V22 | Donor = Freeform Solutions |  | Chinese Sword, Unknown Shard | Chinese Sword | linked |
| V23 | Donor != Freeform Solutions |  | Unknown Shard, Chinese Sword | Unknown Shard | linked, != |
| V24 | Donor = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | linked, blank |
| V25 | Donor != {BLANK} |  | Roman Coin, Unknown Shard | Roman Coin | linked, not blank |
| V26 | Donor = Aoife O'Brien |  | Bronze Mirror, Unknown Shard | Bronze Mirror | a quote |
| V27 | Donor = Hart & Sons Antiques |  | Silk Scroll, Unknown Shard | Silk Scroll | an ampersand |
| V28 | Collections = Weapons |  | Chinese Sword, Unknown Shard | Chinese Sword | linked, several values |
| V29 | Collections != Weapons |  | Unknown Shard, Chinese Sword | Unknown Shard | linked, several values, != |
| V30 | Collections LIKE ns |  | Roman Coin, Unknown Shard | Roman Coin | linked, part of a name |
| V31 | Collections = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | linked, several values, blank |
| V32 | Date of acquisition = 2002 |  | Bronze Mirror, Unknown Shard | Bronze Mirror | a year |
| V33 | Date of acquisition != 2002 |  | Unknown Shard, Bronze Mirror | Unknown Shard | not in a year |
| V34 | Date of acquisition > 2002 |  | Roman Coin, Unknown Shard | Roman Coin | after a year |
| V35 | Date of acquisition = {BLANK} |  | Unknown Shard, Roman Coin | Unknown Shard | date, blank |
| V36 | Date of acquisition = bread |  | Unknown Shard | Nothing | not a date, hidden everywhere |
| V37 | Date of acquisition != bread |  | Unknown Shard | Unknown Shard | not a date, shown everywhere |
| V38 | Era = BCE | Donor = {BLANK}; Donor NOT LIKE Solutions | Unknown Shard, Chinese Sword | Unknown Shard | all and any |
| V39 |  | Provenance = {BLANK}; Era = CE | Unknown Shard, Egyptian Chariot | Unknown Shard | either |
| V40 | Donated to museum = {artifacts_on_loan} |  | Roman Coin, Chinese Sword | Roman Coin | a reference to another field of the same artifact |
| V41 | Donated to museum != {artifacts_on_loan} |  | Chinese Sword, Roman Coin | Chinese Sword | the same, with != |
| V42 | Year > {artifacts_latitude} |  | Roman Coin, Unknown Shard | Roman Coin | nothing is after a blank reference |
| V43 | Appraiser email < z |  | Roman Coin, Chinese Sword | Roman Coin | text, '' is blank, and nothing is before a blank |
| V44 | Date of acquisition < {TODAY} |  | Roman Coin, Unknown Shard | Roman Coin | {TODAY} |
| V45 | creation_datetime <= {TODAY} |  | Roman Coin | Roman Coin | when an entry was made, all of today |
| V46 | creation_datetime > {TODAY} |  | Roman Coin | Nothing | after the end of today |
| V47 | Storage = Climate controlled |  | Roman Coin, Unknown Shard | Roman Coin | text people see that's different from what's stored |
| V48 | Storage LIKE climate |  | Roman Coin, Chinese Sword | Roman Coin | part of the text people see |
| V49 | Handling LIKE glove |  | Florentine Book, Egyptian Chariot | Florentine Book | checkboxes with text people see |
| V50 | Catalogued by = {USER} |  | Roman Coin, Florentine Book | Roman Coin | a list of users, and {USER} |
| V51 | Materials = {artifacts_materials} |  | French Musket, Persian necklace | Everything | a reference that holds several values, on a field that holds them too |
| V52 | Materials != {artifacts_materials} |  | Persian necklace, French Musket | Nothing | the opposite |
| V53 | Era != {artifacts_materials} |  | Persian necklace, Egyptian Chariot | Everything | a field with one value, and a reference with several, none of them the same |
| V54 | Era = {artifacts_materials} |  | Egyptian Chariot, Persian necklace | Nothing | the same, with = |
| V55 | Materials LIKE {artifacts_materials} |  | French Musket, Egyptian Chariot | Everything | LIKE, with several values on both sides |
| V56 | Materials != {artifacts_handling} |  | French Musket, Persian necklace | Everything | a blank reference, so != finds what isn't blank |

## 6. Fundamental filters

Set with `fundamental_filters` on the list screen. Lists go through a different path from the sections above. On the Donor artifacts list, the conditions on artifacts are checked through the connection, so a donor is listed if any one of their artifacts meets them. A donor with no artifacts has none that can. `EMPTYSET:` means no connected entry has that value, which includes a donor with no artifacts.

| Row | List | All of | Any of | Should show | What it tests |
|---|---|---|---|---|---|
| F1 | Catalogue | Era = BCE |  | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | a radio |
| F2 | Catalogue | On loan != Yes |  | Everything except Roman Coin, Florentine Book | Yes/No, and blanks |
| F3 | Catalogue | Provenance = {BLANK} |  | Everything except Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | blank |
| F4 | Catalogue | Appraiser email = {BLANK} |  | Everything except Roman Coin, Japanese Coin | blank, NULL and '' |
| F5 | Catalogue | Materials = Iron |  | Chinese Sword, French Musket | checkboxes, a whole value |
| F6 | Catalogue | Materials != Iron |  | Everything except Chinese Sword, French Musket | checkboxes, and blanks |
| F7 | Catalogue | Donor != Freeform Solutions |  | Everything except Chinese Sword, Japanese Coin | linked, and blanks |
| F8 | Catalogue | Collections != Weapons |  | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, and blanks |
| F9 | Catalogue | Date of acquisition > 2002 |  | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | a year |
| F10 | Catalogue | Date of acquisition != bread |  | Everything | not a date |
| F11 | Catalogue | Era = BCE | Donor = {BLANK}; Donor NOT LIKE Solutions | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | all and any |
| F12 | Donor artifacts | Era = BCE |  | François-Marie Arouet, Emilie Du Châtelet, Aoife O'Brien | an artifact has it |
| F13 | Donor artifacts | Era != CE |  | François-Marie Arouet, Emilie Du Châtelet, Aoife O'Brien | an artifact does not have it |
| F14 | Donor artifacts | Collections != Weapons |  | Everything except L. Warner Foundation | an artifact is not in Weapons, so Freeform Solutions is listed, though another of theirs is |
| F15 | Donor artifacts | Date of acquisition != 2002; Era = BCE |  | François-Marie Arouet | both on the same artifact |
| F16 | Donor artifacts |  | Collections = Weapons; Date of acquisition < 1960 | Freeform Solutions | one or the other |
| F17 | Donor artifacts | First name != {BLANK}; Date of acquisition = 2002 |  | Emilie Du Châtelet, Aoife O'Brien | a condition on the donor, and one on the artifact |
| F18 | Donor artifacts | Era = CE | Email Address = {BLANK}; Collections NOT LIKE ns | François-Marie Arouet, Emilie Du Châtelet, Hart & Sons Antiques | all and any, on the donor and the artifact |
| F19 | Donor artifacts | Era = EMPTYSET:CE |  | L. Warner Foundation, Aoife O'Brien | no artifact is CE |
| F20 | Donor artifacts | Era = EMPTYSET:BCE |  | Freeform Solutions, L. Warner Foundation, Hart & Sons Antiques | no artifact is BCE |
| F21 | Donor artifacts | Collections = EMPTYSET:Weapons |  | Everything except Freeform Solutions | no artifact is in Weapons, on a linked element |
| F22 | Donor artifacts | First name != {BLANK}; Era = EMPTYSET:BCE |  | Nothing | EMPTYSET with a condition on the donor |
| F23 | Catalogue | Storage LIKE climate |  | Roman Coin | text people see that's different from what's stored |
| F24 | Catalogue | Handling = Gloves |  | Roman Coin, Florentine Book, Silk Scroll | checkboxes, by the text people see |
| F25 | Catalogue | creation_datetime <= {TODAY} |  | Everything | when an entry was made, all of today |
| F26 | Catalogue | Catalogued by = {USER} |  | Roman Coin, Persian necklace, Babylonian Spoon | a list of users, and {USER} |
## 7. Search boxes on a list

As curator1. Each term goes in the search box at the top of that column, on the Catalogue list, the Donor artifacts list, or the Artifacts list. The Artifacts list is the Artifacts form's own list, from the menu, and it doesn't have Date of acquisition, so that column is added in Change columns each time it's opened. A column followed by picked has a date range, which opens from a button in its search cell, and the term is the search its From and To shortcuts make: `<={TODAY}` is Clear under From and Today under To. A picked search is the only one in its row. Creation date is typed into on the Catalogue, and picked from on Donor artifacts. A term starting with `!` means the value doesn't have it, and `!=` means it isn't exactly that. Both find blanks, since they aren't a search for a value. `!{BLANK}` is not blank. `OR` in front of terms in several boxes means any one of them.

| Row | List | Typed in | Should show | What it tests |
|---|---|---|---|---|
| B1 | Catalogue | Date of acquisition: `bread` | Nothing | a term that cannot be a date |
| B2 | Catalogue | Date of acquisition: `2002` | Persian necklace, Persian necklace - number 2, Bronze Mirror | a year, including its last day |
| B3 | Catalogue | Date of acquisition: `2003` | Silk Scroll | a year, including its first day |
| B4 | Catalogue | Date of acquisition: `2002-12` | Persian necklace, Persian necklace - number 2, Bronze Mirror | a year and month |
| B5 | Catalogue | Date of acquisition: `December 2002` | Persian necklace, Persian necklace - number 2, Bronze Mirror | a month written out |
| B6 | Catalogue | Date of acquisition: `August 21 2020` | Roman Coin | a date written out |
| B7 | Catalogue | Date of acquisition: `05` | American Bicycle | part of a date |
| B8 | Catalogue | Date of acquisition: `>2002` | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | after the end of a year |
| B9 | Catalogue | Date of acquisition: `>=2003` | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | from the start of a year |
| B10 | Catalogue | Date of acquisition: `<2003` | Persian necklace, Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe, Persian necklace - number 2, Bronze Mirror | before the start of a year, not blanks |
| B11 | Catalogue | Date of acquisition: `>2020` | Viking Silver Armband, Candide - first edition | after a year |
| B12 | Catalogue | Date of acquisition: `>=2020` | Roman Coin, Viking Silver Armband, Candide - first edition | on or after a year |
| B13 | Catalogue | Date of acquisition: `!2002` | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not in a year, and blanks |
| B14 | Catalogue | Date of acquisition: `between 1950-01-01 and 2002-12-02` | Persian necklace, Chinese Sword, Florentine Book, Japanese Coin, Polynesian Canoe, Persian necklace - number 2 | a range |
| B15 | Catalogue | Date of acquisition: `{BLANK}` | Unknown Shard | blank |
| B16 | Catalogue | Date of acquisition: `!{BLANK}` | Everything except Unknown Shard | not blank |
| B17 | Catalogue | Date of acquisition: `=bread` | Nothing | = a term that cannot be a date |
| B18 | Catalogue | Date of acquisition: `!=bread` | Everything | != a term that cannot be a date |
| B19 | Catalogue | Date of acquisition: `>bread` | Nothing | > a term that cannot be a date |
| B20 | Catalogue | Date of acquisition: `!bread` | Everything | not a term that cannot be a date |
| B21 | Catalogue | creation_datetime: `{BLANK}` | Nothing | never blank |
| B22 | Catalogue | creation_datetime: `!{BLANK}` | Everything | never blank |
| B23 | Catalogue | creation_datetime: `=bread` | Nothing | not a date |
| B24 | Catalogue | creation_datetime: `>bread` | Nothing | not a date |
| B25 | Catalogue | creation_datetime: `!=bread` | Everything | not a date |
| B26 | Catalogue | creation_datetime: `>2000` | Everything | after a year |
| B27 | Catalogue | Provenance: `!Cairo` | Everything except Egyptian Chariot | text, and blanks |
| B28 | Catalogue | Provenance: `!=Bought in Cairo` | Everything except Egyptian Chariot | text, exact, and blanks |
| B29 | Catalogue | Provenance: `Sotheby's` | Florentine Book | a quote |
| B30 | Catalogue | Provenance: `!{BLANK}` | Egyptian Chariot, Babylonian Spoon, Florentine Book, French Musket | not blank |
| B31 | Catalogue | Appraiser email: `!museum` | Everything except Roman Coin, Japanese Coin | text, and NULL and '' |
| B32 | Catalogue | Appraiser email: `{BLANK}` | Everything except Roman Coin, Japanese Coin | NULL and '' |
| B33 | Catalogue | Appraiser email: `!{BLANK}` | Roman Coin, Japanese Coin | neither NULL nor '' |
| B34 | Catalogue | Latitude: `!=41.9028` | Everything except Roman Coin | a number, and blanks |
| B35 | Catalogue | Latitude: `!{BLANK}` | Everything except Candide - first edition, Unknown Shard, Bronze Mirror, Silk Scroll | a number, not blank |
| B36 | Catalogue | On loan: `!=Yes` | Everything except Roman Coin, Florentine Book | Yes/No, and blanks |
| B37 | Catalogue | On loan: `!=maybe` | Everything | Yes/No, not a value it can have |
| B38 | Catalogue | Materials: `!=Iron` | Everything except Chinese Sword, French Musket | checkboxes, a whole value, and blanks |
| B39 | Catalogue | Materials: `!Iron` | Everything except Chinese Sword, French Musket, American Bicycle | checkboxes, part of a value, and blanks |
| B40 | Catalogue | Materials: `iron` | Chinese Sword, French Musket, American Bicycle | checkboxes, part of a value |
| B41 | Catalogue | Materials: `{BLANK}` | Japanese Coin, Persian necklace - number 2, Unknown Shard | checkboxes, blank |
| B42 | Catalogue | Donor: `!Freeform` | Everything except Chinese Sword, Japanese Coin | linked, and blanks |
| B43 | Catalogue | Donor: `!=Freeform Solutions` | Everything except Chinese Sword, Japanese Coin | linked, exact, and blanks |
| B44 | Catalogue | Donor: `!{BLANK}` | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | linked, not blank |
| B45 | Catalogue | Donor: `O'Brien` | Bronze Mirror | linked, a quote |
| B46 | Catalogue | Donor: `Hart & Sons` | Silk Scroll | linked, an ampersand |
| B47 | Catalogue | Collections: `!Weapons` | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, and blanks |
| B48 | Catalogue | Collections: `!=Weapons` | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, exact, and blanks |
| B49 | Catalogue | Collections: `!ns` | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | linked, several values, part of a name, so none of Coins or Weapons |
| B50 | Catalogue | Collections: `!=4` | Everything except Chinese Sword, Egyptian Chariot, French Musket | linked, several values, the entry id of Weapons |
| B51 | Catalogue | Donor: `!Freeform`<br>Era: `=CE` | Florentine Book, French Musket, American Bicycle, Polynesian Canoe, Viking Silver Armband, Candide - first edition, Silk Scroll | two boxes |
| B52 | Catalogue | Short name: `ORRoman`<br>Date of acquisition: `ORRoman` | Roman Coin | a word in another column finds it, and the date column does not match everything |
| B53 | Catalogue | Short name: `ORzzz`<br>Date of acquisition: `ORzzz` | Nothing | a word in no column finds nothing |
| B54 | Catalogue | Short name: `OR2002`<br>Date of acquisition: `OR2002` | Persian necklace, Persian necklace - number 2, Bronze Mirror | a year finds it in the date column |
| B55 | Donor artifacts | First name: `!Emilie` | Everything except Emilie Du Châtelet | text |
| B56 | Donor artifacts | First name: `!=Emilie` | Everything except Emilie Du Châtelet | text, exact |
| B57 | Donor artifacts | First name: `Emilie` | Emilie Du Châtelet | a positive search |
| B58 | Donor artifacts | First name: `!{BLANK}` | François-Marie Arouet, Emilie Du Châtelet, Aoife O'Brien | not blank |
| B59 | Donor artifacts | First name: `!Emilie AND !Fran` | Everything except François-Marie Arouet, Emilie Du Châtelet | two terms in a box |
| B60 | Donor artifacts | Email Address: `!freeform` | Everything except Freeform Solutions | and NULL and '' |
| B61 | Donor artifacts | Is the donor anonymous?: `!No` | Everything except L. Warner Foundation | a radio |
| B62 | Donor artifacts | Province: `!Yukon` | Everything | a province no one has |
| B63 | Donor artifacts | Province: `!a` | Everything except Freeform Solutions, Hart & Sons Antiques | a province, a term that matches one of them |
| B64 | Donor artifacts | First name: `!Emilie`<br>Email Address: `!freeform` | Everything except Emilie Du Châtelet, Freeform Solutions | two boxes |
| B65 | Donor artifacts | First name: `OR!Emilie`<br>Last name: `ORArouet` | Everything except Emilie Du Châtelet | OR terms in different boxes |
| B66 | Donor artifacts | Collections: `!Weapons` | Everything except L. Warner Foundation | connected: an artifact is not in Weapons, so Freeform Solutions is found, though another of theirs is. L. Warner Foundation has no artifacts, so is not |
| B67 | Donor artifacts | Latitude: `!=41.9028` | Everything except L. Warner Foundation | connected: an artifact has no latitude, or another one |
| B68 | Catalogue | Storage: `climate` | Roman Coin | text people see that's different from what's stored, part of it |
| B69 | Catalogue | Storage: `!climate` | Everything except Roman Coin | not part of that text, and blanks |
| B70 | Catalogue | Storage: `standard` | Nothing | Standard is stored as 1, and the Roman Coin's value is 11, which doesn't count |
| B71 | Donor artifacts | creation_datetime, picked: `<={TODAY}` | Everything | on or before a day is all of that day, picked from the date range |
| B72 | Catalogue | mod_datetime, picked: `<={TODAY}` | Everything | when an entry was modified, picked from the date range |
| B73 | Catalogue | Appraiser email: `<z` | Roman Coin, Japanese Coin | text, '' is blank, and nothing is before a blank |
| B74 | Catalogue | Handling: `glove` | Roman Coin, Florentine Book, Silk Scroll | checkboxes with text people see |
| B75 | Catalogue | Handling: `=1` | Roman Coin, Florentine Book, Silk Scroll | what's stored, so not Keep flat, which is 12 |
| B76 | Catalogue | Handling: `!flat` | Everything except Florentine Book, Candide - first edition, Silk Scroll | not part of it, and blanks |
| B77 | Catalogue | Catalogued by: `Curator Two` | Florentine Book, French Musket | a list of users, by the name people see |
| B78 | Donor artifacts | Province: `Ontario` | Freeform Solutions | a whole value, so not Prince Edward Island, which is 10 |
| B79 | Catalogue | On loan: `Yes` | Roman Coin, Florentine Book | Yes/No, by what people see |
| B80 | Catalogue | On loan: `No` | Chinese Sword, French Musket, Japanese Coin | Yes/No, by what people see |
| B81 | Artifacts | Date of acquisition: `2011` | Egyptian Chariot | a year, in a column added with Change columns |
| B82 | Artifacts | Date of acquisition: `August 1993` | Chinese Sword | a month written out, in a column added with Change columns |
| B83 | Artifacts | Date of acquisition: `>=2020` | Roman Coin, Viking Silver Armband, Candide - first edition | on or after a year, in a column added with Change columns |
| B84 | Artifacts | Date of acquisition: `bread` | Nothing | a term that cannot be a date, in a column added with Change columns |
| B85 | Catalogue | Handling: `1` | Roman Coin, Florentine Book, Silk Scroll | a stored value, which is whole, so not Keep flat, which is stored as 12 |
| B86 | Catalogue | creation_datetime: `>2000-01-01 00:00` | Everything | a term with a time is that moment |

## 8. Settings MCP can't reach

MCP has no setting for these, so the spec types each row's conditions into the admin UI, as a webmaster does, saving each one, and takes them away again with each one's X. A row with the same conditions as the row before it uses them as they are. The admin UI calls `!=` NOT.

**A disabled condition.** End date in Loan Requests is disabled for all groups when its conditions are met. Should show is the Loan Requests where End date can be edited.

| Row | All of | Any of | Open | Should show | What it tests |
|---|---|---|---|---|---|
| A1 | Approved != Yes |  | City Museum | City Museum | a disabled condition that isn't met |
| A2 | Approved != Yes |  | Harbour Gallery | Nothing | a disabled condition that's met |
| A3 | Approved != Yes |  | University Library | Nothing | a disabled condition that's met by a blank |

**A dynamic default.** Donor contact in Loan Requests defaults to the Donor of the artifact that meets the conditions, and defaults apply when it's blank. Should show is the donor it has selected. Nothing is saved, so the Loan Requests keep no donor contact.

| Row | All of | Any of | Loan Request | Should show | What it tests |
|---|---|---|---|---|---|
| E1 | Short name = {loans_artifact} |  | University Library | Emilie Du Châtelet | a dynamic default, where the left side is the source of the linked `{ }` |
| E2 | Short name = {loans_artifact} |  | (new) | Nothing | a dynamic default, when the `{ }` has no value yet |

**The Artifacts subform in the Weapons collection.** Typed into the filter boxes of the Artifacts subform in the Collections form. Weapons has Chinese Sword, Egyptian Chariot and French Musket, so Everything is those three. A term works when it's what people type, and not only when it's what the element stores: Yes or No for a Yes/No element, and a date written out. What's stored still works too, since conditions made before now use it.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| M1 |  |  | Everything | no conditions |
| M2 | Donated to museum = Yes |  | Chinese Sword | Yes/No, by what people type |
| M3 | Donated to museum = No |  | Egyptian Chariot, French Musket | Yes/No, the other value |
| M4 | Donated to museum LIKE %yes% |  | Chinese Sword | wildcards typed around the term |
| M5 | Donated to museum = 1 |  | Chinese Sword | the code Yes is stored as still works |
| M6 | Era IN BCE |  | Egyptian Chariot | a list of one |
| M7 | Date of acquisition = August 10 1993 |  | Chinese Sword | a date written out |
| M8 | Date of acquisition LIKE 2011 |  | Egyptian Chariot | just the year |
| M9 | Date of acquisition >= 2000 |  | Egyptian Chariot | a year with a comparison is from the start of that year |
| M10 | Donor != Freeform Solutions |  | Egyptian Chariot, French Musket | != takes in the artifacts with no donor |
| M11 | Donor = {BLANK} |  | Egyptian Chariot, French Musket | no donor |
| M12 | Donated to museum = No | Era = BCE; Date of acquisition LIKE 1993 | Egyptian Chariot | all and any together |
| M13 | Collections = {collections_name} | Era = BCE; Donor = Freeform Solutions | Chinese Sword, Egyptian Chariot | a reference to the collection that's open, and any |
| M14 |  | Collections != {collections_name}; Era = BCE | Egyptian Chariot | a negated reference to the collection that's open, in any |

## 9. A site that shows dates as d/m/Y

How a date in a condition or a search is read depends on the site's Short date format, in Settings, under System. This section changes it to `d/m/Y`, in the Settings page, checks that the Catalogue list shows Bronze Mirror as 31/12/2002, runs these rows as curator1, and puts it back. The site's dates stay stored as YYYY-MM-DD, which also still works as a term.

Conditions, set with `sourceEntryFilter` on `locations_subform_artifacts`.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| T1 | Date of acquisition LIKE 12/2002 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | part of a date as the site shows it |
| T2 | Date of acquisition NOT LIKE 12/2002 |  | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not part of a date as the site shows it, and blanks |
| T3 | Date of acquisition LIKE 21/08 |  | Roman Coin | a day and month |
| T4 | Date of acquisition = 21/08/2020 |  | Roman Coin | a whole date, not the 8th of the 21st month |
| T5 | Date of acquisition = 02/12/2002 |  | Persian necklace, Persian necklace - number 2 | a whole date, not February 12 |
| T6 | Date of acquisition = 31/12/2002 |  | Bronze Mirror | a whole date that only works day first |
| T7 | Date of acquisition > 31/12/2002 |  | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | after a date |
| T8 | Date of acquisition LIKE 2002-12 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | part of a date as it's stored still matches |
| T9 | Era = BCE | Date of acquisition LIKE 08/2020; Date of acquisition NOT LIKE 2002 | Roman Coin, Egyptian Chariot, Babylonian Spoon, Unknown Shard | all and any |

Display conditions, set with `display_conditions` on `artifacts_curator_note`, the same as section 5.

| Row | All of | Any of | Open | Should show | What it tests |
|---|---|---|---|---|---|
| W1 | Date of acquisition LIKE 12/2002 |  | Bronze Mirror, Roman Coin | Bronze Mirror | part of a date as the site shows it |
| W2 | Date of acquisition NOT LIKE 12/2002 |  | Unknown Shard, Bronze Mirror | Unknown Shard | not part of a date, on a blank |
| W3 | Date of acquisition = 21/08/2020 |  | Roman Coin, Bronze Mirror | Roman Coin | a whole date |
| W4 | Date of acquisition = 02/12/2002 |  | Persian necklace, Silk Scroll | Persian necklace | a whole date, not February 12 |

Search boxes on the Catalogue list.

| Row | List | Typed in | Should show | What it tests |
|---|---|---|---|---|
| U1 | Catalogue | Date of acquisition: `12/2002` | Persian necklace, Persian necklace - number 2, Bronze Mirror | part of a date as the site shows it |
| U2 | Catalogue | Date of acquisition: `02/12/2002` | Persian necklace, Persian necklace - number 2 | a whole date |
| U3 | Catalogue | Date of acquisition: `31/12/2002` | Bronze Mirror | a whole date that only works day first |
| U4 | Catalogue | Date of acquisition: `!12/2002` | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not part of a date, and blanks |

## 10. A site that shows dates with words

The same as section 9, with the Short date format `jS F Y`, so the Catalogue list shows Bronze Mirror as 31st December 2002. `S` is the day's suffix.

Conditions, set with `sourceEntryFilter` on `locations_subform_artifacts`.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| G1 | Date of acquisition LIKE 1st |  | Roman Coin, Bronze Mirror, Silk Scroll | the day with its suffix, as the site shows it, so the 21st and the 31st too |
| G2 | Date of acquisition LIKE nd December |  | Persian necklace, Persian necklace - number 2 | the suffix and the month together, as the site shows them |
| G3 | Date of acquisition NOT LIKE 1st |  | Everything except Roman Coin, Bronze Mirror, Silk Scroll | not part of the date as the site shows it, and blanks |

## 11. A group's filter on a date

A group's filter on a form decides which of its entries the group's members can see at all, everywhere. It's SQL of its own, in `buildPerGroupFilterWhereClause()` in `forms.php`, not a condition, so its rules aren't the rules in [What the operators mean](#what-the-operators-mean). A negation leaves out entries with nothing in the field. On a date, the term is read the way a list search reads it, so a year is every day in it, and a term that isn't a date is decided without comparing it with a date.

The group is Auditors, and its one member is auditor1, someone from outside the museum who checks its records. 023 makes them on the Groups and Users pages, and lets them see every artifact. MCP can't set a group's filter, so each row's filter is typed into the Auditors' panel on the Permissions tab of Artifacts, the way section 8 types conditions, and then auditor1 opens the Catalogue list. The filter is taken off again afterwards, since 023 left the Auditors with none.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| J1 | Date of acquisition = 2002 |  | Persian necklace, Persian necklace - number 2, Bronze Mirror | a year |
| J2 | Date of acquisition != 2002 |  | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror, Unknown Shard | not in a year, which leaves out blanks |
| J3 | Date of acquisition > 2002 |  | Roman Coin, Egyptian Chariot, Babylonian Spoon, American Bicycle, Viking Silver Armband, Candide - first edition, Silk Scroll | after a year |
| J4 | Date of acquisition = bread |  | Nothing | a term that isn't a date |
| J5 | Date of acquisition != bread |  | Everything except Unknown Shard | every date isn't that, and blanks are left out |
| J6 | Date of acquisition = {BLANK} |  | Unknown Shard | blank |
| J7 | Date of acquisition != {BLANK} |  | Everything except Unknown Shard | not blank |
| J8 | creation_datetime <= {TODAY} |  | Everything | when an entry was made, all of today |
| J9 | Date of acquisition = 2002-12-02 |  | Persian necklace, Persian necklace - number 2 | a whole date, which is compared as it is |
| J10 | Date of acquisition IN 2002-12-02, bread |  | Persian necklace, Persian necklace - number 2 | a list, where the term that isn't a date is left out |
| J11 | Date of acquisition IN bread |  | Nothing | a list with no dates in it |

## 12. What `=` conditions give a new entry

An `=` condition on a subform gives an entry added in the subform its value, so the new entry belongs with the others. A condition on a date can name a year or a month, which isn't one date, so nothing is written.

Set with `sourceEntryFilter` on `locations_subform_artifacts`, with its Add and Delete buttons on, and Keep filtered values in sync off, since Add saves Main Vault, and keeping the values in sync would write them into every artifact there. Then Main Vault is opened as a webmaster, and Add makes one artifact. Saved is what that artifact holds. It's deleted with the subform's Delete button before the next row, and the subform is put back the way 022 made it.

| Row | All of | Any of | Saved | What it tests |
|---|---|---|---|---|
| K1 | Date of acquisition = 2002-12-02 |  | Date of acquisition: 2002-12-02 | a date is written |
| K2 | Date of acquisition = 2002 |  | Date of acquisition: (blank) | a year isn't a date, so nothing is written |
| K3 | Date of acquisition = December 2002 |  | Date of acquisition: (blank) | the same, with a month |
| K4 | Era = BCE |  | Era: BCE | a condition that isn't on a date is written as it was |
| K5 | Era = BCE; Date of acquisition = 2002 |  | Era: BCE; Date of acquisition: (blank) | only what can be saved is written |

## The museum's data

| Artifact | Year | Era | Acquired | Donated | Donor | Collections | Latitude | Materials | On loan | Provenance | Appraiser email | Storage | Handling | Catalogued by |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Roman Coin | 150 | BCE | 2020-08-21 | Yes | François-Marie Arouet | Ancient History, Coins | 41.9028 | Silver | Yes |  | appraiser@museum.example | Climate controlled, stored as 11 | Gloves | Curator One |
| Persian necklace | 350 | BCE | 2002-12-02 | Yes | Emilie Du Châtelet | Ancient History | 29.9355 | Gold, Silver |  |  |  |  |  | Curator One |
| Chinese Sword | 198 | CE | 1993-08-10 | Yes | Freeform Solutions | Ancient History, Weapons | 34.3416 | Iron | No |  | empty string |  |  |  |
| Egyptian Chariot | 1850 | BCE | 2011-09-19 | No |  | Ancient History, Weapons | 25.6872 | Wood, Bronze |  | Bought in Cairo | empty string |  | Two people to lift |  |
| Babylonian Spoon | 1500 | BCE | 2015-09-26 | Yes | François-Marie Arouet | Ancient History | 32.5422 | Bronze |  | City Museum |  |  |  | Curator One |
| Florentine Book | 1588 | CE | 1988-02-29 | Yes | Emilie Du Châtelet | Modern History | 43.7696 | Paper | Yes | Bought at Sotheby's |  |  | Gloves, Keep flat | Curator Two |
| French Musket | 1804 | CE | 1927-03-04 | No |  | Modern History, Weapons | 48.8566 | Iron, Wood | No | Bought in Paris | empty string |  |  | Curator Two |
| Japanese Coin | 1880 | CE | 1955-11-25 | Yes | Freeform Solutions | Coins, Modern History | 35.6762 |  | No |  | appraiser@museum.example |  |  |  |
| American Bicycle | 1884 | CE | 2007-05-09 | No |  | Modern History | 42.3601 | Cast iron |  |  |  |  |  |  |
| Polynesian Canoe | 1701 | CE | 1977-06-14 | Yes | François-Marie Arouet | Modern History | -17.5516 | Wood |  |  |  |  | Two people to lift |  |
| Viking Silver Armband | 875 | CE | 2025-07-10 | Yes | Emilie Du Châtelet | Modern History | 59.8586 | Silver |  |  |  |  |  |  |
| Candide - first edition | 1759 | CE | 2025-11-29 | Yes | François-Marie Arouet | Modern History |  | Paper |  |  |  |  | Keep flat |  |
| Persian necklace - number 2 | 350 | BCE | 2002-12-02 | Yes | Emilie Du Châtelet | Ancient History | 29.9355 |  |  |  |  |  |  |  |
| Unknown Shard | 500 | BCE |  | No |  |  |  |  |  |  | empty string |  |  |  |
| Bronze Mirror | 200 | BCE | 2002-12-31 | Yes | Aoife O'Brien | Ancient History |  | Bronze |  |  | empty string |  |  |  |
| Silk Scroll | 1400 | CE | 2003-01-01 | Yes | Hart & Sons Antiques | Modern History |  | Wood |  |  | empty string |  | Gloves, Keep flat |  |

A blank cell is NULL.

| Donor | Type | First name | Last name | Email | Anonymous | Province |
|---|---|---|---|---|---|---|
| François-Marie Arouet | Individual | François-Marie | Arouet | voltaire@enlightenment.org |  | Quebec |
| Emilie Du Châtelet | Individual | Emilie | Du Châtelet | duchatelet@enlightenment.org |  | Quebec |
| Freeform Solutions | Organization |  |  | formulize@freeform.ca |  | Ontario |
| L. Warner Foundation | Organization |  |  | empty string | No |  |
| Aoife O'Brien | Individual | Aoife | O'Brien | empty string |  |  |
| Hart & Sons Antiques | Organization |  |  | empty string |  | Prince Edward Island |

| Collection | Suitable audience |
|---|---|
| Ancient History | Children, Adults |
| Modern History | Children, Adults |
| Coins | Adults |
| Weapons | Children, Adults |

| Loan Request | Artifact | Collections wanted | Materials | Era | Made after | Donor contact | Start | End | Approved | Storage they have |
|---|---|---|---|---|---|---|---|---|---|---|
| City Museum | Roman Coin | Coins | Silver | BCE | 300 | François-Marie Arouet | 2026-01-15 | 2026-06-30 | Yes | Climate controlled |
| Harbour Gallery | Roman Coin | Weapons, Modern History | Iron, Wood | CE | 1500 | Hart & Sons Antiques | 2003-01-01 |  | No | Standard |
| University Library | Florentine Book |  |  |  |  |  |  |  |  | Climate controlled |
| Regional Archive | Unknown Shard |  |  |  |  |  |  |  |  | Climate controlled |
| Town Hall | Unknown Shard |  |  |  |  |  |  |  | No | Climate controlled |

| Visit | Donor |
|---|---|
| Arouet in March | François-Marie Arouet |
| Arouet in April | François-Marie Arouet |
| Du Châtelet in March | Emilie Du Châtelet |
| Walk-in in April |  |

| Offer | Visit |
|---|---|
| Letters | Arouet in March |
| Snuff box | Arouet in April |
| Telescope | Du Châtelet in March |
| Unlabelled box | Walk-in in April |
| Sketchbook |  |

Entry ids in these rows are fixed by the order setup makes things: Freeform Solutions is donor 3, Weapons is collection 4.

Storage and Storage they have are stored as 1 for Standard and 11 for Climate controlled, and Climate controlled is the default for Storage they have. Handling is stored as 1 for Gloves, 2 for Two people to lift and 12 for Keep flat.

Auditors is a group with one member, auditor1, who isn't staff. They can see every artifact, and nothing else is theirs. Section 11 gives the group a filter, and takes it off again.

## To do

What no row checks yet, and what checking it would take. Then problems found while writing the rows, which aren't fixed yet.

A gap matters most where it's code the branch changed. If no row runs that code, the gap has to be closed before the tests cover the branch. If other rows run the same code, it can wait. A run of the suite with coverage on lists the lines a branch changed that no test runs, which is the way to check. See Measuring coverage in `docs/ci.md`.

The first complete run with coverage, 42561306189, ran 598 of the 668 changed lines that can run. The gaps below name the lines that a row could reach. Of the rest:
- 36 are a function's closing brace, which never runs when every way through the function returns before it
- the others in the condition code are safety checks the screens can't reach, ie: a `{ }` that refers to an entry that doesn't exist, or to something that isn't an element, or an operator that's turned away earlier. In `functions.php`, they're in `_buildConditionsFilterSQL()`, `_entryIdsInLinkedValue()`, `_valuesInReferenceForMultiValueElement()`, `_dateRangeComparisonForConditionsFilterSQL()`, `_convertLiteralTermForConditionsFilterSQL()` and `formulize_dateSpanComparison()`.

### Gaps

- **A group's filter on anything but a date.** Section 11 covers dates. A group's filter makes its own SQL, in `buildPerGroupFilterWhereClause()` in `forms.php`, and its rules for other fields aren't the rules conditions have either:
  - a negation leaves out entries with nothing in the field
  - `{handle}` is another field in the same entry
  - the only keywords are `{USER}`, `{USER_ID}`, `{USER_FULL_NAME}`, `{TODAY}` and `{BLANK}`
  - a linked element has no lookup in its source form, so the term has to become the stored id through `prepareLiteralTextForDB()`

  This branch didn't change those, and nothing checks them but 042, which uses the filter 015 gives All Staff on Surveys, on a Yes/No. To close: J rows on a text box, a Yes/No and a linked element.

- **Applying a default to the entries that already exist.** `applyDefaultToEntries()` in `data.php` runs when a webmaster saves a default and asks for it to go into the existing entries. It now decides what's blank with `formulize_blankValueComparison()`, so a number field holding 0 isn't blank any more. S18 to S28 run that function, including 0 in S24 and S27. The coverage run of 42561306189 shows no test reaching the line in `applyDefaultToEntries()`. To close, without changing the museum's data:
  - make a text box and a number box on Artifacts with MCP, for 045 alone
  - with MCP, give a few artifacts an empty value in the text box, and 0 in the number box, and leave others with no value
  - give each a default, and tick "Apply the default value to existing entries" on the Options tab
  - check which artifacts got the default
  - then delete the two elements with MCP, which takes their values with them

- **Display conditions on a new entry, and after something changes on screen.** Every V row opens a saved artifact. To close: rows that open a new Artifact, and rows with an On screen cell, like section 3. The display condition would refer to a field on the same page, so `conditional.js` checks it again when that field changes.

- **A linked `{ }` element that has a default.** N41 to N43 cover a default on radio buttons, Storage they have. Nothing covers a new entry where the `{ }` refers to a linked element with a default, which is where "empty until chosen" and the default meet. To close: a default on a linked element in Loan Requests, and N rows that refer to it. Section 8 gives Donor contact a dynamic default and takes it away again, so use another element.

- **A `{ }` that refers to checkboxes with a default, in a new entry.** `_valuesInReferenceForMultiValueElement()` in `functions.php` reads the default of the checkboxes the `{ }` refers to, when nothing is on screen. N5 and N6 run it, but Materials they can display has no default, so the default is always empty. To close: a default on checkboxes in Loan Requests, and N rows that refer to them with nothing on screen.

- **Element types whose own conversion now runs on a condition's term.** `_convertLiteralTermForConditionsFilterSQL()` sends a term through the element type's `prepareLiteralTextForDB()`, ie: a time element reads `2:30 pm` as 14:30, and a duration element reads `1d 2h` as minutes. The museum has no time or duration element, so no row runs those. The types it does have are covered, ie: S16, S34, S138, S147 and D15. To close: a time and a duration element on Artifacts, and S rows on them.

- **A site date format with an escaped character.** `formulize_mysqlDateFormat()` in `functions.php` turns a PHP date format into one for MySQL's `DATE_FORMAT()`, for matching part of a date as the site shows it. It writes an escaped character, ie: the o in `\o`, as it is, and no row runs that, because the Settings page doesn't keep the backslash (see Problems found). To close: once it does, put `\o\f` back in section 10's format, ie: `jS \o\f F Y`, and a row like `Date of acquisition LIKE nd of`, which is the Persian necklaces.

- **Timezones on when an entry was made or modified.** On these, a day is all of that day as the person sees it, so its range moves with the person's timezone (`formulize_dateSpanInTerm()`). No row is read by someone whose timezone is different from the server's, so the range never moves. To close: a user with another timezone, and rows like S129 to S132 and B71 read by them.

- **Linked elements that show other columns.** When a linked element shows alternate columns, a search on it compares the term with those columns joined together. No linked element in the museum shows them. To close: a linked element set to show more than one column, ie: a donor's name and province, then B rows and S rows on it.

- **Stored values, not only the text people see.** Several elements store something other than what people see:
  - Yes/No stores 1 and 2
  - Storage and Handling store the values behind their labels
  - a province list stores a code
  - a list of users stores a uid
  - linked elements store entry ids

  Conditions in SQL are checked both ways, ie: M5 is `Donated to museum = 1`, S139 is `Storage = 11`, S144 is `Handling = 1`, S150 is `Handling LIKE 1`, and S56, S57, S71 and S72 use entry ids. The rest only use what people see:
  - **Searches.** B75 (`=1` on Handling), B85 (`1` on Handling) and B50 (`!=4` on Collections) are the only B rows with a stored value. None searches for:
    - `1` or `2` on a Yes/No
    - `11` on Storage, or `1`, which is Standard and shouldn't find the Roman Coin's 11
    - a province code, ie: `10`
    - a positive `4` on Collections
  - **Display conditions.** No V row has `= 1` on a Yes/No, `= 11` on Storage or `= 1` on Handling. Entry ids are left out of section 5 on purpose, see What the operators mean.
  - **A list of users.** S147 and B77 use the name people see, Curator Two. Nothing uses the user's login, which the rules say matches too, or the uid that's stored.

  To close: B, V and S rows with those terms. The museum's data already has the values.

- **A number in a search on a linked element that holds one value.** In a search, a number is an entry id only on a linked element that holds several values (where `formulize_parseFilter()` in `extract.php` builds the EXISTS for a linked element). So searching Donor for `3` looks for 3 in the donors' names. A condition reads `Donor = 3` as an entry id (S56). Master searches the same way. To close: decide whether a search should read the number the way a condition does, then a B row on Donor, ie: `3`, which is Freeform Solutions. Until that's decided, the row would expect what master does.

- **What `=` conditions give a new entry, other than one added in a subform.** Section 12 adds artifacts with a subform's Add button, which runs `getFilterValuesForEntry()` and `formulize_conditionValueCanBeSaved()` in `functions.php`. Two other places give a new entry these values, and no row runs them:
  - a list's fundamental filters, when an entry is added from the list (`readelements.php`). It uses the same `getFilterValuesForEntry()`.
  - the blank entries a subform shows, once they're filled in and the page is saved (`synchSubformBlankDefaults()`). It has a loop of its own, which calls `formulize_conditionValueCanBeSaved()` too.

  The coverage run of 42561306189 shows no test reaching the lines in `synchSubformBlankDefaults()`. No subform in the museum shows blank entries yet. To close: K rows that add an artifact from the Catalogue list, with fundamental filters on it, and K rows that fill in a blank entry in a subform that shows them, ie: the Surveys subform in Exhibits, which has editable rows, with its blank entries turned on for the rows and off again after.

- **`proxy` and `new_0` entries.** `buildConditionsFilterSQL()` treats these markers as `new`: `proxy` is an entry someone makes on behalf of another user, and `new_0` comes from code that makes several entries at once. On-screen values stay under the marker the page used. No row opens a page with either one, so the values are always under the same key as the entry. To close: a user who may make entries for others, and N rows that open a new Loan Request made on someone's behalf.

- **"Nothing" rows that aren't proved.** F22, B1, B17, B19, B21, B23, B24, B53, B70, B84, E2, J4 and J11 find nothing, and they aren't loaded a second time. A query that fails shows no entries, so these rows would pass anyway. To close: in section 6, put the row's conditions in the "one or more" set, alongside one that must match, the way sections 1, 3 and 4 do. In section 7, search again with `OR` in front of each of the row's terms, and `ORRoman Coin` in the Short name box, then check that Roman Coin is there. A row that already uses `OR` or `EMPTYSET` needs some other way.

- **MySQL.** CI runs on MariaDB only. MySQL 8 refuses comparisons that MariaDB lets through, ie: a date with text, so a row can pass here and fail there. To close: a CI run on MySQL. There's a plan for one in `mysql-ci-plan.md` at the root of the repository.

- **"Any of" conditions on the main form and connected forms, in a list's fundamental filters.** F18 has terms on the donor and on artifacts. `formulize_parseFilter()` gives a set like that, with terms on the main form and one connected form, to the connected form as a whole. So a donor is listed through an artifact where one of the terms holds, on the artifact or on its donor, which is what a list with only that connected form does row by row. The count of entries uses the same parts. Two cases no row covers:
  - A main entry with no connected entries, where one of the set's terms on the main form holds, and All of has nothing on that connected form. A list with only that connected form keeps it, because its join leaves a row with no connected entry in it. A list with more connected forms, like Donor artifacts, leaves it out, because there's no connected entry for its EXISTS to find. To close: an F row on Donor artifacts, ie: Any of `Email Address = {BLANK}; Era = BCE`, which L. Warner Foundation, with no artifacts and a blank email, would show the difference on.
  - A set with terms on two or more connected forms. Those are still split by form, and the parts are ANDed. Each form's part now starts empty, where before it began with the terms of the form before it, but that only makes a difference here, so no row runs it. To close: a list with two connected forms that both have conditions in one Any of set, and an EXISTS for each of them inside the set.

  Searches split OR terms by form the same way, and no B row has OR terms on two forms.

  The fix for all of these is in `extract.php`, and isn't small. `formulize_parseFilter()` turns each set into a piece for each form before it knows how the forms are joined, and `dataExtraction()` ANDs the pieces. Instead, `formulize_parseFilter()` would hand back each set as it is, with its own AND or OR and its terms grouped by form, for fundamental filters and searches alike. Then `dataExtraction()`, once it has each connected form's alias and join, would build each set in one place: the main form's terms as they are, each connected form's terms in the set in one EXISTS on that form, and the set's own AND or OR between them. The same sets would decide which entries of each connected form are shown. The F18 fix is the special case that fits the code as it is now.

- **A value taken out on screen in a saved entry.** In a new entry, a value taken out on screen has no value, which is what master does (N19, N20, N25, N31 and N32). No row empties a field on screen in a saved entry, where the `{ }` could mean the blank on screen or the value that's saved. This branch changed one line that only that runs: `=` compared with the value that's saved, in `_nullAwareComparisonWithReference()` in `functions.php`, which the coverage run of 42561306189 shows no test reaching. To close: rows in section 2 with an On screen cell, which open a saved Loan Request and empty a date, checkboxes and linked checkboxes on screen, and expect what master does.

- **Forms that link to themselves.** A form with a list of its own entries isn't in the museum. It was, as Part of a set with, a list on Artifacts of other artifacts, and section 4 had rows for it. But that link from Artifacts to itself was in the relationship the Catalogue list uses, and it broke every fundamental filter on that list: F1 to F11 and F23 to F26, in run 42398600775, on master, which has the same code. `formulize_parseFilter()` gives each form in a fundamental filter its alias in the query: `main` for the list's own form, then `f` and a number for each form linked to it. Artifacts was in both places, so `f2` replaced `main`, and the query that counts the entries failed with "Unknown column 'f2.artifacts_catalogued_by'". Forms that link to themselves are the subject of `self-referential-forms-plan.md`, at the root of the repository, which has this. To close: once that work is done, a form of its own with a list of its own entries, and rows like the ones that were here, ie: `Era = {artifacts_era}` and `Donor = {artifacts_donor}`. The list shouldn't offer the entry that's open, and should always offer the one that's selected.

### Problems found

- **A letter or two can mean Yes or No in a condition.** A condition's term on a Yes/No now goes through the element's own conversion, which was written for searches. It matches part of the word, so in a condition `= e` or `= s` means Yes, and `= n` or `= o` means No, where master finds nothing. `1` and `2` are still the stored values, and a term that's neither is left as it is (S41 and S42). It's unlikely anyone has a condition like that. To fix: in `ynElement.php`, match part of the word only when the term is a partial match, ie: LIKE.

- **A Short date format with a backslash in it isn't kept.** From run 42554478615. Section 10 first used `jS \o\f F Y`, so the site would write "of" as it is. After the Settings page saved that, the box didn't show it again, so the backslashes were lost or changed on the way. A PHP date format uses a backslash for a letter that's written as it is, ie: `j \d\e F` for 21 de agosto. The readout kept only the first lines of the error, so what the box held isn't known yet. Section 9's `d/m/Y` is saved and used as it should be.

- **A `<` or `>` search on a column of the Users page.** This comes from reading the code, not from a run. `formulize_parseFilter()` in `extract.php` now leaves blanks out of every search that compares order, including searches on columns that build their own clause, ie: Last login on the Users page. It decides what's blank with `formulize_blankValueComparison()`, which looks up the column's type in the form's own data table. The Users page's form keeps its data in the `users` table instead, so the type isn't found, the column is taken to be text, and 0 counts as blank. So `<2025-01-01` on Last login would leave out people who have never logged in, whose last login is 0, where master lists them. A few lines up, a negative search already leaves types that build their own clause out of the blank handling. Nothing in the tests searches the Users page this way.

- **IN with a `{ }` in a saved entry.** This comes from reading the code, not from a run. In a saved entry, `_buildConditionsFilterSQL()` makes the comparison `curlybracketform.`handle` AND curlybracketform.`entry_id`=...`, and an IN list goes in brackets, so the condition becomes ``field IN ( curlybracketform.`loans_era` AND curlybracketform.`entry_id`=1 )``. That compares the field with true or false, so it finds nothing. In a new entry, a value on screen works (N47), because it goes in as a value. Master has the same code. In section 2, ie: `Era IN {loans_era}` on City Museum, the list would offer only Roman Coin, which is selected, on both branches.

- **`{BLANK}` in a per-group filter.** `!= {BLANK}` makes `` (`field` != '' OR `field` IS NOT NULL) ``, so an empty value matches. It needs AND. `NOT {BLANK}` makes `` `field` NOT '' ``, which isn't valid SQL, and NOT is one of the operators in the admin UI. A date field avoids both now, but no other field does. Master has the same code.
