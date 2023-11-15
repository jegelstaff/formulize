---
layout: default
permalink: developers/API/functions/buildFilter/
---

# buildFilter( <span style='font-size: 14pt;'>(string) $name, (int | string | object) $element_identifier, (string) $defaultText = "Choose an option", (string) $formDOMId = "", (bool | string | array) $defaultValue = false, (bool) $multi = false</span> ) 

## Location

/modules/formulize/include/functions.php

## Description

Creates a dropdown list or checkbox series based on the given form element. Typically used to create UI elements for users to interact with on screens where the template has been customized to support some unique workflow.

If the filter is based on an element that is a list of users (ie: the {FULLNAMES} or {USERNAMES} flag is used to generate the options for the element), then the values of the options will be the user id numbers.

If the form element is a linked element (where the options are based on the entries in another form), the values of the options will be the entry id numbers of the source entries. 

Otherwise, the values of the options will be the same as the visible labels.

## Parameters

__$name__ - the name of the form element. This will also be used for the DOM id of the element. This name will be the key in $\_POST or $\_GET that you can use to retrieve the value selected in the element, after the page is submitted to the server.<br>
__$element_identifier__ - either an element id, an element handle, or a Formulize element object<br>
__$defaultText__ - Optional. Text to be used as the default (first) option in a dropdown list. Defaults to "Choose an option" in English, or whatever the value of the \_AM\_FORMLINK\_PICK constant is in the active language<br>
__$formDOMId__ - Optional. If you are including the filter in a form you have setup yourself, and you want a change in the filter selection to cause the form to submit to the server, then use this parameter to specify the DOM id of the form. _The DOM id used in Formulize for a list of entries screen is_ controls _and the DOM id of a data entry form is_ formulize_mainform.<br>
__$defaultValue__ - Optional. Normally when a filter appears, there is no default value selected. Set this to _true_ if you want the user's chosen value to be selected by default when the filter is displayed on subsequent page loads. Set to a specific string to have the item with that value be the default selection. To select a specific value(s) when the filter supports multiple values (ie: when the _$multi_ flag is set), use an array of strings. _Note: this must be the value of the option as submitted, not the readable value in the list._<br>
__$multi__ - Optional. Set to true if you want the filter displayed as a series of checkboxes instead of a dropdown list.

## Return Values

Returns the HTML markup for the element, ready to be included in the page.

## Examples

~~~
// make a dropdown list based on the 'student_names' form element
// use 'selectedStudent' as the name of the dropdown list
print buildFilter('selectedStudent', 'student_names');

// if the filter is included inside a form, and the form is submitted,
// then on the next page load you could pickup the value chosen in $_POST:
if(isset($_POST['selectedStudent])) {
    print "The selected student was: ".$_POST['selectedStudent'];
}
~~~

~~~
// use alternate text for the top option 
// (which is normally visible when the page loads)
print buildFilter('selectedStudent', 'student_names', defaultText: 'Choose a student');
~~~

~~~
// set a certain student to be the initially selected option
print buildFilter('selectedStudent', 'student_names', defaultValue: 'Tara');
~~~

~~~
// make a checkbox series instead of a dropdown list
print buildFilter('selectedStudent', 'student_names', multi: true);
~~~

~~~
// when a user selects a value in the filter, submit the form it's part of
print "
    <form id='test_form'>".
        buildFilter('selectedStudent', 'student_names', formDOMId: 'test_form')
    ."</form>
";
~~~

~~~
// Imagine this is inside the top template of a list of entries screen.
// When the user picks a value, trigger a submission of the page

// (The DOM id for list of entries screens is 'controls' and for form
// screens it is 'formulize_mainform'.)

// Also, set the filter to show the value the user selected as its default
// when the page reloads.

// Presumably you would do this because you want to pick up the submitted
// value from $_POST['selectedStudent'] inside some other code elsewhere.

buildFilter('selectedStudent', 'student_names', formDOMId: 'controls', defaultValue: true);
~~~


