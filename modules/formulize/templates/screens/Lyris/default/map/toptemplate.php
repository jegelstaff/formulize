<?php

print "<h1>$title</h1>";

// Introductory text, from the screen's Appearance tab: the administrator's own markup, under the heading
if ($introductoryText) {
	print "<div class='formulize-screen-intro ck-content'>$introductoryText</div>";
}

// If there are filters, display them above the map
if (!empty($filters)) {

	print "
		<div class='formulize-map-filters'>
	";
	foreach ($filters as $heading => $filterMarkup) {
		print "
			<span class='formulize-map-filter-item'>
				<div class='formulize-map-filter-heading'>$heading</div>
				$filterMarkup
			</span>
		";
	}
	if ($filter_button_text) {
		print "
			<input type='submit' value='$filter_button_text'>
		";
	}
	print "
		</div>
	";

}
