<?php

/*
// example usage:

$graph_data = array("Sample A"=>array(4, 8, 6, 5, 4, 6), "Sample B"=>array(6, 5, 7, 6, 4, 8));
$graph_labels = array("2018", "2019", "2010", "2011", "2012", "2013");
$graph = new FormulizeGraph();
echo $graph
    ->width(600)
    ->height(400)
    ->spline(false)
    ->set_data($graph_data, $graph_labels)
    ->output_image_tag();   //->get_filename();    //->output_raw_image();
*/

if (!defined("XOOPS_MAINFILE_INCLUDED")) {
    include_once realpath(dirname(__FILE__) . "/../../../mainfile.php");
}
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/graphdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class FormulizeGraph {
    var $_options = array();
    var $labels = array();
    var $data = array();
    var $type = "Line";     // Line | Bar | Radar

    public function __construct() {
        $this->_options["height"]           = 200;
        $this->_options["width"]            = 300;
        $this->_options["FontName"]         = "arial";
        $this->_options["FontSize"]         = 10;
        $this->_options["spline"]           = false;
        $this->_options["padding"]          = 50;       // padding all the way around; leaves room for the legend
        $this->_options["DisplayValues"]    = false;
        $this->_options["DisplaySubTicks"]  = false;
        $this->_options["XMargin"]          = 40;       // horizontal space between y axis and first and last data points
        $this->_options["LabelRotation"]    = 0;
        $this->_options["Legend"]           = array("x"=>20, "y"=>5, "Style"=>LEGEND_NOBORDER, "Family"=>LEGEND_FAMILY_CIRCLE);
        $this->_options["MinDivHeight"]     = 25;
        $this->_options["GridR"]            = 0;
        $this->_options["GridG"]            = 0;
        $this->_options["GridB"]            = 0;
        $this->_options["GridAlpha"]        = 20;
        $this->_options["GridTicks"]        = 0;
    }

    public function __call($name, $values) {
        $this->_options[$name] = $values[0];
        return $this;
    }

    public function set_manual_scale($min, $max) {
        $this->_options["Mode"]             = SCALE_MODE_MANUAL;
        $this->_options["ManualScale"]      = array("0"=>array("Min"=>$min, "Max"=>$max));
        return $this;
    }

    public function set_title($title, $x, $y) {
        $this->_options['title'] = $title;
        $this->_options['titleX'] = $x;
        $this->_options['titleY'] = $y;
        return $this;
    }

    public function set_data($data, $labels) {
        $this->data = $data;
        $this->labels = $labels;
        return $this;
    }

    function get_filename() {
        $this->_options["return_filename"] = true;
        return displayLineGraph($this->data, $this->labels, "line graph", $this->_options);
    }

    function output_image_tag() {
        return "<img src=\"".XOOPS_URL.$this->get_filename()."\" />";
    }

    function output_raw_image($friendly_file_name = null) {
        $this->_options["return_filename"] = true;
        $graphFileName = $this->get_filename();
        $file_contents = file_get_contents(XOOPS_ROOT_PATH.$graphFileName);
        header("Content-Transfer-Encoding: Binary");
        header("Content-length: ".strlen($file_contents));
        if ($friendly_file_name)
            header("Content-disposition: filename=\"".$friendly_file_name.".png\"");
        header("Content-type: image/png");
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", time())." GMT");
        print($file_contents);
    }
}

