---
layout: default
permalink: documentation/conditional_element_hooks/
title: Running your own JavaScript when a conditional element appears
---

# Running your own JavaScript when a conditional element appears

When an element in a form has a display condition, Formulize shows, hides and redraws that element while the user fills in the form. Each time it draws the element, it fetches fresh markup from the server and replaces whatever was there before. This means that any changes your own JavaScript made to that element when the page loaded are lost. Removed options come back, added classes disappear, and event handlers you attached are gone.

To deal with this, Formulize checks for a JavaScript function named after the element each time it draws a conditional element. If it finds that function, it calls it. Put your customizations in that function, and they will be reapplied every time the element is drawn.

## The conventions

For an element with the ID __187__:

* Write a function called __formulize_conditionalElementUpdate187__. That's "formulize_conditionalElementUpdate" followed by the element's ID number.
* The function must be global, which means it has to be attached to `window`. Declare it at the top level of a script, not inside a `jQuery(document).ready()` handler or any other function. Formulize looks it up by name on `window`, so a function declared inside another function can't be found.
* The function receives one argument: the markup name of the element that was just drawn, for example `de_12_new_187` or `de_12_345_187`. The element's container in the page has the ID `formulize-` plus that name, so `jQuery('#formulize-'+markupName)` gives you everything that was just drawn.

To find an element's ID, open the element in the admin side of Formulize. The ID is the number after `ele_id=` in your browser's address bar.

## When the function is called

Formulize calls your function each time it draws the element after the page has loaded:

* when the element's condition is met and the element appears, and
* when an element that is already showing is redrawn because its contents depend on what the user has entered. For example, a dropdown whose options are filtered by another element's value is redrawn when that other value changes.

Your function is called right after the new markup is in the page, so you can find and change anything inside it immediately.

Your function is __not__ called:

* __when the page first loads.__ Elements that are visible on page load were drawn by the server along with the rest of the page. To change those, also run your code when the document is ready (see the examples below).
* __when the element is hidden.__ Formulize removes the element's contents when it hides it, so there's nothing to change.
* __when the element is a hidden element__ (it renders only as a hidden input and has nothing visible).

If the same element appears more than once on the page, for example in several subform entries, the function runs once for each one that is drawn. Use the markup name argument to work on only the one that was just drawn.

## Where to put the function

The function has to be in the page before the user starts changing values in the form. Either of these places will work:

* In your __theme's theme.html__ file, inside a `<script>` tag in the `<head>`. The function is then available on every page of the site, which is fine because it only runs when its element is drawn.
* In the __Top Template__ or __Bottom Template__ of a form screen, on the screen's Templates tab in the admin side. The function is then only on the pages for that screen. These templates are PHP code, so end the PHP with `?>` before your `<script>` tag, and start it again with `<?php` afterwards if more PHP follows:

~~~php
<?php
// ...the template's existing PHP code...
?>
<script type="text/javascript">
    function formulize_conditionalElementUpdate187(markupName) {
        // your code here
    }
</script>
~~~

Wherever you put it, declare the function at the top level of the script, as `function formulize_conditionalElementUpdate187(markupName) { ... }` or as `window.formulize_conditionalElementUpdate187 = function(markupName) { ... };`.

## Example: remove some options from a dropdown

This example removes every option with "(inactive)" in its text from the dropdown element with ID 187. The function runs when the element is drawn, and the same code runs once on page load in case the element is already visible:

~~~html
<script type="text/javascript">

    function removeInactiveOptions(container) {
        jQuery(container).find('option').each(function() {
            if (jQuery(this).text().indexOf('(inactive)') != -1) {
                jQuery(this).remove();
            }
        });
    }

    // Formulize calls this every time it draws element 187
    function formulize_conditionalElementUpdate187(markupName) {
        removeInactiveOptions('#formulize-' + markupName);
    }

    // Elements that are visible when the page loads were drawn by the server, so handle them here
    jQuery(document).ready(function() {
        removeInactiveOptions('body');
    });

</script>
~~~

## Example: one function for several elements

If several elements need the same treatment, write the shared code once and call it from a short function for each element. This example hides archived people (names beginning "ZZ ") from two autocomplete elements, IDs 187 and 192. It removes them from both the selected-value chips and any dropdown options:

~~~html
<script type="text/javascript">

    function removeArchivedPeople(container) {
        // options in dropdowns and list boxes
        jQuery(container).find('option').each(function() {
            if (jQuery(this).text().indexOf('ZZ ') == 0) {
                jQuery(this).remove();
            }
        });
        // values already chosen in a multi-select autocomplete
        jQuery(container).find('.formulize_autocomplete_selections .auto_multi').each(function() {
            if (jQuery(this).find('.auto_multi_label').text().indexOf('ZZ ') == 0) {
                jQuery(this).remove();
            }
        });
    }

    function formulize_conditionalElementUpdate187(markupName) {
        removeArchivedPeople('#formulize-' + markupName);
    }

    function formulize_conditionalElementUpdate192(markupName) {
        removeArchivedPeople('#formulize-' + markupName);
    }

    jQuery(document).ready(function() {
        removeArchivedPeople('body');
    });

</script>
~~~

Removing a chip only hides it. The value is still selected and will still be saved with the entry. If you need to stop a value from being saved, remove its hidden input from the `#markupName_defaults` container as well.

## Example: reattach an event handler

Event handlers attached to elements inside a conditional element are lost when the element is redrawn, because the elements they were attached to no longer exist. This example shows a warning when the user enters a number over 100 in the text box with ID 205:

~~~html
<script type="text/javascript">

    function attachLargeNumberWarning(container) {
        jQuery(container).find('input[type=text]').bind('change', function() {
            if (parseFloat(jQuery(this).val()) > 100) {
                alert('Numbers over 100 need approval from a supervisor.');
            }
        });
    }

    function formulize_conditionalElementUpdate205(markupName) {
        attachLargeNumberWarning('#formulize-' + markupName);
    }

    jQuery(document).ready(function() {
        jQuery('[id^=formulize-de_][id$=_205]').each(function() {
            attachLargeNumberWarning(this);
        });
    });

</script>
~~~

The page load code selects every container whose ID ends in `_205`, so it finds the element no matter which form and entry it belongs to.

An alternative for event handlers is to bind them once, to the document, with jQuery's `.delegate()` (or `.on()` with a selector, in jQuery 1.7 and later). A handler bound that way also catches events from elements drawn after it was attached, so no update function is needed.

## Things to keep in mind

* __Write code that's safe to run more than once.__ Your function may run many times on the same page as the user changes values. Removing things and setting values is naturally safe to repeat. Adding things, such as appending a note to the element, needs a check so that you don't add it a second time. The fresh markup usually starts clean, but not always: an element that is already showing is only redrawn when its markup changes.
* __Don't rely on what the function did last time.__ Whenever the element is redrawn, its contents are replaced, and anything your function changed has to be done again from scratch.
* __The ID is the element's ID, not its handle.__ If you delete an element and make a new one, the new one has a different ID, and you will need to rename your function.
