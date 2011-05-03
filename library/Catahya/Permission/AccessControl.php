<?php

class Catahya_Permission_AccessControl implements Catahya_Permission {
	
	const EDITACCESS = 1;
	
	protected $_permissions = array(self::EDITACCESS => "Modifiera behörigheter");
	                               
	public function getPermissions() {
		return $this->_permissions;
	}
}
