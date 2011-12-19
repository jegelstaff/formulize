if ( typeof window.$ != 'function' ) {
	function $() {
		var elements = new Array();

		for (var i = 0; i < arguments.length; i++) {
			var element = arguments[i];
			if (typeof element == 'string')
				element = document.getElementById(element);

			if (arguments.length == 1)
				return element;

			elements.push(element);
		}

		return elements;
	}
}

function xoopsGetElementById(id) {
	if (typeof jQuery == 'function') {
		return $("[id='" + id + "']")[0];
	} else {
		return $(id);
	}
}

function xoopsSetElementProp(name, prop, val) {
	var elt=xoopsGetElementById(name);
	if (elt) elt[prop]=val;
}

function xoopsSetElementStyle(name, prop, val) {
	var elt=xoopsGetElementById(name);
	if (elt && elt.style) elt.style[prop]=val;
}

function xoopsGetFormElement(fname, ctlname) {
	var frm=document.forms[fname];
	return frm?frm.elements[ctlname]:null;
}

function justReturn() {
	return;
}

function openWithSelfMain(url,name,width,height,returnwindow) {
	var options = "width=" + width + ",height=" + height + ",toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no";

	var new_window = window.open(url, name, options);
	window.self.name = "main";
	new_window.focus();
	if (returnwindow != null) {
	   return new_window;
	}
}

function setElementColor(id, color){
	xoopsGetElementById(id).style.color = "#" + color;
}

function setElementFont(id, font){
	xoopsGetElementById(id).style.fontFamily = font;
}

function setElementSize(id, size){
	xoopsGetElementById(id).style.fontSize = size;
}

function changeDisplay(id){
	var elestyle = xoopsGetElementById(id).style;
	if (elestyle.display == "block") {
		elestyle.display = "none";
	} else {
		elestyle.display = "block";
	}
}

function setVisible(id){
	xoopsGetElementById(id).style.visibility = "visible";
}

function setHidden(id){
	xoopsGetElementById(id).style.visibility = "hidden";
}

function makeBold(id){
	var eleStyle = xoopsGetElementById(id).style;
	if (eleStyle.fontWeight != "bold" && eleStyle.fontWeight != "700") {
		eleStyle.fontWeight = "bold";
	} else {
		eleStyle.fontWeight = "normal";
	}
}

function makeItalic(id){
	var eleStyle = xoopsGetElementById(id).style;
	if (eleStyle.fontStyle != "italic") {
		eleStyle.fontStyle = "italic";
	} else {
		eleStyle.fontStyle = "normal";
	}
}

function makeUnderline(id){
	var eleStyle = xoopsGetElementById(id).style;
	if (eleStyle.textDecoration != "underline") {
		eleStyle.textDecoration = "underline";
	} else {
		eleStyle.textDecoration = "none";
	}
}

function makeLineThrough(id){
	var eleStyle = xoopsGetElementById(id).style;
	if (eleStyle.textDecoration != "line-through") {
		eleStyle.textDecoration = "line-through";
	} else {
		eleStyle.textDecoration = "none";
	}
}

function appendSelectOption(selectMenuId, optionName, optionValue){
	var selectMenu = xoopsGetElementById(selectMenuId);
	var newoption = new Option(optionName, optionValue);
	selectMenu.options[selectMenu.length] = newoption;
	selectMenu.options[selectMenu.length].selected = true;
}

function disableElement(target){
	var targetDom = xoopsGetElementById(target);
	if (targetDom.disabled != true) {
		targetDom.disabled = true;
	} else {
		targetDom.disabled = false;
	}
}

function xoopsCheckAll( form, switchId ) {
	var eltForm = xoopsGetElementById(form);
	var eltSwitch = xoopsGetElementById(switchId);
	// You MUST NOT specify names, it's just kept for BC with the old lame crappy code
	if ( !eltForm && document.forms[form] )		eltForm = document.forms[form];
	if ( !eltSwitch && eltForm.elements[switchId] )	eltSwitch=eltForm.elements[switchId];

	var i;
	for (i=0;i!=eltForm.elements.length;i++) {
		if ( eltForm.elements[i] != eltSwitch && eltForm.elements[i].type == 'checkbox' ) {
			eltForm.elements[i].checked = eltSwitch.checked;
		}
	}
}

