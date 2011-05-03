<?php
require_once "Catahya/Controller/Action.php";

class Profile_CharacterController extends Catahya_Controller_Action 
{
    
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
	}
    
    public function IndexAction() 
    {
        $db = Zend_Registry::get('db');
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $this->_redirect('/');
        }
        
        $sql  = 'SELECT member_id, member_alias, member_flatalias, '
              . 'member_online, member_photo, member_character.* ';
        $sql .= 'FROM member_character ';
        $sql .= 'INNER JOIN member USING (member_id)  ';
        
        if (is_numeric($id)) {
            $sql .= 'WHERE member_id = ?';
        } else {
            $sql .= 'WHERE member_flatalias = ?';
        }
        
        $stmtCharacter = $db->prepare($sql);
        $stmtCharacter->execute(array($id));
        
        $arrCharacter = $stmtCharacter->fetch(PDO::FETCH_ASSOC);
        
        $stmtCharacter->closeCursor();
        
        if (!$arrCharacter) {
            $_SESSION['flash'] = 'Det finns ingen medlem med detta namn/id';
            $this->_redirect('/profile/' . $id);
        }
        
        $this->_view->member = $arrCharacter;
        $this->compile('character.phtml');
    }
}
