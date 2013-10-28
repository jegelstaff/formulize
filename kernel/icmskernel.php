<?php
/**
 * ICMS kernel Base Class
 *
 * @copyright      http://www.impresscms.org/ The ImpressCMS Project
 * @license         LICENSE.txt
 * @package	kernel
 * @since            1.1
 * @deprecated		Use icms_core_Kernel, instead
 * @todo			Remove this in version 1.4
 * @version		$Id: icmskernel.php 19118 2010-03-27 17:46:23Z skenow $
 */

/**
 * Extremely reduced kernel class
 * Few notes:
 * - modules should use this class methods to generate physical paths/URIs (the ones which do not conform
 * will perform badly when true URL rewriting is implemented)
 * @package		kernel
 * @deprecated	Use icms_core_Kernel, instead
 * @todo		Remove this in version 1.4
 * @since 		1.1
 */
class IcmsKernel extends icms_core_Kernel {
	public function __construct() {
		parent::__construct();
		$this->setErrors = icms_core_Debug::setDeprecated('icms_core_Kernel', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
