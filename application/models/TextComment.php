<?php

class TextComment 
{
	protected $_id = 0;
	protected $_row = array();
	
	protected $_metaFields = array();
	
	static public function insert($textId, $memberId, $timestamp, $title, $text)
	{
		$db = Zend_Registry::get('db');
		
		$sqlInsert  = 'INSERT INTO text_comment (text_id, member_id, comment_timestamp, '
		            . 'comment_title, comment_text) ';
		$sqlInsert .= 'VALUES (?,?,?,?,?)';
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($textId, $memberId, $timestamp, $title, $text));
		
		$commentId = $db->lastInsertId();
		
		return self::selectById($commentId);
	}
	
	static public function selectById($commentId)
	{
		$db = Zend_Registry::get('db');
		
		$sqlSelect = 'SELECT * FROM text_comment WHERE comment_id = ?';
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute(array($commentId));
		
		$row = $stmtSelect->fetch(PDO::FETCH_ASSOC);
		
		$stmtSelect->closeCursor();
		
		if ($row) {
			return new TextComment($row['comment_id'], $row);
		} else {
			return false;
		}
	}

	static public function selectAll($textId)
	{
		$db = Zend_Registry::get('db');
		
		$sqlSelect  = 'SELECT * FROM text_comment ';
		$sqlSelect .= 'INNER JOIN member USING (member_id) ';
		$sqlSelect .= 'WHERE text_id = ? ';
		$sqlSelect .= 'ORDER BY comment_timestamp ';
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute(array($textId));
		
		$arr = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
		
		return $arr;
	}

	public function __construct($id, $row)
	{
		$this->_id = $id;
		$this->_row = $row;
	}
	
	public function update($textId, 
	                       $memberId, 
	                       $timestamp, 
	                       $title, 
	                       $text)
	{
		$db = Zend_Registry::get('db');
		
		$meta = self::_serializeMeta($meta);
		
		$sqlUpdate  = 'UPDATE text_comment SET text_id = ?, member_id = ?, '
		            . 'comment_timestamp = ?, comment_title = ?, comment_text = ? ';
		$sqlUpdate .= 'WHERE comment_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($textId, $memberId, $timestamp, $title, $text, $this->_row['comment_id']));
	}
	
	public function getRow()
	{
		return $this->_row;
	}
}
