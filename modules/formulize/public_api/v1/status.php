<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

if(!defined('FORMULIZE_PUBLIC_API_REQUEST')) {
	http_response_code(500);
    exit();
}
// Report whether the Authorization header survived the trip to PHP. Some server
// configurations, notably CGI and some FastCGI setups, strip it unless they are
// explicitly configured to pass it through, which would break API key authentication
// with no other visible symptom. The admin check that turns the Public API on sends a
// known test header and reads this back. The status value itself is untouched, because
// that is what decides whether the preference is allowed to stay on.
$formulize_authorizationHeaderReceived = function_exists('formulize_publicApiGetAuthorizationHeader')
	? (trim(formulize_publicApiGetAuthorizationHeader()) !== '')
	: false;

print '{
	"status": "healthy",
	"timestamp": '.time().',
	"version": "1",
	"authorization_header_received": '.($formulize_authorizationHeaderReceived ? 'true' : 'false').'
}';
