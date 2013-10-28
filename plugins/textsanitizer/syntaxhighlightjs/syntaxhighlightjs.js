function icmsCodeJS(id, enterJSPhrase) {
	if (enterJSPhrase == null) {
		enterJSPhrase = "Enter The Text To Be JS Code:";
	}
	var text = prompt(enterJSPhrase, "");
	var domobj = xoopsGetElementById(id);
	if (text != null && text != "") {
		var pos = text.indexOf(unescape('%00'));
		if (0 < pos) {
			text = text.substr(0, pos);
		}
		var result = "[code_js]" + text + "[/code_js]";
		xoopsInsertText(domobj, result);
	}

	domobj.focus();
}
