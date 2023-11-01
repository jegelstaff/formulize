---
layout: default
permalink: developers/API/classes/
---

# Classes

## Formulize classes

* [data handler](../classes/data_handler/)
* [element objects](../classes/element_objects/) (coming soon)
* [element handler](../classes/element_handler/) (coming soon)
* [form objects](../classes/form_objects/) (coming soon)
* [form handler](../classes/form_handler/) (coming soon)
* [relationship objects](../classes/relationship_objects/) (coming soon)
* [relationship handler](../classes/relationship_handler/) (coming soon)
* [screen objects](../classes/screen_objects/) (coming soon)
* [screen handlers](../classes/screen_handler/) (coming soon)
* [Formulize permission handler](../classes/formulize_permission_handler/) (coming soon)

## XOOPS classes

* [member handler](../classes/member_handler/) (coming soon)
* [config handler](../classes/config_handler/) (coming soon)
* [group permission handler](../classes/group_permission_handler/) (coming soon)

## About ImpressCMS and XOOPS

Formulize is built on top of ImpressCMS, which was a fork of the XOOPS cms. ImpressCMS rewrote the core functions of XOOPS, but maintained backwards compatibility with older code that predates the fork. 

For example, the core _xoopsObject_ class is found in the file _kernel/object.php_ but all it does is extend the _icms_core_object_ class (found in _libraries/icms/core/Object.php_) and call the parent class's constructor.

The intention with Formulize was always to retain compatibility with both systems, therefore the 'xoops' terminology and references are used throughout, because they work in the original XOOPS system and in ImpressCMS.

## Patterns

The classes in XOOPS follow a pattern where there is a data access object, and then a handler class for working with the data access objects.

The data access objects extend the base class (_xoopsObject_) which contains various low level methods like _getVar_ and _setVar_. The data access objects mostly relate one-to-one with records in the database. For example, each user has a record in the _users_ table in the database. Each _$xoopsUser_ object contains properties that correspond to the fields in that user's record in the database.

The handler classes contain methods that do things with the data access objects, such as creating them, or updating the database with their values. Handlers are created by calling functions which do the work of including the class file and instantiating an object based on the class.

Examples:

~~~
// bootstrap XOOPS
// in this example the web root is one directory up (../) from the current file
include_once "../mainfile.php"; 

// get the member handler which lets you interact with users and related objects
$member_handler = xoops_gethandler('member');

// retrieve a user by ID number
$userObject = $member_handler->getUser(1);

// change the user's name
$userObject->setVar('uname', 'John Smith');

// insert the user back into the database, which will include the changed name
$member_handler->insertUser($userObject);
~~~

~~~
// bootstrap XOOPS
include_once "mainfile.php";

// get the currently logged in user and display their name
global $xoopsUser;
print $xoopsUser->getVar("uname");
~~~

In Formulize itself, this same pattern is followed by some classes. However because Formulize is technically a module inside the XOOPS system, a different function gets the handlers:

~~~
// bootstrap XOOPS
include_once "mainfile.php";

// get the element handler
// param1 is the name of the class file found in /modules/formulize/class
// param2 is the folder name of the module
$element_handler = xoops_getmodulehandler("elements", "formulize");

// retrieve element with id number 9
$elementObject = $element_handler->get(9);
~~~

Formulize also has a unique data handler class which is invoked more directly:

~~~
// bootstrap XOOPS
include_once "mainfile.php";

// include the Formulize functions file, which in turn will include all other parts of the Formulize API
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// setup a data handler for form id number 6
$data_handler = new formulizeDataHandler(6);

// get the entry ids in form 6 which have 'John Smith' as the value for a field 'profile_name'
$entries = $data_handler->findAllEntriesWithValue("profile_name", "John Smith");
~~~





