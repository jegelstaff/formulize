<?php

#
# MySQLi Database extension
#
# Purpose:
# Access MySQL databases using the mysqli_* functions.
#

if (!function_exists("iterable")) {
    function iterable($var) {
        return is_array($var) || $var instanceof ArrayAccess;
    }
}

class db {
    private static $connection     = null;
    private static $settings       = null;
    private static $use_count      = 0;
    private static $trans_count    = 0;           # count transactions started
    private static $trans_ok       = true;        # no errors during transaction
    private static $queries        = array();
    private static $previous_exception_handler = null;
    protected $columns             = array();
    protected $joins               = array();
    protected $where               = array();
    protected $group_by            = array();
    protected $order_by            = array();
    protected $limit               = null;
    protected $offset              = null;

    public static $booloan_true    = 1;
    public static $booloan_false   = 0;

    const GET_RESULTS              = 0;
    const GET_RESULTS_ARRAY        = 1;
    const GET_INSERT               = 2;
    const GET_AFFECTED             = 3;
    const GET_NOTHING              = 4;

    function __construct() {
        if (self::_open_connection()) {
            self::prepare_handle_exception();
        } else {
            throw new Exception("Error: failed to open database connection to ".self::_get_setting('hostname')." with ".
                "username ".self::_get_setting('username').'. '.mysqli_connect_errno().": ".mysqli_connect_error());
        }
    }

    function __destruct() {
        self::_close_connection();
    }

    static function is_connected() {
        return self::_get_connection();
    }

    function begin_transaction() {
        if (self::_increment_transaction()) {
            $this->query('set autocommit=0', self::GET_NOTHING);
            $this->query('start transaction', self::GET_NOTHING);
        }
    }

    function commit_transaction() {
        if (self::_decrement_transaction()) {
            if (self::_check_transaction()) {
                $this->query('commit', self::GET_NOTHING);
            } else {
                $this->query('rollback', self::GET_NOTHING);
            }
            $this->query('set autocommit=1', self::GET_NOTHING);
        }
    }

    static function get_affected_rows() {
        $aff_count = mysqli_affected_rows(self::_get_connection());
        return $aff_count;
    }

    function get_insert_id() {
        $ins_id = mysqli_insert_id(self::_get_connection());
        return $ins_id;
    }

    function show_tables($tablename = null) {
        $row = $this->query('SHOW TABLES FROM `'.self::_get_setting('database').'`'.(null == $tablename ? '' : " like '$tablename'"),
            self::GET_RESULTS);
        if (iterable($row))    # return an array of values
        {
            $values = array();
            foreach ($row as $key => $value) {
                # the $value is a stdClass with a property that may be named 'Tables_in_<database>' which holds the name of a table, so
                # turn it into an array and get the first (only) value, which is the table name.
                $value = (array)$value;
                $values[] = array_pop($value);
            }
            return $values;
        }
        return array();
    }

