function icmsCodeHidden(id, enterHiddenPhrase) {
	if (enterHiddenPhrase == null) {
		enterHiddenPhrase = "Enter The Text To Be Hidden:";
	}
	var text = prompt(enterHiddenPhrase, "");
	var domobj = xoopsGetElementById(id);
	if (text != null && text != "") {
		var pos = text.indexOf(unescape('%00'));
		if (0 < pos) {
			text = text.substr(0, pos);
		}
		var result = "[hide]" + text + "[/hide]";
		xoopsInsertText(domobj, result);
	}

	domobj.focus();
}
