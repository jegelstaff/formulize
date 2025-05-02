<?php

#
# Database Row Object extension
#
# Purpose:
# Provide access to database rows as if they were objects.
#
# Requires mysqli database access.
#

include_once(realpath(dirname(__FILE__))."/mysqli.php");


global $db;
$db = new db();

abstract class rowobj {
    var $_data = null;
    var $_generated = null;
    static $_array_class = "rowobj_set";

    function __construct($class_name, $row_data) {
        $this->_data = new stdClass;
        foreach ($row_data as $field => $value) {
            switch ($class_name::$fields[$name]["type"]) {
                case "id":
                case "int":
                case "bigint":
                $this->_data->{$field} = (int)$value;
                break;

                case "float":
                case "double":
                case "decimal": # ?
                case "numeric": # ?
                $this->_data->{$field} = (double)$value;
                break;

                case "bool":
                case "boolean":
                $this->_data->{$field} = ($value == db::$booloan_true);
                break;

                case "createdate":
                case "timestamp":
                case "datetime":
                $this->_data->{$field} = new date_obj($value);
                break;

                default:
                $this->_data->{$field} = $value;
            }
        }
        $this->_generated = array();    # track the names of generated values so they can be reset if the stored data is changed
        if (isset($this->_data->id) && is_int($this->_data->id) && $this->_data->id > 0) {
            if (!isset($GLOBALS["rowobj-cache"][$class_name]))
                $GLOBALS["rowobj-cache"][$class_name] = array();
            $GLOBALS["rowobj-cache"][$class_name][(int)$this->_data->id] = &$this;
        }
    }

    public function __toString() {
        $idcolname = call_user_func(array($this, 'idcolname'));
        return "[".get_class($this)." object {$this->_data->$idcolname}]";
    }

    static function create_table($class_name){$x=new db();
        $x->create_table($class_name::tablename(), $class_name::$fields, array($class_name::idcolname()=>true));}

    static function update_table($installed_version){return true;}
    static function initialise(){return true;}

    # sometimes the table name can't be the same as the object class name, so use a function to get table name
    #abstract static function tablename();

    # most tables use 'id' as the primary key column name, but in case some don't use a function to get the name
    #abstract static function idcolname();

    #abstract static function &get_id($id = null);

    # PHP530
    #static function &get_id($id = null)
    #{
    #    return self::get()->id($id);
    #}

    #abstract static function &get();

    #static function &get()
    #{
    #    # PHP516
    #    $classname = get_called_class();
    #    $query = new dbquery(get_called_class(), call_user_func(array($classname, "tablename")), call_user_func(array($classname, "idcolname")));
    #
    #    # PHP530
    #    #$query = new dbquery(get_called_class(), static::tablename(), static::idcolname());
    #    return $query;
    #}

    #abstract static function count_all();

    # PHP530
    #static function count_all()
    #{
    #    return self::get()->count();
    #}

    static function begin_transaction() {
        global $db;
        $db->begin_transaction();
    }

    static function commit_transaction() {
        global $db;
        $db->commit_transaction();
    }

    static function static_insert($class, $new_values) {
        foreach ($new_values as $key => &$value) {
            if ("timestamp" == $class::$fields[$key]["type"]
                || "datetime" == $class::$fields[$key]["type"]) {
                if ("" == $value || ($value = date_obj($value)) && !$value->is_valid) {
                    $value = null;
                }
            }
        }

        # PHP516
        global $db;
        return $db->insert(call_user_func(array($class, "tablename")), $new_values);

        # PHP530
        #return $db->insert(static::tablename(), $new_values);
    }

    static function serialize_object($obj) {
        return base64_encode(gzcompress(serialize($obj)));
    }

    static function unserialize_object($txt) {
        return unserialize(gzuncompress(base64_decode($txt)));
    }

    function update($new_values) {
        $tablename = call_user_func(array($this, 'tablename'));
        $idcolname = call_user_func(array($this, 'idcolname'));
        $id_val = $this->$idcolname;

        # update our internal data
        foreach ($new_values as $key => $value) {
            if ('_' != $key[0])    # skip items named with a leading underscore
            {
                if ("timestamp" == $this::$fields[$key]["type"]
                    || "datetime" == $this::$fields[$key]["type"]) {
                    if ("" == $value || ($value = date_obj($value)) && !$value->is_valid) {
                        $value = null;
                    }
                }
                $this->_data->$key = $value;
            }
        }
        $this->clear_all();    # reset all the generated values so they will be re-generated based on updated data
        global $db;
        $db->where($idcolname, "=", $id_val);
        return $db->update($tablename, $new_values);
    }

