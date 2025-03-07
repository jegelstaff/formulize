<?php

static function create_link($app_id, $form_id) {
    return parent::static_insert(__CLASS__, array(
        "appid"     => $app_id,
        "fid"       => $form_id,
    ));
}

static function get_by_application($app_id) {
    return self::get()->where("appid", "=", $app_id)->many();
}

static function get_by_form($form_id) {
    return self::get()->where("fid", "=", $form_id)->one();
}
