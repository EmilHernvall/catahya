function disable(object)
{
	swapstate(object);
	setTimeout("swapstate("+object+")", 5000);
}
function swapstate(buttonid)
{
	var button = document.getElementById(buttonid);
	button.disabled = (!button.disabled);
}
function failed()
{
	alert("Ett inlägg för vara minst 5 tecken och max 2048!");
	return false;
}
function updatecounter()
{
	var len = document.getElementById('len'); 
	var msg = document.getElementById('msg'); 
	len.value = 2048 - msg.value.length;
}
function submitform()
{
	if (document.getElementById('msg').value.length <= 5 || document.getElementById('msg').value.length > 2048) {
		return failed(); 
	}
	else {
		disable('submit_btn');
	}
}