    static function add_column($column_name, $insert_after_column_name) {
        # create column using column attributes in derived class
        # note: rowobj should convert the attributes to a string itself rather than doing it here
        $actual_class = get_called_class();
        global $db;
        return $db->add_column($actual_class::tablename(), $column_name,
            db::column_attributes_to_string($actual_class::$fields[$column_name]),
            $insert_after_column_name);
    }

    #function insert($new_values)
    #{
    #    $tablename = call_user_func(array($this, 'tablename'));
    #    #echo "tablename is $tablename";
    #
    #    #$db->insert($tablename, $new_values);
    #    #return $db->insert_id();
    #}

    function __get($name) {
        if (property_exists($this->_data, $name))    # note isset() won't work with variable set to NULL
        {
            return $this->_data->{$name};
        } else {
            if (method_exists($this, $name)) {
                $this->_data->{$name} = $this->{$name}();
                $this->_generated[$name] = true;
                return $this->_data->{$name};
            }
            $lowername = strtolower($name);
            if (property_exists($this->_data, $lowername)) {
                $this->_data->{$name} = new date_obj($this->_data->{$lowername});
                return $this->_data->{$name};
            }
            if (property_exists($this, 'foreign_keys') && iterable($this->foreign_keys) && isset($this->foreign_keys[$name])) {
                $foreign_object = $this->foreign_keys[$name]['foreign_object'];
                $column_name = $this->foreign_keys[$name]['column'];
                return $foreign_object::get_id($this->$column_name);
            }
            $function_name = get_class($this)."__".$name;
            if (function_exists($function_name)) {
                $this->_data->{$name} = $function_name($this);
                return $this->_data->{$name};
            }
            # the error doesn't actually happen here, but wherever we're called from, so provide a helpful error
            $trace = debug_backtrace(false);
            $err_msg = get_class($this)." object does not have a {$name} value in {$trace[0]['file']} on line {$trace[0]['line']}.";
            throw new Exception($err_msg);
        }
    }

    public function __isset($name) {
        # note isset() won't work with variable set to NULL
        if (property_exists($this->_data, $name)) {
            return true;
        } else {
            if (method_exists($this, $name)) {
                return true;
            }
            $lowername = strtolower($name);
            if (property_exists($this->_data, $lowername)) {
                return true;
            }
            if (property_exists($this, 'foreign_keys') && iterable($this->foreign_keys) && isset($this->foreign_keys[$name])) {
                return true;
            }
        }

        return false;
    }

    function __set($name, $value) {
        $trace = debug_backtrace(false);
        $err_msg = "Error: setting {$name} value on ".get_class($this)." object in {$trace[0]['file']} on line {$trace[0]['line']}.";
        throw new Exception($err_msg);
    }

    # use this to clear calculated values when a base value changes
    function clear() {
        $names = func_get_args();
        foreach ($names as $name) {
            unset($this->_data->{$name});
        }
    }

    function clear_all() {
        foreach ($this->_generated as $name => $value) {
            unset($this->_data->{$name});
            unset($this->_generated[$name]);
        }
    }
}

class dbquery extends db {
    function __construct($class_name, $table, $idcol) {
        $this->class_name = $class_name;
        $this->table = $table;
        $this->idcol = $idcol;
        parent::__construct();
    }

    function count($table = null)    # note: table parameter on the base class is not used here
    {
        return parent::count($this->table);
    }

    function delete($table = null)    # note: table parameter on the base class is not used here
    {
        return parent::delete($this->table);
    }

    function &id($id, $load_cached = true) {
        if ($load_cached && $id > 0 && isset($GLOBALS["rowobj-cache"][$this->class_name]) && isset($GLOBALS["rowobj-cache"][$this->class_name][$id])) {
            #error_log("returning cached copy of $this->class_name $id");
            return $GLOBALS["rowobj-cache"][$this->class_name][$id];
        }
        $this->where($this->idcol, "=", $id);
        return $this->one();
    }

    function &page($limit, $offset = null) {
        $this->limit($limit, $offset);
        return $this;
    }

    function &one() {
        $obj = $this->select_one($this->table, db::GET_RESULTS_ARRAY);
        if ($obj) {
            $class_name = $this->class_name;
            $obj = new $class_name($class_name, $obj);
        }
        return $obj;
    }

    function &many() {
        $class_name = $this->class_name;
        $objects = $this->select($this->table, db::GET_RESULTS_ARRAY);
        $array_class_name = $class_name::$_array_class;
        $items = new $array_class_name();
        if (false !== $objects && count($objects) > 0) {
            foreach ($objects as $object) {
                $items[] = new $class_name($class_name, $object);
            }
        }
        return $items;
    }
}

