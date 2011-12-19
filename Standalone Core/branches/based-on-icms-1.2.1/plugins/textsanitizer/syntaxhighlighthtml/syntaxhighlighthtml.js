				function icmsCodeHTML(id,enterHTMLPhrase){
    				if (enterHTMLPhrase == null) {
    				        enterHTMLPhrase = "Enter The Text To Be HTML Code:";
    				}
					var text = prompt(enterHTMLPhrase, "");
					var domobj = xoopsGetElementById(id);
					if ( text != null && text != "" ) {
						var pos = text.indexOf(unescape('%00'));
						if(0 < pos){
							text = text.substr(0,pos);
						}
					    var result = "[code_html]" + text + "[/code_html]";
					    xoopsInsertText(domobj, result);
					}
					
					domobj.focus();
					}

