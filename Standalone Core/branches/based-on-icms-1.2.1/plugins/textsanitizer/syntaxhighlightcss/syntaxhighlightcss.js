				function icmsCodeCSS(id,enterCSSPhrase){
    				if (enterCSSPhrase == null) {
    				        enterCSSPhrase = "Enter The Text To Be CSS Code:";
    				}
					var text = prompt(enterCSSPhrase, "");
					var domobj = xoopsGetElementById(id);
					if ( text != null && text != "" ) {
						var pos = text.indexOf(unescape('%00'));
						if(0 < pos){
							text = text.substr(0,pos);
						}
					    var result = "[code_css]" + text + "[/code_css]";
					    xoopsInsertText(domobj, result);
					}
					
					domobj.focus();
					}
