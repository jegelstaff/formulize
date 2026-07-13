---
layout: default
permalink: developers/API/custom_form_elements/
title: Writing your own element classes
---

# Writing your own element classes

Every element type in Formulize is defined by a pair of classes in a single file in the __/modules/formulize/class/__ folder. You can add your own element types by adding your own class files to that folder. Formulize scans the folder, so as soon as your file is there, your element type appears in the "Add an element" interface in the admin UI, and works everywhere that elements work: forms, lists, searches, imports, the API, and so on.

There are two ways to write an element class:

1. **Start from scratch**, by extending the base classes. Do this when your element is genuinely a new kind of thing.
2. **Extend an existing element type**, by extending its classes. Do this when your element is a variation on something that already exists. You inherit all the behaviour of the type you extend, and override only what you want to change. There is [a simple example below](#example-radio-buttons-in-random-order).

## The conventions

Formulize finds and instantiates element classes based on naming conventions. For an element type called __myThing__:

* The file must be __/modules/formulize/class/myThingElement.php__ — the type name, plus "Element.php".
* The file must contain an element class called __formulizeMyThingElement__ — "formulize", plus the type name with the first letter capitalized, plus "Element".
* The file must contain a handler class called __formulizeMyThingElementHandler__ — the same, plus "Handler".
* The handler class must have a __create()__ method that returns a new instance of your element class.
* The type name (__myThing__) is what gets stored in the __ele_type__ property of elements, in the database.

The element class represents an element and its settings (it extends the underlying data object pattern used throughout Formulize and XOOPS). The handler class does everything else: rendering the element in forms, saving user input, preparing values for display in lists, converting search text into database values, and providing the admin UI for configuring the element. When you look inside any of the standard element class files, you will find comments on each method explaining what it does and what it must return.

### Parallel class hierarchies — important!

If your element class extends another type's element class, **your handler class must extend that type's handler class**. The two hierarchies must be parallel:

~~~php
// element class hierarchy          // handler class hierarchy
formulizeShuffledRadioElement       formulizeShuffledRadioElementHandler
  extends formulizeRadioElement       extends formulizeRadioElementHandler
    extends formulizeElement            extends formulizeElementsHandler
~~~

This matters because Formulize discovers what "family" your element belongs to by examining the element class hierarchy, and then calls family-specific methods on your **handler**. For example, code that knows it is dealing with a radio-family element will call __previousEntryOptionKey()__ on your handler — a method that exists on __formulizeRadioElementHandler__. If your handler extended the base __formulizeElementsHandler__ instead, that call would fail. (Family-specific methods on the *element* class, like the radio family's __getListOptions()__, are inherited automatically since your element class extends the family's element class — the parallel-hierarchy rule is what extends that same guarantee to the handler side.)

## What you inherit when you extend an existing type

Formulize resolves element types through the class hierarchy, so everywhere the code makes decisions based on the type of an element, your custom type is automatically treated like the type it extends, unless you override the relevant method. That includes:

* **Rendering** in forms, and **saving** what users enter.
* **The admin UI.** The Options tab (and Advanced tab, if any) of the type you extend is used automatically for your type. You do not need to create or register any templates. (If you *want* a custom admin UI, create __/modules/formulize/templates/admin/element_type_myThing.html__, register it in __xoops_version.php__, and update the module — but for most custom types, inheriting the parent's admin UI is exactly right.)
* **Searching and filtering** in lists of entries, including the filter options users can pick from.
* **Imports, exports, and the API**, wherever behaviour depends on the element type.

## Example: radio buttons in random order

Here is a complete, working element type that behaves exactly like the standard Radio Buttons element, except the options appear in a random order on every page load. This is useful in surveys, where the order of options can bias which ones people pick.

Save this as __/modules/formulize/class/shuffledRadioElement.php__ and it will appear as "Radio Buttons (shuffled order)" when adding elements in the admin UI. You configure it exactly like a normal radio button element — it inherits the radio element's admin Options tab.

### Why not just shuffle ele_value and call the parent's render()?

It's tempting to override __render()__ with only two lines — shuffle the incoming __$ele_value__ array, then call __parent::render()__ — and leave everything else inherited. That does not work, and it's worth understanding why, because the reason is a trap that applies to more than just this example.

The standard radio element's __render()__ does not submit the option text as the value of each radio button. It submits the option's __position__ — 1, 2, 3, and so on, counting through __$ele_value__ in whatever order it was given. The __prepareDataForSaving()__ method is called when the form is submitted, and it converts the position number into the text value for that option.

This is a standard practice with all the list element types (dropdowns, radio buttons, checkboxes, etc). The way to deal with this, used below, is to make the submitted value the option text, rather than its position. Then we can just validate that the submitted text is a real option for the element, and we're done.

~~~php
<?php

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/radioElement.php"; // make sure the classes we're extending have been read in first!

class formulizeShuffledRadioElement extends formulizeRadioElement {

	function __construct() {
		parent::__construct(); // sets up all the standard radio button properties
		$this->name = "Radio Buttons (shuffled order)"; // the name webmasters see when adding elements
	}

}

class formulizeShuffledRadioElementHandler extends formulizeRadioElementHandler {

	function create() {
		return new formulizeShuffledRadioElement();
	}

	// Render the element for display in a form, with the options in a random order on every page load.
	// The submitted value is the option text itself, not its position - see the "Why not just shuffle
	// ele_value..." explanation above for why that matters here.
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {

		// ele_value is the element-specific settings for the element
		// in radio buttons it is the set of options (as keys), with the value indicating
		// if it's the selected option, so 1 if it is selected, 0 if it is not
		// example with bananas selected: ['apples' => 0, 'pears' => 0, 'bananas' => 1]
		$selectedOption = "";
		foreach($ele_value as $optionText=>$selected) {
			if($selected > 0) {
				$selectedOption = $optionText;
				break;
			}
		}
		// disabled elements just render as plain, non-interactable text
		if($isDisabled) {
			return new XoopsFormLabel($caption, $selectedOption);
		}

		// gather the option texts, and shuffle them - the whole point of this element type!
		$optionTexts = array_keys($ele_value);
		shuffle($optionTexts);

		// construct the key-value pairs for rendering
		// we're going with the literal option text (random order now), as value and as label
		$keyValuePairs = array();
		foreach($optionTexts as $optionText) {
			$keyValuePairs[$optionText] = $optionText;
		}

		// create a radio button series with these key-value pairs, and the selectedOption
		// separate with line breaks
		$formElement = new XoopsFormRadio($caption, $markupName, $selectedOption, "<br />");
		$formElement->addOptionArray($keyValuePairs);

		// A lot of saving and validation behaviours depend on this global javascript value being set
		// So we make sure to add code to the element so this is set when there's a change in the selection
		$formElement->setExtra("onchange=\"javascript:formulizechanged=1;\"");

		return $formElement;
	}

	// Package up what the user submitted, for insertion into the form's data table.
	//
	// Never trust a submitted value just because it came back through a field your own render()
	// method produced - a user can submit any string they like, regardless of what your HTML offered.
	// optionIsValid() (inherited from the radio element) confirms the text we received is genuinely
	// one of this element's configured options before we accept it.
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		if (!$element->optionIsValid($value)) {
			return null;
		}
		return htmlspecialchars($value);
	}

}
~~~

### What the example deliberately leaves out

The standard radio element's render method supports several extra features that this simple example does not reproduce: the "other" write-in option (__{OTHER|...}__), alternate on-screen text for options (uitext), configurable delimiters between options, and the preservation of out-of-range values saved under an older set of options. If your custom element needs those, study the render and prepareDataForSaving methods in __radioElement.php__ — but note that they work in terms of option positions, so you would need to carry the presented order through to the save step yourself.

## Tips

* Everything the handler methods must do is documented in comments inside the standard element class files. __provinceListElement.php__ is a compact example of a complete element type built from scratch; __ynElement.php__ is a compact example of extending another type (it extends the radio element).
* Test your element as all the different kinds of users: make entries, edit entries, view them in a list, search and filter on your element's column, and check the element in a disabled state (viewing an entry without edit permission).
* Keep your custom class files in your own version control. They live inside the Formulize module folder, so make sure your deployment process preserves them when you upgrade Formulize.