    function query($sql, $get_results = self::GET_NOTHING) {
        $ret = null;
        $this->_reset();
        try {
            $result = mysqli_query(self::_get_connection(), $sql);
        } catch (Exception $e) {
            # add the full query so it is logged and/or emailed
            throw new Exception($e->getMessage()."\nSQL: $sql", $e->getCode(), $e);
        }
        if (false === $result) {
            $err_no = mysqli_errno(self::_get_connection());
            $err_msg = mysqli_error(self::_get_connection());
            throw new Exception("Error: mysql error $err_no: '$err_msg' while running query '$sql'.");
            self::_transaction_error();    # in case we're using a transaction
            if (self::_get_setting('debug')) {
                self::track_query("QUERY FAILED: $sql");
            }
            $ret = false;
        } else {
            if (self::_get_setting('debug')) {
                self::track_query($sql);
            }
            switch ($get_results) {
                case self::GET_RESULTS:
                {
                    $ret = array();
                    do {} while(null !== ($row = mysqli_fetch_object($result)) && $ret[] = $row);
                }
                break;
                case self::GET_RESULTS_ARRAY:
                {
                    $ret = array();
                    do {} while(null !== ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) && $ret[] = $row);
                }
                break;
                case self::GET_INSERT:
                $ret = $this->get_insert_id();
                break;
                case self::GET_AFFECTED:
                $ret = $this->get_affected_rows();
                break;
                case self::GET_NOTHING:
                default:
                $ret = null;
                break;
            }
#            mysqli_free_result($result);
        }
        return $ret;
    }

    # query functions

    function count($table) {
        $sql = 'select count(*) as row_count from '.$this->escape_table($table);
        $sql .= $this->joins_to_string($table);
        $sql .= $this->where_to_string($table);
        $count = $this->query($sql, self::GET_RESULTS);
        return $count[0]->row_count;
    }

    function select($table, $type = self::GET_RESULTS) {
        # if no column specified, then assume all columns
        $select_columns = "*";
        if (0 == count($this->columns)) {
            if (count($this->joins) > 0) {
                # Fix to prevent column collisions silently overwriting data when joining tables.
                # selecting from multiple tables without specifying columns will cause column values to be overwritten
                #   when a column from the second table has the same name as a column in the first table. prevent this
                #   by assuming only data from the first table is required and the second table is used only as a constraint.
                #   if data from the second table is required, specify the columns explicity
                $select_columns = $this->escape_table($table).".*";
            }
        } else {
            $select_columns = implode(", ", $this->columns);
        }
        $sql = "select $select_columns";
        $sql .= ' from '.$this->escape_table($table);
        $sql .= $this->joins_to_string($table);
        $sql .= $this->where_to_string($table);
        if (count($this->group_by) > 0) {
            $sql .= ' group by '.implode(', ', $this->group_by);
        }
        if (count($this->order_by) > 0) {
            $sql .= ' order by '.implode(', ', $this->order_by);
        }
        if (null != $this->limit) {
            $sql .= ' limit '.$this->limit;
            if (null != $this->offset) {
                $sql .= ' offset '.$this->offset;
            }
        }
        return $this->query($sql, $type);
    }

    function select_one($table, $type = self::GET_RESULTS) {
        # note: do not limit to one result because the query must do that
        $result = $this->select($table, $type);
        return (1 == count($result) ? $result[0] : null);
    }

    function select_value($table, $column, $default = null) {
        $this->columns = array($this->_escape_column($column));
        $row = $this->select($table);
        if (1 == count($row)) {
            return $row[0]->$column;
        } else {
            if (iterable($row))    # return an array of values
            {
                $values = array();
                foreach ($row as $value) {
                    $values[] = $value->$column;
                }
                return $values;
            }
        }
        return $default;
    }

    function select_values($table, $column, $default = null) {
        $this->columns = array($this->_escape_column($column));
        $row = $this->select($table);
        $values = array();
        foreach ($row as $value) {
            $values[] = $value->$column;
        }
        return $values;
    }

    function subselect($table, $value_name) {
        if (count($this->columns) > 0) {
            $sql = 'select '.array_pop($this->columns);
            $sql .= ' from '.$this->escape_table($table);
            $sql .= $this->joins_to_string($table);
            $sql .= $this->where_to_string($table);
            if (count($this->group_by) > 0) {
                $sql .= ' group by '.implode(', ', $this->group_by);
            }
            if (count($this->order_by) > 0) {
                $sql .= ' order by '.implode(', ', $this->order_by);
            }
            if (null != $this->limit) {
                $sql .= ' limit '.$this->limit;
                if (null != $this->offset) {
                    $sql .= ' offset '.$this->offset;
                }
            }
            $this->_reset(false);    # retain existing columns
            $this->column("($sql) as $value_name", false);
        } else {
            throw new Exception("Error: subselect with no columns.");
        }
    }

    function insert($table, $data = null) {
        if (iterable($data)) {
            $columns = array();
            $values = array();
            foreach ($data as $column => $value) {
                $columns[] = $this->_escape_column($column);
                $values[] = $this->_escape_data($value);
            }
            $sql = 'insert into '.$this->escape_table($table).
                '('.implode(', ', $columns).') values ('.implode(', ', $values).')';
            return $this->query($sql, self::GET_INSERT);
        } else {
            throw new Exception("Error: no data while inserting into '$table'.");
        }
    }

    function update($table, $data) {
        if (iterable($data)) {
            $new_values = array();
            foreach ($data as $column => $value) {
                $new_values[] = $this->_escape_column($column).' = '.$this->_escape_data($value);
            }
            $sql = 'update '.$this->escape_table($table);
            $sql .= $this->joins_to_string($table);     # when updating with joins in mysql, put the join before the set
            $sql .=' set '.implode(', ', $new_values);
            $sql .= $this->where_to_string($table);
            if (null != $this->limit) {
                $sql .= ' limit '.$this->limit;
                if (null != $this->offset) {
                    $sql .= ' offset '.$this->offset;
                }
            }
            return $this->query($sql, self::GET_AFFECTED);
        } else {
            throw new Exception("Error: no data while inserting into '$table'.");
        }
    }

    function insert_or_update($table, $data, $where) {
        if (iterable($data)) {
            foreach ($where as $key => $value) {
                $this->where($key, '=', $value);
            }
            if (0 == $this->count($table)) {
                # insert!
                foreach ($where as $key => $value) {
                    # merge the constraint value into the data
                    $data[$key] = $value;
                }
                $this->insert($table, $data);
            } else {
                # update!
                foreach ($where as $key => $value) {
                    # where value was reset, so do it again
                    $this->where($key, '=', $value);
                }
                $this->update($table, $data);
            }
        } else {
            throw new Exception("Error: no data while inserting or updating into '$table'.");
        }
    }

    function delete($table) {
        $sql = 'delete from '.$this->escape_table($table);
        $sql .= $this->where_to_string($table);
        if (null != $this->limit) {
            $sql .= ' limit '.$this->limit;
            if (null != $this->offset) {
                $sql .= ' offset '.$this->offset;
            }
        }
        return $this->query($sql, self::GET_AFFECTED);
    }

    function create_table($table, $columns, $keys = array()) {
        if (count($columns) > 0) {
            $sql = 'create table '.self::_get_setting('if_not_exists').' '.$this->escape_table($table)." (";
            $add_comma = false;
            foreach ($columns as $column => $type) {
                if ($add_comma) {
                    $sql .= ', ';
                } else {
                    $add_comma = true;
                }
                $sql .= $this->_escape_column($column)." ".self::column_attributes_to_string($type);
            }
            $p_keys = '';
            $db_keys = '';
            foreach ($keys as $key => $primary) {
                $key = $this->_escape_column($key);
                if (true === $primary) {
                    $p_keys .= ", primary key $key ($key)";
                } else {
                    $db_keys .= ", key $key ($key)";
                }
            }
            $sql .= " {$p_keys}$db_keys) ENGINE=".self::_get_setting('mysql_engine').
                " AUTO_INCREMENT=1 DEFAULT CHARSET=".self::_get_setting('charset');
            $this->query($sql, self::GET_NOTHING);
        } else {
            throw new Exception("Warning: attempting to create table '$table' with no columns.");
        }
    }


    # alter table functions

    function add_column($table, $column, $type, $after_col = null) {
        $sql = 'show columns from '.$this->escape_table($table).' where Field = '.$this->_escape_data($column);
        $row = $this->query($sql, self::GET_RESULTS);
        if (count($row) == 0) {
            $sql = 'alter table '.$this->escape_table($table)." add ".$this->_escape_column($column)." ".self::column_attributes_to_string($type);
            if (null != $after_col) {
                $sql .= ' after '.$this->_escape_column($after_col);
            }
            $this->query($sql, self::GET_NOTHING);
        }
    }

    function drop_column($table, $column) {
        $sql = "alter table ".$this->escape_table($table)." drop ".$this->_escape_column($column);
        $this->query($sql, self::GET_NOTHING);
    }

    function change_column_type($table, $column, $type) {
        $sql = "alter table ".$this->escape_table($table)." change ".$this->_escape_column($column)." ".$this->_escape_column($column).
            " ".self::column_attributes_to_string($type);
        $this->query($sql, self::GET_NOTHING);
    }

    function change_column_name($table, $current_name, $new_name, $type) {
        $sql = "alter table ".$this->escape_table($table)." change ".$this->_escape_column($current_name)." ".
            $this->_escape_column($new_name)." ".self::column_attributes_to_string($type);
        $this->query($sql, self::GET_NOTHING);
    }

    # get/set parameter functions

    function column($columns, $escape = true) {
        if (iterable($columns)) {
            foreach ($columns as $name => $alias) {
                if (is_string($name)) {
                    if ($escape) {
                        $this->columns[] = $this->_escape_column($name).' as '.$this->_escape_column($alias);
                    } else {
                        # should be safe to escape the alias
                        $this->columns[] = $name.' as '.$this->_escape_column($alias);
                    }
                } else {
                    $this->column($alias, $escape);        # in this case, it isn't really an alias
                }
            }
        } else {
            if ($escape) {
                $this->columns[] = $this->_escape_column($columns);
            } else {
                $this->columns[] = $columns;
            }
        }
        return $this;
    }

    function join($table_a, $col_a, $table_b, $col_b, $type = "left", $options = array()) {
        # notes:
        # - table b might be aliased
        # - either column might not need escaping
        $join           = new stdClass;
        $join->table_a  = $table_a;
        $join->table_b  = $table_b;
        $join->col_a    = $col_a;
        $join->col_b    = $col_b;
        $join->type     = $type;
        $join->options  = iterable($options) ? $options : array();
        $this->joins[]  = $join;
        return $this;
    }
    private function joins_to_string($table) {
        $joins_to_string = "";
        foreach ($this->joins as $join) {
            # if table_a isn't set, assume it is the table this query is selecting from
            if (null == $join->table_a)$join->table_a = $table;
            # join on table b
            $join->join_table = $this->escape_table($join->table_b);
            # if table b is aliased, then use that for the 'on' portion of the join
            if (isset($join->options["alias_table_b"])){$join->table_b = $join->options["alias_table_b"];}
            # escape the table names unless specifically directed not to
            if (!isset($join->options["noescape_table_b"])){$join->table_b = $this->escape_table($join->table_b);}
            if (!isset($join->options["noescape_table_a"])){$join->table_a = $this->escape_table($join->table_a);}
            # escape the join column names unless specfically directed not to
            if (!isset($join->options["noescape_col_a"])){$join->col_a = $this->_escape_column($join->col_a);}
            if (!isset($join->options["noescape_col_b"])){$join->col_b = $this->_escape_column($join->col_b);}

            # build the join string
            $joins_to_string .= " {$join->type} join ".
                $join->join_table.(isset($join->options["alias_table_b"]) ? " as ".$join->options["alias_table_b"] : null).
                " on ".$join->table_a.".".$join->col_a." = ".
                (isset($join->options["alias_table_b"]) ? $join->options["alias_table_b"] : $join->table_b).".".$join->col_b;
        }
        return $joins_to_string;
    }

    function where($column, $operator = true, $value = null, $and = true, $options = array()) {
        if (!in_array($operator, array("=", "<>", "is", "is not", "<", ">", ">=", "<="))) {
            # to help when porting code from rowBase objects which do not pass an operator, shift parameters
            $value = $operator;
            $operator = "=";
            $trace = debug_backtrace();
error_log("assuming operator is = for where query on $column - $value in {$trace[0]['file']} : {$trace[0]['line']}");
        }
        $this->_where($column, $operator, $value, $and, $options);
        return $this;
    }
    function or_where($column, $operator = true, $value = null, $options = array()) {
        $this->_where($column, $operator, $value, false, $options);
        return $this;
    }
    function and_where($column, $operator = true, $value = null, $options = array()) {
        $this->_where($column, $operator, $value, true, $options);
        return $this;
    }
    function where_in($column, Array $items, $and = true, $options = array()) {
        $escaped_items = array();
        foreach ($items as $item) {
            $escaped_items[] = $this->_escape_data($item);
        }
        $options["no_escape_value"] = true;
        $this->_where($column, "in", "(".implode(", ", $escaped_items).")", $and, $options);
        return $this;
    }
    function where_not_in($column, Array $items, $and = true, $options = array()) {
        $escaped_items = array();
        foreach ($items as $item) {
            $escaped_items[] = $this->_escape_data($item);
        }
        $options["no_escape_value"] = true;
        $this->_where($column, "not in", "(".implode(", ", $escaped_items).")", $and, $options);
        return $this;
    }

    function group_by($group_by, $escape = true) {
        $this->group_by[]    = $escape ? $this->_escape_column($group_by) : $group_by;
        return $this;
    }
    function order_by($order_by, $sort = 'asc', $escape = true) {
        $this->order_by[]    = ($escape ? $this->_escape_column($order_by) : $order_by).' '.$sort;
        return $this;
    }
    function random_order() {
        $this->order_by[]    = "RAND()";
        return $this;
    }
    function &limit($limit, $offset = null) {
        if (0 == $limit) {
            throw new Exception("Query limit is zero. Check if limit and offset are swapped.");
        }
        $this->limit        = $limit;
        $this->offset       = max(0, $offset);
        return $this;
    }

    # internal functions

    function _reset($reset_columns = true) {
        if ($reset_columns) {
            $this->columns = array();
        }
        $this->joins       = array();
        $this->where       = array();
        $this->group_by    = array();
        $this->order_by    = array();
        $this->limit       = null;
        $this->offset      = null;
    }

    function escape_table($name) {
        return $this->_escape_column($name);
    }

    function _escape_column($name) {
        return ('*' == $name) ? '*' : ('(' == $name[0] ? $name : '`'.str_replace('.','`.`',$name).'`');
    }

    function _escape_data($value) {
        switch (gettype($value)) {
            case 'object':     return "'".mysqli_real_escape_string(self::_get_connection(), (string)$value)."'"; break;
            case 'string':     return "'".mysqli_real_escape_string(self::_get_connection(), $value)."'"; break;
            case 'boolean':    return (true === $value) ? 1 : 0; break;
            case 'NULL':       return 'NULL'; break;
            default:           return $value; break;
        }
    }

    private function _where($column, $operator, $value, $and, $options = array()) {
        $where              = new stdClass;
        $where->column      = $column;
        $where->operator    = $operator;
        $where->value       = $value;
        $where->and         = $and;
        $where->options     = iterable($options) ? $options : array();  # can be: no_escape_column=>true, no_escape_value=>true
        $this->where[]      = $where;
    }
    private function where_to_string($table) {
        $where_to_string = "";
        foreach ($this->where as $where) {
            if (iterable($where->column)) {
                $subquery = '';
                foreach ($where->column as $value) {
                    if (iterable($value) && count($value) > 2) {
throw new Exception("bam"); # how does subquery work?
#                        $this->_where($subquery, $value[0], $value[1], $value[2],
#                            isset($value[3]) ? $value[3] : true, isset($value[4]) ? $value[4] : true);
                    } else {
                        throw new Exception("Error: parameters to where() are not array for {$where->column[0]} {$where->column[1]} {$where->column[2]}.");
                    }
                }
                if ($subquery != '') {
                    if ($where_to_string != '') {
                        $where_to_string .= ($where->and ? ' and' : ' or');
                    }
                    $where_to_string .= " ($subquery)";
                }
            } else {
                # if this where clause follows another, add 'and' or 'or'
                if ($where_to_string != '') {
                    $where_to_string .= ($where->and ? " and" : " or");
                }
                if (isset($where->options["begin_group"]) && $where->options["begin_group"] > 0) {
                    $where_to_string .= " ".str_repeat("(", $where->options["begin_group"]);
                }

                # add the column name
                if (isset($where->options["no_escape_column"]) && $where->options["no_escape_column"]) {
                    $where_to_string .= " $where->column";
                } else {
                    $where_to_string .= " ".$this->_escape_column($where->column);
                }

                # confirm the operator and add it
                if ($where->operator == "!=") {
                    $where->operator == "<>";
                }
                if (null === $where->value) {
                    # note: the operator should be "is" or "is not"
                    if ($where->operator == "=") {
                        $where->operator = "is";
                    }
                    else if ($where->operator == "<>") {
                        $where->operator = "is not";
                    }
                }
                $where_to_string .= " $where->operator";

                # add the value
                if (0 == strcasecmp('now()', $where->value) || isset($where->options["no_escape_value"]) && $where->options["no_escape_value"]) {
                    $where_to_string .= " $where->value";
                } else {
                    $where_to_string .= " ".$this->_escape_data($where->value);
                }
                if (isset($where->options["end_group"]) && $where->options["end_group"] > 0) {
                    $where_to_string .= str_repeat(")", $where->options["end_group"]);
                }
            }
        }
        if (strlen($where_to_string) > 0) {
            $where_to_string = " where".$where_to_string;
        }
        return $where_to_string;
    }

    static function _last_query() {
        return count(self::$queries) > 0 ? self::$queries[count(self::$queries)-1] : null;
    }

    static function _all_queries() {
        return implode("<br />\n", self::$queries);
    }

    static function _open_connection() {
        if (null == self::$settings) {
            # set_setting_default('database', 'debug', false);
            # set_setting_default('database', 'if_not_exists', 'if not exists');
            # set_setting_default('database', 'charset', 'utf8');
            # set_setting_default('database', 'mysql_engine', 'InnoDB');
            # set_setting_default('database', 'mysql_port', 3306);
            # set_setting_default('database', 'mysql_socket', null);
            # self::$settings = get_setting(null, 'database');
            self::$settings = array("username"=>SDATA_DB_USER, "password"=>SDATA_DB_PASS, "database"=>SDATA_DB_NAME,
                "hostname"=>SDATA_DB_HOST, "charset"=>"utf8", "if_not_exists"=>"if not exists", "mysql_engine"=>"InnoDB",
                "mysql_port"=>3306, "mysql_socket"=>null, "debug"=>false);
        }
        if (null == self::$connection) {
            self::$connection = mysqli_connect('p:'.self::$settings['hostname'],  self::$settings['username'],
                self::$settings['password'], self::_get_setting('database'), self::_get_setting('mysql_port'),
                self::_get_setting('mysql_socket'));
            if (null != self::$connection) {
                ++self::$use_count;
                return true;
            }
            return false;
        }
        ++self::$use_count;
        return true;
    }

    static function _get_connection() {
        return self::$connection;
    }

    static function _close_connection() {
        if (0 == --self::$use_count && null != self::$connection) {
            mysqli_close(self::$connection);
            self::$connection = null;
            if (self::_get_setting('debug')) {
                error_log("----- begin database queries -----");
                foreach (self::$queries as $query) {
                    error_log($query);
                }
                error_log("----- end database queries -----");
            }
        }
    }

    static function _get_setting($name) {
        return self::$settings[$name];
    }

    static function column_attributes_to_string($attributes) {
        $value = "";
        if (iterable($attributes)) {
            switch ($attributes["type"]) {
                # http://dev.mysql.com/doc/refman/5.6/en/numeric-type-overview.html
                # todo: review mysql column types and re-consider these mappings
                case "id":    # todo: bigint support?
                $value = "INTEGER UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE";
                break;

                case "bool":
                case "boolean":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT ".($attributes["default"] ? "TRUE" : "FALSE")) : "";
                $value = "BOOLEAN{$null}{$default}";
                break;

                case "int":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT ".(int)$attributes["default"]) : "";
                $signed = (isset($attributes["signed"]) && !$attributes["signed"]) ? " UNSIGNED" : " SIGNED";
                $value = "INTEGER{$signed}{$null}{$default}";
                break;

                case "float":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT ".(float)$attributes["default"]) : "";
                $signed = (isset($attributes["signed"]) && !$attributes["signed"]) ? " UNSIGNED" : "";
                $value = "FLOAT{$signed}{$null}{$default}";
                break;

                case "double":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT ".(double)$attributes["default"]) : "";
                $signed = (isset($attributes["signed"]) && !$attributes["signed"]) ? " UNSIGNED" : "";
                $value = "FLOAT{$signed}{$null}{$default}";
                break;

                case "decimal":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT ".(double)$attributes["default"]) : "";
                $signed = (isset($attributes["signed"]) && !$attributes["signed"]) ? " UNSIGNED" : "";
                $digits = (isset($attributes["digits"]) && iterable($attributes["digits"]))
                    ? "({$attributes['digits'][0]},{$attributes['digits'][1]})" : "";
                $value = "DECIMAL{$digits}{$signed}{$null}{$default}";
                break;

                case "enum":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT \"{$attributes["default"]}\"") : "";
                $value = "{$attributes["enum"]}{$null}{$default}";
                break;

                case "text":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT \"{$attributes["default"]}\"") : "";
                $unique = (isset($attributes["unique"]) && $attributes["unique"]) ? " UNIQUE" : "";
                $value = "TEXT{$null}{$default}{$unique}";
                break;

                case "varchar":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT \"{$attributes["default"]}\"") : "";
                $size = (isset($attributes["size"]) && (int)$attributes["size"] > 0) ? (int)$attributes["size"] : 10;
                $unique = (isset($attributes["unique"]) && $attributes["unique"]) ? " UNIQUE" : "";
                $value = "VARCHAR($size){$null}{$default}{$unique}";
                break;

                case "blob":
                case "binary":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $default = (isset($attributes["default"])) ? (null === $attributes["default"]
                    ? " DEFAULT NULL" : " DEFAULT \"{$attributes["default"]}\"") : "";
                $value = "BLOB{$null}{$default}";
                break;

                case "createdate":
                $value = "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";    # DATETIME
                break;

                case "timestamp":
                case "datetime":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $value = "DATETIME{$null}";
                break;

                case "date":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $value = "DATE{$null}";
                break;

                case "time":
                $null = (isset($attributes["null"]) && $attributes["null"]) ? "" : " NOT NULL";
                $value = "TIME{$null}";
                break;

                default:
                throw new Exception("Unknown database column type '{$attributes["type"]}'.");
                break;
            }
        } else {
            $value = $attributes;
        }
        return $value;
    }

    static function _increment_transaction() {
        if (0 == self::$trans_count++) {
            self::$trans_ok = true;
            return true;
        }
        return false;
    }

    static function _transaction_error() {
        # if we're using a transaction, save the error
        if (self::$trans_count > 0) {
            self::$trans_ok = false;
        }
    }

    static function _decrement_transaction() {
        return (self::$trans_count >= 0 && 0 == --self::$trans_count);
    }

    static function _check_transaction() {
        return self::$trans_ok;
    }

    private static function track_query($new_query) {
        self::$queries[] = $new_query;
    }

    static function prepare_handle_exception() {
        if (null == self::$previous_exception_handler) {
            self::$previous_exception_handler = set_exception_handler(array(__CLASS__, "_handle_exception"));
        }
    }

    static function _handle_exception($exception) {
error_log(print_r($exception, true));

        if (self::$trans_count >= 0) {
            # we're in the middle of a transaction, so roll it back
            mysqli_rollback(self::_get_connection());
            self::$queries[] = "rollback";
        }
        if (is_string(self::$previous_exception_handler)) {
            # call the previous exception handler
            $function = self::$previous_exception_handler;
            $function($exception);
        }
    }
}
