<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// form1 and form2 are coming through get. Should be form ids

// NEED TO FIGURE OUT EXISTING LINKED ELEMENTS BETWEEN THE FORMS

// If there's no primary identifer element in form 1, then... prompt user for one?

// Know PI form 1, and the existing connections, Options could then be:
// - any linked element in form 2 that points to the same source as a linked element in form 1, as a common value (would be a pair)
// - any linked element in form 2 that points to any element in form 1 (would be a pair)
// -- also take vice versa for multiselect linked elements, if relationship is one to many
// -- also take vice versa for non-multiselect linked elements, if relationship is one to one
// - any textbox element as a common value to PI form 1 (would be pair of PI form 1 plus an element selector for form 2)
// - a new element option in selector for form 2 (above) to create a connection to PI form 1:
// -- textbox (common value), or checkboxes or dropdown or autocomplete or multiselect autocomplete (linked element)

// OPTIONS FOR CONNECTION MUST BE SET, INCLUDING EXTRA ONES FOR ONE TO ONE
// IF ONE TO MANY, ADDITIONAL OPTION FOR SUBFORM ELEMENT CREATION
// OPTION TO EMBED FORM 2 INSIDE FORM 1, SHOW FORM INSIDE - THE OLD "DISPLAY TOGETHER" IDEA
// - ONLY AVAILABLE IF THERE ISN'T A SUBFORM ELEMENT ALREADY INSIDE FORM 1 THAT SHOWS FORM 2
// - GIVE AN OPTION FOR WHICH PAGE OF THE DEFAULT SCREEN THE ELEMENT SHOULD SHOW UP ON, OR ADD A NEW PAGE FOR IT

// ADD LINKS TO THE ELEMENT CONFIG PAGES IN THE CONNECTION DETAILS READOUT!!
