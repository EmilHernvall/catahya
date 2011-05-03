<?php

class Catahya_Permission_Forum implements Catahya_Permission {
	
	const VIEW = 1;
	const WRITE = 2;
	const REPLY = 4;
	const EDIT = 8;
	const DELETE = 16;
	const MOVE = 32;
	const SPLIT = 64;
	const MERGE = 128;
	
	protected $_permissions = array(self::VIEW => "Visa forum", 
	                                self::WRITE => "Skriva i forum",
	                                self::REPLY => "Besvara",
	                                self::EDIT => "Redigera inlägg",
	                                self::DELETE => "Radera inlägg",
	                                self::MOVE => "Flytta inlägg",
	                                self::SPLIT => "Dela trådar",
	                                self::MERGE => "Slå ihop trådar");
	                               
	public function getPermissions() {
		return $this->_permissions;
	}
}
