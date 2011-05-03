var guestbookInlineReply = function() {
	var sendMessage = function() {
		var idSplit = this.id.split("-");
		var postId = idSplit[1];
		var memberId = idSplit[2];
		//alert(this.id);
		
		var message = document.getElementById("message-" + postId).value;
		if (message.length < 5) {
			alert("Meddelandet är för kort!");
			return false;
		}
		
		var private = document.getElementById("private-" + postId).checked ? "1" : "0";
		
		var url = "/profile/" + memberId + "/guestbook/postCommit?noredir=1&gid=" + postId;
		$.post(url, { msg: message, secret: private });
		
		var containerDiv = document.getElementById("inlineComment" + postId);
		containerDiv.innerHTML = "Meddelandet skickades!";
		
		setTimeout(function() {
			containerDiv.removeChild(containerDiv.firstChild);
			containerDiv.className = "";
		}, 3000);
		
		return false;
	}

	var createForm = function() {

		var idSplit = this.id.split("-");
		var id = idSplit[1];
		var div = document.getElementById("inlineComment" + id);
		div.className = "inlineMessage";
		if (div.firstChild != null) {
			div.className = "";
			div.removeChild(div.firstChild);
			return;
		}
		
		var form = document.createElement("form");
		form.className = "stdForm";
		form.method = "post";
		form.id = "replyForm-" + idSplit[1] + "-" + idSplit[2];
		form.onsubmit = sendMessage;
		
			var field = document.createElement("div");
			field.className = "field";
		
				var label = document.createElement("label");
				//label.for = "message_" + id;
				
					var labelMessage = document.createTextNode("Meddelande");
					label.appendChild(labelMessage);
					
				field.appendChild(label);
		
				var textDiv = document.createElement("div");
				textDiv.className = "textArea";
		
					var text = document.createElement("textarea");
					text.id = "message-" + id;
					textDiv.appendChild(text);
					
				field.appendChild(textDiv);
			
			form.appendChild(field);
			
			var fieldPrivate = document.createElement("div");
			fieldPrivate.className = "field";
		
				var labelPrivate = document.createElement("label");
				//label.for = "message_" + id;
				
					labelPrivate.appendChild(document.createTextNode("Privat"));
					
				fieldPrivate.appendChild(labelPrivate);
		
				var checkboxDiv = document.createElement("div");
				checkboxDiv.className = "textArea";
		
					var checkbox = document.createElement("input");
					checkbox.type = "checkbox";
					checkbox.id = "private-" + id;
					checkbox.value = "1";
					
					checkboxDiv.appendChild(checkbox);
					
					checkboxDiv.appendChild(document.createTextNode("Ja"));
					
				fieldPrivate.appendChild(checkboxDiv);
			
			form.appendChild(fieldPrivate);
			
			var buttonField = document.createElement("div");
			buttonField.className = "button";
			
				var button = document.createElement("button");
				button.type = "submit";
				button.appendChild(document.createTextNode("Skicka"));
				
				buttonField.appendChild(button);
			
			form.appendChild(buttonField);
		
		div.appendChild(form);
		
		text.focus();
	}

	var links = document.getElementsByTagName("a");
	
	for (var i = 0; i < links.length; i++) {
		if (links[i].className == "replyLink") {
			var link = links[i];
			var id = link.id.split("-")[1];
			link.href = "javascript:doNothing()";
			link.onclick = createForm;
		}
	}
}

var doNothing = function() {}

$(document).ready(function() {
	guestbookInlineReply();
});
