---
layout: default
permalink: documentation/entries_are_users_and_groups/
title: Forms That Create Users and Groups
description: How forms can create user accounts and groups from their entries, so that an application keeps running with little or no backend administration.
---

# Forms that create users and groups

A form can create a user account for each entry, or a set of groups for each entry. These are often called **entries-are-users** and **entries-are-groups** forms.

Each one sounds simple: make an entry, and it makes an account or a group. The real value comes from connecting these forms together, so that your data organizes your users and permissions. You configure the application once. After that, adding a department or a project is just a matter of creating an entry in a form, and it works. There's nothing else to set up, now or later.

You choose this on a form's **Settings** tab, under **What does each entry in this form represent?**

## Forms that create user accounts

Staff often use an application to keep records of volunteers, students, or whatever kinds of people the system manages. Choose **People with accounts** for a form like that, and each new entry creates a user account. Nobody has to go into the admin side to do it.

You can control two things:

- **Which entries create accounts.** Under **Create a user account for**, choose **Only entries matching conditions**, and then set the conditions.
- **Which groups the accounts belong to.** Under **Group Memberships**, add the groups. You can give each one conditions of its own. For example, people get the Managers group only if "Is a Manager" is Yes.

These memberships are enforced. While the conditions apply, the person stays in the group, and can't be removed from it by hand.

## Forms that create groups

If the information in your application is divided by project, you can have the groups and permissions follow along. Choose **Groups of people** for your Projects form, and each new project entry creates groups.

One entry can create several groups, because you may need different groups for a project's managers, staff and customers. List these under **User Categories for each entry in this form**. There is always an All Users category.

## Setting permissions with template groups

For a Projects form with Staff and Volunteers categories, Formulize creates a **template group** for each category: Projects All Users, Projects Staff and Projects Volunteers. These represent the categories in general, not any one project.

**Set your permissions on the template groups.** When someone creates a project called Ravine Cleanup, Formulize creates these groups for it:

- Ravine Cleanup - All Users
- Ravine Cleanup - Staff
- Ravine Cleanup - Volunteers

Each one automatically gets a copy of the permissions of the matching template group. If you change a template group's permissions later, its groups get the change too.

The groups for each entry stay out of the way when you're setting permissions. If one really needs something different, you can set an override on it.

## Using both kinds of form together

Put a linked element in a form that creates accounts, with a form that creates groups as its source, and a new user's groups can follow what's selected.

For example, a Volunteers form has a dropdown list linked to the Projects form, and Projects has a Volunteers category. In the Volunteers form's **Group Memberships**, add the Projects Volunteers template group, and choose the dropdown as the element that determines which project's group to use.

Now, when someone creates a volunteer and selects Ravine Cleanup, that person gets an account and joins Ravine Cleanup - Volunteers, with that group's permissions. Nobody has to set up an account, a group or a permission by hand.

To find out how forms link together, see [How forms connect](/documentation/relationships/).
