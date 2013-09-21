function icmsCodeYoutube(id, enterYoutubePhrase, enterYoutubeWidthPhrase,
		enterYoutubeHeightPhrase) {

	var text = prompt(enterYoutubePhrase, "");
	var domobj = xoopsGetElementById(id);
	if (text == "" || text == null) {
		domobj.focus();
		return false;
	}
	if (text.length > 0) {
		var text2 = prompt(enterYoutubeWidthPhrase, "425");
		if (text2 == "" || text2 == null) // added by vinod for XDH 2.5
			text2 = 425;
		var text3 = prompt(enterYoutubeHeightPhrase, "350");
		if (text3 == "" || text3 == null) // added by vinod for XDH 2.5
			text3 = 350;
		var result = "[youtube=" + text2 + "," + text3 + "]" + text
				+ "[/youtube]";
		xoopsInsertText(domobj, result);
	}
	domobj.focus();
}
