---
layout: default
permalink: developers/API/code_flow/
---

# The flow through the code

## Introduction

Formulize is a complex piece of software, that has grown organically over many years. The basic idea is that the user (a webmaster of some kind) has defined a form, and the rules for _who_ can interact with the form, and _how_ they can interact with the form. Formulize reads all that configuration data, and dynamically generates a user interface based on the configuration.

Formulize is a web-based software written in PHP. It resides on a web server, and communicates with a client (web browser) through the http protocol. The server responds with a normal stream of HTML and CSS and Javascript code, and the web browser does the rest.

Formulize is 20 years old. What started out as a simple form generator, is now a full database application creation environment. The flow of execution throgh the code has got more complex and nuanced. This page tries to explain how it works.

Formulize began as a module in the XOOPS cms. XOOPS was forked several times and fizzled out as Drupal and then WordPress gained popularity. One prominent fork was ImpressCMS, and that is the version that the standalone version of Formulize is based on. Architecturally, the Formulize code is still a module inside the XOOPS/ImpressCMS system, and the lowest level operations, session management, database i/o, basic page rendering, etc, are all handled by the underlying cms.

## Code organization

Here is a partial listing of the files and folders in Formulize, and what they do. These are the main ones that are key to making the website operate. There are a lot of other files and folders besides these!

* __Root Folder of Website__
    * ___mainfile.php___ << bootstraps the entire system, included at the top of every page generation process
    * ___header.php___ << starts up the theming and template engine (which is Smarty)
    * ___footer.php___ << renders the page contents using Smarty
    * __libraries__
        * __icms__ << the core files that drive all basic operations, the db connection, the user session, page rendering, etc
    * __modules__
        * __formulize__ << where the magic happens
            * ___index.php___ << handles all formulize page requests, hands off to initialize.php
            * ___master.php___ << alternate starting point for Formulize requests, that ignores screens and renders the most basic version of the list/form, which is useful for seeing all entries in a form, regardless of any screen settings that might hide certain things
            * ___initialize.php___ << primary routing controller, figures out what functions/classes/methods to call to render the page
            * __admin__ << all the logic for generating backend admin pages and saving configuration data
            * __blocks__
                * ___mymenu.php___ << the logic for generating the elements in the sidebar menu
            * __class__ 
                * ___data.php___ << the data handler class
                * ___elementrenderer.php___ << where the Formulize config data is turned into a renderable XOOPS form element object
                * ___elements.php___ << the base class for all form elements
                * ___checkboxElement.php___ << any file ending in 'Element.php' is a class file for a specific element type
                * ___screen.php___ << the base class for Formulize screens
                * ___formScreen.php___ << handles legacy Form screens, hands off rendering to _include/formdisplay.php_
                * ___multiPageScreen.php___ << handles Form screens, hands off rendering to _include/formdisplaypages.php_
                * ___listOfEntriesScreen.php___ << handles list-of-entries screens, hands off rendering to _include/entriesdisplay.php_
                * ___templateScreen.php___ << handles template screens
                * ___calendarScreen.php___ << handles calendar screens, hands off rendering to _include/calendardisplay.php_
                * ___frameworks.php___ << the form relationship class
            * __include__ 
                * ___formdisplay.php___ << handles rendering of forms, includes an extension of the XOOPS form class to render elements
                * ___formdisplaypages.php___ << handles rendering of the multipage structure of form screens, hands off to _formdisplay.php_
                * ___elementdisplay.php___ << makes renderable XOOPS form element objects, hands off to _class/elementrenderer.php_
                * ___entriesdisplay.php___ << handles rendering of data into interactive lists of entries
                * ___calendardisplay.php___ << handles rendering of data into calendars (not well refined yet)
                * ___graphdisplay.php___ << handles rendering of data into graphs (not well refined yet)
                * ___extract.php___ << the data extraction layer, for retrieving data from any dataset, and functions for handling it
                * ___readelements.php___ << handles receiving data from a form submission and saving it to the DB
                * ___functions.php___ << a giant set of functions for doing different operations, some tiny, some huge
            * __templates__
                * __admin__ << where all the page templates are for the backend admin pages
                * __screens__
                    * __Anari__ << contains default templates, and folders with numeric names corresponding to screen id numbers. These numeric folders contain any custom templates used for each screen.
                        * __default__ << where the default templates are for lists and forms in the Anari theme
                            * __form__ << templates for rendering forms when no screen is in effect. Also used for legacy form screens from before Formulize 7 (all form screens are multipage now)
                            * __listOfEntries__ << templates for rendering lists of entries
                                * ___toptemplate.php___ << the template for rendering contents above the list of entries, ie: buttons, navigation aids, etc
                                * ___openlisttemplate.php___ << the template for content that opens the list of entries, ie: <table> etc. Also contains any items that occur only at the top of the list, but after the list container
                                * ___listtemplate.php___ << the template that renders each item in the list
                                * ___closelisttemplate.php___ << the template the closes the list
                                * ___bottomtemplate.php___ << the template that renders all content below the list
                            * __multiPage__ << templates for rendering form screens
                                * ___toptemplate.php___ << the template for rendering the title, tab navigation, and any other details that appear above the form elements
                                * ___elementcontainero.php___ << the template that opens the DOM element that contains a form element (ie: a <tr> or <div> etc)
                                * ___elementtemplate1.php___ << the template that is used to render form elements when the form is using a single column layout
                                * ___elementtemplate2.php___ << the template that is used to render form elements when the form is using a two column layout (and this layout should collapse responsively into a single column layout if the form is rendered on a narrow screen)
                                * ___elementcontainerc.php___ << the template that closes the DOM element that contains a form element (ie: a </tr> or </div> etc)
                                * ___bottomtemplate.php___ << the template that renders the page below the main list of entries
                    * __default__ << where the general default templates are for lists and forms if there are none for the active theme
    * __themes__
        * __Anari__
            * ___theme.html___ << the main page template used for all pages in the website
            * __css__
                * ___style.css___ << the css file containing all the site-wide styles for the website
            

