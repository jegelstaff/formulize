<?php
/**
 * tar Class
 *
 * This class reads and writes Tape-Archive (TAR) Files and Gzip
 * compressed TAR files, which are mainly used on UNIX systems.
 * This class works on both windows AND unix systems, and does
 * NOT rely on external applications!! Woohoo!
 *
 * @author	Josh Barger <joshb@npt.com>
 * @copyright	Copyright (C) 2002  Josh Barger
 *
 * @package     kernel
 * @subpackage  core
 * @deprecated	Use icms_file_TarFileHandler instead
 * @todo Remove in version 1.4
*/
class tar extends icms_file_TarFileHandler {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_TarFileHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}