<?php

static function get_by_handle($handle) {
    return self::get()->where("ele_handle", "=", $handle)->one();
}

static function get_for_form($form_id) {
    return self::get()
        ->where("id_form", "=", $form_id)
        ->order_by("ele_order", "ASC")
        ->many();
}

static function create_text_element($form, $handle, $caption, $column_heading, $description) {
    if (!is_a($form, "formulize_form")) {
        throw new Exception("Error: cannot create a new form element without the form object.");
        return false;
    }
    if (null != formulize_element::get_by_handle($handle)) {
        return false;
    }

    $new_element_id = parent::static_insert(__CLASS__, array(
        "id_form"                       => $form->id,
        //"ele_id"                        => autonumber,
        "ele_type"                      => "text",
        "ele_caption"                   => $caption,
        "ele_desc"                      => $description,
        "ele_colhead"                   => $column_heading,
        "ele_handle"                    => $handle,
        "ele_order"                     => self::next_element_sort_value($form->id),
        "ele_req"                       => 0,
        "ele_encrypt"                   => 0,
        "ele_value"                     => "a:10:{i:0;s:2:\"30\";i:1;s:3:\"255\";i:2;s:0:\"\";i:11;s:1:\"0\";i:3;s:1:\"0\";i:5;s:1:\"0\";i:6;s:0:\"\";i:10;s:0:\"\";i:7;s:1:\".\";i:8;s:1:\",\";}",
        "ele_uitext"                    => "",
        "ele_delim"                     => "",
        "ele_display"                   => 1,
        "ele_disabled"                  => 0,
        "ele_filtersettings"            => serialize(array(0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, )), //"a:4:{i:0;N;i:1;N;i:2;N;i:3;N;}",
        "ele_forcehidden"               => 0,
        "ele_private"                   => 0,
        "ele_use_default_when_blank"    => 0,
    ));

    if ($new_element_id > 0) {
        global $db;
        $db->add_column($form->form_tablename, $handle, array("type"=>"text", "null"=>true));
        if (null != $form->form_revision_tablename) {
            $db->add_column($form->form_revision_tablename, $handle, array("type"=>"text", "null"=>true));
        }
    }

    return $new_element_id;
}

static function create_textarea_element($form, $handle, $caption, $column_heading, $description) {
    $ele_value = serialize(array(0 => $code, 1 => "0", 2 => "", 3 => ".", 4 => ",", 5 => ""));
    $ele_filter_settings = serialize(array(0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL));
    return self::_create_new_element($form, $handle, "textarea", $caption, $column_heading, $description, $ele_value, $ele_filter_settings);
}

static function create_derived_element($form, $handle, $caption, $column_heading, $description, $code) {
    $ele_value = serialize(array(0 => $code, 1 => "0", 2 => "", 3 => ".", 4 => ",", 5 => ""));
    $ele_filter_settings = serialize(array(0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL));
    return self::_create_new_element($form, $handle, "derived", $caption, $column_heading, $description, $ele_value, $ele_filter_settings);
}

static function create_date_element($form, $handle, $caption, $column_heading, $description) {
    $ele_value = serialize(array(0 => ""));
    $ele_filter_settings = serialize(array(0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL));
    return self::_create_new_element($form, $handle, "date", $caption, $column_heading, $description, $ele_value, $ele_filter_settings);
}

static function create_checkbox_element($form, $handle, $caption, $column_heading, $description) {
    $ele_value = serialize(array(0 => ""));
    $ele_filter_settings = serialize(array(0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL));
    return self::_create_new_element($form, $handle, "checkbox", $caption, $column_heading, $description, $ele_value, $ele_filter_settings);
}

