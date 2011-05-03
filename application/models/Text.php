<?php

require_once 'Text/Review/Movie.php';
require_once 'Text/Review/Book.php';
require_once 'Text/Review/Game.php';
require_once 'Text/Review/Music.php';

class Text 
{
	protected $_id = 0;
	protected $_row = array();
	
	protected $_metaFields = array();
	
	static public function insert($typeId, $memberId, $imageId, $timestamp, $title, $text, $pretext, $showpretext, $gallery)
	{
		$db = Zend_Registry::get('db');
		
		$sqlInsert  = 'INSERT INTO text (type_id, member_id, image_id, '
		            . 'text_timestamp, text_title, text_text, text_pretext, '
					. 'text_showpretext, text_gallery) ';
		$sqlInsert .= 'VALUES (?,?,?,?,?,?,?,?,?)';
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($typeId, $memberId, $imageId, $timestamp, $title, $text, $pretext, $showpretext, $gallery));
		
		$textId = $db->lastInsertId();
		
		return self::selectById($textId);
	}
	
	static public function selectById($textId)
	{
		$db = Zend_Registry::get('db');
		
		$sqlSelect  = 'SELECT text.*, text_type.*, member.*, text_image.image_id FROM text ';
		$sqlSelect .= 'INNER JOIN text_type USING (type_id) ';
		$sqlSelect .= 'INNER JOIN member USING (member_id) ';
		$sqlSelect .= 'LEFT JOIN text_image USING (image_id) ';
		$sqlSelect .= 'WHERE text.text_id = ?';
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute(array($textId));
		
		$row = $stmtSelect->fetch(PDO::FETCH_ASSOC);
		
		$stmtSelect->closeCursor();
		
		if (!$row) {
			return false;
		}
		
		$obj = null;
		$class = $row["type_class"];
		if (class_exists($class)) {
			$obj = new $class($row['text_id'], $row);
		} else {
			$obj = new Text($row['text_id'], $row);
		}
		
		return $obj;
	}

	static public function selectLatest($typeIds, $limit = 20)
	{
		$db = Zend_Registry::get('db');
		
		$sqlSelect  = 'SELECT * FROM text ';
		$sqlSelect .= 'INNER JOIN member USING (member_id) ';
		$sqlSelect .= sprintf('WHERE type_id IN (%s) ', implode(',', $typeIds));
		$sqlSelect .= 'ORDER BY text_timestamp DESC ';
		$sqlSelect .= sprintf('LIMIT %d', $limit);
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute();
		
		$arr = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
		
		return $arr;
	}
	
	static public function selectByTypeId($typeId, $memberId = 0, $sortOrder = false, $orderDesc = false)
	{
		$db = Zend_Registry::get('db');
		
    	$sqlType = 'SELECT * FROM text_type ';
		if (is_numeric($typeId)) {
			$sqlType .= 'WHERE type_id = ?';
		} else {
			$sqlType .= 'WHERE type_name = ?';
		}
		
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($typeId));
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	$stmtType->closeCursor();
		
		if (!$type) {
			return array();
		}
		
		$sqlSelect  = 'SELECT text_id, type_id, text_timestamp, text_title, member.* ';
		if ($type["type_metatable"]) {
			$sqlSelect .= ', a.* ';
		}
		$sqlSelect .= 'FROM text ';
		$sqlSelect .= 'INNER JOIN member USING (member_id) ';
		if ($type["type_metatable"]) {
			$sqlSelect .= sprintf('INNER JOIN %s a USING (text_id) ', $type["type_metatable"]);
		}
		$sqlSelect .= 'WHERE type_id = ?';
		
		$params[] = $type["type_id"];
		if ($memberId) {
			$sqlSelect .= 'AND member_id = ? ';
			$params[] = $memberId;
		}
		
		if ($sortOrder) {
			$sqlSelect .= sprintf("ORDER BY %s %s", 
				$sortOrder,
				!$orderDesc ? "ASC" : "DESC");
		}
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute($params);
		
		$arr = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
		
		return $arr;
	}
	
	static public function selectUnpublishedByTypeId($typeId)
	{
		$db = Zend_Registry::get('db');

		$sqlSelect  = 'SELECT * FROM text ';
		$sqlSelect .= 'INNER JOIN member USING (member_id) ';
		$sqlSelect .= 'WHERE type_id = ? ';
		$sqlSelect .= 'AND (text_timestamp = 0 OR text_timestamp > unix_timestamp()) ';
		$sqlSelect .= 'ORDER BY text_timestamp ';
		
		$stmtSelect = $db->prepare($sqlSelect);
		$stmtSelect->execute(array($typeId));
		
		$arr = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
		
		return $arr;
	}

	public function __construct($id, $row)
	{
		$this->_id = $id;
		$this->_row = $row;
	}
	
	public function update($typeId, 
						   $imageId,
	                       $title, 
	                       $text, 
	                       $pretext, 
	                       $showpretext, 
						   $gallery)
	{
		$db = Zend_Registry::get('db');
		
		$sqlUpdate  = 'UPDATE text SET type_id = ?, '
		            . 'image_id = ?, text_title = ?, text_text = ?, '
		            . 'text_pretext = ?, text_showpretext = ?, '
					. 'text_gallery = ? ';
		$sqlUpdate .= 'WHERE text_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($typeId, $imageId, $title, $text, $pretext, 
			$showpretext, $gallery, $this->_row['text_id']));
	}
	
	public function selectMeta()
	{
		if (!array_key_exists("type_metatable", $this->_row)) {
			return array();
		}
		
		$db = Zend_Registry::get("db");
		
		$sqlMeta  = sprintf("SELECT * FROM %s ", $this->_row["type_metatable"]);
		$sqlMeta .= "WHERE text_id = ?";
		
		$stmtMeta = $db->prepare($sqlMeta);
		$stmtMeta->execute(array($this->_row["text_id"]));
	
		$res = $stmtMeta->fetch(PDO::FETCH_ASSOC);
		
		$stmtMeta->closeCursor();
	
		return $res;
	}
	
	public function updateMeta($meta)
	{
		$db = Zend_Registry::get("db");
	
		if (!$this->_row["type_metatable"]) {
			return;
		}
		
		$fields = array();
		$markers = array();
		$params = array($this->_row["text_id"]);
		foreach ($this->_metaFields as $field => $data) {
			if (array_key_exists($field, $meta)) {
				$fields[] = $field;
				$markers[] = "?";
				$params[] = $meta[$field];
			}
		}
		
		$sqlReplace  = "REPLACE INTO %s (text_id, %s) ";
		$sqlReplace .= "VALUES (?,%s)";
		
		$sqlReplace = sprintf($sqlReplace, $this->_row["type_metatable"],
			implode(",", $fields), implode(",", $markers));
			
		$stmtReplace = $db->prepare($sqlReplace);
		$stmtReplace->execute($params);
	}
	
	public function getRow()
	{
		return $this->_row;
	}
	
	public function getMetaFields()
	{
		return $this->_metaFields;
	}
}
