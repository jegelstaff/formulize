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

