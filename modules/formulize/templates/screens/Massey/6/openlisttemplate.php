<?php

// if the user requested to download calculations, draw the link for downloading calculations
if($downloadCalculationsURL AND $downloadCalculationsText) {
	print "
	<div id='exportlink' style='display: none;'>
		<center><p><a href='$downloadCalculationsURL' target='_blank'>$downloadCalculationsText</a></p></center>
	</div>";
}

// scrollbox always on in Anari
$scrollBoxClassOnOff = 'scrollbox';

// start the main table of entries
print "
<div class='activity-list-of-entries' id='formulize-list-of-entries'>";
