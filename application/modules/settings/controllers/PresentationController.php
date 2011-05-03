<?php

require_once "Catahya/Controller/Action.php";

class Settings_PresentationController extends Catahya_Controller_Action
{

    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
        
        $sql = 'SELECT * ';
        $sql .= 'FROM member_profile p ';
        $sql .= 'INNER JOIN member m USING (member_id) ';
        $sql .= 'WHERE member_id = ?';

        $stmtProfile = $db->prepare($sql);
        $stmtProfile->execute( array($_SESSION['id']) );

        $arrPresentation = $stmtProfile->fetch(PDO::FETCH_ASSOC);
        
        $stmtProfile->closeCursor();
        
        if ($arrPresentation['member_id'] != $_SESSION['id']) {
            $_SESSION['flash'] = 'Du har inte behörighet att visa den här sidan.';
			$this->_redirect('/');
		}
        
		$this->_view->member = $arrPresentation;
		$this->compile('presentation.phtml');
	}
		
	public function PresentationCommitAction() 
	{
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
        
		$presentation = trim($request->presentation);

        $sql  = 'UPDATE member_profile ';
        $sql .= 'SET member_presentation = ? ';
        $sql .= 'WHERE member_id = ?';

        $stmtUpdate = $db->prepare($sql);
        $stmtUpdate->execute(array($presentation, $_SESSION['id']));

        $stmtUpdate->closeCursor();

        $_SESSION['flash'] = 'Din presentation är uppdaterad!';
        $this->_redirect('/settings/presentation');   
    }
}
