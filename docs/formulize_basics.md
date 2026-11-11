---
layout: default
permalink: documentation/basics/
title: Formulize Basics
description: A short guide to the words you'll meet in Formulize, including forms, elements, entries, screens, users, groups and permissions.
---

# Formulize basics

These are the words you'll meet when you use Formulize or build something with it.

## Your data

**Application**
: A set of forms that belong together, with a menu that takes people to them. A form can be in more than one application.

**Form**
: The basic building block. A form holds one kind of information, such as Artifacts or Donors. Most forms hold data records, but a form's entries can also represent [people with accounts, or groups of people](/documentation/entries_are_users_and_groups/).

**Element**
: One part of a form, usually a question for people to answer. Elements include text boxes, dropdown lists, radio buttons, check boxes and date selectors. Others hold headings and instructions, or embed other forms.

**Entry**
: A set of answers from a filled-in form: one Artifact, one Donor. All the entries in a form have the same elements.

**Owner**
: The user an entry belongs to, usually the person who made it. An entry also belongs to its owner's groups, which can allow certain other people to see it depending how the permissions are configured.

**Principal identifier**
: The element that names an entry in a form, such as an Artifact's name. Formulize uses it wherever it needs to show which entry is which.

**Derived value**
: An element that works out its value with a formula, instead of someone typing it in. A Full Name could be made from a first name and a last name. Derived values are worked out when an entry is saved.

## What people see

**Screen**
: A way of presenting a form to people. A form can have several screens, for different jobs. You don't usually need a separate screen for each group, because the same screen follows the permissions of whoever is using it.

| Type of screen | What it does |
|---|---|
| Form screen | Lets people create and edit entries, on one or more pages |
| List screen | Shows the entries in a form as a list, to find, search and open them |
| Calendar screen | Shows entries on a calendar |
| Map screen | Shows entries on a map |
| Template screen | Shows whatever you want, in a custom template |

**Menu**
: How people get around. A menu item leads to a form, a screen or a web address. Most items are inside an application, and some can be beside the applications, at the top of the menu. It's only a link, so it doesn't give anyone permission to use the form, and having permission doesn't put an item in someone's menu. You set those separately. See [The menu](../menu/).

**Saved view**
: A list set up a particular way, with its columns, searches and sort order. Saved views are a handy way to store list settings so you can easily see those entries again.

**Look**
: How a site's pages look, including the logo, colours, fonts and sizes. See [Looks and the Appearance editor](/documentation/appearance_editor/).

## Who can do what

**User**
: An account that someone logs in with.

**Group**
: A set of users, such as Curators or Managers. A user can belong to many groups. A few groups come with every site:

| Group | Who's in it |
|---|---|
| Webmasters | The people who run the site. They can do everything. |
| Registered Users | Everyone with an account |
| Anonymous Users | Visitors who aren't logged in |

**Permissions**
: What people can do with a form. Permissions are never given to users directly. They're given to groups, and a user gets all the permissions of all the groups they belong to.

Each form has its own permissions, and they come in three kinds:

- **Access** lets a group's members reach the form. Nothing works without it.
- **Visibility** decides which entries they can see once they're in.
- **Abilities** are what they can do, such as creating entries, or changing and deleting them.

**Visibility**
: Which entries someone can see. It might be only the entries they made, the entries made by people in their groups, or all the entries in the form. Everyone can always see the entries they made themselves. A group can also have conditions that limit it further.

**Their groups**
: For one person and one form, this means the groups that person belongs to which also give access to the form. It's what "the entries in their groups" refers to in the permissions.

## How things connect

**Connection**
: A link between two forms, so that their entries can be used together. See [How forms connect](/documentation/relationships/).

**Linked element**
: An element whose options come from the entries in another form. This is the main way forms get connected.

**Subform**
: A form embedded inside another form, so that people can work with connected entries in one place.

**Relationship**
: A set of connections between forms. The Primary Relationship holds every connection in the system, and it's kept up to date automatically.

**Conditions**
: Rules such as "Status is Active". They decide things like when an element appears, and which entries someone can see, and they're used in many places throughout Formulize.
