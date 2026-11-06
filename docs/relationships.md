---
layout: default
permalink: documentation/relationships/
title: How Forms Connect
description: How connections between forms work in Formulize, which kind to choose, and why there is no many-to-many option.
---

# How forms connect

Most applications have more than one form, and the forms are related. Artifacts come from Donors. Exhibits show Artifacts. When two forms are connected, Formulize can show their entries together, and let people search and filter on them together.

## Think about one entry at a time

In Formulize, a connection describes what one entry in a form can have in the other form:

- **One to one:** each entry has one entry in the other form.
- **One to many:** each entry can have many entries in the other form.
- **Many to one:** many entries can share the same entry in the other form.

One to many and many to one are the same connection, seen from each side. Each Donor has many Artifacts, and many Artifact have the same Donor.

If you've worked with SQL databases, this isn't a join that you define. There is also no many-to-many option, because from any one entry, the other side is either one entry or many. See [Connecting many entries to many entries](#connecting-many-entries-to-many-entries) for how to do that.

## Making a connection

You don't need to set up a relationship first. Add one of these to a form, and the forms are connected:

| Add this | What it does |
|---|---|
| An element with options linked to another form | People choose from the entries in the other form. Most connections are made this way. |
| An embedded form | Entries from the other form appear inside this one. |
| Elements with a common value | The two forms each have an element holding the same value, such as a Teacher Name in both a Teacher Profile and a Class List. |

Formulize keeps a catalogue of every connection in the system, called the **Primary Relationship**. It's updated automatically as you make connections, and new screens use it by default, so you can usually forget it's there.

## Choosing the linked element

The element you link decides what people can pick:

| You want | Use | Example |
|---|---|---|
| Each entry connects to one entry in the other form | A linked dropdown list, or a linked autocomplete box | Each Artifact has one Donor. Many Artifacts can have the same Donor. |
| Each entry connects to several entries in the other form | Linked checkboxes, or a linked multi-autocomplete box | Each Exhibit has several Artifacts. An Artifact can be in several Exhibits. |

## Connecting many entries to many entries

The Exhibits example above is what other database tools call many to many: each Exhibit has many Artifacts, and each Artifact can be in many Exhibits.

To do this in Formulize, put linked checkboxes or a linked multi-autocomplete box in one of the forms. You don't need a third form to hold the connections. The connection then works as one to many from one form, and many to one from the other.

## One to one connections

A one to one connection can change a lot about how two forms behave when they're shown together. Create one-to-one connections through the _Add a connection_ link in the Settings tab for a form.

There are two settings for each one-to-one connection:

- **When the user saves an entry in one form, create a matching entry in the other form if one does not exist already.** This ensures the two forms have corresponding entries in sync. Someone fills in one form and the other form will have a matching entry even if it isn't part of the same screen.
- **When the forms are shown together, and the linking value is changed, change the entry in the other form to match.** This displays a different entry from one form based on what's entered in the other. For example, one form has a dropdown of countries, and it's connected one to one with a Cities form. The entry from the Cities form that matches the selection is used as the rest of the form. Choose a different country, and that Cities entry is swapped for the new one.

## Deleting connected entries

Every connection has this setting: **When the user deletes an entry in one form, delete the linked entries in the other form.** Turn it on if the entries in the other form mean nothing without this one. Leave it off if they should stay.

## Connections and user accounts

A connection can also decide which groups a person belongs to. See [Forms that create users and groups](/documentation/entries_are_users_and_groups/).