private static function _create_new_element($form, $handle, $type, $caption, $column_heading, $description, $ele_value, $ele_filter_settings) {
    if (!is_a($form, "formulize_form")) {
        error_log("Error: cannot create a new form element '$handle' without the form object.");
        throw new Exception("Error: cannot create a new form element '$handle' without the form object.");
        return false;
    }
    if (null != formulize_element::get_by_handle($handle)) {
        return false;
    }

    $new_element_id = parent::static_insert(__CLASS__, array(
        "id_form"                       => $form->id,
        //"ele_id"                        => autonumber,
        "ele_type"                      => $type,
        "ele_caption"                   => $caption,
        "ele_desc"                      => $description,
        "ele_colhead"                   => $column_heading,
        "ele_handle"                    => $handle,
        "ele_order"                     => self::next_element_sort_value($form->id),
        "ele_req"                       => 0,
        "ele_encrypt"                   => 0,
        "ele_value"                     => $ele_value,
        "ele_uitext"                    => "",
        "ele_delim"                     => "",
        "ele_display"                   => 1,
        "ele_disabled"                  => 0,
        "ele_filtersettings"            => $ele_filter_settings,
        "ele_forcehidden"               => 0,
        "ele_private"                   => 0,
        "ele_use_default_when_blank"    => 0,
    ));

    if ($new_element_id > 0) {
        global $db;
        switch ($type) {
            case 'text':
            case 'textarea':
            case 'derived':
            case 'checkbox':
                $db->add_column($form->form_tablename, $handle, array("type"=>"text", "null"=>true));
                break;

            case 'date':
                $db->add_column($form->form_tablename, $handle, array("type"=>"date", "null"=>true));
                break;

            default:
                error_log("Error: unknown element type '$type' -- cannot create database column");
                throw new Exception("Error: unknown element type '$type' -- cannot create database column", 1);
                break;
        }

        if (null != $form->form_revision_tablename) {
            switch ($type) {
                case 'text':
                case 'textarea':
                case 'derived':
                case 'checkbox':
                    $db->add_column($form->form_revision_tablename, $handle, array("type"=>"text", "null"=>true));
                    break;

                case 'date':
                    $db->add_column($form->form_revision_tablename, $handle, array("type"=>"date", "null"=>true));
                    break;

                default:
                    error_log("Error: unknown element type '$type' -- cannot create revision database column");
                    throw new Exception("Error: unknown element type -- cannot create database column", 1);
                    break;
            }
        }
    }

    return $new_element_id;
}

static function next_element_sort_value($form_id) {
    $sort = self::get()
        ->column("max(ele_order) as sort", false)
        ->where("id_form", "=", $form_id)
        ->order_by("ele_order", "desc")
        ->limit(1)
        ->select(self::tablename());
    if (1 == count($sort)) {
        return 1 + $sort[0]->sort;
    }
    return 1;
}

static function set_element_sort_order($handle, $new_sort_order) {
    $the_element = self::get_by_handle($handle);
    $the_element->set_sort_order($new_sort_order);
}

function options() {
    return unserialize($this->ele_value);
}

function get_subform_filter_options() {
    if ("subform" != $this->ele_type) {
        throw new Exception("This element is not a subform.");
    }
    if (isset($this->options[7])) {
        return $this->options[7];
    }
    return array(); // always an array
}

function add_subform_filter($column, $operator, $filter) {
    if ("subform" != $this->ele_type) {
        throw new Exception("This element is not a subform.");
    }
    $options = $this->options;
    if (!isset($options[7])) {
        $options[7] = array();
    }
    $options[7][0][] = $column;
    $options[7][1][] = $operator;
    $options[7][2][] = $filter;
    $options[7][3][] = "all";

    return parent::update(array(
        "ele_value" => serialize($options)
    ));
}

function set_caption($caption) {
    return parent::update(array(
        "ele_caption" => $caption
    ));
}

function set_sort_order($new_sort_order) {
    if ($this->ele_order != $new_sort_order and $new_sort_order > 0) {
        // eg: move element 5 to 10. bump elements starting at 10 up one. set the new element to 10.
        // original: 1 2 3 4 [5] 6 7 8 9 10 11 12 13 14 15
        // bump other elements: 1 2 3 4 [5] 6 7 8 9 [] 11 12 13 14 15 16
        // move this element to 10: 1 2 3 4 [] 6 7 8 9 [10] 11 12 13 14 15 16
        // fill the gap left by moving 5: 1 2 3 4 5 6 7 8 [9] 10 11 12 13 14 15
        global $db;
        // bump other elements
        $sql = "UPDATE ".self::tablename()." SET ele_order = ele_order + 1 WHERE ele_order >= $new_sort_order AND id_form = {$this->id_form}";
        $db->query($sql);

        // fill the gap (starting with the original sort order value)
        $sql = "UPDATE ".self::tablename()." SET ele_order = ele_order - 1 WHERE ele_order >= {$this->ele_order} AND id_form = {$this->id_form}";
        $db->query($sql);

        // move the element into place (adjusting down one because the gap has moved down)
        return parent::update(array(
            "ele_order" => $new_sort_order - 1 // should go down one because we filled in the gap
        ));
    }
    return false;
}
