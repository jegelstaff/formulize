# Conditions and searches

Every row here is something a person can set up, and what they should see when they look at the page. The specs read this file, so a row is a test: section 7 runs in `02-validate`, and the others in `03-validate-sequential`, where `030-literal-terms-in-subform-filters.spec.js` also covers the filter boxes in the admin UI. To check a behaviour, read its row. To add a case, add a row.

The museum's data is in `01-setup/020-data-entry.spec.js` and `01-setup/022-locations-and-loan-requests.spec.js`, and there is a summary at the end of this file.

## Writing a row

- **All of** and **Any of** are the two sets of conditions: all of the first set, and at least one of the second. Conditions in a set are separated by `;`.
- A condition is the element's caption, an operator, and a term: `Era = BCE`. The operators are `=`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, `NOT LIKE` and `IN`.
- `(empty)` is a condition with nothing in the term. `{BLANK}` is the keyword for blank.
- `{handle}` refers to a field in the entry that's open.
- **Should show** is `Everything`, `Nothing`, `Everything except` some names, or a list of names, separated by commas.

## How each section is checked

A row says what should be on the page. This table says where to look for it, and the specs read their selectors and sets of names from here, so if the markup changes, this is the one place to change.

| Rows | Open, as curator1 | Look at | Names | A row passes when |
|---|---|---|---|---|
| S | Main Vault | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| R | The row's saved Loan Request | `.form-row:has(.formulize-label-loans_artifact) select option` | Artifact | The options are exactly the row's names |
| N | A new Loan Request, then what the row puts on screen | `.form-row:has(.formulize-label-loans_artifact) select option` | Artifact | The options are exactly the row's names, once the redraw has finished |
| L | The row's artifact, on its Location and loans tab | `.formulize-subform td` | Loan Request | The names in those cells are exactly the row's names, out of that artifact's requests |
| D | A new Loan Request | `.form-row:has(.formulize-label-loans_donor) select option` | Donor | The options are exactly the row's names |
| C | A new Loan Request | `.form-row:has(.formulize-label-loans_collections) label` | Collection | The boxes are exactly the row's names |
| V | Each artifact the row names, on its Location and loans tab | `.form-row:has(.formulize-label-artifacts_curator_note) input[type="text"]` | Artifact | The box is on the page for the artifact in Shows on, and not for the one in Hidden on |
| F | The row's list | `[id^="celladdress_"]` | By list | The names in those cells are exactly the row's names |
| B | The row's list, then what the row types in the search boxes | `[id^="celladdress_"]` | By list | The names in those cells are exactly the row's names, and each box still holds what was typed |
| T | Main Vault | `.formulize-subform td` | Artifact | The names in those cells are exactly the row's names |
| W | Each artifact the row names, on its Location and loans tab | `.form-row:has(.formulize-label-artifacts_curator_note) input[type="text"]` | Artifact | The box is on the page for the artifact in Shows on, and not for the one in Hidden on |
| U | The Catalogue list, then what the row types in the search boxes | `[id^="celladdress_"]` | Artifact | The names in those cells are exactly the row's names, and each box still holds what was typed |

- **Names** is one of the tables at the end of this file, by its first column. Only text that is exactly one of those names counts, so other text in the same cells can't be mistaken for a result. By list means Artifact on the Catalogue list and Donor on the Donor artifacts list.
- **The match is exact.** An entry that shouldn't be there fails a row as much as one that's missing.
- **`Everything`** means every name in the set, `Everything except` means the rest of the set, and anything else is the list of names. A name that isn't in the set stops the spec, so a typo here can't pass.
- **`Nothing` has to be proved.** A query that fails shows no entries, the same as one that finds none. So rows that find nothing in sections 1, 3 and 4 are loaded again with their conditions in the "one or more" set, alongside one that must match, and that entry must appear.

## What the operators mean

