<?php

class icms_db_Connection extends PDO implements icms_db_IConnection {

	public function id($string) {
		return '"' . str_replace('"', '""', $string) . '"';
	}
	public function escape($string) {
		return substr($this->quote($string), 1, -1);
	}
	public function query() {
		$args = func_get_args();
		$sql = $args[0];
		icms_Event::trigger('icms_db_IConnection', 'execute', $this, array('sql' => $args[0]));
		return call_user_func_array(array('parent', 'query'), $args);
	}


}
