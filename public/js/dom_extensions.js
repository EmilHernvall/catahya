/**
 * dom_extensions.js @ catahya.net
 * 
 * Extend the Document Object Model by some presentional markup 
 * not needed for structure.
 *
 * @author Anders Ytterström <anders.ytterstrom@gmail.com>
 * @version 1.0
 */
 
addLoadEvent(graphicsChooser);
addLoadEvent(charCounter);
//addLoadEvent(appendBoxFooter);

// inserts a blank footer underneath a white space (not working)
function appendBoxFooter() {
	var boxes = getElementsByClassName("box");
	for(var i=0; i<boxes.length; i++) {
		var children = boxes[i].getElementsByTagName("div");
		if(children.length != null) {
			if(children[(children.length - 1)].getAttribute("class") == "body") {
				var footer = document.createElement("div");
				footer.appendChild(document.createTextNode(" "));
				footer.setAttribute("subh");
				boxes[i].appendChild(footer);
			}
		}
	}
}

// Creates a division (div#graphics) with content and append 
// it to the body element. The content is hidden by css rules. 
function graphicsChooser() {
 	if (!document.getElementsByTagName) return false;
 	if (!document.getElementById) return false;
	var wrap = document.getElementsByTagName("body")[0];
	var graphics = document.createElement("div");
	graphics.setAttribute("id", "graphics");
	var opt = document.createElement("ul");
	opt.setAttribute("class", "box vnav");
	// header one "Grafikval"
	var header = document.createElement("li");
	header.setAttribute("class", "h");
	header.appendChild( document.createTextNode("Grafikval") );
	opt.appendChild(header);
	// option one "Original"
	var org = document.createElement("li");
	org.setAttribute("class", "subh");
	var orglink = document.createElement("a");
	orglink.setAttribute("href", "#");
	orglink.setAttribute("onClick", "renderOriginalGraphics(); return false");
	orglink.appendChild( document.createTextNode("Catahya 1.0") );
	org.appendChild(orglink);
	opt.appendChild(org);
	// option two "Raven 2.0"
	var newg = document.createElement("li");
	newg.setAttribute("class", "subh");
	var newglink = document.createElement("a");
	newglink.setAttribute("href", "#");
	newglink.setAttribute("onClick", "renderNewGraphics(\"raven\"); return false");
	newglink.appendChild( document.createTextNode("Raven 2.0") );
	newg.appendChild(newglink);
	opt.appendChild(newg);
	// append theme chooser
	appendThemes(opt);
	// final appends
	graphics.appendChild(opt);
	wrap.appendChild(graphics);
}

// completing function to graphicsChooser(). Exends the graphics chooser by a menu of available themes.
function appendThemes(opt) {
	// header two "Teman"
	var header = document.createElement("li");
	header.setAttribute("class", "h");
	header.appendChild( document.createTextNode("Teman") );
	opt.appendChild(header);
	// option one "Original"
	var raven = document.createElement("li");
	raven.setAttribute("class", "subh");
	var ravenlink = document.createElement("a");
	ravenlink.setAttribute("href", "#");
	ravenlink.setAttribute("onClick", "switchTheme(\"raven\"); return false");
	ravenlink.appendChild( document.createTextNode("Raven") );
	raven.appendChild(ravenlink);
	opt.appendChild(raven);
	// option two "Raven 2.0"
	var skymning = document.createElement("li");
	skymning.setAttribute("class", "subh");
	var skymninglink = document.createElement("a");
	skymninglink.setAttribute("href", "#");
	skymninglink.setAttribute("onClick", "switchTheme(\"skymning\"); return false");
	skymninglink.appendChild( document.createTextNode("Skymning") );
	skymning.appendChild(skymninglink);
	opt.appendChild(skymning);
}

// switch to 2.0 theme graphics
// @TODO write a cookie to make the changes last?
function renderNewGraphics(theme) {
 	if (!document.getElementsByTagName) return false;
 	if (!document.getElementById) return false;
 	var bodyelem = document.getElementsByTagName("body");
 	bodyelem[0].setAttribute("class", "newGraphics");
 	var branding = document.getElementById("branding").getElementsByTagName("img")[0];
 	branding.setAttribute("src", "/themes/" + theme + "/image/branding.jpg");
}

// switch to original graphics
// @TODO write a cookie to make the changes last?
function renderOriginalGraphics() {
 	if (!document.getElementsByTagName) return false;
 	if (!document.getElementById) return false;
 	var bodyelem = document.getElementsByTagName("body");
 	bodyelem[0].setAttribute("class", "");
 	var branding = document.getElementById("branding").getElementsByTagName("img")[0];
 	branding.setAttribute("src", "/images/branding.jpg");
}

// insert a dynamic charCounter for the 'new guestbook post' textarea
function charCounter() {
	if( !document.getElementById("msg")) return false;
	var msg = document.getElementById("msg");
	msg.setAttribute("onkeyup","updatecounter()");
	msg.setAttribute("onkeydown","updatecounter()");
	var wrapper = document.createElement("div");
	wrapper.setAttribute("id","charcount");
	var helper = document.createTextNode("Tecken kvar: ");
	var len = document.createElement("input");
	len.setAttribute("size","4");
	len.setAttribute("disabled","disabled");
	len.setAttribute("value",2048);
	len.setAttribute("id","len");
	wrapper.appendChild(helper);
	wrapper.appendChild(len);
	insertAfter(wrapper,msg);
}

// help function to load events, by Jeremy Keith:
// 'DOM Scripting', ISBN 1590595335
function addLoadEvent(func) {
 	var oldonload = window.onload;
 	if (typeof window.onload != 'function') {
 		window.onload = func;
 	} else {
 		window.onload = function() {
 			oldonload();
 			func();
 		}
 	}
}

// insertAfter function, by Jeremy Keith:
// 'DOM Scripting', ISBN 1590595335
function insertAfter(newElement,targetElement) {
	var parent = targetElement.parentNode;
	if (parent.lastChild == targetElement) {
		parent.appendChild(newElement);
	} else {
		parent.insertBefore(newElement,targetElement.nextSibling);
	}
}

// getElementsByClassName, by Robert Nyman:
// http://www.robertnyman.com/2005/11/07/the-ultimate-getelementsbyclassname/
function getElementsByClassName(className, tag, elm){
	var testClass = new RegExp("(^|\s)" + className + "(\s|$)");
	var tag = tag || "*";
	var elm = elm || document;
	var elements = (tag == "*" && elm.all)? elm.all : elm.getElementsByTagName(tag);
	var returnElements = [];
	var current;
	var length = elements.length;
	for(var i=0; i<length; i++){
		current = elements[i];
		if(testClass.test(current.className)){
			returnElements.push(current);
		}
	}
	return returnElements;
}