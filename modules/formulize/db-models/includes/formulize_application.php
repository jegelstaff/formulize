<?php

static function get_by_name($name) {
    return self::get()->where("name", "=", $name)->one();
}

function forms() {
    return formulize_form::forms_for_application($this->appid);
}

function set_name($name) {
    return parent::update(array(
        "name" => $name
    ));
}
