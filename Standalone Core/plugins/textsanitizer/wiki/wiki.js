				function icmsCodeWIKI(id,enterWIKIPhrase){
    				if (enterWIKIPhrase == null) {
    				        enterWIKIPhrase = "Enter The Text To Be WIKI Code:";
    				}
					var text = prompt(enterWIKIPhrase, "");
					var domobj = xoopsGetElementById(id);
					if ( text != null && text != "" ) {
						var pos = text.indexOf(unescape("%00"));
						if(0 < pos){
							text = text.substr(0,pos);
						}
					    var result = "[[" + text + "]]";
					    xoopsInsertText(domobj, result);
					}
					
					domobj.focus();
					}