function xoopsCheckGroup( form, switchId, groupName ) {
	var eltForm = xoopsGetElementById(form);
	var eltSwitch = xoopsGetElementById(switchId);
	// You MUST NOT specify names, it's just kept for BC with the old lame crappy code
	if ( !eltForm && document.forms[form] )		eltForm = document.forms[form];
	if ( !eltSwitch && eltForm.elements[switchId] )	eltSwitch=eltForm.elements[switchId];

	var i;
	for (i=0;i!=eltForm.elements.length;i++) {
		var e=eltForm.elements[i];
		if ( (e.type == 'checkbox') && ( e.name == groupName ) ) {
			e.checked = eltSwitch.checked;
			e.click(); e.click();  // Click to activate subgroups twice so we don't reverse effect
		}
	}
}

function xoopsCheckAllElements(elementIds, switchId) {
	var switch_cbox = xoopsGetElementById(switchId);
	for (var i = 0; i < elementIds.length; i++) {
		var e = xoopsGetElementById(elementIds[i]);
		if ((e.name != switch_cbox.name) && (e.type == 'checkbox')) {
			e.checked = switch_cbox.checked;
		}
	}
}

function xoopsSavePosition(id)
{
	var textareaDom = xoopsGetElementById(id);
	if (textareaDom.createTextRange) {
		textareaDom.caretPos = document.selection.createRange().duplicate();
	}
}

function xoopsInsertText(domobj, text)
{
	if (domobj.createTextRange && domobj.caretPos){
  		var caretPos = domobj.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) 
== ' ' ? text + ' ' : text;  
	} else if (domobj.getSelection && domobj.caretPos){
		var caretPos = domobj.caretPos;
		caretPos.text = caretPos.text.charat(caretPos.text.length - 1)  
== ' ' ? text + ' ' : text;
	} else {
		domobj.value = domobj.value + text;
  	}
}

function xoopsCodeSmilie(id, smilieCode) {
	var revisedMessage;
	var textareaDom = xoopsGetElementById(id);
	xoopsInsertText(textareaDom, smilieCode);
	textareaDom.focus();
	return;
}

function showImgSelected(imgId, selectId, imgDir, extra, xoopsUrl) {
	if (xoopsUrl == null) {
		xoopsUrl = "./";
	}
	imgDom = xoopsGetElementById(imgId);
	selectDom = xoopsGetElementById(selectId);
if (selectDom.options[selectDom.selectedIndex].value != "") {
	imgDom.src = xoopsUrl + "/"+ imgDir + "/" + selectDom.options[selectDom.selectedIndex].value + extra;
} else {
	imgDom.src = xoopsUrl + "/images/blank.gif";
	}
}

function xoopsCodeUrl(id, enterUrlPhrase, enterWebsitePhrase){
	if (enterUrlPhrase == null) {
		enterUrlPhrase = "Enter the URL of the link you want to add:";
	}
	var text = prompt(enterUrlPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		if (enterWebsitePhrase == null) {
			enterWebsitePhrase = "Enter the web site title:";
		}
		var text2 = prompt(enterWebsitePhrase, "");
		if ( text2 != null ) {
			if ( text2 == "" ) {
				var result = "[url=" + text + "]" + text + "[/url]";
			} else {
				var pos = text2.indexOf(unescape('%00'));
				if(0 < pos){
					text2 = text2.substr(0,pos);
				}
				var result = "[url=" + text + "]" + text2 + "[/url]";
			}
			xoopsInsertText(domobj, result);
		}
	}
	domobj.focus();
}

