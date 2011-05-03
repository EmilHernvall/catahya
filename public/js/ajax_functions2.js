/**
 *  ffcounter.js - A simple "odometer-style" counter for Firefox downloads.
 *
 *  Version 2.0
 *	  - updated to work with a customized feed from Infocraft
 *	  - instantly accurate: the feed pulls a moving 10-minute average rate
 *	  - automatically corrects for transit delay
 *	  - no jumping numbers; the counter speeds up or slows down to re-sync
 *		with the official count
 *
 *  Copyright 2005 Infocraft
 *  http://www.infocraft.com/
 *
 *  The latest version of this script can be found at:
 *  http://www.infocraft.com/projects/firefox_counter/ffcounter.js
 *
 *  You may use this script in its entirety, modify it to suit your needs,
 *  adapt its functions, or just use it for inspiration.  However, some form
 *  of acknowledgement, especially a link back to http://www.infocraft.com/,
 *  is always appreciated.  Also, if you're mirroring the feed from Infocraft,
 *  please consider making a donation to help cover bandwidth costs.
 */

/******************************************************************************
	Adjustable Parameters
 ******************************************************************************/

// Make sure to change these based on your settings!

var counterURL = "counter.php";	// The URL of your local counter mirror.
var counterID = "ffcounter";	// The ID of the counter element.

/******************************************************************************
	Global Variables
 ******************************************************************************/

var currentCount;
var displayCount;
var rate;
var request;

/******************************************************************************
	Intialization
 ******************************************************************************/

addEvent(window, "load", initCounter);

function initCounter()
{
	// Begin getCount interval
	getCount();
	setInterval("getCount()", 60000);
	
	// Begin update timeout
	update();
}

// Gets and returns the most recent delay, rate and count
function getCount()
{
	// Generate the XMLHttpRequest
	loadXML(counterURL);
}

// Processes the information from a returned count XML
function processCount()
{
	if (request.readyState == 4) {
		if (response = request.responseXML) {

			count = parseInt(getNodeValue(response, "count"));
			rate = parseFloat(getNodeValue(response, "rate"));
			delay = parseInt(getNodeValue(response, "delay"));

			// Determine the current count based on time difference and rate
			currentCount = Math.round(count + delay * rate);
		}
	}
}

function update()
{
	if (currentCount) {

		interval = (1000 / rate);
	
		if (!displayCount)
			displayCount = currentCount + 1;
		else if (displayCount < currentCount)
			setTimeout("catchUp()", interval / 2);
		else if (displayCount > currentCount)
			displayCount += 0;
		else
			displayCount += 1;
	
		currentCount++;

		// Update the display
		setText(document.getElementById(counterID), formatCount(displayCount));
	
		// Reset the update timeout
		setTimeout("update()", interval);

	} else {
		setTimeout("update()", 1000);
	}
}

function catchUp()
{
	displayCount++;
	setText(document.getElementById(counterID), formatCount(displayCount));
}

/******************************************************************************
	Utility Functions
 ******************************************************************************/

function loadXML(url) 
{
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		request = new XMLHttpRequest();
		request.onreadystatechange = processCount;
		request.open("GET", url, true);
		request.send(null);

	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		request = new ActiveXObject("Microsoft.XMLHTTP");
		if (request) {
			request.onreadystatechange = processCount;
			request.open("GET", url, true);
			request.send();
		}
	}
}

// Adds an event handler to an element
function addEvent(element, type, handler)
{
	if (element.addEventListener) {
		element.addEventListener( type, handler, false );
	} else if (element.attachEvent) {
		element["e" + type + handler] = handler;
		element[type + handler] = function() { element["e" + type + handler](window.event); }
		element.attachEvent("on" + type, element[type + handler]);
	}
}

// Removes an event handler from an element
function removeEvent(element, type, handler)
{
	if (element.removeEventListener) {
		element.removeEventListener(type, handler, false);
	} else if (element.detachEvent) {
		element.detachEvent("on" + type, element[type+handler]);
		element[type+handler] = null;
	}
}


// Sets the text of an element (element should be empty or first child text)
function setText(element, text)
{
	var textNode = document.createTextNode(text);

	if (element.firstChild)
		element.replaceChild(textNode, element.firstChild);
	else
		element.appendChild(textNode);
}

function getNodeValue(topNode, tagName)
{
	if (elements = topNode.getElementsByTagName(tagName))
			value = elements[0].firstChild.data;
	else
			value = '';

	return value;
}

// Formats a count for display by making it into a string and inserting commas.
function formatCount(count)
{
	count = count.toString();

	for (var i = count.length - 3; i > 0 ; i -= 3) {
		count = count.slice(0, i) + ',' + count.slice(i, count.length);
	}

	return count;
}
