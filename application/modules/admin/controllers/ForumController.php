<?php
require_once 'Catahya/Controller/Action.php';

/**
 * @todo Radera forum.
 */
class Admin_ForumController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->section = 'administration';
    	$this->_view->pageSection = 'forum';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
    public function init()
    {
    	parent::init();
    	
    	if (!Catahya_Access::hasAccess('forum')) {
    		$this->_redirect('/');
    	}
    }
    
    public function IndexAction() 
    {
    	$db = Zend_Registry::get('db');
    	
		$sqlForums  = 'SELECT * FROM forum ';
		$sqlForums .= 'INNER JOIN forum_category USING (category_id) ';
		$sqlForums .= 'ORDER BY category_id ';
		$stmtForums = $db->prepare($sqlForums);
		$stmtForums->execute();
		
		$arrForums = $stmtForums->fetchAll(PDO::FETCH_ASSOC);
    	
		$this->_view->forums = $arrForums;
    	$this->compile('forum.phtml');
    }
    
    public function EditAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$forumId = $request->get('forumid');
    	$arrForum = array();
    	if ($forumId) {
    		$sqlForum  = 'SELECT * FROM forum ';
    		$sqlForum .= 'WHERE forum_id = ? ';
    		
    		$stmtForum = $db->prepare($sqlForum);
    		$stmtForum->execute(array($forumId));
    		
    		$arrForum = $stmtForum->fetch(PDO::FETCH_ASSOC);
    		
    		$stmtForum->closeCursor();
    	}
    	
    	$sqlCategories = 'SELECT * FROM forum_category';
    	
    	$stmtCategories = $db->prepare($sqlCategories);
    	$stmtCategories->execute();
    	
    	$arrCategories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
    	
    	$sqlAccess  = 'SELECT * FROM access ';
    	$sqlAccess .= 'INNER JOIN access_object USING (object_id) ';
    	$sqlAccess .= 'WHERE object_name = ?';
    	
    	$stmtAccess = $db->prepare($sqlAccess);
    	$stmtAccess->execute(array('forum'));
    	
    	$arrAccess = $stmtAccess->fetchAll(PDO::FETCH_ASSOC);
    	
    	$this->_view->forum = $arrForum;
    	$this->_view->categories = $arrCategories;
    	$this->_view->access = $arrAccess;
    	$this->compile('forum_edit.phtml');
    }
    
    public function EditCommitAction()
    {
		$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$forumId = $request->get('forumid');
    	$name = $request->get('name');
    	$description = $request->get('description');
    	$categoryId = intval($request->get('category'));
    	$accessId = intval($request->get('access'));
    	
    	if ($forumId) {
    		$sqlUpdate  = 'UPDATE forum SET forum_name = ?, '
    		            . 'forum_description = ?, category_id = ?, access_id = ? ';
			$sqlUpdate .= 'WHERE forum_id = ?';
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($name, $description, $categoryId, $accessId, $forumId));
			
			$_SESSION['flash'] = 'Forumet har uppdaterats.';
    	} else {
    		$sqlInsert  = 'INSERT INTO forum (category_id, access_id, '
    		            . 'forum_name, forum_description) ';
			$sqlInsert .= 'VALUES (?, ?, ?, ?)';
			
			$stmtInsert = $db->prepare($sqlInsert);
			$stmtInsert->execute(array($categoryId, $accessId, $name, $description));
			
			$_SESSION['flash'] = 'Forumet har skapats.';
    	}
    	
    	$this->_redirect('/admin/forum');
    }
}
