<?php
/**
 * A static class for on-screen messages
 *
 * Using a static class instead of a include file with global functions, along with
 * autoloading of classes, reduces the memory usage and only includes files when needed.
 *
 * @category	Core
 * @package		Message
 * @author		Steve Kenow <skenow@impresscms.org>
 * @copyright	(c) 2007-2008 The ImpressCMS Project - www.impresscms.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id: Message.php 20515 2010-12-11 14:21:53Z phoenyx $
 * @since		1.3
 */

/**
 * Create and display messages on the screen
 */
class icms_core_Message {

    /* Since all the methods are static, there is no __construct necessary	 */

	/**
	 * Replaces xoops_warning() and icms_warning_msg()
	 *
	 * @param string $msg
	 * @param string $title
	 * @param boolean $render
	 */
	static public function warning($msg, $title='', $render = false) {
		$ret = '<div class="warningMsg">';
		if ($title != '') {
			$ret .= '<h4>' . $title . '</h4>';
		}
		if (is_array($msg)) {
			foreach ($msg as $m) {
				$ret .= $m . '<br />';
			}
		} else {
			$ret .= $msg;
		}
		$ret .= '</div>';
		if ($render) {
			echo $ret;
		} else {
			return $ret;
		}
	}

	/**
	 * Replaces xoops_error() and icms_error_msg()
	 *
	 * @param string $msg
	 * @param string $title
	 * @param boolean $render
	 * @return mixed
	 */
	static public function error($msg, $title = '', $render = true) {
		$ret = '<div class="errorMsg">';
		if ($title != '') {
			$ret .= '<h4>' . $title . '</h4>';
		}
		if (is_array($msg)) {
			foreach ($msg as $m) {
				$ret .= $m . '<br />';
			}
		} else {
			$ret .= $msg;
		}
		$ret .= '</div>';
		if ($render) {
			echo $ret;
		} else {
			return $ret;
		}
	}

	/**
	 * Render result message (echo, so no return string)
	 * Replaces xoops_result()
	 *
	 * @param string $msg
	 * @param string $title
	 * @return void
	 */
	static public function result($msg, $title='') {
		echo '<div class="resultMsg">';
		if ($title != '') {
			echo '<h4>' . $title . '</h4>';
		}
		if (is_array($msg)) {
			foreach ($msg as $m) {
				echo $m . '<br />';
			}
		} else {
			echo $msg;
		}
		echo '</div>';

	}

	/**
	 * Will render (echo) the form so no return in this function
	 * Replaces xoops_confirm()
	 *
	 * @param array $hiddens Array of Hidden values
	 * @param string $action The Form action
	 * @param string $msg The message in the confirm form
	 * @param string $submit The text on the submit button
	 * @param boolean $addtoken Whether or not to add a security token
	 * @return void
	 */
	static public function confirm($hiddens, $action, $msg, $submit='', $addtoken = true) {
	$submit = ($submit != '') ? trim($submit) : _SUBMIT;
	echo '<div class="confirmMsg">
			<h4>' . $msg . '</h4>
			<form method="post" action="' . $action . '">';
	foreach ($hiddens as $name => $value) {
		if (is_array($value)) {
			foreach ($value as $caption => $newvalue) {
				echo '<input type="radio" name="' . $name . '" value="'
					. htmlspecialchars($newvalue) . '" /> ' . $caption;
			}
			echo '<br />';
		} else {
			echo '<input type="hidden" name="' . $name . '" value="'
				. htmlspecialchars($value) . '" />';
		}
	}
	if ($addtoken !== false) {
		echo icms::$security->getTokenHTML();
	}
	echo '<input type="submit" name="confirm_submit" value="' . $submit
		. '" /> <input type="button" name="confirm_back" value="' . _CANCEL
		.'" onclick="javascript:history.go(-1);" />
	</form></div>';

	}
}
