<?php
/**
 * info.php
 *
 * Retrieve the quickinfo displayed in the layout
 * @package somename
 * @author Emil Hernvall <aderyn@gmail.com>
 * @version 1.0
 */

/**
 * Retrieve some info displayed in the layout
 *
 * @return string
 */
function getInfo()
{
	$db = Zend_Registry::get('db');
	
	$res = array();
	
	// No need to cache these fields
	if ($_SESSION["online"]) {
		$res['logintime'] = $_SESSION['logintime'];
		$res['minonline'] = ceil((time()-$_SESSION['logintime'])/60);
	} else {
		$res['logintime'] = 0;
		$res['minonline'] = 0;
	}
	
	// Number of members online, and total count of members
	$stmt = $db->prepare('SELECT count(*) as c FROM member_online');
	$stmt->execute();
	$res['onlinecount'] = $stmt->fetchColumn(0);
	$stmt->closeCursor();
	
	/*$stmt = $db->prepare('SELECT count(*) as c FROM member_member');
	$stmt->execute();
	$res['totalcount'] = $stmt->fetchColumn(0);
	$stmt->closeCursor();*/
	
	// Number of guestbookentries and logins last hour
	$sql  = 'SELECT count(login_id) as c FROM member_login ';
	$sql .= 'WHERE login_timestamp > unix_timestamp() - 3600';
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$res['logincount'] = $stmt->fetchColumn(0);
	$stmt->closeCursor();
	
	$sql  = 'SELECT count(guestbook_id) as c FROM member_guestbook ';
	$sql .= 'WHERE guestbook_timestamp > unix_timestamp() - 3600';
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$res['gbcount'] = $stmt->fetchColumn(0);
	$stmt->closeCursor();

	return $res;
}
