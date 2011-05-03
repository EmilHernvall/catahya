<?php

class Catahya_Permission_Wiki implements Catahya_Permission 
{	
	const VIEW = 1;
	const EDIT = 2;
	const DELETE = 4;

	protected $_permissions = array(self::VIEW => "Visa",
	                                self::EDIT => "Redigera",
									self::DELETE => "Radera");
	                               
	public function getPermissions() 
	{
		return $this->_permissions;
	}
}
