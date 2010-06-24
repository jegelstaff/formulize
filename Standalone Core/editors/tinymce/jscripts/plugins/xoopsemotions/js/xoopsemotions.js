tinyMCEPopup.requireLangPack();

var XoopsemotionsDialog = {
	init : function()
		{
//		tinyMCEPopup.resizeToInnerSize();
		},

	insert : function(file_name,title)
		{
		// Insert the contents from the input into the document
//		title = tinyMCE.getLang(title);
		if (title == null) 
			title = "";
		if (window.opener)
			{
			// XML encode
			title = title.replace(/&/g, '&amp;');
			title = title.replace(/\"/g, '&quot;');
			title = title.replace(/</g, '&lt;');
			title = title.replace(/>/g, '&gt;');
			var html = '<img src="' + file_name + '" mce_src="' + file_name + '" border="0" alt="' + title + '" title="' + title + '" />';
			tinyMCEPopup.editor.execCommand('mceInsertContent', false, html);
			tinyMCEPopup.close();
			}
		}
};

tinyMCEPopup.onInit.add(XoopsemotionsDialog.init, XoopsemotionsDialog);
