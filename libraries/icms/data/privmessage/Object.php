<?php
/**
 * Private messages
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Privmessage
 * @version		SVN: $Id:Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A handler for Private Messages
 *
 * @category	ICMS
 * @package		Privmessage
 * @author		Kazumi Ono	<onokazu@xoops.org>
 */
class icms_data_privmessage_Object extends icms_core_Object {

	/**
	 * constructor
	 **/
	public function __construct() {
		parent::__construct();
		$this->initVar('msg_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('msg_image', XOBJ_DTYPE_OTHER, 'icon1.gif', false, 100);
		$this->initVar('subject', XOBJ_DTYPE_TXTBOX, null, true, 255);
		$this->initVar('from_userid', XOBJ_DTYPE_INT, null, true);
		$this->initVar('to_userid', XOBJ_DTYPE_INT, null, true);
		$this->initVar('msg_time', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('msg_text', XOBJ_DTYPE_TXTAREA, null, true);
		$this->initVar('read_msg', XOBJ_DTYPE_INT, 0, false);
	}
}

