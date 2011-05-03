<?php
require_once 'Catahya/Controller/Action.php';

class Profile_IndexController extends Catahya_Controller_Action 
{
	public function compile($template, $layout = 'layout.phtml') 
	{
		$this->_view->pageMenu = $this->_view->render('menu.phtml');
		parent::compile($template, $layout);
	}
	
	public function indexAction()
	{
		$db = Zend_Registry::get('db');
		
		$id = $this->getRequest()->getParam('id');
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sql  = 'SELECT member_id, member_alias, member_flatalias, member_gender, member_age, '
		      . 'member_status, member_photo, member_photostatus, member_city, '
		      . 'member_quickdesc, member_online, '
		
		      . 'member_visitstotal, member_lastlogin, member_memberdate, '
		      . 'member_logintotal, member_gbrecv, member_gbsent, '
		      . 'member_minonline, '
		
		      . 'member_name, member_email, '
		      . 'member_birthdate, member_jabber, member_msn, member_homepage,'
		      . 'member_note, member_presentation, '
		      . 'character_race, character_class, character_alignment ';
		
		$sql .= 'FROM member ';
		$sql .= 'INNER JOIN member_userdata USING (member_id) ';
		$sql .= 'INNER JOIN member_profile USING (member_id) ';
		$sql .= 'LEFT JOIN member_character USING (member_id) ';
		
		if (is_numeric($id)) {
			$sql .= 'WHERE member_id = ?';
		} else {
			$sql .= 'WHERE member_flatalias = ?';
		}
		
		$stmtMember = $db->prepare($sql);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();

		if (!$arrMember) {
		    /* Checks for a deleted member that wasn't found by the inner joins */
		    $sqldel = 'SELECT member_alias, member_status FROM member ';
		    if (is_numeric($id)) {
		        $sqldel .= ' WHERE member_id = ?';
		    } else {
		        $sqldel .= ' WHERE member_alias = ?';
		    }//if
		    $stmtdel = $db->prepare($sqldel);
		    $stmtdel->execute(array($id));
		    $arrDel = $stmtdel->fetch(PDO::FETCH_ASSOC);
		    $stmtdel->closeCursor();
		    if(!$arrDel) {
			   $_SESSION['flash'] = 'Det finns ingen medlem med detta namn/id!';
			   //$this->_redirect('/');
			   $flash = 1;
		    } else {
		       $_SESSION['flash'] = $arrDel['member_alias'].' '.$this->randomexit(); 
		       $flash = 1;
		    }//if
		}
		
		if ($arrMember['member_status'] == 'discontinued') {
			$_SESSION['flash'] = 'Denna medlemmen har valt att lämna oss.';
			//$this->_redirect('/');
			$flash = 1;
		} elseif ($arrMember['member_status'] == 'inactivated') {
			$_SESSION['flash'] = 'Denna medlem är avstängd.';
			//$this->_redirect('/');
			$flash = 1;
		}
		
		$_SESSION['lastvisit'] = array_key_exists('lastvisit', $_SESSION) ? $_SESSION['lastvisit'] : '0';
		if ($arrMember['member_id'] != $_SESSION['id'] && 
		    $_SESSION['lastvisit'] != $arrMember['member_id']) {
		    	
			$sqlInsert  = 'INSERT INTO member_profilevisit (member_id, '
			            . 'profilevisit_visitorid, profilevisit_timestamp) ';
			$sqlInsert .= 'VALUES (?, ?, UNIX_TIMESTAMP())';
			$stmt = $db->prepare($sqlInsert);
			$stmt->execute(array($arrMember['member_id'], $_SESSION['id']));
		}
		$_SESSION['lastvisit'] = $arrMember['member_id'];
		
		$relations = array();
		if ($_SESSION["online"] && $_SESSION["id"] != $arrMember["member_id"]) {
			$sqlRelation  = "SELECT relation_id ";
			$sqlRelation .= "FROM member_relation ";
			$sqlRelation .= "WHERE (relation_memberid1 = ? AND relation_memberid2 = ?) ";
			$sqlRelation .= "OR (relation_memberid2 = ? AND relation_memberid1 = ?)";
			
			$stmtRelation = $db->prepare($sqlRelation);
			$stmtRelation->execute(array($arrMember["member_id"], $_SESSION["id"], 
				$arrMember["member_id"], $_SESSION["id"]));
				
			$relations = $stmtRelation->fetchAll(PDO::FETCH_ASSOC);
		}
		
		$this->_view->member = $arrMember;
		$this->_view->hasRelation = $relations;
		$this->_view->showOptions = true;
		if(isset($flash) && $flash == 1) {
		    $this->compile('noprofile.phtml');
		} else {
		    $this->compile('profile.phtml');
		}//if
	}
	
	private function randomexit ()
	{
	    $arrMsg = array('blev styckad av en Nazgul.', 
	    'blev uppäten av en Trollock.',
	    'blev biten av en utsvulten vampyr.', 
	    'hade en dispyt med en drakes eldproduktion, och förlorade.',
	    'torterades till döds av en galen Aes Sedai.',
	    'blev ihjältrampad av en flock skenande hippogriffer.');
	    
	    shuffle($arrMsg);
	    return ($arrMsg[0]);
	}
}
