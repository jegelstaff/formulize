<?php
print "<h1>Weighting Overrides</h1>";
print "<p>Clear Overrides for: ".buildFilter('clearYear', 14)." ".$clearOverridesButton."</p>";

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";
$locked = daraShowYearFilter($quickFilterro_module_year);
?>
<script type='text/javascript'>
	var updateDisplayHack = false;
	var parentId;
</script>