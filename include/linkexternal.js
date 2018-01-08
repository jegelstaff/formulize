// External Links

// @copyright	The ImpressCMS Project http://www.impresscms.org/
// @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
// @package	core
// @since	1.0
// @author 	vaughan
// @version	$Id: linkexternal.js 1000 2008-03-07 15:34:34Z m0nty_ $

// This function enables the use of javascript to open new windows for link urls
// in replace of target="_blank" etc due to xhtml validation not recognising target anymore.
// typical values for rel in the <a href> tag are:
// rel="external" - opens  destination link in external window
// rel="nofollow" - instructs web crawlers & bots to not follow or score the destination link (SEO necessity)
// rel="nofollow external" a combination of both the above.
// example use: <a href="http://www.impresscms.org" rel="nofollow external" />ImpressCMS</a>

function icms_ExternalLinks() {  
	if (!document.getElementsByTagName) return;  

	var anchors = document.getElementsByTagName("a");  
	for (var i=0; i<anchors.length; i++) {  
		var anchor = anchors[i];  
		var relvalue = anchor.getAttribute("rel"); 

		if (anchor.getAttribute("href")) { 
			var external = /external/; 
			var relvalue = anchor.getAttribute("rel"); 
			if (external.test(relvalue)) {
				anchor.target = "_blank";
			} 
		}  
	} 
}
window.onload = icms_ExternalLinks;