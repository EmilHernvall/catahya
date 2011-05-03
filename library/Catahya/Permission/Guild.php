<?php

class Catahya_Permission_Guild implements Catahya_Permission {
	const VIEW = 1;
	const VALIDATE = 2;
	const EDIT = 4;
	
	protected $_permissions = array(self::VIEW => "Se administration f&ouml;r gillen", 
	                                self::VALIDATE => "Validera nya gillen",
	                                self::EDIT => "Redigera gillen");
	                               
	public function getPermissions() {
		return $this->_permissions;
	}
}