function xoopsCodeImg(id, enterImgUrlPhrase, enterImgPosPhrase, imgPosRorLPhrase, errorImgPosPhrase){
	if (enterImgUrlPhrase == null) {
		enterImgUrlPhrase = "Enter the URL of the image you want to add:";
	}
	var text = prompt(enterImgUrlPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		if (enterImgPosPhrase == null) {
			enterImgPosPhrase = "Now, enter the position of the image.";
		}
		if (imgPosRorLPhrase == null) {
			imgPosRorLPhrase = "'R' or 'r' for right, 'L' or 'l' for left, 'C' or 'c' for center, or leave it blank.";
		}
		if (errorImgPosPhrase == null) {
			errorImgPosPhrase = "ERROR! Enter the position of the image:";
		}
		var text2 = prompt(enterImgPosPhrase + "\n" + imgPosRorLPhrase, "");
		while ( ( text2 != "" ) && ( text2 != "r" ) && ( text2 != "R" ) && ( text2 != "c" ) && ( text2 != "C" ) && ( text2 != "l" ) && ( text2 != "L" ) && ( text2 != null ) ) {
			text2 = prompt(errorImgPosPhrase + "\n" + imgPosRorLPhrase,"");
		}
		if ( text2 == "l" || text2 == "L" ) {
			text2 = " align=left";
		} else if ( text2 == "r" || text2 == "R" ) {
			text2 = " align=right";
		} else if ( text2 == "c" || text2 == "C" ) {
			text2 = " align=center";
		} else {
			text2 = "";
		}
		var result = "[img" + text2 + "]" + text + "[/img]";
		xoopsInsertText(domobj, result);
	}
	domobj.focus();
}

function xoopsCodeEmail(id, enterEmailPhrase){
	if (enterEmailPhrase == null) {
		enterEmailPhrase = "Enter the email address you want to add:";
	}
	var text = prompt(enterEmailPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var result = "[email]" + text + "[/email]";
		xoopsInsertText(domobj, result);
	}
	domobj.focus();
}

function xoopsCodeQuote(id, enterQuotePhrase){
	if (enterQuotePhrase == null) {
		enterQuotePhrase = "Enter the text that you want to be quoted:";
	}
	var text = prompt(enterQuotePhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "[quote]" + text + "[/quote]";
		xoopsInsertText(domobj, result);
	}
	domobj.focus();
}
/*function xoopsCodeHidden(id,enterHiddenPhrase){
	if (enterHiddenPhrase == null) {
		enterHiddenPhrase = "Enter The Text To Be Hidden:";
	}
	var text = prompt(enterHiddenPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "[hide]" + text + "[/hide]";
		xoopsInsertText(domobj, result);
	}
		
domobj.focus();
}*/
function xoopsmakeleft(id,enterHiddenPhrase){
	if (enterHiddenPhrase == null) {
		enterHiddenPhrase = "Enter The Text To Be Aligned On The Left Side:";
	}
	var text = prompt(enterHiddenPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "[left]" + text + "[/left]";
		xoopsInsertText(domobj, result);
	}
		
domobj.focus();
}
function xoopsmakecenter(id,enterHiddenPhrase){
	if (enterHiddenPhrase == null) {
		enterHiddenPhrase = "Enter The Text To Be Aligned On The Center Side:";
	}
	var text = prompt(enterHiddenPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "[center]" + text + "[/center]";
		xoopsInsertText(domobj, result);
	}
		
domobj.focus();
}
function xoopsmakeright(id,enterHiddenPhrase){
	if (enterHiddenPhrase == null) {
		enterHiddenPhrase = "Enter The Text To Be Aligned On The Right Side:";
	}
	var text = prompt(enterHiddenPhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "[right]" + text + "[/right]";
		xoopsInsertText(domobj, result);
	}
		
domobj.focus();
}
function xoopsCodeCode(id, enterCodePhrase, enterCodeLangPhrase, CodeLangTypePhrase, errorCodeLangPhrase){
	if (enterCodePhrase == null) {
		enterCodePhrase = "Enter the codes that you want to add.";
	}
	var text = prompt(enterCodePhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		if (enterCodeLangPhrase == null) {
			enterCodeLangPhrase = "Now, enter the language of your code.";
		}
		if (CodeLangTypePhrase == null) {
			CodeLangTypePhrase = "'P' or 'p' for PHP, 'C' or 'c' for CSS, 'J' or 'j' for JAVASCRIPT, 'H' or 'h' for HTML, or leave it blank.";
		}
		if (errorCodeLangPhrase == null) {
			errorCodeLangPhrase = "ERROR! Enter the language of your code:";
		}
		var text2 = prompt(enterCodeLangPhrase + "\n" + CodeLangTypePhrase, "");
		while ( ( text2 != "" ) && ( text2 != "p" ) && ( text2 != "P" ) && ( text2 != "c" ) && ( text2 != "C" )  && ( text2 != "j" ) && ( text2 != "J" )  && ( text2 != "h" ) && ( text2 != "H" ) && ( text2 != null ) ) {
			text2 = prompt(errorCodeLangPhrase + "\n" + CodeLangTypePhrase,"");
		}
		if ( text2 == "p" || text2 == "P" ) {
			text2 = "php";
		} else if ( text2 == "c" || text2 == "C" ) {
			text2 = "css";
		} else if ( text2 == "j" || text2 == "J" ) {
			text2 = "js";
		} else if ( text2 == "h" || text2 == "H" ) {
			text2 = "html";
		} else {
			text2 = "";
		}
		var result = "[code" + text2 + "]" + text + "[/code" + text2 + "]";
		xoopsInsertText(domobj, result);
	}
	domobj.focus();
}