class rowobj_set implements ArrayAccess, Iterator, Countable {
    private $container = array();
    public function __construct($data = array()){$this->container = $data;}
    public function offsetSet($offset,$value){if ($offset == ""){$this->container[] = $value;}else{$this->container[$offset] = $value;}}
    public function offsetExists($offset){return isset($this->container[$offset]);}
    public function offsetUnset($offset){unset($this->container[$offset]);}
    public function offsetGet($offset){return isset($this->container[$offset]) ? $this->container[$offset] : null;}
    public function rewind(){reset($this->container);}
    public function current(){return current($this->container);}
    public function key(){return key($this->container);}
    public function next(){return next($this->container);}
    public function valid(){return $this->current() !== false;}
    public function count(){return count($this->container);}

    function first() {
        if (count($this->container) > 0) {
            $temp = $this->container;
            reset($temp);
            return current($temp);
        }
        return null;
    }

    function last() {
        if (count($this->container) > 0) {
            $temp = $this->container;
            return end($temp);
        }
        return null;
    }

    function reverse() {
        $reverse = new rowobj_set(array_reverse((array)$this->container, true));
        return $reverse;
    }

    function __get($name) {
        switch ($name) {
            case "first": return $this->first();
            case "last": return $this->last();
            case "reverse": return $this->reverse();
            default: return null;
        }
    }

    function __isset($name) {
        return in_array($name, array("first", "last", "reverse"));
    }
}

function date_obj($date_string = "") {
    return new date_obj($date_string);
}

