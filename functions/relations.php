<?php
/**
 * relations.php
 *
 * Retrieve relations
 * @package somename
 * @author Emil Hernvall <aderyn@gmail.com>
 * @version 1.0
 */

/**
 * Get the logged on relations of the current user, cached.
 * 
 * @return string
 */
function getRelations()
{
	$db = Zend_Registry::get('db');
		
	$sql  = 'SELECT member_id, member_alias, member_flatalias, member_gender, member_age ';
	$sql .= 'FROM member,member_relation ';
	$sql .= 'WHERE relation_approved = "1" '
		  . 'AND relation_memberid1 = ? '
		  . 'AND relation_memberid2 = member_id '
		  . 'AND member_online = "1" ';
	$sql .= 'ORDER BY member_alias';
	
	$stmt = $db->prepare($sql);
	$stmt->execute(array($_SESSION['id']));
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	return $res;
	return array();
}

/**
 * Clear the cache
 */
function clearRelations()
{
	$_SESSION['relationstimestamp'] = 0;
	
	$path = CACHE_PATH . '/relations/' . $_SESSION['id'] . '.xml';
	if (file_exists($path)) {
		unlink($path);
	}
}
