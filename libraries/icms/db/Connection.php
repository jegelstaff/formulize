<?php

class icms_db_Connection extends PDO implements icms_db_IConnection {

	public function id($string) {
		return '"' . str_replace('"', '""', $string) . '"';
	}
	public function escape($string) {
		return substr($this->quote($string), 1, -1);
	}
	public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs) {
		icms_Event::trigger('icms_db_IConnection', 'execute', $this, array('sql' => $query));
		return call_user_func_array(array('parent', 'query'), func_get_args());
	}


}