- `=` and `LIKE` on an element with several values mean any of them. `!=` and `NOT LIKE` mean none of them, whichever order they were saved in.
- A blank field is not equal to anything, so `!=` and `NOT LIKE` find it. Nothing is before or after a blank, so `>` and `<` don't.
- NULL and an empty string are both blank.
- A year or month in a date condition means all of that period: `= 2002` is any day in 2002, `> 2002` is after the end of it, and `< 2002` is before the start of it.
- A term that can't be a date finds nothing with `=`, `>`, `<` and `LIKE`, and everything with `!=` and `NOT LIKE`.
- A number in a condition on a linked element is an entry id, if no entry has that number as its text. Display conditions don't do this, so section 5 doesn't have rows for it.
- A condition on a connected form keeps the main entries with at least one connected entry that meets it, and shows only the connected entries that do. See [Conditions on connected forms](../../../docs/filters.md#conditions-on-connected-forms).
- `{ }` that refers to a blank field: `=` and `LIKE` find blanks, `!=` and `NOT LIKE` find anything that isn't blank, and `>` and `<` find nothing.
- In a new entry, a `{ }` with nothing on screen has no meaning yet, so that condition is ignored. Any other conditions still apply.
- Except on a linked element, when the `{ }` is a linked element with the same source. That condition finds nothing until a choice is made, whatever the operator, so a list that depends on another list is empty until then.
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
| S71 | Collections = 4 |  | Chinese Sword, Egyptian Chariot, French Musket | linked, several values, the entry id of Weapons |
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

## 3. `{ }` in a new Loan Request

The same setting, on a new entry. With nothing on screen the `{ }` condition is ignored, except that a list that depends on another list has nothing in it until a choice is made. Then a person fills in what the row says, and the options change while the page is open, with nothing saved. A term in **On screen** like `Materials they can display: Iron + Wood` means those boxes, in that order. `(blank)` means clearing it again after a value.

| Row | All of | Any of | On screen | Should show | What it tests |
|---|---|---|---|---|---|
| N1 | Era = {loans_era} |  |  | Everything | ignored, nothing on screen |
| N2 | Era != {loans_era} |  |  | Everything | ignored, whatever the operator |
| N3 | Year > {loans_made_after} |  |  | Everything | ignored on a number |
| N4 | Date of acquisition < {loans_start_date} |  |  | Everything | ignored on a date |
| N5 | Materials = {loans_materials} |  |  | Everything | ignored on checkboxes |
| N6 | Materials != {loans_materials} |  |  | Everything | ignored on checkboxes, with != |
| N7 | Collections = {loans_collections} |  |  | Nothing | empty until a choice is made, linked, several values |
| N8 | Collections != {loans_collections} |  |  | Nothing | empty until a choice is made, with != |
| N9 | Donor = {loans_donor} |  |  | Nothing | empty until a choice is made, linked, one value |
| N10 | Donor != {loans_donor} |  |  | Nothing | empty until a choice is made, with != |
| N11 | Era = {loans_era}; Collections = Weapons |  |  | Chinese Sword, Egyptian Chariot, French Musket | ignored, and the other condition still applies |
| N12 |  | Era = {loans_era}; Collections = Weapons |  | Chinese Sword, Egyptian Chariot, French Musket | ignored in the set of either, and the other still applies |
| N13 | Era = {loans_era} |  | Era wanted: CE | Everything except Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | a radio on screen |
| N14 | Era != {loans_era} |  | Era wanted: CE | Roman Coin, Persian necklace, Egyptian Chariot, Babylonian Spoon, Persian necklace - number 2, Unknown Shard, Bronze Mirror | != on screen |
| N15 | Year > {loans_made_after} |  | Made after year: 1500 | Egyptian Chariot, Florentine Book, French Musket, Japanese Coin, American Bicycle, Polynesian Canoe, Candide - first edition | a number on screen |
| N16 | Date of acquisition < {loans_start_date} |  | Start date: 2003-01-01 | Persian necklace, Chinese Sword, Florentine Book, French Musket, Japanese Coin, Polynesian Canoe, Persian necklace - number 2, Bronze Mirror | a date on screen |
| N17 | Date of acquisition = {loans_start_date} |  | Start date: 2003-01-01 | Silk Scroll | the same date on screen |
| N18 | Date of acquisition != {loans_start_date} |  | Start date: 2003-01-01 | Everything except Silk Scroll | != with a date on screen |
| N19 | Date of acquisition = {loans_start_date} |  | Start date: (blank) | Unknown Shard | a date cleared on screen |
| N20 | Date of acquisition != {loans_start_date} |  | Start date: (blank) | Everything except Unknown Shard | != with a date cleared on screen |
| N21 | Materials = {loans_materials} |  | Materials they can display: Iron + Wood | Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | checkboxes on screen |
| N22 | Materials != {loans_materials} |  | Materials they can display: Iron + Wood | Everything except Chinese Sword, Egyptian Chariot, French Musket, Polynesian Canoe, Silk Scroll | != with checkboxes on screen |
| N23 | Materials LIKE {loans_materials} |  | Materials they can display: Iron + Wood | Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | LIKE with checkboxes on screen |
| N24 | Materials NOT LIKE {loans_materials} |  | Materials they can display: Iron + Wood | Everything except Chinese Sword, Egyptian Chariot, French Musket, American Bicycle, Polynesian Canoe, Silk Scroll | NOT LIKE with checkboxes on screen |
| N25 | Materials = {loans_materials} |  | Materials they can display: (blank) | Japanese Coin, Persian necklace - number 2, Unknown Shard | checkboxes cleared on screen |
| N26 | Collections = {loans_collections} |  | Collections wanted: Weapons + Coins | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | linked checkboxes on screen |
| N27 | Collections = {loans_collections} |  | Collections wanted: Coins + Weapons | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | the other order finds the same |
| N28 | Collections != {loans_collections} |  | Collections wanted: Weapons + Coins | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | != with linked checkboxes on screen |
| N29 | Collections LIKE {loans_collections} |  | Collections wanted: Weapons + Coins | Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | LIKE with linked checkboxes on screen |
| N30 | Collections NOT LIKE {loans_collections} |  | Collections wanted: Weapons + Coins | Everything except Roman Coin, Chinese Sword, Egyptian Chariot, French Musket, Japanese Coin | NOT LIKE with linked checkboxes on screen |
| N31 | Collections = {loans_collections} |  | Collections wanted: (blank) | Unknown Shard | linked checkboxes cleared on screen |
| N32 | Collections != {loans_collections} |  | Collections wanted: (blank) | Everything except Unknown Shard | != with linked checkboxes cleared |
| N33 | Donor != {loans_donor} |  | Donor contact: Freeform Solutions | Everything except Chinese Sword, Japanese Coin | linked on screen |
| N34 | Donor = {loans_donor} |  | Donor contact: Hart & Sons Antiques | Silk Scroll | an ampersand on screen |
| N35 | Provenance != {loans_borrower} |  | Borrower: Bought in Cairo | Everything except Egyptian Chariot | text on screen |
| N36 |  | Provenance != {loans_borrower}; Era = BCE | Borrower: Bought in Cairo | Everything | one of a reference on screen and a literal term |
| N37 | Donated to museum = {loans_approved} |  | Approved: Yes | Everything except Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | Yes on screen |
| N38 | Donated to museum != {loans_approved} |  | Approved: Yes | Egyptian Chariot, French Musket, American Bicycle, Unknown Shard | != with Yes on screen |

## 4. Other surfaces

**The Loan requests subform in an Artifact.** Set with `sourceEntryFilter` on `artifacts_subform_loan_requests`, and read on the artifact's Location and loans tab. A subform only shows entries connected to the entry that's open, so these rows are about the Loan Requests for that artifact.

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

**The Collections wanted boxes in a new Loan Request.** Set with `sourceEntryFilter` on `loans_collections`.

| Row | All of | Any of | Should show | What it tests |
|---|---|---|---|---|
| C1 | Suitable audience != Children |  | Coins | checkboxes |
| C2 | Suitable audience != Children; Name != Coins |  | Nothing | nothing left |
| C3 |  | Suitable audience != Children; Name != Coins | Everything | one of two |
| C4 | Suitable audience = Adults; Suitable audience NOT LIKE Child |  | Coins | has one and not the other |
| C5 | Suitable audience != Children | Name LIKE History; Name = Coins; Suitable audience = {BLANK} | Coins | all and any |

## 5. Display conditions

Set with `display_conditions` on `artifacts_curator_note`, then open each artifact named on its Location and loans tab as curator1. Curator note is on the page for one, and not for the other. Blanks are the ones that go wrong, so Unknown Shard is used wherever it can be.

| Row | All of | Any of | Shows on | Hidden on | What it tests |
|---|---|---|---|---|---|
| V1 | Provenance = Bought in Cairo |  | Egyptian Chariot | Unknown Shard | text |
| V2 | Provenance != Bought in Cairo |  | Unknown Shard | Egyptian Chariot | != on a blank |
| V3 | Provenance NOT LIKE Cairo |  | Unknown Shard | Egyptian Chariot | NOT LIKE on a blank |
| V4 | Provenance = {BLANK} |  | Unknown Shard | French Musket | blank |
| V5 | Provenance != {BLANK} |  | French Musket | Unknown Shard | not blank |
| V6 | Provenance = (empty) |  | Unknown Shard | French Musket | no term |
| V7 | Appraiser email = {BLANK} |  | Unknown Shard | Roman Coin | blank, NULL and '' |
| V8 | Appraiser email != {BLANK} |  | Roman Coin | Unknown Shard | not blank, NULL and '' |
| V9 | Latitude != 0 |  | Unknown Shard | Nothing | number |
| V10 | Latitude < 50 |  | Roman Coin | Unknown Shard | a blank is not less |
| V11 | Latitude = {BLANK} |  | Unknown Shard | Roman Coin | a blank number |
| V12 | Era = BCE |  | Unknown Shard | Chinese Sword | radio |
| V13 | Era != CE |  | Unknown Shard | Chinese Sword | radio, != |
| V14 | On loan = Yes |  | Roman Coin | Unknown Shard | Yes/No, by what people see |
| V15 | On loan != Yes |  | Unknown Shard | Roman Coin | Yes/No, != |
| V16 | On loan = {BLANK} |  | Unknown Shard | Roman Coin | Yes/No, blank |
| V17 | Materials = Iron |  | Chinese Sword | Unknown Shard | checkboxes, a whole value |
| V18 | Materials LIKE Iron |  | Chinese Sword | Unknown Shard | checkboxes, part of a value |
| V19 | Materials != Iron |  | Unknown Shard | Chinese Sword | checkboxes, none is Iron |
| V20 | Materials = {BLANK} |  | Unknown Shard | Roman Coin | checkboxes, blank |
| V21 | Materials != {BLANK} |  | Roman Coin | Unknown Shard | checkboxes, not blank |
| V22 | Donor = Freeform Solutions |  | Chinese Sword | Unknown Shard | linked |
| V23 | Donor != Freeform Solutions |  | Unknown Shard | Chinese Sword | linked, != |
| V24 | Donor = {BLANK} |  | Unknown Shard | Roman Coin | linked, blank |
| V25 | Donor != {BLANK} |  | Roman Coin | Unknown Shard | linked, not blank |
| V26 | Donor = Aoife O'Brien |  | Bronze Mirror | Unknown Shard | a quote |
| V27 | Donor = Hart & Sons Antiques |  | Silk Scroll | Unknown Shard | an ampersand |
| V28 | Collections = Weapons |  | Chinese Sword | Unknown Shard | linked, several values |
| V29 | Collections != Weapons |  | Unknown Shard | Chinese Sword | linked, several values, != |
| V30 | Collections LIKE ns |  | Roman Coin | Unknown Shard | linked, part of a name |
| V31 | Collections = {BLANK} |  | Unknown Shard | Roman Coin | linked, several values, blank |
| V32 | Date of acquisition = 2002 |  | Bronze Mirror | Unknown Shard | a year |
| V33 | Date of acquisition != 2002 |  | Unknown Shard | Bronze Mirror | not in a year |
| V34 | Date of acquisition > 2002 |  | Roman Coin | Unknown Shard | after a year |
| V35 | Date of acquisition = {BLANK} |  | Unknown Shard | Roman Coin | date, blank |
| V36 | Date of acquisition = bread |  | Nothing | Unknown Shard | not a date, hidden everywhere |
| V37 | Date of acquisition != bread |  | Unknown Shard | Nothing | not a date, shown everywhere |
| V38 | Era = BCE | Donor = {BLANK}; Donor NOT LIKE Solutions | Unknown Shard | Chinese Sword | all and any |
| V39 |  | Provenance = {BLANK}; Era = CE | Unknown Shard | Egyptian Chariot | either |

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
## 7. Search boxes on a list

Read-only, so these run in `02-validate`, as curator1. Each term goes in the search box at the top of that column, on the Catalogue list or the Donor artifacts list. A term starting with `!` means the value doesn't have it, and `!=` means it isn't exactly that. Both find blanks, since they aren't a search for a value. `!{BLANK}` is not blank. `OR` in front of terms in several boxes means any one of them.

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
| B63 | Donor artifacts | Province: `!a` | Everything except Freeform Solutions | a province, a term that matches one of them |
| B64 | Donor artifacts | First name: `!Emilie`<br>Email Address: `!freeform` | Everything except Emilie Du Châtelet, Freeform Solutions | two boxes |
| B65 | Donor artifacts | First name: `OR!Emilie`<br>Last name: `ORArouet` | Everything except Emilie Du Châtelet | OR terms in different boxes |
| B66 | Donor artifacts | Collections: `!Weapons` | Everything except L. Warner Foundation | connected: an artifact is not in Weapons, so Freeform Solutions is found, though another of theirs is. L. Warner Foundation has no artifacts, so is not |
| B67 | Donor artifacts | Latitude: `!=41.9028` | Everything except L. Warner Foundation | connected: an artifact has no latitude, or another one |

## 8. Settings MCP can't reach

MCP has no setting for these, so they're made in the admin UI.

| Row | Setting | Open | Should see | What it tests |
|---|---|---|---|---|
| A1 | End date is disabled when `Approved != Yes` | City Museum | End date can be edited | a disabled condition that isn't met |
| A2 | The same | Harbour Gallery | End date can't be edited | a disabled condition that's met |
| A3 | The same | University Library | End date can't be edited | a disabled condition that's met by a blank |
| A4 | Donor contact has a dynamic default of the Donor in Artifacts, where `Short name = {loans_artifact}`, and defaults apply when blank | University Library | Donor contact shows Emilie Du Châtelet | a dynamic default, where the left side is the source of the linked `{ }` |

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

Display conditions, set with `display_conditions` on `artifacts_curator_note`.

| Row | All of | Any of | Shows on | Hidden on | What it tests |
|---|---|---|---|---|---|
| W1 | Date of acquisition LIKE 12/2002 |  | Bronze Mirror | Roman Coin | part of a date as the site shows it |
| W2 | Date of acquisition NOT LIKE 12/2002 |  | Unknown Shard | Bronze Mirror | not part of a date, on a blank |
| W3 | Date of acquisition = 21/08/2020 |  | Roman Coin | Bronze Mirror | a whole date |
| W4 | Date of acquisition = 02/12/2002 |  | Persian necklace | Silk Scroll | a whole date, not February 12 |

Search boxes on the Catalogue list.

| Row | List | Typed in | Should show | What it tests |
|---|---|---|---|---|
| U1 | Catalogue | Date of acquisition: `12/2002` | Persian necklace, Persian necklace - number 2, Bronze Mirror | part of a date as the site shows it |
| U2 | Catalogue | Date of acquisition: `02/12/2002` | Persian necklace, Persian necklace - number 2 | a whole date |
| U3 | Catalogue | Date of acquisition: `31/12/2002` | Bronze Mirror | a whole date that only works day first |
| U4 | Catalogue | Date of acquisition: `!12/2002` | Everything except Persian necklace, Persian necklace - number 2, Bronze Mirror | not part of a date, and blanks |

## The museum's data

| Artifact | Year | Era | Acquired | Donated | Donor | Collections | Latitude | Materials | On loan | Provenance | Appraiser email |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Roman Coin | 150 | BCE | 2020-08-21 | Yes | François-Marie Arouet | Ancient History, Coins | 41.9028 | Silver | Yes |  | appraiser@museum.example |
| Persian necklace | 350 | BCE | 2002-12-02 | Yes | Emilie Du Châtelet | Ancient History | 29.9355 | Gold, Silver |  |  |  |
| Chinese Sword | 198 | CE | 1993-08-10 | Yes | Freeform Solutions | Ancient History, Weapons | 34.3416 | Iron | No |  | empty string |
| Egyptian Chariot | 1850 | BCE | 2011-09-19 | No |  | Ancient History, Weapons | 25.6872 | Wood, Bronze |  | Bought in Cairo | empty string |
| Babylonian Spoon | 1500 | BCE | 2015-09-26 | Yes | François-Marie Arouet | Ancient History | 32.5422 | Bronze |  | City Museum |  |
| Florentine Book | 1588 | CE | 1988-02-29 | Yes | Emilie Du Châtelet | Modern History | 43.7696 | Paper | Yes | Bought at Sotheby's |  |
| French Musket | 1804 | CE | 1927-03-04 | No |  | Modern History, Weapons | 48.8566 | Iron, Wood | No | Bought in Paris | empty string |
| Japanese Coin | 1880 | CE | 1955-11-25 | Yes | Freeform Solutions | Coins, Modern History | 35.6762 |  | No |  | appraiser@museum.example |
| American Bicycle | 1884 | CE | 2007-05-09 | No |  | Modern History | 42.3601 | Cast iron |  |  |  |
| Polynesian Canoe | 1701 | CE | 1977-06-14 | Yes | François-Marie Arouet | Modern History | -17.5516 | Wood |  |  |  |
| Viking Silver Armband | 875 | CE | 2025-07-10 | Yes | Emilie Du Châtelet | Modern History | 59.8586 | Silver |  |  |  |
| Candide - first edition | 1759 | CE | 2025-11-29 | Yes | François-Marie Arouet | Modern History |  | Paper |  |  |  |
| Persian necklace - number 2 | 350 | BCE | 2002-12-02 | Yes | Emilie Du Châtelet | Ancient History | 29.9355 |  |  |  |  |
| Unknown Shard | 500 | BCE |  | No |  |  |  |  |  |  |  |
| Bronze Mirror | 200 | BCE | 2002-12-31 | Yes | Aoife O'Brien | Ancient History |  | Bronze |  |  |  |
| Silk Scroll | 1400 | CE | 2003-01-01 | Yes | Hart & Sons Antiques | Modern History |  | Wood |  |  |  |

A blank cell is NULL.

| Donor | Type | First name | Last name | Email | Anonymous | Province |
|---|---|---|---|---|---|---|
| François-Marie Arouet | Individual | François-Marie | Arouet | voltaire@enlightenment.org |  | Quebec |
| Emilie Du Châtelet | Individual | Emilie | Du Châtelet | duchatelet@enlightenment.org |  | Quebec |
| Freeform Solutions | Organization |  |  | formulize@freeform.ca |  | Ontario |
| L. Warner Foundation | Organization |  |  | empty string | No |  |
| Aoife O'Brien | Individual | Aoife | O'Brien | empty string |  |  |
| Hart & Sons Antiques | Organization |  |  | empty string |  |  |

| Collection | Suitable audience |
|---|---|
| Ancient History | Children, Adults |
| Modern History | Children, Adults |
| Coins | Adults |
| Weapons | Children, Adults |

| Loan Request | Artifact | Collections wanted | Materials | Era | Made after | Donor contact | Start | End | Approved |
|---|---|---|---|---|---|---|---|---|---|
| City Museum | Roman Coin | Coins | Silver | BCE | 300 | François-Marie Arouet | 2026-01-15 | 2026-06-30 | Yes |
| Harbour Gallery | Roman Coin | Weapons, Modern History | Iron, Wood | CE | 1500 | Hart & Sons Antiques | 2003-01-01 |  | No |
| University Library | Florentine Book |  |  |  |  |  |  |  |  |
| Regional Archive | Unknown Shard |  |  |  |  |  |  |  |  |
| Town Hall | Unknown Shard |  |  |  |  |  |  |  | No |

Entry ids in these rows are fixed by the order setup makes things: Freeform Solutions is donor 3, Weapons is collection 4.
