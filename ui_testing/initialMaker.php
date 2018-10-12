<?php
/**
 * Created by PhpStorm.
 * User: Sam
 * Date: 2018-09-22
 * Time: 10:56 AM
 */

$string = "Sam @";

$newString = findInitials($string);

echo "Name: ".$string."\n";
echo "Initials: ".$newString."";

function findInitials($ogName){
    $ogName = trim($ogName);
    $words = array();
    $words = preg_split('/\s+/', $ogName);
    $first = $words[0];


    if(sizeof($words)<2)
        $second = ' ';
    else
        $second = $words[1];
    if(sizeof($words)<3)
        $third = ' ';
    else
        $third = $words[2];

    echo "first letter=".$first."\n";
    echo "second letter=".$second."\n";
    echo "third letter=".$third."\n";

    $init = strtoupper($first[0]).strtoupper($second[0]).strtoupper($third[0]);
    return $init;
}