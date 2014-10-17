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

function form_tablename() {
    return SDATA_DB_PREFIX . "_formulize_" . $this->form_handle;
}

function form_revision_tablename() {
    if (1 == $this->store_revisions) {
        return SDATA_DB_PREFIX . "_formulize_" . $this->form_handle . "_revisions";
    }
    return null;
}

function set_title($title) {
    return parent::update(array(
        "desc_form" => $title
    ));
}

function enable_revisions() {
    if (0 == $this->store_revisions) {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        // 0 is the id of a form we're cloning, false is the map of old elements to new elements when cloning so n/a here,
        //  true is the flag for making a revisions table
        if ($form_handler->createDataTable($this->id_form, 0, false, true)) {
            return parent::update(array(
                "store_revisions" => 1
            ));
        }
        print "Error: could not create the revision history table for the '{$this->form_handle}' form.<br/>";
        error_log("Error: could not create the revision history table for the '{$this->form_handle}' form.");
    } else {
        error_log("Already enabled revisions for {$this->form_handle}.");
    }
    return 0;
}
