<?php

require_once("../../../mainfile.php");
include_once XOOPS_ROOT_PATH."/modules/formulize/include/readelements.php";
include_once(XOOPS_ROOT_PATH."/modules/formulize/db-models/model_generator.php");

include XOOPS_ROOT_PATH.'/header.php';
global $xoopsTpl;
$xoopsTpl->assign('icms_pagetitle', "Database Model Demo");

$client_form = formulize_form::get_by_handle("client");
if (null != $client_form) {
    // example 1: just get client_name
    $client_data = client_form::get()
        ->column(array("client_name"))
        ->where("entry_id", ">", 0)
        ->order_by("client_name", "ASC")
        ->many();
    foreach ($client_data as $client) {
        if (!isset($client->client_name)) {
            echo "client name is not set in example 1";
            die;
        }
        if (isset($client->client_city)) {
            echo "client city is set in example 1";
            die;
        }
    }

    // example 2: just get client_city
    $client_data = client_form::get()
        ->column(array("client_city"))
        ->where("entry_id", ">", 0)
        ->order_by("client_name", "ASC")
        ->many();
    foreach ($client_data as $client) {
        if (isset($client->client_name)) {
            echo "client name is set in example 2";
            die;
        }
        if (!isset($client->client_city)) {
            echo "client city is not set in example 2";
            die;
        }
    }

    // example 3: just get client_city
    $client_data = client_form::get()
        // no columns selected, so the default of * is returned
        ->where("entry_id", ">", 0)
        ->order_by("client_name", "ASC")
        ->many();
    foreach ($client_data as $client) {
        if (!isset($client->entry_id)) {
            echo "entry id is not set in example 3";
            die;
        }
        if (!isset($client->client_name)) {
            echo "client name is not set in example 3";
            die;
        }
        if (!isset($client->client_city)) {
            echo "client city is not set in example 3";
            die;
        }
    }
    $xoopsTpl->assign("client_data", $client_data);
}

$xoopsTpl->display("file:".XOOPS_ROOT_PATH."/modules/formulize/db-models/model-demo.html");
include XOOPS_ROOT_PATH.'/footer.php';
