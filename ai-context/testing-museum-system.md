# The museum system the tests build

The setup specs in `tests/e2e/formulize-core/01-setup/` build a small collection-management system for a museum, and every later spec runs against it. This is an outline of what's in it, so the specs make sense as you read them. It doesn't replace reading them. If it disagrees with them, the specs are right, so fix this file.

For the order the folders run in and what each one needs, see [local-environment-and-testing](local-environment-and-testing.md#what-each-set-of-tests-needs). The data itself is listed under "The museum's data" at the end of `tests/e2e/formulize-core/conditions-and-searches.md`, with every artifact, donor, collection, loan request, visit and offer, and how blanks and codes are stored.

## Users and groups

Spec 005 builds these through Formulize forms in a **Staff Management** application, not through the system admin pages. For how to write specs about them, see [testing-eau-eag](testing-eau-eag.md).

- **Departments** is an entries-are-groups form, with the categories Staff and Curators as well as All Users. Each entry makes three groups. Ancient History makes `Ancient History - All Users`, `Ancient History - Staff` and `Ancient History - Curators`, and Modern History makes the same three. A third entry, Astronomy, is left over from the rename check at the end of 005 and has no members.
- The template groups `Departments - All Users`, `Departments - Staff` and `Departments - Curators` have no members. Permissions and menu links are granted on them, and pass down to each department's groups.
- **All Staff** and **All Curators** are plain groups made on the Groups page.
- **Staff** is an entries-are-users form, so each entry is a user. Its Department element (checkboxes linked to Departments) and its Is curator? element (yes/no) put the user in groups through default-group conditions, which are enforced: removing an enforced membership by hand is undone on the next save.
- The first visit to the Groups and Users pages creates the System Groups and System Users forms, which are part of the same application.

Every user's password is `12345`. The admin's login is in `formulize-core/config.js`.

| Login | Name (`uname`) | Departments | Groups |
|---|---|---|---|
| ahstaff | Ancient History Staff | Ancient History | Ancient History - All Users, Ancient History - Staff, All Staff |
| mhstaff | Modern History Staff | Modern History | The same for Modern History |
| curator1 | Curator One | Both | Each department's All Users and Curators groups, All Curators |
| curator2 | Curator Two | Both | The same as curator1 |
| auditor1 | Auditor One | None | Auditors only |

auditor1 and the Auditors group come from 023. They're made on the Users and Groups pages, because an auditor isn't staff. Pages like `groups.php` list users by `uname`, not by login.

## The Museum application

Specs 010, 021, 022 and 023 make the forms. Element handles start with the form's name, such as `artifacts_short_name`, except in Loan Requests, where they start with `loans_`.

- **Artifacts** is at the centre. ID Number is set to `M001` and so on by an after-save procedure. It has a short name, a rich-text description, dimensions in a table, Year and Era with a derived Year-Era, a date of acquisition (set to Search box - type only in lists, so the specs can type date terms into it, where a date element would otherwise get a date range), Donated to museum, a Donor (linked to Donors, shown only when donated), Condition (a slider from 1 to 10), Collections, and Latitude and Longitude. 022 adds Location, Materials, On loan, Provenance, Storage, Appraiser email, Curator note, Handling and Catalogued by (a list of users), on new tabs of the Artifact screen.
- **Donors** shows different name fields for an Individual and an Organization, and has a derived Name. 021 adds Anonymous, a rich-text Bio and an Organization background, both shown only under conditions.
- **Collections** has a Name and Suitable for.
- **Exhibits** has a Name, a Curator (a user, from All Curators), Collections, and Artifacts, a list narrowed to the collections the exhibit picked.
- **Surveys** is for visitors, and anonymous visitors can fill it in at `/survey`. The Favourite artifact list is narrowed to the artifacts in the chosen exhibit. Flagged is shown only to Webmasters and All Curators. Staff comments is shown to those two and All Staff.
- **Locations** (022) has one entry, Main Vault, which holds every artifact.
- **Loan Requests** (022) record a borrower, what they want, the Artifact they're asking for, and whether it's approved.
- **Visits** and **Offers** (023) are the one pair of forms with a link each way. An offer names the Visit it was made at, and a visit's Follow up on is a list of Offers. Visits link to Donors.

How the forms link, through linked elements, with subforms shown on the form being pointed at:

```
Artifacts     -> Donors, Collections, Locations
Exhibits      -> Collections, Artifacts
Surveys       -> Exhibits, Artifacts
Loan Requests -> Artifacts, Collections, Donors
Visits        -> Donors, Offers
Offers        -> Visits
```

## Permissions

- **Artifacts:** Departments - All Users can add entries, and see, change and delete the entries of their own groups. Departments - Curators can see and change everything. So ahstaff and mhstaff each see only their own department's entries, and the curators see both. 026 checks this isolation.
- **Donors, Collections, Exhibits, Locations, Loan Requests, Visits and Offers** inherit their permissions from Artifacts.
- **Surveys:** every department user can see every entry, and Anonymous Users can add one. All Staff have a filter, so they see only the surveys that are flagged.
- **Staff:** only Webmasters and All Curators.
- **Auditors** can see every artifact.

## Screens, menus and other settings

- Each form has its default form and list screens. The Museum forms' screens have chosen elements, columns and pages.
- Other screens: **Artifact origins**, a map of where artifacts come from. **Catalogue** (`artifact_catalogue`), a list with every artifact on one page. Its Creation date column is set to Search box - type only, so section B of `conditions-and-searches.md` can type terms into it that a date range can't make. Its Last modification date keeps the date range it starts with, which section B picks from. **Donor artifacts** (`donor_artifacts`), a list of donors and their artifacts across the relationship. **Artifact Form - subform of Donors** (`artifacts_donor_subform`).
- The Artifacts list has a custom button, Mark as in perfect condition, that sets Condition to 10.
- The Museum menu has links to Artifacts, Donors, Collections, Exhibits, Surveys and Artifact origins, for Webmasters and Departments - All Users. The forms 022 and 023 add have no menu links, on purpose, so the menus other specs check don't change.
- curator1 has two saved views of the Artifacts list, and there's a notification to Departments - Curators when an artifact in good condition is updated. 03-validate-sequential renames the elements these use, and checks they still work.

## When you read a spec

- Ids depend on the order setup makes things. Specs look forms and screens up by title or handle, with helpers like `formId()` in `01-setup/museum-forms.js`, rather than relying on fixed ids.
- A validate spec that needs to change a form makes its own with `createMuseumForm` and deletes it afterwards. The specs in `03-validate-sequential/` change shared things and put them back.
- 002 rewrites the admin's password hash during the bcrypt upgrade check.
- The site uses the Anari theme after install. `04-appearance/` switches it to Lyris and back. See [themes-anari-and-lyris](themes-anari-and-lyris.md).
