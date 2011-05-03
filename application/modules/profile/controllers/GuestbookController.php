<?php
require_once 'Catahya/Controller/Action.php';

class Profile_GuestbookController extends Catahya_Controller_Action 
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
		$page = $request->has('pg') ? (int)$request->pg : 0;
		
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender, '
		            . 'member_age, member_photo, member_gbrecv ';
		$sqlMember .= 'FROM member ';
		$sqlMember .= 'INNER JOIN member_userdata USING (member_id) ';
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
		
		$perPage = 20;
		$total = ceil($arrMember['member_gbrecv']/$perPage);
		$page = $page > 0 ? $page : 1;
		
		$start = ($page-1) * $perPage;
		$start = $start > $arrMember['member_gbrecv'] - $perPage 
		         ? $arrMember['member_gbrecv'] - $perPage : $start;
		$start = $start < 0 ? 0 : $start;
		
		$sqlGB  = 'SELECT guestbook_id, guestbook_from, guestbook_to, guestbook_timestamp, '
		        . 'guestbook_msg, guestbook_secret, guestbook_read, guestbook_answered, '
		        . 'member_alias,member_flatalias,member_gender,member_age,member_online,member_photo, '
		        . 'member_quickdesc ';
		$sqlGB .= 'FROM member_guestbook ';
		$sqlGB .= 'LEFT JOIN member ON member_id = guestbook_from ';
		
		if ($arrMember['member_id'] == $_SESSION['id']) {
			$sqlGB .= 'WHERE guestbook_to = ? ';
			$sqlGB .= 'ORDER BY guestbook_id DESC ';
			$sqlGB .= sprintf('LIMIT %d, %d', $start, $perPage);
			
			$stmtGB = $db->prepare($sqlGB);
			$stmtGB->execute(array($_SESSION['id']));
		} else {
			$sqlGB .= 'WHERE guestbook_to = ? ';
			$sqlGB .= 'AND (guestbook_secret = "0" or guestbook_from = ?) ';
			$sqlGB .= 'ORDER BY guestbook_timestamp DESC ';
			$sqlGB .= sprintf('LIMIT %d, %d', $start, $perPage);
			
			$stmtGB = $db->prepare($sqlGB);
			$stmtGB->execute(array($arrMember['member_id'], $_SESSION['id']));
		}

		//var_dump($sqlGB);exit;
		
		if ($_SESSION['online']) {
			if ($_SESSION['id'] == $arrMember['member_id'] && !$request->has('noread')) {
				$sqlSetRead  = 'UPDATE member_guestbook ';
				$sqlSetRead .= 'SET guestbook_read = "1" ';
				$sqlSetRead .= 'WHERE guestbook_to = ? AND guestbook_read = "0"';
				$stmt = $db->prepare($sqlSetRead);
				$stmt->execute(array($arrMember['member_id']));
				
				$this->_view->status['gbcount'] = 0;
			}
		}
		
		$this->_view->member = $arrMember;
		$this->_view->totalPages = $total;
		$this->_view->currentPage = $page;
		/*for ( $i = $page < 5 ? 1 : $page - 4; 
		      $i < $page + 5 && $i <= $total; 
		      $i++ ) {
			$xml['pages']['nr'][] = $i;
		}*/
		
		$this->_view->posts = $stmtGB->fetchAll(PDO::FETCH_ASSOC);
		$this->_view->postCount = count($this->_view->posts);
		$this->_view->totalCount = $arrMember['member_gbrecv'];
		$this->compile('guestbook.phtml');
	}
	
	public function deleteCommitAction() 
	{
		if (!$_SESSION['online']) {
			$this->_redirect("/");
		}
		
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$postID = $request->has('gid') ? $request->gid : 0;
		$embedded = $request->embedded;
		
		if (!$postID) {
			$this->_redirect("/");
		}
		
		$db->beginTransaction();
		
		$sqlPost  = 'SELECT guestbook_id, guestbook_from, guestbook_to, '
		          . 'guestbook_timestamp, guestbook_msg, guestbook_secret, '
		          . 'guestbook_read, guestbook_answered ';
		$sqlPost .= 'FROM member_guestbook ';
		$sqlPost .= 'WHERE guestbook_id = ?';
		          
		$stmtPost = $db->prepare($sqlPost);
		$stmtPost->execute(array($postID));
		
		$post = $stmtPost->fetch(PDO::FETCH_ASSOC);
		
		$stmtPost->closeCursor();
		
		if (!$post) {
			$this->_redirect('/');
		}
		
		if ($post["guestbook_to"] != $_SESSION["id"] && 
			$post["guestbook_from"] != $_SESSION["id"]) {
		
			$this->_redirect(sprintf("/profile/%s/guestbook", $post["guestbook_to"]));
		}
		
		$sqlDelete  = "DELETE FROM member_guestbook ";
		$sqlDelete .= "WHERE guestbook_id = ?";
		
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->execute(array($postID));
		
		$sqlUpdateRecv  = "UPDATE member_userdata ";
		$sqlUpdateRecv .= "SET member_gbrecv = member_gbrecv - 1 ";
		$sqlUpdateRecv .= "WHERE member_id = ?";
		
		$stmtUpdateRecv = $db->prepare($sqlUpdateRecv);
		$stmtUpdateRecv->execute(array($post["guestbook_to"]));
		
		$sqlUpdateSent  = "UPDATE member_userdata ";
		$sqlUpdateSent .= "SET member_gbsent = member_gbsent - 1 ";
		$sqlUpdateSent .= "WHERE member_id = ?";
		
		$stmtUpdateSent = $db->prepare($sqlUpdateSent);
		$stmtUpdateSent->execute(array($post["guestbook_from"]));
		
		$db->commit();
		
		$this->_redirect(sprintf("/profile/%s/guestbook", $post["guestbook_to"]));
	}
	
	public function postAction() 
	{
		if (!$_SESSION['online']) {
			$this->_redirect("/");
		}
		
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$postID = $request->has('gid') ? $request->gid : 0;
		$embedded = $request->embedded;
		
		if (!$postID) {
			$this->_redirect("/");
		}
		
		$sqlPost  = 'SELECT guestbook_id, guestbook_from, guestbook_to, '
		          . 'guestbook_timestamp, guestbook_msg, guestbook_secret, '
		          . 'guestbook_read, guestbook_answered ';
		$sqlPost .= 'FROM member_guestbook ';
		$sqlPost .= 'WHERE guestbook_id = ?';
		          
		$stmtPost = $db->prepare($sqlPost);
		$stmtPost->execute(array($postID));
		
		$arrPost = $stmtPost->fetch(PDO::FETCH_ASSOC);
		
		$stmtPost->closeCursor();
		
		if (!$arrPost) {
			$this->_redirect('/');
		}
		
		$id = $arrPost["guestbook_from"];
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender, '
		            . 'member_age, member_city, member_photo, member_gbrecv ';
		$sqlMember .= 'FROM member ';
		$sqlMember .= 'INNER JOIN member_userdata USING (member_id) ';
		$sqlMember .= 'WHERE member_id = ?';
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		if (!$arrMember) {
			$this->_redirect('javascript:window.close()');
		}
		
		$this->_view->member = $arrMember;
		$this->_view->post = $arrPost;
		$this->_view->embedded = $embedded;
		if ($embedded) {
			$this->compile('guestbook_post.phtml', false);
		} else {
			$this->compile('guestbook_post.phtml');
		}
	}
	
	public function historyAction() 
	{
		if (!$_SESSION['online']) {
			$this->_redirect("/");
		}
		
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = $request->getParam('id');
		$postID = $request->has('gid') ? $request->gid : 0;
		
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender, '
		            . 'member_age, member_city, member_photo, member_gbrecv ';
		$sqlMember .= 'FROM member ';
		$sqlMember .= 'INNER JOIN member_userdata USING (member_id) ';
		$sqlMember .= 'WHERE member_id = ?';
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		if (!$arrMember) {
			$this->_redirect('/');
		}
		
		$sqlHistory  = 'SELECT guestbook_id, guestbook_from, guestbook_timestamp, '
		             . 'guestbook_msg, guestbook_secret, guestbook_read, '
		             . 'member_alias, member_flatalias, member_gender, member_age, member_city, '
		             . 'member_quickdesc, member_photo, member_online ';
		$sqlHistory .= 'FROM member_guestbook ';
		$sqlHistory .= 'INNER JOIN member ON guestbook_from = member_id ';
		$sqlHistory .= 'WHERE guestbook_to = ? AND guestbook_from = ? ';
		$sqlHistory .= 'UNION ';
		$sqlHistory .= 'SELECT guestbook_id, guestbook_from, guestbook_timestamp, '
		             . 'guestbook_msg, guestbook_secret, guestbook_read, '
		             . 'member_alias, member_flatalias, member_gender, member_age, member_city, '
		             . 'member_quickdesc, member_photo, member_online ';
		$sqlHistory .= 'FROM member_guestbook ';
		$sqlHistory .= 'INNER JOIN member ON guestbook_from = member_id ';
		$sqlHistory .= 'WHERE guestbook_to = ? AND guestbook_from = ? ';
		$sqlHistory .= 'ORDER BY guestbook_timestamp DESC ';
		$sqlHistory .= 'LIMIT 20';
		
		$stmtHistory = $db->prepare($sqlHistory);
		$stmtHistory->execute(array($_SESSION['id'],
		                            $arrMember['member_id'], 
		                            $arrMember['member_id'], 
		                            $_SESSION['id']));
		
		$arrPosts = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
		
		$stmtHistory->closeCursor();
		               
		$this->_view->postId = $postID;       
		$this->_view->member = $arrMember;
		$this->_view->posts = $arrPosts;
		$this->compile('guestbook_history.phtml');
	}
	
	public function postCommitAction() 
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		$id = $request->getParam('id');
		
		if (!$id) {
			$this->_redirect('/');
		}
		
		$sqlMember  = 'SELECT member_id, member_alias, member_flatalias, member_gender, '
		            . 'member_age, member_city, member_photo, member_gbrecv ';
		$sqlMember .= 'FROM member ';
		$sqlMember .= 'INNER JOIN member_userdata USING (member_id) ';
		if (is_numeric($id)) {
			$sqlMember .= 'WHERE member_id = ?';
		} else {
			$sqlMember .= 'WHERE member_alias = ?';
		}
		
		$stmtMember = $db->prepare($sqlMember);
		$stmtMember->execute(array($id));
		
		$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
		
		$stmtMember->closeCursor();
		
		if (!$arrMember) {
			$this->_redirect('/');
		}
		
		if (!$_SESSION['online']) {
			$this->_redirect('/profile/' . $arrMember['member_flatalias'] . '/guestbook');
		}
		
		$postId = $request->has('gid') ? $request->gid : 0;
		$msg = trim($request->msg);
		$secret = (int)$request->secret;
		
		// Prevent members from posting in their own guestbook
		if ($_SESSION['id'] == $id) {
			$_SESSION['flash'] = 'Pågrund av missbruk kan man inte längre skicka gästboksinlägg till sig själv.';
			$this->_redirect('/profile/' . $arrMember['member_flatalias'] . '/guestbook');
		}
		
		// Prevent spamming by making the members wait 10 seconds before reposting
		$sqlSelect  = 'SELECT guestbook_timestamp FROM member_guestbook ';
		$sqlSelect .= 'WHERE guestbook_to = ? AND guestbook_from = ? ';
		$sqlSelect .= 'ORDER BY guestbook_id DESC LIMIT 1';
		
		$stmtLast = $db->prepare($sqlSelect);
		$stmtLast->execute(array($arrMember['member_id'], $_SESSION['id']));
		
		$last = $stmtLast->fetchColumn(0);
		
		$stmtLast->closeCursor();
		
		if (time() - $last < 10 && $last !== FALSE) {
			$_SESSION['flash'] = 'Du måste vänta minst 10 sekunder innan du skickar ett till inlägg.';
			$this->_redirect('/profile/' . $arrMember['member_flatalias'] . '/guestbook');
		}
		
		// Add 1 post to the receiver/senders stats
		$sqlUserdata  = 'UPDATE member_userdata a, member_userdata b ';
		$sqlUserdata .= 'SET a.member_gbrecv = a.member_gbrecv + 1, '
		              . 'b.member_gbsent = b.member_gbsent + 1 ';
		$sqlUserdata .= 'WHERE a.member_id = ? AND b.member_id = ?';
		$stmt = $db->prepare($sqlUserdata);
		$stmt->execute(array($arrMember['member_id'], $_SESSION['id']));
		
		// Save the actual post
		$sqlInsert  = 'INSERT INTO member_guestbook (guestbook_from, guestbook_to, '
		            . 'guestbook_timestamp, guestbook_msg, guestbook_secret) ';
		$sqlInsert .= 'VALUES (?, ?, unix_timestamp(), ?, ?);';
		$stmt = $db->prepare($sqlInsert);
		$stmt->execute(array($_SESSION['id'], $arrMember['member_id'], $msg, $secret));
		
		// Mark previous post as answered, if there was any
		if ($stmt->rowCount() && $postId) {
			$sqlAnswer  = 'UPDATE member_guestbook ';
			$sqlAnswer .= 'SET guestbook_answered = "1" ';
			$sqlAnswer .= 'WHERE guestbook_id = ? AND guestbook_to = ?';
			$stmt = $db->prepare($sqlAnswer);
			$stmt->execute(array($postId, $_SESSION['id']));
		}
		
		$_SESSION['flash'] = 'Ditt inlägg har sparats.';
		
		// Close window or redirect?
		if (!$request->has('noredir')) {
			$this->_redirect('/profile/' . $arrMember['member_flatalias'] . '/guestbook');
		}

	}
}
