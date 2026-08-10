---
layout: default
permalink: searching/
title: Searching
---

# Cheat Sheet for searching through your entries

There are a lot of different search terms that you can type in the “quicksearch” boxes at the top of each column in the list of entries pages.  Here is a brief summary:

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


## Duration

 **This search term:** 				| **Means this:** 														| **It will find:**
:-----------------------------------|:----------------------------------------------------------------------|:--------------------------------------------------------------
 1d 1h 10m <br/> 10m 1d 1h 			| Find a duration set to exactly 1510 minutes							| A duration set to exactly 1510 minutes
 <=1h 10m 							| Find a duration less than or equal to 70 minutes						| Any duration less than or equal to 70 minutes
 <5h AND >10m 						| Find a duration greater than 10 minutes and less than 300 minutes		| Any duration greater than 10 minutes and less than 300 minutes


## More than one search on the same column

You can put several search terms in one box, joined by AND or by OR.  Type them in capitals, with a space on either side.

 **This search term:** 			| **Means this:** 												| **It will find:**
:-------------------------------|:--------------------------------------------------------------|:----------------------------------------------------------------------
 >10 AND <100 					| find numbers between 11 and 99 								| 11, 12, etc
 >10 AND <10 					| find entries that are both greater than 10 and less than 10 	| it will find nothing...the search condition is logically impossible
 santa AND !monica 				| find entries that contain “santa” and not “monica” 			| Santa Claus <br/> Santa Cruz
 >=2008-10-01 AND <=2008-10-31 	| find dates within October 2008 								| 2008-10-01 <br/> 2008-10-15
 red AND orange AND blue 		| find those three values.  You can have as many terms as you want in a single column. | Searches like this will usually only find matches if the field you're searching accepts multiple selections (ie: checkboxes), and red, orange and blue were three of the options.
 red OR blue 					| find entries that match either one. Terms joined by OR only need one of them to match. | red <br/> blue

If you use OR in more than one column at a time, all of those terms end up in a single group, so an entry only has to match one of them.  See “OR searches on multiple columns” below for how that works, and how to make more than one separate group.


## Using AND and OR in the same column

Writing AND and OR between terms in the same box does not work — there is no way to tell which part of “red OR blue AND green” you meant to group together, so the whole thing is searched for as plain text and finds nothing.

To do it, join every term with AND, and put OR: on the front of the ones that should only need one match.  The OR: terms become a group of their own, and that group is then one of the things that has to be matched.

 **This search term:** 											| **Means this:** 														| **It will find:**
