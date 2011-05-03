<?php
require_once "Catahya/Controller/Action.php";

class Register_AccountController extends Catahya_Controller_Action
{
    public function IndexAction()
    {
        //$db = Zend_Registry::get('db');
        //$this->compile('done.phtml');
    }
	
	public function InvalidAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$type = $request->type;
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		$sql  = "SELECT * FROM member ";
		$sql .= "INNER JOIN member_account USING (member_id) ";
		$sql .= "LEFT JOIN member_auditlog USING (auditlog_id) ";
		$sql .= "WHERE member.member_id = ?";
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION["tmp_id"]));
		
		$member = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$member) {
			$this->_redirect("/");
		}
		
		$stmt->closeCursor();
		
		$this->_view->type = $type;
		$this->_view->member = $member;
		$this->compile('account_invalid.phtml');
	}
	
	public function ConfirmAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$type = $request->type;
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		$sql  = "SELECT * FROM member ";
		$sql .= "INNER JOIN member_account USING (member_id) ";
		$sql .= "WHERE member_id = ?";
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION["tmp_id"]));
		
		$member = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$member) {
			$this->_redirect("/");
		}
		
		$stmt->closeCursor();
		
		$this->_view->type = $type;
		$this->_view->member = $member;
		$this->compile('account_confirm.phtml');
	}
	
	public function ConfirmCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$correct = $request->correct;
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		if ($correct == 1) {
			$sqlUpdate  = "UPDATE member_account SET member_timestamp = unix_timestamp() ";
			$sqlUpdate .= "WHERE member_id = ?";
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($_SESSION["tmp_id"]));
			
			$_SESSION["login_id"] = $_SESSION["tmp_id"];
			unset($_SESSION["tmp_id"]);
			
			$this->_redirect("/index/loginCommit");
			
		} else {
			$sqlUpdate  = "UPDATE member_account SET member_accountstatus = 'invalid', auditlog_id = 0 ";
			$sqlUpdate .= "WHERE member_id = ?";
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($_SESSION["tmp_id"]));
			
			$this->_redirect("/register/account/edit");
		}
	}
	
	public function EditAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		$sql  = "SELECT * FROM member ";
		$sql .= "INNER JOIN member_account USING (member_id) ";
		$sql .= "LEFT JOIN member_auditlog USING (auditlog_id) ";
		$sql .= "WHERE member.member_id = ?";
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION["tmp_id"]));
		
		$member = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$member) {
			$this->_redirect("/");
		}
		
		$stmt->closeCursor();
		
		$this->_view->member = $member;
		$this->compile('account_edit.phtml');
	}
	
	public function EditCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		$email = $request->email;
		$firstname = $request->firstname;
		$surname = $request->surname;
		$address = $request->address;
		$zipcode = $request->zipcode;
		$city = $request->city;
		$country = $request->country;
		$phonenr = $request->phonenr;
		
		$sqlUpdate  = "UPDATE member_account SET member_accountstatus = 'pending', "
		            . "member_timestamp = unix_timestamp(), member_firstname = ?, "
					. "member_surname = ?, member_address = ?, member_zipcode = ?, "
					. "member_city = ?, member_country = ?, member_phonenr = ?, "
					. "member_email = ? ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($firstname, $surname, $address, $zipcode, $city,
			$country, $phonenr, $email, $_SESSION["tmp_id"]));
			
		$_SESSION["login_id"] = $_SESSION["tmp_id"];
		unset($_SESSION["tmp_id"]);
		
		$this->_redirect("/index/loginCommit");
	}
	
	public function DeleteCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!array_key_exists("tmp_id", $_SESSION)) {
			$this->_redirect("/");
		}
		
		$db->beginTransaction();
		
		$sqlUpdate  = "UPDATE member SET member_password = '', member_age = 0, "
					. "member_status = 'discontinued', member_online = '0', "
					. "member_photo = '', member_photostatus = '1', member_quickdesc = '', "
					. "member_city = '' ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($_SESSION["tmp_id"]));
		
		$tables = array("member_account", "member_avatar", "member_character", 
			"member_online", "member_profile", "member_userdata");
			
		foreach ($tables as $table) {
			$sqlDelete  = sprintf("DELETE FROM %s ", $table);
			$sqlDelete .= "WHERE member_id = ?";
			
			$stmtDelete = $db->prepare($sqlDelete);
			$stmtDelete->execute(array($_SESSION["tmp_id"]));
		}
		
		// TODO: OnDeploy: Change to commit.
		$db->rollback();
		
		$this->_redirect("/register/account/gone");
	}
	
	public function GoneAction() 
	{
		unset($_SESSION["tmp_id"]);
		$this->compile('account_gone.phtml');
	}
}
