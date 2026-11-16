<?php

class icms_db_Connection extends PDO implements icms_db_IConnection {

	/**
	 * The session variable that holds the key that encrypted fields are encrypted with. SQL names the variable, as in
	 * AES_DECRYPT(f.`field`, @formulize_aes_key), and never holds the key itself, so the key is in no query that's logged,
	 * shown in the Developer Dashboard, printed in an error or cached in a file. A variable is only read where SQL expects an
	 * expression, never inside a quoted string, so nothing someone types can stand for it. See aesKeySQL().
	 */
	const AES_KEY_VARIABLE = '@formulize_aes_key';

	/**
	 * Whether this connection's session holds the key yet
	 * @var bool
	 */
	private $aesKeyVariableIsSet = false;

	public function id($string) {
		return '"' . str_replace('"', '""', $string) . '"';
	}
	public function escape($string) {
		return substr($this->quote($string), 1, -1);
	}
	#[\ReturnTypeWillChange]
	public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs) {
		icms_Event::trigger('icms_db_IConnection', 'execute', $this, array('sql' => $query));
		return call_user_func_array(array('PDO', 'query'), func_get_args());
	}

	/**
	 * What to write in SQL for the key that encrypted fields are encrypted with. The first time it's asked for, the key is put
	 * in this connection's session variable, as a bound parameter, by a statement that isn't logged. Every query on the
	 * connection can then use it, and the variable goes when the connection closes.
	 *
	 * If the key can't be put in the variable, this stops, rather than let AES_ENCRYPT() be given NULL, which would write NULL
	 * over the values being saved.
	 *
	 * @return string The variable's name, to write in SQL as it is, without quotes
	 */
	public function aesKeySQL() {
		if(!$this->aesKeyVariableIsSet) {
			// a real prepared statement, so the server gets the statement and the key separately, and the statement's text, which the
			// server can keep in its history, is only SET @formulize_aes_key = ?. PHP otherwise puts the value into the text itself.
			$wasEmulating = null;
			try {
				$wasEmulating = $this->getAttribute(PDO::ATTR_EMULATE_PREPARES);
				$this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$statement = $this->prepare('SET ' . self::AES_KEY_VARIABLE . ' = ?');
				$isSet = ($statement AND $statement->execute(array(self::aesKey())));
			} catch (Exception $e) {
				$isSet = false;
			}
			if($wasEmulating !== null) {
				$this->setAttribute(PDO::ATTR_EMULATE_PREPARES, $wasEmulating);
			}
			if(!$isSet) {
				throw new RuntimeException('The key for encrypted fields could not be given to the database connection.');
			}
			$this->aesKeyVariableIsSet = true;
		}
		return self::AES_KEY_VARIABLE;
	}

	/**
	 * The key that encrypted fields are encrypted with. It comes from the database password, so it stays the same as long as the
	 * password does, and it is never stored.
	 * @return string The key
	 */
	public static function aesKey() {
		$dbPass = (defined('SDATA_DB_PASS') AND SDATA_DB_PASS) ? SDATA_DB_PASS : XOOPS_DB_PASS;
		return sha1($dbPass."I'm a cool, cool, cool dingbat");
	}

}
