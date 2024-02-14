<?php

print "
<article class='activity-list-of-entries-row'>
	<div class='activity-list-of-entries-row-image'>
		<img src='http://localhost:8080/themes/Massey/images/logo-for-calendar.png' />
	</div>
	<div class='activity-list-of-entries-row-content'>
		".($viewEntryLinksShown ? $viewEntryLink : '')."
		<h2>".display($entry, 'activities_activity_name')."</h2>
		<p>".display($entry, 'activities_details')."</p>
	</div>
	<div class='activity-list-of-entries-row-details'>
		<div>Date: ".display($entry, 'activities_date')."</h2>
		<div>Time: ".display($entry, 'activities_time')."</h2>
		<div>Location: ".display($entry, 'activities_location')."</h2>
	</div>
</article>";
