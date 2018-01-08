tinyMCEPopup.requireLangPack();

var IcmsmlcontentDialog = {
	init : function() {
		var f = document.forms[0];
		// Get the selected contents as text and place it in the input
		f.mltext.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		window.focus();
		},

	insertMLC : function() {
		var f = document.forms[0];
		if (window.opener) {
			var mltext = f.mltext.value;
			var selectlang = f.langfield.value;
			mltext.replace(new RegExp("<",'g'), "&lt;");
			mltext.replace(new RegExp(">",'g'), "&gt;");
			var html = '['+selectlang+']';
			html += mltext+'[/'+selectlang+']';
			// Insert the contents from the input into the document
			tinyMCEPopup.editor.execCommand('mceInsertContent', true, html);
			}
		tinyMCEPopup.close();
		}
	}

tinyMCEPopup.onInit.add(IcmsmlcontentDialog.init, IcmsmlcontentDialog);
