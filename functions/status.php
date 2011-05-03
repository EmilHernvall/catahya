<?php
/**
 * status.php
 *
 * Retrieve activity status
 * @package somename
 * @author Emil Hernvall <aderyn@gmail.com>
 * @version 1.0
 */

/**
 * Get member status, cached
 *
 * @param int $id
 * @return string
 */
function getStatus($id = NULL)
{
	if (!$id) {
		$id = $_SESSION['id'];
	}
	
	$db = Zend_Registry::get('db');
		
	$arr = array();

	$sql  = 'SELECT count(guestbook_id) as gbcount';
	$sql .= ' FROM member_guestbook';
	$sql .= ' WHERE guestbook_read = "0" AND guestbook_to = ?';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_SESSION['id']));
	$arr['gbcount'] = $stmt->fetchColumn(0);
	
	$sql = 'SELECT count(member_id) c ';
	$sql .= 'FROM message_thread_member ';
	$sql .= 'WHERE member_id = ? AND thread_read = "0" ';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_SESSION['id']));
	$arr['messcount'] = $stmt->fetchColumn(0);
	
	$sql  = 'SELECT count(relation_id) as relcount ';
	$sql .= 'FROM member_relation ';
	$sql .= 'WHERE relation_approved = "0" AND relation_memberid2 = ?';
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_SESSION['id']));
	$arr['relcount'] = $stmt->fetchColumn(0);
	
	return $arr;
}

/**
 * Clear the status cache
 *
 * @param int $id
 */
function clearStatus($id = FALSE)
{
	if (!$id) {
		$id = $_SESSION['id'];
	}
	
	$path = CACHE_PATH . '/status/' . $id . '.cache';
	
	if (file_exists($path)) {
		unlink($path);
	}
}
	
