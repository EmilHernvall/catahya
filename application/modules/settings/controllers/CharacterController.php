<?php

require_once "Catahya/Controller/Action.php";

class Settings_CharacterController extends Catahya_Controller_Action
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
        
        $sql  = 'SELECT * ';
        $sql .= 'FROM member_character ';
        $sql .= 'INNER JOIN member USING (member_id) ';
        $sql .= 'WHERE member_id = ?';
        
        $stmtChar = $db->prepare($sql);
        $stmtChar->execute(array($_SESSION['id']));
        $arrCharacter = $stmtChar->fetch();
        $stmtChar->closeCursor();
        
        $this->_view->member = $arrCharacter;
        $this->compile('character.phtml');   
    }
    
    public function CharacterCommitAction() 
    {
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$race = $request->race;
		$class = $request->class;
		$alignment = $request->alignment;
		$description = $request->description;
        
        $sql  = 'UPDATE member_character ';
        $sql .= 'SET character_race = ?, character_class = ?, '
              . 'character_alignment = ?, character_description = ? ';
        $sql .= 'WHERE member_id = ?';
        
        $stmtUpdate = $db->prepare($sql);
        $stmtUpdate->execute(array($race, $class, $alignment, $description, $_SESSION['id']));
        
        $_SESSION['flash'] = 'Din karaktärsinfo är nu uppdaterad.';
        $this->_redirect('/settings/character');
    }
}