:---------------------------------------------------------------|:------------------------------------------------------------------------|:-------------------------------------------------------------
 santa AND OR:cruz AND OR:claus 								| santa has to match, and then either cruz or claus						| Santa Cruz <br/> Santa Claus <br/> (it won't find Santa Fe)
 ORSET1:red AND ORSET1:blue AND ORSET2:small AND ORSET2:large 	| two separate groups, each needing only one match.  Use this when a plain OR: group isn't enough, the same way it works across columns. | entries that are (red or blue), and that are also (small or large)

Remember that plain OR: terms all join one group no matter which column they are in, so if you are already using OR: on another column, use the numbered ORSET form here to keep the two sets of terms apart.


## Wildcard terms {TODAY}, {USER}, {BLANK}, and {element_handle}

 **This search term:**		| **Means this:** 											| **It will find:**
:---------------------------|:----------------------------------------------------------|:--------------------------------------------------
 {TODAY} 					| find today's date 										| the text of today's date, in YYYY-mm-dd format
 >={TODAY-30} 				| find dates anytime from the past 30 days into the future 	| you get the idea
 >={TODAY-30} AND <={TODAY}	| find dates between 30 days ago and today
 >{TODAY+14} 				| Find dates more than 14 days in the future
 {USER} 					| Find entries that match the current user's full name, or if no full name is in their profile, then match on their username
 {BLANK} 					| Find entries that are blank or empty
 >{attendance_buyers} 		| Compare against the value in another column, instead of against a value you type.  What goes in the braces is an element handle.  Typed into the attendance_sellers column, this finds the entries where the value in attendance_sellers is greater than the value in attendance_buyers. | An entry with attendance_buyers 12 and attendance_sellers 15
 ={attendance_buyers} 		| Find entries where the two columns hold exactly the same value.  Every operator works this way, so <, >=, != and the rest all compare the two values. | An entry with attendance_buyers 12 and attendance_sellers 12
 {attendance_buyers} 			| With no operator in front, it means "contains", the same as any other search term.  So this finds entries where the value in attendance_sellers contains the value in attendance_buyers. | An entry with attendance_buyers 12 and attendance_sellers 120

The name inside the braces is an **element handle** — the handle of the element whose column you want to compare against, exactly as it appears in the form editor.  The search does not look for that text.  It reads the value held in that column and compares this column against it, and it does that for each entry using that entry's own two values, so every entry is measured against itself rather than against any other entry.

A few other things to know about comparing two columns:

- The element you name has to be on the same form as the column you are searching, or on the main form of the list.  A reference to an element on any other form is ignored.
- The comparison follows the kind of column it is.  Two number columns compare as numbers, and two date columns compare as dates, but two ordinary text columns compare as text, where "9" is bigger than "10" because it starts with a bigger character.
- If the name in the braces is not an element handle, and is not being filled in from the page address, the search term is ignored.


## “OR” searches on multiple columns

The OR you type between two terms only joins terms inside that one box.  To make an OR search that spans two different columns, put OR on the front of the term in each of them instead.

 **This search term:** | **Means this:** | **It will find:**
:----------------------|:----------------|:------------------
 on the fruit column: <br/> OR:apples <br/> and at the same time on the vegetable column: <br/> OR:carrots | Find entries that match either apples in the fruit column, or carrots in the vegetable column (normally, search terms on multiple columns must all be matched) | An entry with Apples in Fruits, or an entry with Carrots in Vegetables
 on the fruit column: <br/> ORSET1:apples <br/> on the vegetable column: <br/> ORSET1:carrots <br/> on the colour column: <br/> ORSET2:red <br/> on the size column: <br/> ORSET2:large | Make more than one separate “OR” group.  All the plain OR terms join together into a single group, but sometimes you need to ask two independent questions.  Terms with the same number are OR'd together, and then every group has to be matched. | entries that have (apples or carrots), and that also have (red or large)


## Finding entries that have nothing matching in a connected form

Some lists show information from more than one form at once. A list of customers might also show each customer's orders, for example. In a list like that, a single customer might have multiple orders showing.

Searching for what is *missing* in a list like that needs a special search term. If you search the product column for “!shoes” (not shoes), you will find customers who have at least one order that isn't shoes. That includes customers who have an order for shoes, and an order for socks (because “socks” matches “not shoes”). Usually that isn't the question you meant to ask.

EMPTYSET: asks it a different way.  Instead of looking at each order (row) on its own, it looks at all of one customer's orders together, and finds the customers where *none* of them match.

 **This search term:** | **Means this:** | **It will find:**
:----------------------|:----------------|:------------------
 on the product column: <br/> EMPTYSET:shoes | Find the customers who have never ordered shoes | Customers with no shoe order at all, including customers who have never ordered anything
 on the product column: <br/> EMPTYSET:shoes <br/> and at the same time on the status column: <br/> EMPTYSET:cancelled | Find the customers who have no cancelled orders for shoes. When you put EMPTYSET: on more than one column, each record is matched against all of those conditions together. | Customers with no cancelled shoe order.  A customer who cancelled a sock order is found. A customer who ordered shoes and kept them, is found.
 on the product column: <br/> EMPTYSET1:shoes <br/> and at the same time on the status column: <br/> EMPTYSET2:cancelled | Ask two separate questions instead of one.  Adding a number makes a separate group, the same way ORSET does above, so these become two independent conditions rather than a description of one order. | customers who have never ordered shoes, and who have also never cancelled anything

A few things to know about EMPTYSET:

- It only works on columns that come from a connected form. In the example above, EMPTYSET: belongs on Order form columns, not on Customer Profile form columns.
- Entries with nothing connected to them at all are always found. A customer who has never placed an order would be included, regardless of the search term.
- You can search for anything you would normally type, not just plain words.  EMPTYSET:>100 on an Amount column finds the entries with no connected record with an amount over 100.
- EMPTYSET terms are always combined with “and”.


## Advanced developer-focused search terms

 **This search term:** 	| **Means this:**
:-----------------------|:---------------
 {order}				| Find entries that match whatever is in $_POST['order'] or if that's empty, $_GET['order'].  This is meant for use in a complex application where a certain screen might need to show different things at different times.  You can save one view with this kind of search term and then make some kind of architecture (with javascript maybe?) to populate $_POST or $_GET with the values you need at the right time.  If there is no value to find, the search term is ignored.  A name that is both a request value and an element handle is taken as the request value, so a request value always wins over the column comparison described above.
 !{order} 				| Find entries that do not match whatever is in $_POST['order'] or $_GET['order']
 !orange! 				| Persist this search term even if this column is not included in the view.  Certain columns are visible only to certain groups of users.  You might want to use a search term on a certain column that only webmasters have access to, in order to limit the list of entries.  Then you could publish that view to other users who do not have that column available, but the list of entries would still be limited by this search term.
 !!monica! AND !santa! 	| When persisting a search that includes multiple terms, put the ! ! around each term separately, not around the whole thing.  Don't get confused by “not” operators (!) that may be part of the terms (as in “not monica” at the beginning of this set of terms).