if (strnatcmp(phpversion(), "5.2.10") >= 0) {
    class date_obj extends DateTime {
        var $_data          = null;
        var $is_valid       = true;

        function __construct($date_string) {
            $this->_data = new stdClass;
            parent::__construct($date_string);
            $user_timezone = date_default_timezone_get(); //get_setting("timezone", "user");
            if ($user_timezone) {
                $this->setTimezone(new DateTimeZone($user_timezone));
            }
            if ("" == $date_string || "0000-00-00 00:00:00" == $date_string) {
                $this->is_valid = false;
            }
        }

        function __toString() {
            return $this->datetime;
        }

        function value() {
            return $this->is_valid ? $this->getTimestamp() : null;
        }

        function is_valid() {
            return $this->is_valid;
        }

        function in_the_past() {
            if ($this->value) {
                return $this->value < date_obj("now")->value();
            }
            return null;
        }

        function in_the_future() {
            if ($this->value) {
                return $this->value > date_obj("now")->value();
            }
            return null;
        }

        function days_diff() {
            return abs(date_obj("now")->value() - $this->value()) / (60 * 60 * 24);
        }

        function now() {
            # it's silly to calculate the difference, then add it, but DateTime doesn't seem to offer a simple set() function
            # note that the timezone should still be the same as what was set in the constructor
            $this->add($this->diff(date_obj("now")));
            $this->_data = new stdClass;
            $this->is_valid = true;
            return $this;
        }

        function adjust($interval) {
            $this->modify($interval);
            $this->_data = new stdClass;
            return $this;
        }

        function __get($name) {
            if (property_exists($this->_data, $name))       # note isset() won't work with variable set to NULL
            {
                return $this->_data->{$name};
            } else {
                if (method_exists($this, $name)) {
                    $this->_data->{$name} = $this->{$name}();
                    return $this->_data->{$name};
                }
                # the error doesn't actually happen here, but wherever we're called from, so provide a helpful error
                $trace = debug_backtrace(false);
                $err_msg = get_class($this)." object does not have a {$name} value in {$trace[0]['file']} on line {$trace[0]['line']}.";
                throw new Exception($err_msg);
            }
        }

        function long() {
            if (!$this->is_valid()) return "";
            return date("l, F jS, Y", $this->value());      # eg: Saturday, March 20th, 2010
        }

        function short() {
            if (!$this->is_valid()) return "";
            return date("F jS, Y", $this->value());         # eg: March 20th, 2010
        }

        function ymd() {
            if (!$this->is_valid()) return "";
            return date("Y-m-d", $this->value());           # eg: 2010-03-17
        }

        function datetime() {
            if (!$this->is_valid()) return "";
            return date("Y-m-d H:i:s", $this->value());     # eg: 2010-03-20 17:27:17
        }

        function relative() {
            if (!$this->is_valid()) return "";
            # array of time period chunks
            $chunks = array(
                array(60 * 60 * 24 * 365 , 'year'),
                array(60 * 60 * 24 * 30 , 'month'),
                array(60 * 60 * 24 * 7, 'week'),
                array(60 * 60 * 24 , 'day'),
                array(60 * 60 , 'hour'),
                array(60 , 'minute'),
            );
            $chunk_count = count($chunks);

            $today = date_obj("now")->value();
            $diff = abs($today - $this->value());

            for ($i = 0; $i < $chunk_count; $i++) {
                $seconds = $chunks[$i][0];
                $name = $chunks[$i][1];

                # finding the biggest chunk (if the chunk fits, break)
                if (($count = floor($diff / $seconds)) != 0)
                    break;
            }
            $print = ($count == 1) ? "1 $name" : "$count {$name}s";

            if ($i + 1 < $chunk_count) {
                # now getting the second item
                $seconds2 = $chunks[$i + 1][0];
                $name2 = $chunks[$i + 1][1];

                # add second item if it's greater than 0
                if (($count2 = floor(($diff - ($seconds * $count)) / $seconds2)) != 0) {
                    $print .= ($count2 == 1) ? ", 1 $name2" : ", $count2 {$name2}s";
                }
            }
            return $print;
        }
    }
} else {
    class date_obj {
        var $_data       = null;
        var $date_string = null;
        var $date_value  = null;

        function __construct($date_string) {
            $this->update($date_string);
        }

        public function update($date_string) {
            $this->_data = new stdClass;
            if ("" == $date_string || "0000-00-00 00:00:00" == $date_string) {
                $this->date_string = "";
                $this->date_value  = false;
            } else {
                $this->date_string = $date_string;
            }
        }

        function __toString() {
            return $this->datetime; # not date_string
        }

        function is_valid() {
            return ($this->value !== false);
        }

        function &value() {
            if (null == $this->date_value) {
                $this->date_value = strtotime($this->date_string);
            }
            return $this->date_value;
        }

        function in_the_past() {
            if ($this->value) {
                return $this->value < time();
            }
            return null;
        }

        function in_the_future() {
            if ($this->value) {
                return $this->value > time();
            }
            return null;
        }

        function days_diff() {
            return abs(time() - $this->value()) / (60 * 60 * 24);
        }

        function now() {
            $new = date_obj(date("c", time()));
            $this->_data = $new->_data;
            $this->date_string = $new->date_string;
            $this->date_value = $new->date_value;
            return $this;
        }

        function adjust($interval) {
            $new = date_obj(date("c", strtotime($interval, $this->value)));
            $this->_data = $new->_data;
            $this->date_string = $new->date_string;
            $this->date_value = $new->date_value;
            return $this;
        }

        function __get($name) {
            if (property_exists($this->_data, $name))       # note isset() won't work with variable set to NULL
            {
                return $this->_data->{$name};
            } else {
                if (method_exists($this, $name)) {
                    $this->_data->{$name} = $this->{$name}();
                    return $this->_data->{$name};
                }
                # the error doesn't actually happen here, but wherever we're called from, so provide a helpful error
                $trace = debug_backtrace(false);
                $err_msg = get_class($this)." object does not have a {$name} value in {$trace[0]['file']} on line {$trace[0]['line']}.";
                throw new Exception($err_msg);
            }
        }

        function long() {
            if (!$this->is_valid()) return "";
            return date("l, F jS, Y", $this->value());      # eg: Saturday, March 20th, 2010
        }

        function short() {
            if (!$this->is_valid()) return "";
            return date("F jS, Y", $this->value());         # eg: March 20th, 2010
        }

        function date() # remove this as it collides with DateTime variable name
        {
            if (!$this->is_valid()) return "";
            return date("Y-m-d", $this->value());           # eg: 2010-03-17
        }

        function ymd() {
            if (!$this->is_valid()) return "";
            return date("Y-m-d", $this->value());           # eg: 2010-03-17
        }

        function datetime() {
            if (!$this->is_valid()) return "";
            return date("Y-m-d H:i:s", $this->value());     # eg: 2010-03-20 17:27:17
        }

        function relative() {
            if (!$this->is_valid()) return "";
            # array of time period chunks
            $chunks = array(
                array(60 * 60 * 24 * 365 , 'year'),
                array(60 * 60 * 24 * 30 , 'month'),
                array(60 * 60 * 24 * 7, 'week'),
                array(60 * 60 * 24 , 'day'),
                array(60 * 60 , 'hour'),
                array(60 , 'minute'),
            );
            $chunk_count = count($chunks);

            $today = time();
            $diff = abs($today - $this->value());

            for ($i = 0; $i < $chunk_count; $i++) {
                $seconds = $chunks[$i][0];
                $name = $chunks[$i][1];

                # finding the biggest chunk (if the chunk fits, break)
                if (($count = floor($diff / $seconds)) != 0)
                    break;
            }
            $print = ($count == 1) ? "1 $name" : "$count {$name}s";

            if ($i + 1 < $chunk_count) {
                # now getting the second item
                $seconds2 = $chunks[$i + 1][0];
                $name2 = $chunks[$i + 1][1];

                # add second item if it's greater than 0
                if (($count2 = floor(($diff - ($seconds * $count)) / $seconds2)) != 0) {
                    $print .= ($count2 == 1) ? ", 1 $name2" : ", $count2 {$name2}s";
                }
            }
            return $print;
        }
    }
}
