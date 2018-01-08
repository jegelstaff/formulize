/*
 * DO NOT REMOVE THIS NOTICE
 *
 * PROJECT:   mygosuMenu
 * VERSION:   1.4.2
 * COPYRIGHT: (c) 2003,2004 Cezary Tomczak
 * LINK:	  http://gosu.pl/dhtml/mygosumenu.html
 * LICENSE:   BSD (revised)
 */
function AdMenu(id) { this.type = "horizontal"; this.position = { "level1": { "top": 0, "left": 0}, "levelX": { "top": 0, "left": 0}
}
this.zIndex = { "visible": 1, "hidden": -1
}
this.arrow1 = null; this.arrow2 = null; this.browser = { "ie": Boolean(document.body.currentStyle), "ie5": (navigator.appVersion.indexOf("MSIE 5.5") != -1 || navigator.appVersion.indexOf("MSIE 5.0") != -1)
}; if (!this.browser.ie) { this.browser.ie5 = false;}
this.init = function() { if (!document.getElementById(this.id)) alert("Element '"+ this.id +"' does not exist in this document. AdMenu cannot be initialized."); if (this.type != "horizontal" && this.type != "vertical") { return alert("AdMenu.init() failed. Unknown menu type: '"+this.type+"'");}
document.onmousedown = click; if (this.browser.ie && this.browser.ie5) { this.fixWrap();}
this.fixSections(); this.parse(document.getElementById(this.id).childNodes, this.tree, this.id);}
this.fixSections = function() { var arr = document.getElementById(this.id).getElementsByTagName("div"); var sections = new Array(); var widths = new Array(); for (var i = 0; i < arr.length; i++) { if (arr[i].className == "section") { sections.push(arr[i]);}
}
for (var i = 0; i < sections.length; i++) { widths.push(this.getMaxWidth(sections[i].childNodes));}
for (var i = 0; i < sections.length; i++) { sections[i].style.width = (widths[i]) + "px";}
if (self.browser.ie) { for (var i = 0; i < sections.length; i++) { this.setMaxWidth(sections[i].childNodes, widths[i]);}
}
}
this.fixWrap = function() { var elements = document.getElementById(this.id).getElementsByTagName("a"); for (var i = 0; i < elements.length; i++) { if (/item/.test(elements[i].className)) { elements[i].innerHTML = '<div nowrap="nowrap">'+elements[i].innerHTML+'</div>';}
}
}
this.getMaxWidth = function(nodes) { var maxWidth = 0; for (var i = 0; i < nodes.length; i++) { if (nodes[i].nodeType != 1 || nodes[i].className == "section") { continue;}
if (nodes[i].offsetWidth > maxWidth) maxWidth = nodes[i].offsetWidth;}
return maxWidth;}
this.setMaxWidth = function(nodes, maxWidth) { for (var i = 0; i < nodes.length; i++) { if (nodes[i].nodeType == 1 && /item/.test(nodes[i].className) && nodes[i].currentStyle) { if (this.browser.ie5) { nodes[i].style.width = (maxWidth) + "px";} else { nodes[i].style.width = (maxWidth - parseInt(nodes[i].currentStyle.paddingLeft) - parseInt(nodes[i].currentStyle.paddingRight)) + "px";}
}
}
}
this.parse = function(nodes, tree, id) { for (var i = 0; i < nodes.length; i++) { if (nodes[i].nodeType != 1) { continue }; switch (nodes[i].className) { case "button":
nodes[i].id = id + "-" + tree.length; tree.push(new Array()); nodes[i].onmouseover = buttonOver; nodes[i].onclick = buttonClick; break; case "item":
nodes[i].id = id + "-" + tree.length; tree.push(new Array()); nodes[i].onmouseover = itemOver; nodes[i].onmouseout = itemOut; nodes[i].onclick = itemClick; break; case "section":
nodes[i].id = id + "-" + (tree.length - 1) + "-section"; var box1 = document.getElementById(id + "-" + (tree.length - 1)); var box2 = document.getElementById(nodes[i].id); var el = new Element(box1.id); if (el.level == 1) { if (this.type == "horizontal") { box2.style.top = (box1.offsetTop + box1.offsetHeight + this.position.level1.top) + "px"; if (this.browser.ie5) { box2.style.left = (this.position.level1.left) + "px";} else {
/* Start open menu from right_to_left - added by stranger @ www.impresscms.ir , Script & Original hack are made by irmtfan (www.jadoogaran.org) */
box2.style.Right = ( box1.offsetLeft + this.position.level1.left) + "px";}
/* End open menu from right_to_left - added by stranger @ www.impresscms.ir , Script & Original hack are made by irmtfan (www.jadoogaran.org) */
} else if (this.type == "vertical") { box2.style.top = (box1.offsetTop + this.position.level1.top) + "px"; if (this.browser.ie5) { box2.style.left = (box1.offsetWidth + this.position.level1.left) + "px";} else { box2.style.left = (box1.offsetLeft + box1.offsetWidth + this.position.level1.Left) + "px";}
}
} else { box2.style.top = (box1.offsetTop + this.position.levelX.top) + "px";
/* Start open popup from right_to_left on mouse click - added by stranger @ www.impresscms.ir , Script & Original hack are made by irmtfan (www.jadoogaran.org) */
box2.style.right = (box1.offsetLeft + box1.offsetWidth + this.position.levelX.left) + "px";}
/* End open popup from right_to_left on mouse click - added by stranger @ www.impresscms.ir , Script & Original hack are made by irmtfan (www.jadoogaran.org) */
break; case "arrow":
nodes[i].id = id + "-" + (tree.length - 1) + "-arrow"; break;}
if (nodes[i].childNodes) { if (nodes[i].className == "section") { this.parse(nodes[i].childNodes, tree[tree.length - 1], id + "-" + (tree.length - 1));} else { this.parse(nodes[i].childNodes, tree, id);}
}
}
}
this.hideAll = function() { for (var i = this.visible.length - 1; i >= 0; i--) { this.hide(this.visible[i]);}
}
this.hideHigherOrEqualLevels = function(n) { for (var i = this.visible.length - 1; i >= 0; i--) { var el = new Element(this.visible[i]); if (el.level >= n) { this.hide(el.id);} else { return;}
}
}
this.hide = function(id) { var el = new Element(id); document.getElementById(id).className = (el.level == 1 ? "button" : "item"); if (el.level > 1 && this.arrow2) { document.getElementById(id + "-arrow").src = this.arrow1;}
document.getElementById(id + "-section").style.visibility = "hidden"; document.getElementById(id + "-section").style.zIndex = this.zIndex.hidden; if (this.visible.contains(id)) { if (this.visible.getLast() == id) { this.visible.pop();} else { throw "AdMenu.hide("+id+") failed, trying to hide element that is not deepest visible element";}
} else { throw "AdMenu.hide("+id+") failed, cannot hide element that is not visible";}
}
this.show = function(id) { var el = new Element(id); document.getElementById(id).className = (el.level == 1 ? "button-active" : "item-active"); if (el.level > 1 && this.arrow2) { document.getElementById(id + "-arrow").src = this.arrow2;}
document.getElementById(id + "-section").style.visibility = "visible"; document.getElementById(id + "-section").style.zIndex = this.zIndex.visible; this.visible.push(id);}
function click(e) { var el; if (e) { el = e.target.tagName ? e.target : e.target.parentNode;} else { el = window.event.srcElement; if (el.parentNode && /item/.test(el.parentNode.className)) { el = el.parentNode;}
}
if (!self.visible.length) { return }; if (!el.onclick) { self.hideAll();}
}
function buttonOver() { if (!self.visible.length) { return;}
if (self.visible.contains(this.id)) { return }; self.hideAll(); var el = new Element(this.id); if (el.hasChilds()) { self.show(this.id);}
}
function buttonClick() { this.blur(); if (self.visible.length) { self.hideAll();} else { var el = new Element(this.id); if (el.hasChilds()) { self.show(this.id);}
}
}
function itemOver() { var el = new Element(this.id); self.hideHigherOrEqualLevels(el.level); if (el.hasChilds()) { self.show(this.id);}
}
function itemOut() { var el = new Element(this.id); if (!el.hasChilds()) { document.getElementById(this.id).className = "item";}
}
function itemClick() { this.blur(); var el = new Element(this.id); self.hideHigherOrEqualLevels(el.level); if (el.hasChilds()) { self.show(this.id);}
}
function Element(id) { this.getLevel = function() { var s = this.id.substr(this.menu.id.length); return s.substrCount("-");}
this.hasChilds = function() { return Boolean(document.getElementById(this.id + "-section"));}
if (!id) { throw "AdMenu.Element(id) failed, id cannot be empty";}
this.menu = self; this.id = id; this.level = this.getLevel();}
this.id = id; var self = this; this.tree = new Array(); this.visible = new Array();}
if (typeof Array.prototype.contains == "undefined") { Array.prototype.contains = function(s) { for (var i = 0; i < this.length; i++) { if (this[i] === s) { return true;}
}
return false;}
}
if (typeof Array.prototype.getLast == "undefined") { Array.prototype.getLast = function() { return this[this.length-1];}
}
if (typeof String.prototype.substrCount == "undefined") { String.prototype.substrCount = function(s) { return this.split(s).length - 1;}
}
