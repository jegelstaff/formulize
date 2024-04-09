/*
 * Formulize remote include javascript library
 * Copyright the Formulize Project 2023
 */

var formulize = {url:''};
formulize.url = document.getElementById('formulize.remote.js').getAttribute('src').replace('/remote.js', '');
if (formulize.url == '') {
    console.log('Formulize Remote Error: could not determine url for your Formulize instance. Check that the <script> tag that includes the remote.js file has the id "formulize.remote.js".');
}

if(typeof window.jQuery == 'undefined') {
    var dochead = document.getElementsByTagName('head')[0];
    const formulize_jQueryScript = document.createElement('script');
    formulize_jQueryScript.src = 'https://code.jquery.com/jquery-1.12.4.js';
    formulize_jQueryScript.crossorigin = 'anonymous';
    dochead.appendChild(formulize_jQueryScript);
    const formulize_jQueryUIScript = document.createElement('script');
    formulize_jQueryUIScript.src = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
    formulize_jQueryUIScript.crossorigin = 'anonymous';
    dochead.appendChild(formulize_jQueryUIScript);
    const formulize_moduleCSS = document.createElement('link');
    formulize_moduleCSS.rel = 'stylesheet';
    formulize_moduleCSS.href = formulize.url+'/modules/formulize/templates/css/formulize.css';
    formulize_moduleCSS.type = 'text/css';
    dochead.appendChild(formulize_moduleCSS);
    const formulize_themeCSS = document.createElement('link');
    formulize_themeCSS.rel = 'stylesheet';
    formulize_themeCSS.href = formulize.url+'/themes/Anari/css/style.css';
    formulize_themeCSS.type = 'text/css';
    dochead.appendChild(formulize_themeCSS);
		const poppinsFont = document.createElement('link');
    poppinsFont.rel = 'stylesheet';
    poppinsFont.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap';
    poppinsFont.type = 'text/css';
    dochead.appendChild(poppinsFont);

}

var lastLoadedScreenId = 0;
var lastUsedDomTarget = '';
function formulize_remoteRenderScreen(screen_id, dom_id, formData='') {
    lastLoadedScreenId = parseInt(screen_id);
    lastUsedDomTarget = dom_id;
    jQuery.ajax({
        type: "POST",
        data: formData,
        url: formulize.url+'/modules/formulize/index.php?sid='+screen_id,
        headers: { 'formulize-remote-include': 1 },
        success: function(html) {
                jQuery('#'+dom_id).empty();
				jQuery('#'+dom_id).append(html).ready(function() {
                    jQuery('body').show(200, function() {
                        jQuery('.formulizeThemeForm').each(function() {
                            jQuery(this).show();
                        });
                        jQuery(window).keydown(function(event){
                            if(event.keyCode == 13) {
                                event.preventDefault();
                                formulize_remoteSubmitList();
                            }
                            return true;
                        });
                        var formulize_pageShown = new CustomEvent('formulize_pageShown');
                        window.dispatchEvent(formulize_pageShown);
                    });
                });
            }
    });
}

function formulize_remoteSubmitList() {
    formulize_remoteRenderScreen(lastLoadedScreenId, lastUsedDomTarget, jQuery('#controls').serialize());
}

function formulize_remoteSubmitForm() {
    formulize_remoteRenderScreen(lastLoadedScreenId, lastUsedDomTarget, jQuery('#formulize_mainform').serialize());
}
