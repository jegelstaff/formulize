<?php

static function get_by_handle($handle) {
    return self::get()->where("form_handle", "=", $handle)->one();
}

static function create_form($title, $handle) {
    return parent::static_insert(__CLASS__, array(
        "id_form"           => $form_id,
        "desc_form"         => $title,
        //"singleentry"       => $singleentry,
        //"headerlist"        => $headerlist,
        //"tableform"         => $tableform,
        //"defaultform"       => $defaultform,
        //"defaultlist"       => $defaultlist,
        //"menutext"          => $menutext,
        //"form_handle"       => $handle,
        //"store_revisions"   => $store_revisions,
        //"on_before_save"    => $on_before_save,
        //"note"              => $note,
    ));
}

static function forms_for_application($app_id) {
    return self::get()
        ->join(self::tablename(), "id_form", formulize_application_forms::tablename(), "fid", "left")
        ->where(formulize_application_forms::tablename().".appid", "=", $app_id)
        ->order_by("desc_form", "asc")
        ->many();
}

function elements() {
    return formulize_element::get_for_form($this->id_form);
}

function title() {
    return $this->desc_form;
}

function set_title($title) {
    return parent::update(array(
        "desc_form" => $title
    ));
}
