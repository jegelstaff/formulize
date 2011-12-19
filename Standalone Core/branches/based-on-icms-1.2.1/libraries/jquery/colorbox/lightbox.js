//This hides the inline content if JavaScript is supported.
document.write("<style type='text/css'>.hidden{display:none;}<\/style>");

$(document).ready(function(){
	$("a[rel='lightbox']").colorbox({transition:"elastic", contentCurrent:"{current} / {total}"});
});
