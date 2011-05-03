<?php

require_once 'Catahya/Controller/Action.php';

class Community_LoginController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('community_menu.phtml');
        parent::compile($template, $layout);
    }

	public function IndexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$sqlSelect  = "SELECT * FROM member ";
		$sqlSelect .= "INNER JOIN member_userdata USING (member_id) ";
		$sqlSelect .= "WHERE member_lastlogin > 0 ";
		$sqlSelect .= "ORDER BY member_lastlogin DESC ";
		$sqlSelect .= "LIMIT 50";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute();

		$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->members = $members;
		$this->compile('login_index.phtml');
	}
}
