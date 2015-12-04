<?php   
 /* CAT:Bar Chart */

 /* pChart library inclusions */
 include("../class/pData.class.php");
 include("../class/pDraw.class.php");
 include("../class/pImage.class.php");

 /* Create and populate the pData object */
 $MyData = new pData();  
 $MyData->addPoints(Array(2000000, 600000, 12000000),"sum of City Population");
 $MyData->setAxisName(0,"sum of City Population");
 $MyData->addPoints(array("San Fransisco", "Seattle", "New York"),"City Name");
 $MyData->setSerieDescription("City Name","City Name");
 $MyData->setAbscissa("City Name");
 $MyData->setAbscissaName("City Name");
 //$MyData->setAxisDisplay(0,AXIS_FORMAT_METRIC,1);

/* Create the pChart object */
$myPicture = new pImage(500, 300, $MyData);
$myPicture -> drawGradientArea(0, 0, 500, 300, DIRECTION_VERTICAL, array("StartR" => 225, "StartG" => 22, "StartB" => 22, "EndR" => 225, "EndG" => 22, "EndB" => 22, "Alpha" => 100));
$myPicture->drawGradientArea(0,0,500,500,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>30));
$myPicture -> setFontProperties(array("FontName" => "../fonts/arial.ttf", "FontSize" => 8));

$paddingtoLeft = 500 * 0.15;
$paddingtoTop = 300 * 0.2;
if( $paddingtoTop > 50){
    $paddingtoTop = 50;
}

/* Draw the chart scale */
$myPicture -> setGraphArea($paddingtoLeft, $paddingtoTop, 500 * 0.90, 300 * 0.88);

if("vertical" == "vertical"){
    $myPicture -> drawScale(array("CycleBackground" => TRUE, "DrawSubTicks" => TRUE, "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10, "Pos" => SCALE_POS_TOPBOTTOM, "Mode" => SCALE_MODE_ADDALL_START0, "Decimal" => 0, "MinDivHeight" => 50));
}else{
    $myPicture -> drawScale(array("CycleBackground" => TRUE, "DrawSubTicks" => TRUE, "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10, "Mode" => SCALE_MODE_ADDALL_START0, "Decimal" => 0, "MinDivHeight" => 50));
}

/* Turn on shadow computing */
$myPicture -> setShadow(TRUE, array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));

$Palette = array("0"=>array("R"=>190,"G"=>106,"B"=>70,"Alpha"=>100));

for($i = 1 ; $i < 3 ; $i++){
    $Palette[$i] = array("R"=>190,"G"=>106,"B"=>70,"Alpha"=>100);
}

// print_r($Palette);

$myPicture->drawBarChart(array("OverrideColors"=>$Palette));

/* Draw the chart */
$myPicture -> drawBarChart(array("DisplayPos" => LABEL_POS_INSIDE, "DisplayValues" => TRUE, "Rounded" => TRUE, "Surrounding" => 30, "OverrideColors"=>$Palette));



 /* Render the picture (choose the best way) */
 $myPicture->autoOutput("pictures/example.drawBarChart.vertical.png");
?>