var updateFields = function() {
	var handleResponse = function(response) {
		var obj = eval("[" + response + "]");
		
		var info = obj[0]["info"];
		document.getElementById("onlinecount").innerHTML = info["onlinecount"] + " " + (info["onlinecount"] == 1 ? "medlem" : "medlemmar");
		document.getElementById("logincount").innerHTML = info["logincount"];
		document.getElementById("gbcount").innerHTML = info["gbcount"];
		
		var online = obj[0]["id"];
		if (online) {
			document.getElementById("logintime").innerHTML = info["logintime"];
			document.getElementById("minonline").innerHTML = info["minonline"];
		
			var id = obj[0]["id"];
			var alias = obj[0]["alias"];
			
			var status = obj[0]["status"];
			
			if (status["gbcount"] > 0) {
				document.title = "Catahya (" + status["gbcount"] + ")";
			} else {
				document.title = "Catahya";
			}
			
			document.getElementById("messages_num").innerHTML = status["messcount"];
			document.getElementById("guestbook_num").innerHTML = status["gbcount"];
			document.getElementById("relations_num").innerHTML = status["relcount"];
			
			var relations = obj[0]["relations"];
			var relationsTable = document.getElementById("relations");
			for (var child = relationsTable.firstChild; child != null; ) {
				next = child.nextSibling;
				relationsTable.removeChild(child);
				child = next;
			}
			
			if (relations.length > 0) {
				for (var i = 0; i < relations.length; i++) {
					//alert(relations[i]["member_alias"]);
					var newRow = document.createElement("TR");
					
					var firstCell = document.createElement("TH");
					var newLink = document.createElement("A");
					newLink.href = "/profile/" + relations[i]["member_flatalias"];
					newLink.appendChild(document.createTextNode(relations[i]["member_alias"]));
					firstCell.appendChild(newLink);
					newRow.appendChild(firstCell);
					
					var secondCell = document.createElement("TD");
					var newLink = document.createElement("A");
					newLink.href = "/profile/" + relations[i]["member_flatalias"] + "/guestbook";
					newLink.appendChild(document.createTextNode("GB"));
					secondCell.appendChild(newLink);
					newRow.appendChild(secondCell);
					
					var thirdCell = document.createElement("TD");
					var newLink = document.createElement("A");
					newLink.href = "/message/thread/write?to=" + relations[i]["member_alias"];
					newLink.appendChild(document.createTextNode("M"));
					thirdCell.appendChild(newLink);
					newRow.appendChild(thirdCell);
					
					relationsTable.appendChild(newRow);
				}
			} else {
				var newTR = document.createElement("TR");
				var newTH = document.createElement("TH");
				newTH.className = "norel";
				var content = document.createTextNode("Inga relationer online.");
				newTH.appendChild(content);
				newTR.appendChild(newTH);
				relationsTable.appendChild(newTR);
			}
			
			document.getElementById("friendsLink").innerHTML = oldFiendsLinkValue;
			document.getElementById("friendsLink").href = "javascript:updateFields()";
		}
	}

	var friendsLink = document.getElementById("friendsLink");
	var oldFiendsLinkValue = "";
	if (friendsLink) {
		oldFiendsLinkValue = document.getElementById("friendsLink").innerHTML;
		document.getElementById("friendsLink").innerHTML = "Uppdaterar...";
		document.getElementById("friendsLink").href = "#";
	}
	var result = $.get("/default/index/status", handleResponse);
	//alert(result.response);
	
	setTimeout(updateFields, 300000);
}
