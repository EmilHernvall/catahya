function confirmredir(msg, url)
{
	if (confirm(msg)) {
		self.location.href = url;
	}
}

function popupwindow(href, width, height)
{
	var win = window.open(href,"_",'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,fullscreen=no,width='+width+',height='+height+',left=10,top=10');
}
