function icmsCodePHP(id, enterPHPPhrase) {
	if (enterPHPPhrase == null) {
		enterPHPPhrase = "Enter The Text To Be PHP Code:";
	}
	var text = prompt(enterPHPPhrase, "");
	var domobj = xoopsGetElementById(id);
	if (text != null && text != "") {
		var pos = text.indexOf(unescape('%00'));
		if (0 < pos) {
			text = text.substr(0, pos);
		}
		var result = "[code_php]" + text + "[/code_php]";
		xoopsInsertText(domobj, result);
	}

	domobj.focus();
}
