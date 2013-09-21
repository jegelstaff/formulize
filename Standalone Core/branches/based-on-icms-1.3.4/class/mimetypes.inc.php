<?php
// $Id: mimetypes.inc.php 10337 2010-07-13 15:37:14Z skenow $
/**
 * Extension to mimetype lookup table
 *
 * This file is provided as an helper for objects who need to perform filename to mimetype translations.
 * Common types have been provided, but feel free to add your own one if you need it.
 * <br /><br />
 * See the enclosed file LICENSE for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The Xoops project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author       Skalpa Keo <skalpa@xoops.org>
 * @since        2.0.9.3
 */

icms_core_Debug::setDeprecated('/class/mimetypes.inc.php', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
return icms_Utils::mimetypes();

?>