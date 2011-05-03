<?php

require_once 'Catahya/Controller/Action.php';

class Community_OnlineController extends Catahya_Controller_Action
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
		$sqlSelect .= "INNER JOIN member_online USING (member_id) ";
		$sqlSelect .= "INNER JOIN member_userdata USING (member_id) ";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute();

		$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->members = $members;
		$this->compile('online_index.phtml');
	}
}
