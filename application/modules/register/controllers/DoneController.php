<?php
require_once "Catahya/Controller/Action.php";

class Register_DoneController extends Catahya_Controller_Action
{
    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
        $this->compile('done.phtml');
    }
	
	public function ConfirmAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = $request->id;
		
		$sqlUpdateMember  = "UPDATE member SET member_status = 'active' ";
		$sqlUpdateMember .= "WHERE member_id = ?";
		
		$stmt = $db->prepare($sqlUpdateMember);
		$stmt->execute(array($id));
		
		$this->_redirect("/register/done/final");
	}
	
	public function FinalAction()
	{
        $db = Zend_Registry::get('db');
        $this->compile('final.phtml');
	}
}