function xoopsCodeText(id, hiddentext, enterTextboxPhrase){
	var textareaDom = xoopsGetElementById(id);
	var textDom = xoopsGetElementById(id + "Addtext");
	var fontDom = xoopsGetElementById(id + "Font");
	var colorDom = xoopsGetElementById(id + "Color");
	var sizeDom = xoopsGetElementById(id + "Size");
	var xoopsHiddenTextDomStyle = xoopsGetElementById(hiddentext).style;
	var textDomValue = textDom.value;
	var fontDomValue = fontDom.options[fontDom.options.selectedIndex].value;
	var colorDomValue = colorDom.options[colorDom.options.selectedIndex].value;
	var sizeDomValue = sizeDom.options[sizeDom.options.selectedIndex].value;
	if ( textDomValue == "" ) {
		if (enterTextboxPhrase == null) {
			enterTextboxPhrase = "Please input text into the textbox.";
		}
		alert(enterTextboxPhrase);
		textDom.focus();
	} else {
		if ( fontDomValue != "FONT") {
			textDomValue = "[font=" + fontDomValue + "]" + textDomValue + "[/font]";
			fontDom.options[0].selected = true;
		}
		if ( colorDomValue != "COLOR") {
			textDomValue = "[color=" + colorDomValue + "]" + textDomValue + "[/color]";
			colorDom.options[0].selected = true;
		}
		if ( sizeDomValue != "SIZE") {
			textDomValue = "[size=" + sizeDomValue + "]" + textDomValue + "[/size]";
			sizeDom.options[0].selected = true;
		}
		if (xoopsHiddenTextDomStyle.fontWeight == "bold" || xoopsHiddenTextDomStyle.fontWeight == "700") {
			textDomValue = "[b]" + textDomValue + "[/b]";
			xoopsHiddenTextDomStyle.fontWeight = "normal";
		}
		if (xoopsHiddenTextDomStyle.fontStyle == "italic") {
			textDomValue = "[i]" + textDomValue + "[/i]";
			xoopsHiddenTextDomStyle.fontStyle = "normal";
		}
		if (xoopsHiddenTextDomStyle.textDecoration == "underline") {
			textDomValue = "[u]" + textDomValue + "[/u]";
			xoopsHiddenTextDomStyle.textDecoration = "none";
		}
		if (xoopsHiddenTextDomStyle.textDecoration == "line-through") {
			textDomValue = "[d]" + textDomValue + "[/d]";
			xoopsHiddenTextDomStyle.textDecoration = "none";
		}
		xoopsInsertText(textareaDom, textDomValue);
		textDom.value = "";
		xoopsHiddenTextDomStyle.color = "#000000";
		xoopsHiddenTextDomStyle.fontFamily = "";
		xoopsHiddenTextDomStyle.fontSize = "12px";
		xoopsHiddenTextDomStyle.visibility = "hidden";
		textareaDom.focus();
	}
}