## Code flow

1. __index.php__

    This is the file that starts every Formulize page load. A typical URL in Formulize would be: https://mysite.com/modules/formulize/index.php?fid=6
    
    When such a URL is requested, this file includes the core _mainfile.php_, which sets up the user's session and various other "housekeeping." Then it includes _header.php_ which starts up the theme and template systems. The _index.php_ file includes Formulize's own _initialize.php_ file, which leads to the main execution of Formulize. Lastly, this file includes _footer.php_ which renders the page contents using the theme and template system that started up inside _header.php_.

    However, if a _$formulize_screen_id_ is set, then this file will not call _mainfile.php_, or _header.php, or _footer.php_. Instead processing will proceed directly to initialize.php. This is because we assume that if _$formulize_screen_id_ is already set, then someone is working with Formulize in PHP code, rather than making a request via the URL. We assume they know what they're doing and will have included the necessary core files themselves.
    
    If you want to follow the full XOOPS/ImpressCMS bootstrap flow, _mainfile.php_ includes _include/common.php_, which then ultimately invokes a series of classes in the _libraries/icms/_ folder, and elsewhere, including handling the session, establishing the database connection, etc. 

2. __initialize.php__

    This file does a lot of basic checks and gathering of information, including a basic security check to see if the user has permission to view the requested form. The requested form is determined based on the URL parameters _fid_ or _sid_. They stand for form id and screen id. Alternatively, if _$formulize_screen_id_ was already set, then that will be used to determine which screen to display.

    Prior to rendering the list of entries or the form, this file calls _include/readelements.php_ which is the single file that handles all the reading of data submitted from a form. If the user submitted data from the prior pageload, the _readelements.php_ is how that data gets written to the database. 

    Once _readelements.php_ is finished, then execution hands off to the relevant classes for rendering the screen. If a form was requested rather than a specific screen, the default list screen for that form will be displayed (unless the user only has permission for a single entry in the form, in which case the default form screen will be displayed instead).
    
    If a form was requested, but it has no default screen declared, then the displayEntries or displayForm functions are called, depending on the user permissions and form settings. This low level rendering, bypassing screens, is also used when _master.php_ is requested (instead of _index.php_).

3. __class/listOfEntriesScreen.php__ or __class/multiPageScreen.php__ (or other screen classes)

    If a screen is being rendered, execution will flow to the appropriate class file. These files contains the classes for list of entries screen objects, form screen objects, etc, which contain all the configuration settings for those types of entities in Formulize. These files also have handler classes that have a render method to display a screen based on a given object of that type.
    
    When the render method of the handler is called, it in turn calls the _displayEntries_ function in the _include/entriesdisplay.php_ file for lists of entries. Or the _displayFormPages_ function in the _include/formdisplaypages.php_ file for forms. The screen object is passed to those functions, so the screen settings can be taken into account as the rendering happens.
    
