<?php

$activityImageLink = viewEntryLink("<img src='/themes/Massey/images/logo-for-calendar.png'>");
$activityTitleLink = viewEntryLink(display($entry, 'activities_activity_name'));
$activityType = display($entry, 'activities_type');
$activityDate = display($entry, 'activities_activity_date');
$activityTime = display($entry, 'activities_activity_time');
$activityLocation = display($entry, 'activities_activity_location');

print "
<article class='activity-list-of-entries-row'>
	<div class='activity-list-of-entries-row-image'>
		".$activityImageLink."
	</div>
	<div class='activity-list-of-entries-row-content'>
		<h2>".$activityTitleLink."</h2>
		<p>".display($entry, 'activities_details')."</p>
	</div>
	<div class='activity-list-of-entries-row-details'>
		".($activityType ? "<div>Type: ".$activityType."</div>" : '')."
		".($activityDate ? "<div>Date: ".$activityDate."</div>" : '')."
		".($activityTime ? "<div>Time: ".$activityTime."</div>" : '')."
		".($activityLocation ? "<div>Location: ".$activityLocation."</div>" : '')."
	</div>
</article>
";
