<?php

print "
<div style='clear: both;'></div>
</div>
</div>
</div>

<hr>

<div id='multipage-controls'>
$previousPageButton $savePageButton $nextPageButton $pageIndicator $pageSelector
</div>

<script type='text/javascript'>

function getCurrentHeightOfTabs() {
	return jQuery('#pageNavTable').innerHeight();
}

function getDefaultHeightOfTabs() {
	return jQuery('#pageNavTable > a.navtab').outerHeight(true);
}

function getTabFontSize() {
	return parseInt(jQuery('#pageNavTable').css('font-size'));
}

function setFormOffsetFromTabs() {
	let currentHeight = getCurrentHeightOfTabs();
	let defaultHeight = getDefaultHeightOfTabs();
	let offset = 0;
	if(currentHeight > defaultHeight) {
		let fontSize = getTabFontSize();
		offset = Math.round((currentHeight - defaultHeight) / fontSize);
	}
	jQuery('#formulize_mainform > div.card').css('margin-top', offset+'em');
}

jQuery(document).ready(function() {
	setFormOffsetFromTabs();
	setTimeout(setFormOffsetFromTabs,500);
});

jQuery(window).resize(function () {
	setFormOffsetFromTabs();
});

</script>
";
