<?php
/**
 * icms_db_IConnection interface definition
 *
 * @category	ICMS
 * @package		icms_db
 * @subpackage	icms_db_IConnection
 *
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id$
 */

/**
 * Interface for database adapters.
 *
 * @since 1.4
 */
interface icms_db_IConnection {
	/**
	 * Public contructor
	 */
	public function __construct( $dsn, $user, $pwd, $options = array() );

	/**
	 * Set the value of a database connection attribute.
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute( $attribute, $value );
	/**
	 * Return the value of a database connection attribute.
	 * @param int $attribute
	 * @return mixed
	 */
	public function getAttribute( $attribute );

	/**
	 * Last error as an SQLSTATE, a five characters alphanumeric identifier.
	 * @return mixed
	 */
	public function errorCode();
	/**
	 * Get an array of error information about the last operation.
	 * @return array
	 */
	public function errorInfo();

	/**
	 * Places quotes around the input string and escapes special characters within the input string.
	 * @param string $string
	 * @param int $type
	 * @return string
	 */
	public function quote( $string, $type = PDO::PARAM_STR );
	public function escape( $string );
	/**
	 * Places identifier quotes around the input string
	 * @param string $string
	 * @return string
	 */
	public function id( $string );

	/**
	 * Turns off autocommit mode and starts recording transaction.
	 * @return bool
	 */
	public function beginTransaction();
	/**
	 * Commits current transaction.
	 * @return bool
	 */
	public function commit();
	/**
	 * Rolls back current transaction.
	 * @return bool
	 */
	public function rollBack();

	/**
	 * Prepares an SQL statement
	 * @param string $sql
	 * @param array $options
	 * @return icms_db_Statement
	 */
	public function prepare( $sql, $options = array() );
	/**
	 * Executes an SQL statement and returns the number of affected rows.
	 * @param string $sql
	 * @return int
	 */
	public function exec( $sql );
	/**
	 * Executes an SQL statement and returns a result set as a IStatement object.
	 * @param string $sql
	 * @return icms_db_Statement
	 */
	public function query();
	/**
	 * Returns the ID of the last inserted row or the last value from a sequence object.
	 * @param string $name
	 * @return string
	 */
	public function lastInsertId( $name = null );

}
