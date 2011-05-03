<?php
require_once 'Catahya/Controller/Action.php';

class Profile_RelationController extends Catahya_Controller_Action 
{
	public function compile($template, $layout = 'layout.phtml') 
	{
		$this->_view->pageMenu = $this->_view->render('menu.phtml');
		parent::compile($template, $layout);
	}
	
	public function indexAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = $request->getParam('id');
		
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender,  '
		            . 'member_age, member_photo ';
		$sqlMember .= 'FROM member ';
		if (is_numeric($id)) {
			$sqlMember .= 'WHERE member_id = ?';
		} else {
			$sqlMember .= 'WHERE member_flatalias = ?';
		}
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		if (!$arrMember) {
			$this->_redirect('/');
		}
		
		$sql  = 'SELECT ';
		$sql .= 'relation_id, relation_action, relation_timestamp, relation_irl, ';
		$sql .= 'member_id, member_alias, member_flatalias, member_gender, member_age, member_photo, member_city ';
		$sql .= 'FROM member_relation ';
		$sql .= 'INNER JOIN member ON relation_memberid2 = member_id ';
		$sql .= 'WHERE relation_memberid1 = ? AND relation_approved = "1" ';
		$sql .= 'ORDER BY member_alias';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($arrMember['member_id']));
		
		$arrApproved = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt->closeCursor();
		
		$sql  = 'SELECT ';
		$sql .= 'relation_id, relation_memberid1, relation_memberid2, relation_action, relation_timestamp, relation_irl, ';
		$sql .= 'member_id, member_alias, member_flatalias, member_gender, member_age, member_photo, member_city ';
		$sql .= 'FROM member_relation, member ';
		$sql .= 'WHERE ';
		$sql .= '((relation_memberid1 = ? AND relation_memberid2 = member_id) OR ';
		$sql .= '(relation_memberid2 = ? AND relation_memberid1 = member_id)) ';
		$sql .= 'AND relation_approved = "0" ';
		$sql .= 'ORDER BY member_alias';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($arrMember['member_id'],$arrMember['member_id']));
		
		$arrUnapproved = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt->closeCursor();
		
		$this->_view->member = $arrMember;
		$this->_view->approved = $arrApproved;
		$this->_view->unapproved = $arrUnapproved;
		$this->compile('relation.phtml');
	}
	
	public function createAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = $request->getParam('id');
		
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender,  '
		            . 'member_age, member_city, member_photo ';
		$sqlMember .= 'FROM member ';
		if (is_numeric($id)) {
			$sqlMember .= 'WHERE member_id = ?';
		} else {
			$sqlMember .= 'WHERE member_flatalias = ?';
		}
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		if (!$arrMember) {
			$this->_redirect('/');
		}
		
		$sql  = 'SELECT relation_id ';
		$sql .= 'FROM member_relation ';
		$sql .= 'WHERE relation_memberid1 = ? AND relation_memberid2 = ?';
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION['id'], $arrMember['member_id']));
		
		if ($stmt->fetch()) {
			$this->_redirect("/profile/".$arrMember["member_flatalias"]);
		}
		
		$stmt->closeCursor();
		
		$sql  = 'SELECT relation_irl ';
		$sql .= 'FROM member_relation ';
		$sql .= 'WHERE relation_memberid2 = ? AND relation_memberid1 = ?';
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION['id'], $arrMember['member_id']));
		
		$relation = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt->closeCursor();
		
		$this->_view->member = $arrMember;
		$this->_view->relation = $relation;
		$this->compile('relation_create.phtml');
	}
	
	public function createCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$member1 = $request->has('member1') ? (int)$request->member1 : false;
		$member2 = $request->has('member2') ? (int)$request->member2 : false;
		
		$irl = $request->has('irl') ? (int)$request->irl : "0";
		$action = $request->action_;
		
		if (!$member1 || !$member2) {
			$this->_redirect('/');
		}
		
		$sql  = 'SELECT relation_id ';
		$sql .= 'FROM member_relation ';
		$sql .= 'WHERE relation_memberid1 = ? AND relation_memberid2 = ? ';
		
		$stmt = $db->prepare($sql);
		$stmt->execute(array($member1, $member2));
		
		$res = $stmt->fetch();
		
		$stmt->closeCursor();
		
		if (!$res) {
			$sql  = 'INSERT INTO member_relation ';
			$sql .= '(relation_memberid1, relation_memberid2, relation_action, '
			      . 'relation_irl, relation_timestamp) ';
			$sql .= 'VALUES(?, ?, ?, ?, UNIX_TIMESTAMP())';
			
			$stmt = $db->prepare($sql);
			$stmt->execute(array($_SESSION['id'], $member2, trim($action), $irl));
			
			$sql  = 'SELECT ';
			$sql .= 'relation_id ';
			$sql .= 'FROM member_relation ';
			$sql .= 'WHERE (relation_memberid1 = ? AND relation_memberid2 = ?) '
			      . 'OR (relation_memberid2 = ? AND relation_memberid1 = ?) ';
			$stmt = $db->prepare($sql);
			$stmt->execute(array($_SESSION['id'], $member2, $_SESSION['id'], $member2));
			
			if (count($stmt->fetchAll()) == 2) {
				// A complete relation is created
				$sql  = 'UPDATE member_relation ';
				$sql .= 'SET relation_approved = "1" ';
				$sql .= 'WHERE (relation_memberid1 = ? AND relation_memberid2 = ?) '
				      . 'OR (relation_memberid2 = ? AND relation_memberid1 = ?) ';
				$stmt = $db->prepare($sql);
				$stmt->execute(array($_SESSION['id'], $member2, $_SESSION['id'], $member2));
			
			} else {
				// A new relation request
			}
		}
		
		$_SESSION["flash"] = "Relationen har skapats.";
		$this->_redirect("/profile/" . $_SESSION["alias"] . "/relation");
	}
	
	public function deleteCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = intval($request->id);
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$sqlDelete  = "DELETE FROM member_relation ";
		$sqlDelete .= "WHERE (relation_memberid1 = ? AND relation_memberid2 = ?) ";
		$sqlDelete .= "OR (relation_memberid2 = ? AND relation_memberid1 = ?) ";
		
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->execute(array($id, $_SESSION["id"], $id, $_SESSION["id"]));
		
		$_SESSION["flash"] = "Relationen har brytits.";
		$this->_redirect("/profile/" . $_SESSION["alias"] . "/relation");
	}
}
