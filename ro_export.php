<?php

include "mainfile.php";

global $xoopsUser;
if(!$xoopsUser) {
    exit();
}

include "header.php";
?>

<h1>RO Export/Import</h1>

<p><a href="https://dara.daniels.utoronto.ca/modules/formulize/index.php?sid=81">Course Data</a></p>
<p><a href="https://dara.daniels.utoronto.ca/modules/formulize/index.php?sid=84">Section Data</a></p>
<p><a href="https://dara.daniels.utoronto.ca/modules/formulize/index.php?sid=85">Section Times</a></p>

<?php
include "footer.php";