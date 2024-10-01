<?php

# Purpose: this generates rowobj model files for tables in a mysql database.

if (!function_exists("iterable")) {
    function iterable($var) {
        return is_array($var) || $var instanceof ArrayAccess;
    }
}
if (!function_exists("log_var")) {
    function log_var($var, $description = "") {
        error_log($description.print_r($var, true));
    }
}

include_once(realpath(dirname(__FILE__))."/rowobj.php");

function model_autoloader($class) {
    global $xoopsDB;
    switch ($class) {
        case "formulize_application":
        $table_name = SDATA_DB_PREFIX . "_formulize_applications";
        break;

        case "formulize_application_forms": # link between applications and forms
        $table_name = SDATA_DB_PREFIX . "_formulize_application_form_link";
        break;

        case "formulize_element":
        $table_name = SDATA_DB_PREFIX . "_formulize";
        break;

        case "formulize_form":
        $table_name = SDATA_DB_PREFIX . "_formulize_id";
        break;

        default:
        if ("_form" == substr($class, -5)) {
            $class = substr($class, 0, (strlen($class) - 5));
            $table_name = SDATA_DB_PREFIX . "_formulize_" . $class;
            $class .= "_form";
        } else {
            return;
        }
    }
    if (null != $xoopsDB->queryF("select count(*) from $table_name")) {
        $the_class = create_model_file_for_tables($table_name, $class);
				$fileName = XOOPS_ROOT_PATH.'/cache/'.$class.'.php';
				file_put_contents($fileName, "<?php\n\n".$the_class);
        include_once $fileName;
    } else {
        //error_log("table $table_name not found when making a class for $class");
    }
}
spl_autoload_register("model_autoloader");

function create_model_file_for_tables($tablename = null, $class = null, $output_file_path = null) {
    $definitions = array();
    global $db;
    $columns = $db->query("describe $tablename", db::GET_RESULTS);
    $max_column_len = 0;
    foreach ($columns as $column) {
        $max_column_len = max($max_column_len, strlen($column->Field));
    }
    $max_column_len = ((int)(($max_column_len+2) / 4) + 1) * 4; # add 2 for the quotes
    $entry_id_column = "entry_id";
    $column_definition = "static \$fields = array(\n";
    foreach ($columns as $column) {
        if (NULL === $column->Default) {
            $default_def = ", \"default\"=>null";
        } else {
            $default_def = ", \"default\"=>\"$column->Default\"";
        }

        if ("YES" == $column->Null) {
            $null_def = ", \"null\"=>true";
        } else {
            $null_def = ", \"null\"=>false";
        }

        $size_def = null;
        $count = preg_match("|(?P<size>[\d]+)|", $column->Type, $matches);
        if ($count > 0) {
            $index = 0;
            while (isset($matches[$index])) {
                unset($matches[$index]);
                $index++;
            }
            if (count($matches) > 0) {
                $column->Type = str_replace("({$matches['size']})", "", $column->Type);
                if (10 == $matches['size']) {
                    $matches['size'] = 11;
                }
                $size_def = ", \"size\"=>{$matches['size']}";
            }
        }
        $count = preg_match("|(?P<digits>[\d]+,[\d]+)|", $column->Type, $matches);
        if ($count > 0) {
            $index = 0;
            while (isset($matches[$index])) {
                unset($matches[$index]);
                $index++;
            }
            if (count($matches) > 0) {
                $column->Type = str_replace("({$matches['digits']})", "", $column->Type);
                $digits = "{$matches['digits']}";
            }
        }

        # enum
        if (0 == substr_compare("enum(", $column->Type, 0, 5)) {
            $column->Type = "text"; # pretend it's text...
        }

        # check for id column
        if ("auto_increment" == $column->Extra) {
            $entry_id_column = $column->Field;
        }

        $column_definition .= "        \"{$column->Field}\"".str_repeat(" ", $max_column_len - strlen($column->Field))."=> array(";
        switch ($column->Type) {
            case "int":
            case "tinyint":
            case "smallint":
            case "smallint unsigned":
            case "bigint":
            case "int unsigned":
            case "mediumint unsigned":
            if ($column->Extra == "auto_increment") {
                $column_definition .= "\"type\"=>\"id\"";
                $default_def = null;
                $null_def = null;
                $size_def = null;
            } else {
                $column_definition .= "\"type\"=>\"int\"";
                if (strpos($column->Type, "unsigned") > 0) {
                    $column_definition .= ", \"signed\"=>true";
                } else {
                    $column_definition .= ", \"signed\"=>false";
                }
            }
            break;

            case "timestamp":
            if ("0000-00-00 00:00:00" == $column->Default) {
                $column_definition .= "\"type\"=>\"datetime\"";
            } else {
                $column_definition .= "\"type\"=>\"createdate\"";
                $default_def = null;
                $null_def = null;
                $size_def = null;
            }
            break;

            case "date":        # hmm... should this just be date?
            case "datetime":
            $column_definition .= "\"type\"=>\"datetime\"";
            break;

            case "varchar":
            $column_definition .= "\"type\"=>\"varchar\"";
            break;

            case "tinytext":
            case "text":
            $column_definition .= "\"type\"=>\"text\"";
            break;

            case "float":
            case "double":
            $column_definition .= "\"type\"=>\"double\"";
            break;

            case "double unsigned":
            $column_definition .= "\"type\"=>\"double\", \"signed\"=>false";
            break;

            case "decimal":
            #case "decimal(10,4)":
            #case "decimal(10,4)":
            $column_definition .= "\"type\"=>\"decimal\", \"digits\"=>array($digits)";
            break;

            case "tinyint unsigned":
            $column_definition .= "\"type\"=>\"bool\"";
            break;

            case "longblob":
            $column_definition .= "\"type\"=>\"blob\"";
            break;

            case "time":
            $column_definition .= "\"type\"=>\"time\"";
            break;

            default:
            log_var($column);
            throw new Exception("Column '{$column->Field}' type '{$column->Type}' in table '$tablename' not accounted for.");
            break;
        }
        $column_definition .= "{$size_def}{$default_def}{$null_def}),\n";
    }
    $column_definition .= "    );\n";
    $definitions[$tablename] = $column_definition;

    $extra_functions = "";
    $class_include_file = realpath(dirname(__FILE__))."/includes/".$class.".php";
    if (file_exists($class_include_file)) {
        $extra_functions = file_get_contents($class_include_file);
        if (strlen($extra_functions) > 5) {
            // remove the PHP open tag
            $extra_functions = substr($extra_functions, 6);
        }
    }

    if (null == $output_file_path) {
        $obj_class = <<<EOF
class $class extends rowobj {
    $column_definition
    static function tablename(){return "$tablename";}
    static function idcolname(){return "$entry_id_column";}
    static function create_table(\$class_name = null){rowobj::create_table(__CLASS__);}
    static function &get(){\$query = new dbquery(__CLASS__, self::tablename(), self::idcolname());return \$query;}
    static function count_all(){return self::get()->count();}
    static function &get_id(\$id = null){return self::get()->id(\$id);}
    function id() {return \$this->$entry_id_column;}
    $extra_functions
}
EOF;
    } else {
        if (!file_exists("$output_file_path/$tablename.php")) {
            file_put_contents("$output_file_path/$tablename.php", "<?php\n".$obj_class);
        }
    }
    return $obj_class;
}
