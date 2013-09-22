<?php
/**
 * icms_Event class definition
 *
 * @category	ICMS
 *
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id$
 */

class icms_Event {
	/**
	 * Registered event handlers
	 * @var array
	 */
	static protected $handlers = array();
	/**
	 * Events currently being processed
	 * @var array
	 */
	static protected $events = array();

	/**
	 * Returns information about a fired event.
	 * @param int $index 0 for current, 1 for parent (if current event due to another event), and so on...
	 * @return icms_Event
	 */
	static public function current($index = 0) {
		return isset(self::$events[$index]) ? self::$events[$index] : false;
	}

	/**
	 * Registers an event handler
	 *
	 * icms_Event::attach( 'icms_db_IConnection', 'connect', 'something' );
	 * => will call something( $eventParams, $event ) when the event is fired
	 * icms_Event::attach( 'icms_db_IConnection', 'connect', array( $object, 'something' ) );
	 * => will call $object->something( $eventParams, $event ) when the event is fired
	 * icms_Event::attach( 'icms_db_IConnection', '*', array( 'MyClass', 'something' ) );
	 * => will call MyClass::something( $eventParams, $event ) when any event that
	 * belongs to the 'icms_db_IConnection' namespace is fired
	 * Also, on PHP 5.3+, you can use closures:
	 * icms_Event::attach( 'icms_db_IConnection', 'execute', function ( $params, $event ) {
	 *    echo 'Executing: ' . $params['sql'];
	 * } );
	 *
	 * @param string $namespace Event namespace
	 * @param string $name Event name (use * to attach to all signals of $namespace)
	 * @param mixed $callback
	 * @return void
	 */
	static public function attach($namespace, $name, $callback) {
		if (!isset(self::$handlers[$namespace][$name])) {
			self::$handlers[$namespace][$name] = array();
		}
		self::$handlers[$namespace][$name][] = $callback;
	}
	/**
	 * Detach the specified event handler.
	 * @param string $namespace
	 * @param string $name
	 * @param mixed $callback
	 */
	static public function detach($namespace, $name, $callback) {
		if (isset(self::$handlers[$namespace][$name])) {
			foreach (self::$handlers[$namespace][$name] as $k => $handler) {
				if ($handler === $callback) {
					unset(self::$handlers[$namespace][$name][$k]);
					return;
				}
			}
		}
	}

	/**
	 * Triggers an event.
	 *
	 * @param string $namespace Event namespace
	 * @param string $name Event name (use * to attach to all events of $namespace)
	 * @param object $source Object that triggered this event
	 * @param array $parameters Event parameters
	 * @return icms_Event
	 */
	static public function trigger($namespace, $name, $source, $parameters = array()) {
		$cancancel = false;
		if (substr($name, 0, 1) == "*") {
			$cancancel = true;
			$name = substr($name, 1);
		}
		$event = new icms_Event($namespace, $name, $source, $parameters, $cancancel);
		array_unshift(self::$events, $event);
		foreach (array("*", $name) as $handlers) {
			if (isset(self::$handlers[$namespace][$handlers])) {
				foreach (self::$handlers[$namespace][$handlers] as $callback) {
					call_user_func($callback, $parameters, $event);
					if ($cancancel && $event->canceled) break 2;
				}
			}
		}
		return array_shift(self::$events);
	}

	/**
	 * Namespace this event belongs to.
	 * @var string
	 * @readonly
	 */
	public $namespace = "";

	/**
	 * Name of this event
	 * @var string
	 * @readonly
	 */
	public $name = "";

	/**
	 * Object that fired this event.
	 * @var object
	 * @readonly
	 */
	public $source;

	/**
	 * Parameters
	 * @var array()
	 * @readonly
	 */
	public $parameters = array();

	/**
	 * Whether this event can be canceled or not.
	 * @var bool
	 * @readonly
	 */
	public $canCancel = false;

	/**
	 * Whether this event has been canceled or not.
	 * @var bool
	 */
	public $canceled = false;

	public function __construct($namespace, $name, $source, $params = array(), $canCancel = false) {
		$this->namespace = $namespace;
		$this->name	= $name;
		$this->source = $source;
		$this->parameters = $params;
		$this->canCancel = $canCancel;
	}
}