<?php

class Catahya_Permission_Text implements Catahya_Permission 
{
	const WRITE = 1;
	const CONFIRM = 2;
	const FULL = 4;
	
	protected $_permissions = array(self::WRITE => "Skriva texter", 
	                                self::CONFIRM => "Validera texter",
									self::FULL => "Tillgång till andras text");
	                               
	public function getPermissions() 
	{
		return $this->_permissions;
	}
}
