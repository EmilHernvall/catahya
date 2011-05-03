<?php
require_once 'Catahya/Controller/Action.php';

/**
 * @todo Radera grupper
 * @todo Radera behörigheter
 */
class Admin_AccessController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->section = 'administration';
    	$this->_view->pageSection = 'access';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
    public function init()
    {
    	parent::init();
    	
    	if (!Catahya_Access::hasAccess('admin_accesscontrol')) {
    		$this->_redirect('/');
    	}
    }
    
    public function IndexAction() 
    {
    	$this->compile('access.phtml');
    }
    
    public function MemberEditAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$memberId = $request->get('id');
    	$arrMember = array();
    	if (!$memberId) {
			$_SESSION['flash'] = 'Alias eller id saknas!';
			$this->_direct('/admin/access');	
    	}
    	
    	$sqlMember  = 'SELECT * FROM member ';
    	if (is_numeric($memberId)) {
    		$sqlMember .= 'WHERE member_id = ?';
    	} else {
    		$sqlMember .= 'WHERE member_alias = ?';
    	}
    	$stmtMember = $db->prepare($sqlMember);
    	$stmtMember->execute(array($memberId));
    	
    	$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtMember->closeCursor();    
    	
    	if (!$arrMember) {
			$_SESSION['flash'] = 'Hittade ingen medlem med detta alias eller id!';
			$this->_direct('/admin/access');	
    	}
    	
    	$sqlGroups  = 'SELECT `group`.*, member_id FROM `group` ';
    	$sqlGroups .= 'LEFT JOIN group_member ON group.group_id = group_member.group_id '
    	            . 'AND group_member.member_id = ?';
    	$stmtGroups = $db->prepare($sqlGroups);
    	$stmtGroups->execute(array($arrMember['member_id']));
    	
    	$arrGroups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);
    	
    	$this->_view->member = $arrMember;
    	$this->_view->groups = $arrGroups;
    	$this->compile('access_member_edit.phtml');
    }
    
    public function MemberEditCommitAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$memberId = $request->get('memberid');
    	$arrMember = array();
    	if (!$memberId) {
			$_SESSION['flash'] = 'Alias eller id saknas!';
			$this->_direct('/admin/access');	
    	}
    	
    	$sqlMember  = 'SELECT * FROM member ';
		$sqlMember .= 'WHERE member_id = ?';
    	$stmtMember = $db->prepare($sqlMember);
    	$stmtMember->execute(array($memberId));
    	
    	$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtMember->closeCursor();    
    	
    	if (!$arrMember) {
			$_SESSION['flash'] = 'Hittade ingen medlem med detta alias eller id!';
			$this->_direct('/admin/access');	
    	}
    	
    	$sqlDeleteGroups  = 'DELETE FROM group_member WHERE member_id = ?';
    	$stmtDeleteGroups = $db->prepare($sqlDeleteGroups);
    	$stmtDeleteGroups->execute(array($arrMember['member_id']));
    	
    	$groups = $request->get('group');
    	if (is_array($groups)) {
    		$sqlInsertGroup  = 'INSERT INTO group_member VALUES (?, ?)';
    		$stmtInsertGroup = $db->prepare($sqlInsertGroup);
    		
    		foreach ($groups as $groupId => $bool) {
    			$groupId = intval($groupId);
    			$stmtInsertGroup->execute(array($groupId, $arrMember['member_id']));
    			print_r(array($groupId, $arrMember['member_id']));
    		}
    	}
    	
    	$_SESSION['flash'] = 'Ändringarna har sparats.';
    	$this->_redirect('/admin/access');
    }
    
    public function GroupListAction()
    {
    	$db = Zend_Registry::get('db');
    	
    	$sqlGroups  = 'SELECT * FROM `group` ';
    	$stmtGroups = $db->prepare($sqlGroups);
    	$stmtGroups->execute();
    	
    	$arrGroups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);
    	
    	if (array_key_exists('flash', $_SESSION)) {
    		$this->_view->flash = $_SESSION['flash'];
    		unset($_SESSION['flash']);
    	}
    	
    	$this->_view->groups = $arrGroups;
    	$this->compile('access_group_list.phtml');
    }
    
    public function GroupEditAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$groupId = $request->get('groupid');
    	$arrGroup = array();
    	if ($groupId) {
	    	$sqlGroup  = 'SELECT * FROM `group` ';
	    	$sqlGroup .= 'WHERE group_id = ?';
	    	$stmtGroup = $db->prepare($sqlGroup);
	    	$stmtGroup->execute(array($groupId));
	    	
	    	$arrGroup = $stmtGroup->fetch(PDO::FETCH_ASSOC);
	    	
	    	$stmtGroup->closeCursor();    		
    	}
    	
    	$sqlAccess  = 'SELECT access.*, object_id, object_name, access_permission, group_id FROM access ';
    	$sqlAccess .= 'LEFT JOIN access_object USING (object_id) ';
    	$sqlAccess .= 'LEFT JOIN access_group '
    	            . 'ON access_group.access_id = access.access_id '
    	            . 'AND group_id = ?';
    	$stmtAccess = $db->prepare($sqlAccess);
    	$stmtAccess->execute(array($groupId));
    	
    	$arrAccess = $stmtAccess->fetchAll(PDO::FETCH_ASSOC);
    	
    	foreach ($arrAccess as &$access) {
    		$permissions = array();
    		if ($access['object_id']) {
    			$className = sprintf('Catahya_Permission_%s', ucfirst($access['object_name']));
    			if (class_exists($className)) {
    				$class = new $className;
    				$permissions = $class->getPermissions();
    			}
    		}
    		$access['permissions'] = $permissions;
    	}
    	
    	$this->_view->group = $arrGroup;
    	$this->_view->access = $arrAccess;
    	$this->compile('access_group_edit.phtml');
    }
    
    public function GroupEditCommitAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$groupId = $request->get('groupid');
    	$title = $request->get('title');
    	$description = $request->get('description');
    	
    	// Edit
    	if ($groupId) {
    		$sqlUpdate  = 'UPDATE `group` SET group_title = ?, group_description = ? ';
    		$sqlUpdate .= 'WHERE group_id = ? ';
    		
    		$stmtUpdate = $db->prepare($sqlUpdate);
    		$stmtUpdate->execute(array($title, $description, $groupId));
    		
    		$_SESSION['flash'] = 'Dina ändringar har sparats.';
    	}
    	// Insert
    	else {
    		$sqlInsert  = 'INSERT INTO `group` (group_title, group_description) ';
    		$sqlInsert .= 'VALUES (?, ?)';
    		
    		$stmtInsert = $db->prepare($sqlInsert);
    		$stmtInsert->execute(array($title, $description));
    		
    		$groupId = $db->lastInsertId();
    		
    		$_SESSION['flash'] = 'Gruppen har lagts till.';
    	}
    	
			$sqlDeleteAccess  = 'DELETE FROM access_group WHERE group_id = ?';
			$stmtDeleteAccess = $db->prepare($sqlDeleteAccess);
			$stmtDeleteAccess->execute(array($groupId));
		
    	$access = $request->get('access');
    	$permissions = $request->get('permission');

    	if (is_array($access)) {
    		if (!is_array($permissions)) {
    			$permissions = array();
    		}
    		
    		$sqlInsertAccess = 'INSERT INTO access_group VALUES (?, ?, ?)';
    		$stmtInsertAccess = $db->prepare($sqlInsertAccess);
    		
    		foreach ($access as $currentAccess => $bool) {
    			$currentAccess = intval($currentAccess);
    			$permission = 0;
    			if (array_key_exists($currentAccess, $permissions)) {
    				foreach ($permissions[$currentAccess] as $currentPermission => $bool2) {
    					$permission += intval($currentPermission);
    				}
    			}

    			$stmtInsertAccess->execute(array($groupId, $currentAccess, $permission));
    		}
    	}
    	
    	$this->_redirect('/admin/access/groupList');
    }
    
    public function AccessListAction()
    {
    	$db = Zend_Registry::get('db');
    	
    	$sqlAccess  = 'SELECT * FROM access ';
    	$sqlAccess .= 'LEFT JOIN access_object USING (object_id) ';
    	$sqlAccess .= 'ORDER BY access_name';
    	$stmtAccess = $db->prepare($sqlAccess);
    	$stmtAccess->execute();
    	
    	$arrAccess = $stmtAccess->fetchAll(PDO::FETCH_ASSOC);
    	
    	if (array_key_exists('flash', $_SESSION)) {
    		$this->_view->flash = $_SESSION['flash'];
    		unset($_SESSION['flash']);
    	}
    	
    	$this->_view->access = $arrAccess;
    	$this->compile('access_access_list.phtml');
    }
    
    public function AccessEditAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$accessId = $request->get('accessid');
    	$arrAccess = array();
    	if ($accessId) {
	    	$sqlAccess  = 'SELECT * FROM access ';
	    	$sqlAccess .= 'WHERE access_id = ?';
	    	$stmtAccess = $db->prepare($sqlAccess);
	    	$stmtAccess->execute(array($accessId));
	    	
	    	$arrAccess = $stmtAccess->fetch(PDO::FETCH_ASSOC);
	    	
	    	$stmtAccess->closeCursor();    		
    	}
    	
    	$sqlObjects  = 'SELECT * FROM access_object';
    	$stmtObjects = $db->prepare($sqlObjects);
    	$stmtObjects->execute();
    	
    	$arrObjects = $stmtObjects->fetchAll(PDO::FETCH_ASSOC);
    	
    	$this->_view->access = $arrAccess;
    	$this->_view->objects = $arrObjects;
    	$this->compile('access_access_edit.phtml');
    }
    
    public function AccessEditCommitAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$accessId = $request->get('accessid');
    	$name = $request->get('name');
    	$title = $request->get('title');
    	$description = $request->get('description');
    	$object = $request->get('object');
    	
    	// Edit
    	if ($accessId) {
    		$sqlUpdate  = 'UPDATE access SET object_id = ?, access_name = ?, '
			            . 'access_title = ?, access_description = ? ';
    		$sqlUpdate .= 'WHERE access_id = ? ';
    		
    		$stmtUpdate = $db->prepare($sqlUpdate);
    		$stmtUpdate->execute(array($object, $name, $title, $description, $accessId));
    		
    		$_SESSION['flash'] = 'Dina ändringar har sparats.';
    	}
    	// Insert
    	else {
    		$sqlInsert  = 'INSERT INTO access (object_id, access_name, access_title, access_description) ';
    		$sqlInsert .= 'VALUES (?, ?, ?, ?)';
    		
    		$stmtInsert = $db->prepare($sqlInsert);
    		$stmtInsert->execute(array($object, $name, $title, $description));
    		
    		$_SESSION['flash'] = 'Behörigheten har lagts till.';
    	}
    	
    	$this->_redirect('/admin/access/accessList');
    }
    
    public function AccessPermissionAction() {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$accessId = $request->get('accessid');
    	if (!$accessId) {
    		$this->_redirect('/admin/access/accesslist');
    	}
    	
    	$sqlAccess  = 'SELECT * FROM access ';
    	$sqlAccess .= 'INNER JOIN access_object USING (object_id) ';
    	$sqlAccess .= 'WHERE access_id = ?';
    	$stmtAccess = $db->prepare($sqlAccess);
    	$stmtAccess->execute(array($accessId));
    	
    	$arrAccess = $stmtAccess->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtAccess->closeCursor();    		
    	if (!$arrAccess) {
    		$this->_redirect('/admin/access/accesslist');
    	}
    	
		$className = sprintf('Catahya_Permission_%s', ucfirst($arrAccess['object_name']));
		$permissions = array();
		if (class_exists($className)) {
			$class = new $className;
			$permissions = $class->getPermissions();
		}
		
    	$this->_view->access = $arrAccess;
    	$this->_view->permissions = $permissions;
    	$this->compile('access_access_permission.phtml');
    }
    
    public function AccessPermissionCommitAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$accessId = $request->get('accessid');
    	if (!$accessId) {
    		$this->_redirect('/admin/access/accesslist');
    	}
    	
    	$sqlAccess  = 'SELECT * FROM access ';
    	$sqlAccess .= 'INNER JOIN access_object USING (object_id) ';
    	$sqlAccess .= 'WHERE access_id = ?';
    	$stmtAccess = $db->prepare($sqlAccess);
    	$stmtAccess->execute(array($accessId));
    	
    	$arrAccess = $stmtAccess->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtAccess->closeCursor();    		
    	if (!$arrAccess) {
    		$this->_redirect('/admin/access/accesslist');
    	}
    	
    	$permissions = $request->get('permission');
    	
    	$permission = 0;
    	if (is_array($permissions)) {
			foreach ($permissions as $currentPermission => $bool2) {
				$permission += intval($currentPermission);
			}
    	}
    	
		$sqlUpdateAccess  = 'UPDATE access SET access_defaultpermission = ? ';
		$sqlUpdateAccess .= 'WHERE access_id = ?';
		$stmtUpdateAccess = $db->prepare($sqlUpdateAccess);
		$stmtUpdateAccess->execute(array($permission, $arrAccess['access_id']));
    	
    	$this->_redirect('/admin/access/accessList');
    }
}