4. __include/entriesdisplay.php__

    This file contains the function _displayEntries_ which reads the status of things based on the URL, the logged in user, and the data submitted from the prior page load. Based on that, and the screen settings, it then draws the list of entries, or if the user has drilled down into an entry, it hands off execution to a form screen (or the _displayForm_ function directly if no screen is specified).
    
    When displaying a list of entries, execution is passed to various other functions in the file, and other files. Here are the major operations it performs:
    
    1. First, the saving and loading of _Saved Views_ is performed. _Saved Views_ are collections of settings used to define what columns and searches, etc, are in effect. _Saved Views_ can be used to control the starting configuration of a screen. End users might also save views, so they can quickly return to a set of options that is useful to them. On any given page load we could be saving a new view, or loading up a previously saved view.
    
    2. Any _Custom Buttons_ that were clicked on the prior page are processed. This is so they have a chance to affect data prior to the data being gathered.
    
    3. A determination is made of whether the user has requested a specific entry, and if so what screen do we hand off execution to so that the entry can be displayed. This happens if the user has clicked the icons for editing/viewing a specific entry. If execution hands off to another screen, then no further execution occurs in _entriesdisplay.php_.
    
    4. We call _getData_ in the _include/extract.php_ file, which retrieves the data to display in the screen. The _getData_ function retrieves all data for any dataset, regardless of the number of forms or the way they are joined.
    
    5. We call _drawInterface_ (in this same file) which determines what UI elements are available for above the screen, and then renders the _toptemplate.html_ for the screen.
    
    6. We call _drawEntries_ (in this same file) which determines what UI elements are available in the list of the screen, and then renders the _openlisttemplate.html_, then loops through all the records in the dataset and displays each one using the _listtemplate.html_, and then renders the _closelisttemplate.html_
    
    7. Back within the _displayEntries_ function, it renders the _bottomtemplate.html_ for the parts of the page below the list of entries.
    
5. __include/formdisplaypages.php__

    This file contains the function _displayFormPages_ which reads the status of things based on the URL, the logged in user, and the data submitted from the prior page load. Based on that, and the screen settings, it figures out which page to display and then hands off rendering of the actual HTML form to the _displayForm_ function in the _include/formdisplay.php_ file.
    
6. __include/formdisplay.php__

    This file contains the function _displayForm_ function which reads the status of things based on the URL, the logged in user, and the data submitted from the prior page load. Based on that, and the screen settings, it gathers the necessary elements to display, figures out which page to display and does the rendering of the actual HTML form.
    
    This file contains several other important functions and classes, which are called at various times in the process of displaying the form. Here are the major ones:
    
    * The *formulize_themeForm* class, which extends the underlying XOOPS theme form class, but includes many customizations for displaying Formulize forms, including handling the rendering of all the templates for the form screen.
    
    * _getEntryValues_ which gathers the current values from the database for a given entry so they can be displayed in the form when editing an entry
    
    * _drawSubLinks_ which has extensive code related to the rendering of subform elements, which are elements that contain entire entries from another form
    
    * _compileElements_ which loops through the elements that are part of the page, and converts the Formulize configuration settings for each one into a XOOPS form element object, which can be rendered in the _formulize_themeForm_ class (this conversion happens in the _include/entriesdisplay.php_ file, and the _class/elementrenderer.php_ file)
    
    * _loadValue_ which takes the values from the database for a given entry, and injects them into the Formulize configuration settings for a given element, so that when the element is rendered, it shows the saved value instead of its default value
    
    * _writeHiddenSettings_ which creates hidden elements to include in the form that will pass back various indicators of what has happened on this page load, so the next page load has the right cues to start where this one left off
    
    * _drawJavascript_ which contains all the Javascript code that makes the interactive elements of the form work
    
7. __include/elementdisplay.php__

    This file primarily contains the _displayElement_ function, which figures out if the user has permission to see a given form element, within a given entry, and if so, it converts the Formulize configuration settings to a XOOPS form element object. This conversion happens by calling the _elementrenderer_ class in _class/elementrenderer.php_.
    
    The XOOPS form element objects are rendered later in the *formulize_themeForm* class (found in _include/formdisplay.php_).
