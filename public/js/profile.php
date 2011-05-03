<?php
//session_start();
$_SESSION['theme'] = 'standard';
?>
var press = true;
function switchinfo()
{
	var presentation = document.getElementById('block_presentation');
	var ptitle = document.getElementById('sel_presentation');
	var info = document.getElementById('block_info');
	var ititle = document.getElementById('sel_info');

	if (press)
	{
		ititle.style.backgroundImage = 'url(/themes/<?php echo $_SESSION['theme']; ?>/images/headfader.png)';
		ptitle.style.backgroundImage = 'url(/themes/<?php echo $_SESSION['theme']; ?>/images/lightheadfader.png)';

		info.style.display = 'block';
		presentation.style.display = 'none';
		press = false;
	}
	else {
		ititle.style.backgroundImage = 'url(/themes/<?php echo $_SESSION['theme']; ?>/images/lightheadfader.png)';
		ptitle.style.backgroundImage = 'url(/themes/<?php echo $_SESSION['theme']; ?>/images/headfader.png)';

		info.style.display = 'none';
		presentation.style.display = 'block';
		press = true;
	}
}

