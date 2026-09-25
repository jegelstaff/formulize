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

define('FORMULIZE_QUERY_SCOPE_GROUP', 'group');
define('FORMULIZE_QUERY_SCOPE_MINE', 'mine');
define('FORMULIZE_QUERY_SCOPE_GLOBAL', 'all');

// values for useviewentrylinks/dedisplay (list of entries screens) and ele_value['edit_icon_style']
// (subform elements): which icon, if any, is used for the link/button that lets a user click
// through to view/edit an entry or activate an inline-editable element.
define('FORMULIZE_EDIT_ICON_STYLE_OFF', 0);
define('FORMULIZE_EDIT_ICON_STYLE_PEN', 1);
define('FORMULIZE_EDIT_ICON_STYLE_MAGNIFIER', 2);
// The theme an embedded screen renders with, unless the site names a different one in the
// "Theme for embedded screens" preference. See formulize_embedThemeName().
define('FORMULIZE_DEFAULT_EMBED_THEME', 'formulize_embed');

// Stands in for this site's address in the embed code shown on a screen's settings page. The right
// address depends on how the screen is embedded - this site's own for anonymous visitors, or an
// embedding address on the host website's domain for signed-in ones - so the administrator fills it
// in. See formulize_screenEmbedCode().
define('FORMULIZE_ADDRESS_PLACEHOLDER', '{formulize-address}');

// A file with this name in a theme folder marks that theme as one for rendering embedded screens.
// See formulize_themeIsAnEmbedTheme() and the marker file in themes/formulize_embed.
define('FORMULIZE_EMBED_THEME_MARKER', 'formulize-embed-theme.marker');

// Cookie that binds an anonymous visitor's security tokens to their own browser when the session
// cookie cannot reach an embedded screen. See formulize_anonTokenBindKey().
// The __Host- prefix is part of the name and is load bearing: browsers refuse to store a cookie
// named this way unless it is Secure, Path=/ and has no Domain, which stops a neighbouring
// subdomain from planting a bind key of its own choosing. Do not rename it without that prefix.
define('FORMULIZE_ANON_BIND_COOKIE', '__Host-formulize_anonbind');
