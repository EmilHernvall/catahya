<?php

require 'Permission.php';
require 'Permission/Forum.php';
require 'Permission/AccessControl.php';
require 'Permission/Text.php';
require 'Permission/Wiki.php';
require 'Permission/Guild.php';

class Catahya_Access {
	
	public static function init($groupAccess) 
	{
		
		$accessArray = array();
		foreach ($groupAccess as $access) {
			$access['access_permission'] = (int)$access['access_permission'] | (int)$access['access_defaultpermission'];
			$accessArray[$access['access_id']] = $access;
		}
		
		$_SESSION['access'] = $accessArray;
		
	}
	
	public static function hasAccess($accessIdentifier) 
	{
		
		if (!$_SESSION['online']) {
			return false;
		}
		
		if (func_num_args() > 1) {
			$accessIdentifiers = func_get_args();
			$access = false;
			foreach ($accessIdentifiers as $accessIdentifier) {
				if (self::hasAccess($accessIdentifier)) {
					return true;
				}
			}
		} else if (is_numeric($accessIdentifier)) {
			return array_key_exists($accessIdentifier, $_SESSION['access']);
		} else {
			foreach ($_SESSION['access'] as $access) {
				if ($access['access_name'] == $accessIdentifier) {
					return true;
				}
			}
		}
		
		return false;		
	}
	
	public static function hasPermission($accessIdentifier, $permission, $defaultPermission = 0) 
	{

		if (((int)$defaultPermission & (int)$permission) == (int)$permission) {
			return true;
		}
		
		if (!$_SESSION['online']) {
			return false;
		}
		
		$access = false;
		if (is_numeric($accessIdentifier)) {
			$access = array_key_exists($accessIdentifier, $_SESSION['access']) ? 
			            $_SESSION['access'][$accessIdentifier] : false;
		} else {
			foreach ($_SESSION['access'] as $currentAccess) {
				if ($currentAccess['access_name'] == $accessIdentifier) {
					$access = $currentAccess;
				}
			}
		}
		
		if (!$access) {
			return false;
		}
		
		return ((int)$access['access_permission'] & (int)$permission) == (int)$permission;
	}
	
	public static function hasDefaultPermission($defaultPermissions, $permission) 
	{
		return ($defaultPermissions & $permission) == $permission;
	}
	
}