function xoopsValidate(subjectId, textareaId, submitId, plzCompletePhrase, msgTooLongPhrase, allowedCharPhrase, currCharPhrase) {
	var maxchars = 65535;
	var subjectDom = xoopsGetElementById(subjectId);
	var textareaDom = xoopsGetElementById(textareaId);
	var submitDom = xoopsGetElementById(submitId);
	if (textareaDom.value == "" || subjectDom.value == "") {
		if (plzCompletePhrase == null) {
			plzCompletePhrase = "Please complete the subject and message fields.";
		}
		alert(plzCompletePhrase);
		return false;
	}
	if (maxchars != 0) {
		if (textareaDom.value.length > maxchars) {
			if (msgTooLongPhrase == null) {
				msgTooLongPhrase = "Your message is too long.";
			}
			if (allowedCharPhrase == null) {
				allowedCharPhrase = "Allowed max chars length: ";
			}
			if (currCharPhrase == null) {
				currCharPhrase = "Current chars length: ";
			}
			alert(msgTooLongPhrase + "\n\n" + allowedCharPhrase + maxchars + "\n" + currCharPhrase + textareaDom.value.length + "");
			textareaDom.focus();
			return false;
		} else {
			submitDom.disabled = true;
			return true;
		}
	} else {
		submitDom.disabled = true;
		return true;
	}
}

function icms_showDiv(type,id,classname){
	divs = document.getElementsByTagName('div');
	for (i=0; i<divs.length;i++){
		if (/classname/.test(divs[i].className)){
			divs[i].style.display = 'none';
		}
	}
	if (!id)id = '';
	changeDisplay(type+id);
	document.anchors.item(type+id+'_anchor').scrollIntoView();
}
function appendSelectOption(selectMenuId, optionName, optionValue){
	var selectMenu = xoopsGetElementById(selectMenuId);
	var newoption = new Option(optionName, optionValue);
	newoption.selected = true;
	selectMenu.options[selectMenu.options.length] = newoption;
}

function imageResize(img, maxWidth){
	if(img.width > maxWidth && maxWidth > 0) img.width = maxWidth;
}

/* Who is the original author of the scripts? let's find out later on */
function CaricaFoto(img){
	foto1= new Image();
	foto1.src=(img);
	Controlla(img);
}

function Controlla(img){
	if((foto1.width!=0)&&(foto1.height!=0)){
 		viewFoto(img);
	}else{
		funzione="Controlla('\"+img+\"')";
		intervallo=setTimeout(funzione,20);
	}
}

function viewFoto(img){
	largh=foto1.width;
	altez=foto1.height;
	stringa="width="+largh+",height="+altez;
	finestra=window.open('','',stringa);
	finestra.document.write ("<html><body leftmargin=0 topmargin=0>");
	finestra.document.write ("<a href='javascript:this.close()'><img border=0 src=");
	finestra.document.write (img);
	finestra.document.write ("></a></body></html>");
	finestra.document.close();
	return false;
}
function icmsCode_languages(id,enterLanguagePhrase,langcode){
	if (enterLanguagePhrase == null) {
			enterLanguagePhrase = "Enter The Text To Be Language:";
	}
	var text = prompt(enterLanguagePhrase, "");
	var domobj = xoopsGetElementById(id);
	if ( text != null && text != "" ) {
		var pos = text.indexOf(unescape('%00'));
		if(0 < pos){
			text = text.substr(0,pos);
		}
		var result = "["+langcode+"]" + text + "[/"+langcode+"]";
		xoopsInsertText(domobj, result);
	}
	
	domobj.focus();
	}
