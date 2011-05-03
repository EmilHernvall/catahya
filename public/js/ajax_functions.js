var gb;
var request;

function getActivities()
{
	document.getElementById('guestbook_num').innerHTML = '...';
	document.getElementById('messages_num').innerHTML = '...';
	document.getElementById('relations_num').innerHTML = '...';
	setTimeout("loadXml('/services/status', 'activities');", 500);
}
function getRelations()
{
	document.getElementById('element_relations_list').innerHTML = 'Uppdaterar...';
	setTimeout("loadXml('/services/relations', 'relations')", 500);
}

function getNodeValue(topNode, tagName)
{
	if (elements = topNode.getElementsByTagName(tagName))
			value = elements[0].firstChild.data;
	else
			value = '';

	return value;
}
function loadXml(url, type) 
{
	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		request = new XMLHttpRequest();
		switch (type)
		{
			case 'activities':
				request.onreadystatechange = processActivities;
				break;
			case 'relations':
				request.onreadystatechange = processRelations;
				break;
		}
		request.open("GET", url, true);
		request.send(null);

	// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		request = new ActiveXObject("Microsoft.XMLHTTP");
		if (request) {
			switch (type)
			{
				case 'activities':
					request.onreadystatechange = processActivities;
					break;
				case 'relations':
					request.onreadystatechange = processRelations;
					break;
			}
			request.open("GET", url, true);
			request.send();
		}
	};
}
function processActivities()
{
	if (request.readyState == 4)
	{
		if (request.status == 200)
		{
			response = request.responseXML;
			document.getElementById('guestbook_num').innerHTML = (getNodeValue(response, "gbcount"));
			document.getElementById('messages_num').innerHTML = (getNodeValue(response, "messcount"));
			document.getElementById('relations_num').innerHTML = (getNodeValue(response, "relcount"));
			
			if (getNodeValue(response, "gbcount") > 0) {
				setTitle(title + ' ('+getNodeValue(response, "gbcount")+'gb)');
			}
			else {
				setTitle('Catahya - ' + title);
			}
		}
	}
}
function processRelations()
{
	if (request.readyState == 4)
	{
		if (request.status == 200)
		{
			response = request.responseXML;

			var buffer;
			
			if (response.getElementsByTagName('id').length == 0)
			{
				buffer = '<ul class="class_stdlist"><li>Inga relationer online.</li></ul>';
			}
			else
			{
				buffer = '<ul>';
				for (i = 0; i < response.getElementsByTagName('id').length; i++)
				{
					id = response.getElementsByTagName('id')[i];
					alias = response.getElementsByTagName('alias')[i];
					gender = response.getElementsByTagName('gender')[i];
					age = response.getElementsByTagName('age')[i];
					buffer+= '<li><a href="/profile/'+id.firstChild.data+'">'+alias.firstChild.data+'</a></li>';
				}
				buffer+= '</ul>';
			}


			document.getElementById('element_relations_list').innerHTML = buffer;
			
			//document.getElementById('layout_relations_list').innerHTML = response.getElementsByTagName('id')[0].firstChild.data;
		}
	}
}
