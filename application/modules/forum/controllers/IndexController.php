<?php
require_once 'TemplateController.php';

class Forum_IndexController extends TemplateController
{
	public function indexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		$sqlCategories = 'SELECT * FROM forum_category ORDER BY category_name';
		$stmtCategories = $db->prepare($sqlCategories);
		$stmtCategories->execute();
		
		$sqlForums  = 'SELECT * FROM forum ';
		$sqlForums .= 'LEFT JOIN member ON member_id = forum_lastmemberid ';
		$sqlForums .= 'LEFT JOIN access USING (access_id) ';
		$sqlForums .= 'ORDER BY forum_name ';
		$stmtForums = $db->prepare($sqlForums);
		$stmtForums->execute();
		
		$forums = array();
		foreach ($stmtForums->fetchAll(PDO::FETCH_ASSOC) as $forum) {
			if ($forum['access_id'] &&
			    !Catahya_Access::hasPermission($forum['access_id'], 
			                                   Catahya_Permission_Forum::VIEW, 
			                                   $forum['access_defaultpermission'])) {

				continue;
			}
			
			$forums[$forum['category_id']][] = $forum;
		}
		
		$categories = array();
		$i = 0;
		foreach ($stmtCategories->fetchAll(PDO::FETCH_ASSOC) as $category) {
			$categories[$i] = $category;
			if (array_key_exists($category['category_id'], $forums)) {
				$categories[$i]['forums'] = $forums[$category['category_id']];
			} else {
				$categories[$i]['forums'] = array();
			}
			$i++;
		}
		
		$this->_view->categories = $categories;
		$this->compile('index.phtml');
	}
}
