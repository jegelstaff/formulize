---
layout: default
permalink: searching/
---

# Cheat Sheet for searching through your entries

There are a lot of different search terms that you can type in the “quicksearch” boxes at the top of each column in the list of entries pages.  Here is a brief summary:

## The basics

 **This search term:**	| **Means this:** 											| **It will find:**
:-----------------------|:----------------------------------------------------------|:------------------------------------------------------
 orange		       		| find "orange" by itself or inside something else			| orange <br/> orange peels <br/> apples and oranges
 =orange				| find the exact text “orange”, no more, no less			| orange <br/> (it won't find “oranges”)
 !orange 	 			| find entries that do not have orange in them				| red <br/> purple <br/> banana peels
 !=orange 	 			| find entries that do not match the exact text “orange” 	| red <br/> apples and oranges <br/> orange peels
 15 					| find the text “15” by itself or inside something else 	| 15 baseballs <br/> 12715 <br/> 15
 =15 					| find the exact number 15  					   			| 15
 !15 					| find text that does not contain “15” 						| 1848 <br/> 3 strikes <br/> etc
 !=15 					| find anything except the exact number 15 					| 14 <br/> 16 <br/> 15 baseballs


## Numbers

 **This search term:**  | **Means this:** 								| **It will find:**
:-----------------------|:----------------------------------------------|:------------------
 >10 					| find numbers greater than 10					| 11, 12, 1485
 >=1000 				| find numbers greater than or equal to 1000 	| 1000, 1001, etc
 <65 					| find numbers less than 65 					| 64, 10, -401
 <=65 					| find numbers less than or equal to 65 		| 65, 64, etc


## Dates

 **This search term:** 			| **Means this:** 																	| **It will find:**
:-------------------------------|:----------------------------------------------------------------------------------|:-------------------------------------------------------------------------
 2009-01-01 					| find January 1, 2009 																| 2009-01-01
 >=2009-01-01 					| find dates on or after January 1, 2009 											| 2009-01-01 <br/> 2009-05-23 <br/> 2010-02-20 <br/> etc
 <2009-01//>2007-12 			| find dates in the year 2008 (see below for more info on using two terms at once) 	| Any date starting with 2008
 >=2008-10-01//<=2008-10-31 	| find dates in October 2008 (see below for more info on using two terms at once) 	| Any date starting with 2008-10
 2008-10 						| find dates in October 2008 														| Any date starting with 2008-10 (same as the previous, more complex search


## More than one search on the same column

 **This search term:** 			| **Means this:** 												| **It will find:**
:-------------------------------|:--------------------------------------------------------------|:----------------------------------------------------------------------
 >10//<100 						| find numbers between 11 and 99 								| 11, 12, etc
 >10//<10 						| find entries that are both greater than 10 and less than 10 	| it will find nothing...the search condition is logically impossible
 santa//!monica 				| find entries that contain “santa” and not “monica” 			| Santa Claus <br/> Santa Cruz
 >=2008-10-01//<=2008-10-31 	| find dates within October 2008 								| 2008-10-01 <br/> 2008-10-15
 red//orange//blue 				| find those three values.  You can have as many terms as you want in a single column. | Searches like this will usually only find matches if the field you're searching accepts multiple selections (ie: checkboxes), and red, orange and blue were three of the options.


## Wildcard terms {TODAY}, {USER} and {BLANK}

 **This search term:**		| **Means this:** 											| **It will find:**
:---------------------------|:----------------------------------------------------------|:--------------------------------------------------
 {TODAY} 					| find today's date 										| the text of today's date, in YYYY-mm-dd format
 >={TODAY-30} 				| find dates anytime from the past 30 days into the future 	| you get the idea
 >={TODAY+30}//<={TODAY}	| find dates between 30 days ago and today
 >{TODAY+14} 				| Find dates more than 14 days in the future
 {USER} 					| Find entries that match the current user's full name, or if no full name is in their profile, then match on their username
 {BLANK} 					| Find entries that are blank or empty


## "OR" searches on multiple columns

 **This search term:** | **Means this:**
:----------------------|:----------------
 on the fruit column: <br/> ORapples <br/> and at the same time on the vegetable column: <br/> ORcarrots | Find entries that match either apples in the fruit column, or carrots in the vegetable column (normally, search terms on multiple columns must all be matched)

## "natural language" searches in a single column

 **This search term:** | **Means this:**																		| **It will find:**
 :---------------------|:---------------------------------------------------------------------------------------|:-------------------
 banana OR apple	   | find entries that contain banana or apple.  This is equivalent to ORbanana//ORapple.	| bananas <br/> apples <br/> grapples
 red AND orange AND blue | find entries that contain all three values.  Equivalent to red//orange//blue.		| red orange blue

## Advanced developer-focused search terms

 **This search term:** 	| **Means this:**
:-----------------------|:---------------
 {order}				| Find entries that match whatever is in $_POST['order'] or if that's empty, $_GET['order'].  This is meant for use in a complex application where a certain screen might need to show different things at different times.  You can save one view with this kind of search term and then make some kind of architecture (with javascript maybe?) to populate $_POST or $_GET with the values you need at the right time.
 !{order} 				| Find entries that do not match whatever is in $_POST['order'] or $_GET['order']
 !orange! 				| Persist this search term even if this column is not included in the view.  Certain columns are visible only to certain groups of users.  You might want to use a search term on a certain column that only webmasters have access to, in order to limit the list of entries.  Then you could publish that view to other users who do not have that column available, but the list of entries would still be limited by this search term.
 !!monica//santa! 		| When persisting a search that includes multiple terms, put the ! ! at the very beginning and very end of the terms.  Don't get confused by “not” operators (!) that may be part of the terms (as in “not monica” at the beginning of this set of terms).

