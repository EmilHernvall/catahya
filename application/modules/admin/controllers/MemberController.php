<?php
require_once 'Catahya/Controller/Action.php';

class Admin_MemberController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->section = 'administration';
    	$this->_view->pageSection = 'member';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
    public function init()
    {
    	parent::init();
    	
    	/*if (!Catahya_Access::hasAccess('accesscontrol')) {
    		$this->_redirect('/');
    	}*/
    }
    
    public function IndexAction() 
    {
		$this->compile("member_index.phtml");
	}
	
	public function AuditListAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		
		$sqlMembers  = "SELECT * FROM member_account ";
		$sqlMembers .= "INNER JOIN member USING (member_id) ";
		$sqlMembers .= "WHERE member_accountstatus = 'pending' ";
		$sqlMembers .= "ORDER BY member_timestamp";
		
		$stmtMembers = $db->prepare($sqlMembers);
		$stmtMembers->execute();
		
		$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->members = $members;
		$this->compile("member_audit_list.phtml");
	}
	
	public function AuditAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		
		$memberId = $request->memberid;
		
		$sqlMember  = "SELECT member_account.*, member_alias FROM member_account ";
		$sqlMember .= "INNER JOIN member USING (member_id) ";
		$sqlMember .= "WHERE member_id = ? ";
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($memberId));
		
		$member = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		$this->_view->member = $member;
		$this->compile("member_audit.phtml");
	}
	
	public function AuditCommitAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		
		$memberId = $request->memberid;
		$do = $request->do;
		$message = $request->message;
		
		$db->beginTransaction();
		
		$auditId = 0;
		if ($do != "save") {
			$sqlUpdate  = "UPDATE member_account SET member_accountstatus = ? ";
			$sqlUpdate .= "WHERE member_id = ?";
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			if ($do == "accept") {
				$stmtUpdate->execute(array("valid", $memberId));
			} else {
				$stmtUpdate->execute(array("invalid", $memberId));
			}
		
			$sqlInsert  = "INSERT INTO member_auditlog (member_id, auditlog_timestamp, "
			            . "auditlog_auditor, auditlog_confirmed, auditlog_message) ";
			$sqlInsert .= "VALUES (?, ?, ?, ?, ?)";
			
			$stmtInsert = $db->prepare($sqlInsert);
			if ($do == "accept") {
				$stmtInsert->execute(array($memberId, time(), $_SESSION["id"],
					"confirmed", $message));
			} else {
				$stmtInsert->execute(array($memberId, time(), $_SESSION["id"],
					"rejected", $message));			
			}
			
			$auditId = $db->lastInsertId();
		}

		$email = $request->email;
		$firstname = $request->firstname;
		$surname = $request->surname;
		$address = $request->address;
		$zipcode = $request->zipcode;
		$city = $request->city;
		$country = $request->country;
		$phonenr = $request->phonenr;
		$ssn = $request->ssn;
		
		$sqlUpdate  = "UPDATE member_account SET auditlog_id = ?, "
		            . "member_ssn = ?, member_firstname = ?, "
					. "member_surname = ?, member_address = ?, member_zipcode = ?, "
					. "member_city = ?, member_country = ?, member_phonenr = ?, "
					. "member_email = ? ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($auditId, $ssn, $firstname, $surname, $address, $zipcode, $city,
			$country, $phonenr, $email, $memberId));
			
		$db->commit();
		
		$this->_redirect("/admin/member/auditlist");
	}
}
