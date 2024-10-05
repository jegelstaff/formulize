---
layout: default
permalink: developers/API/functions/security_check/
---

# security_check( <span style='font-size: 14pt;'>(int) $form_id, (int) $entry_id = "", (int) $user_id = ""</span> )

## Location

/modules/formulize/include/functions.php

## Description

Checks if the user has permission to access the given form, and entry if an entry id is specified.

If no user is specified, then permissions will be checked for the user of the current session, ie: the logged in user at the time the function executes.

Generally, this check is performed internally by all Formulize screens and you do not need to check permissions yourself, unless you are doing something very out of the ordinary.

## Parameters

__$form_id__ - the id number of the form to be checked<br>
__$entry_id__ - Optional. The id number of the entry to be checked. If not specified, the function only checks if the user can access the form.<br>
__$user_id__ - Optional. The id number of the user to be checked. If not specified, this defaults to the user of the current session, ie: the logged in user.

## Return Values

Returns __true__ or __false__, depending if the user has access to the form, or to the entry if an entry id was specified.

## Examples

~~~
// Check if the current user has access to form 6
if(security_check(6) {
    echo "User has access!";
} else {
    echo "User does not have access!";
}
~~~

~~~
// Check if the current user has access to entry 99 in form 6
if(security_check(6, 99) {
    echo "User has access!";
} else {
    echo "User does not have access!";
}
~~~

~~~
// Check if user 17 has access to entry 99 in form 6
if(security_check(6, 99, 17) {
    echo "User has access!";
} else {
    echo "User does not have access!";
}
~~~
