// created 2005-1-12 by Martin Sadera (sadera@e-d-a.info)
// ported to Xoops CMS by ralf57
// updated to TinyMCE v3.0.1 / 2008-02-29 / by luciorota
tinyMCEPopup.requireLangPack();

var IcmshideDialog = {
	init : function()
		{
		var formObj = document.forms[0];
		// Get the selected contents as text and place it in the input
		formObj.htext.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		},
	insert : function()
		{
		// Insert the contents from the input into the document
		var formObj = document.forms[0];
		if (window.opener)
			{
			var htext = formObj.htext.value;
			htext.replace(new RegExp("<",'g'), "&lt;");
			htext.replace(new RegExp(">",'g'), "&gt;");
			var html = '[hide]';
			html += htext+'[/hide]<br />';
			tinyMCEPopup.editor.execCommand('mceInsertContent', true, html);
			tinyMCEPopup.close();
			}
		}
};

tinyMCEPopup.onInit.add(IcmshideDialog.init, IcmshideDialog);
