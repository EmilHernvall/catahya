<?php
require_once 'Catahya/Controller/Action.php';

class Message_ThreadController extends Catahya_Controller_Action 
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
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$msgId = $request->get('id');
		if (!$msgId) {
			$this->_redirect('/message/index');
		}
		
		// Retrieve the message
		$sqlMessage  = 'SELECT * ';
		$sqlMessage .= 'FROM message_thread ';
		$sqlMessage .= 'WHERE thread_id = ? ';
		$sqlMessage .= 'ORDER BY thread_timestamp DESC';
		
		$stmtMessage = $db->prepare($sqlMessage);
		$stmtMessage->execute(array($msgId));
		
		$arrMessage = $stmtMessage->fetch(PDO::FETCH_ASSOC);
		
		// Check if the message exists
		if (!$arrMessage) {
			$this->_redirect('/message/index');	
		}
		
		// Retrieve members
		$sqlMembers  = "SELECT a.*, b.member_id, b.member_flatalias, b.member_alias ";
		$sqlMembers .= "FROM message_thread_member a ";
		$sqlMembers .= "INNER JOIN member b USING (member_id) ";
		$sqlMembers .= "WHERE thread_id = ? ";
		$sqlMembers .= "ORDER BY thread_role";
		
		$stmtMembers = $db->prepare($sqlMembers);
		$stmtMembers->execute(array($msgId));
		
		$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
		
		// Make sure that people aren't reading my messages
		$threadMember = array();
		foreach ($members as $member) {
			if ($member["member_id"] == $_SESSION["id"]) {
				$threadMember = $member;
			}
		}
		
		if (!$threadMember) {
			$this->_redirect('/message/index');	
		}
		
		// Get the folders
		$sqlFolders  = 'SELECT folder_id, folder_name, folder_type ';
		$sqlFolders .= 'FROM message_folder ';
		$sqlFolders .= 'WHERE member_id = ?';
		
		$stmtFolders = $db->prepare($sqlFolders);
		$stmtFolders->execute(array($_SESSION['id']));
		
		$folders = $stmtFolders->fetchAll(PDO::FETCH_ASSOC);
		
		// Fetch any replies
		$sqlReplies  = 'SELECT a.*, b.member_id, b.member_flatalias, b.member_alias ';
		$sqlReplies .= 'FROM message_reply a ';
		$sqlReplies .= 'INNER JOIN member b USING (member_id) ';
		$sqlReplies .= 'WHERE thread_id = ? ';
		$sqlReplies .= 'ORDER BY reply_timestamp';
		
		$stmtReplies = $db->prepare($sqlReplies);
		$stmtReplies->execute(array($arrMessage['thread_id']));
		
		$replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);
		
		// Set the thread as read
		if ($threadMember["thread_read"] == "0") {
			$sqlSetRead  = 'UPDATE message_thread_member ';
			$sqlSetRead .= 'SET thread_read = "1" ';
			$sqlSetRead .= 'WHERE thread_id = ? AND member_id = ? ';
			$stmtUpdate = $db->prepare($sqlSetRead);
			$stmtUpdate->execute(array($msgId, $_SESSION["id"]));
		}
		
		$this->_view->message = $arrMessage;
		$this->_view->members = $members;
		$this->_view->folders = $folders;
		$this->_view->replies = $replies;
		$this->compile('thread.phtml');
	}
	
	public function writeAction()
	{
		$request = $this->getRequest();
		$to = $request->to;
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$this->_view->to = $to;
		$this->compile('thread_write.phtml');
	}
	
	public function writeCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$recv = $request->get('recv');
		$title = $request->get('title');
		$text = $request->get('text');
		
		$msgid = md5($recv.$title.$text);
		
		if (isset($_SESSION['msgid']) && $_SESSION['msgid'] == $msgid) {
			$_SESSION['flash'] = 'Detta meddelande är identiskt med det förra du skickade, och har därför blockerats!';
			$this->_redirect('/message/index');
		}
		
		$db->beginTransaction();
		
		// Store form data and validate it
		$recv = explode(',', $recv);
		
		// Validate receivers and distinguish between id:s and alias:es
		$recv_id = $recv_alias = array('null');
		foreach($recv as $member) {	
			$member = trim($member);
			
			if ($member == $_SESSION['alias'] || $member == $_SESSION['id']) {
				continue;
			}
			
			if (is_numeric($member)) { 
				$recv_id[] = intval($member); 
			} else { 
				$recv_alias[] = $db->quote($member); 
			}
		}
		
		// Check if the members specified are present in the database, and grab their inbox id, while we're at it
		$sqlValidate  = 'SELECT a.member_id, a.member_alias, b.folder_id ';
		$sqlValidate .= 'FROM member a ';
		$sqlValidate .= 'LEFT JOIN message_folder b ON a.member_id = b.member_id AND b.folder_type = "system" ';
		$sqlValidate .= 'WHERE (a.member_id IN ('.implode(',',$recv_id).') '
		              . 'OR a.member_alias IN ('.implode(',',$recv_alias).')) ';
		              
		$stmtValidate = $db->prepare($sqlValidate);
		$stmtValidate->execute();
		
		$arrValidate = $stmtValidate->fetchAll(PDO::FETCH_ASSOC);
		
		// If our query doesn't return any valid receiptants, we redirect the user back to the write page
		if (!count($arrValidate)) {
			$_SESSION['flash'] = 'Inga giltiga mottagare.';
		
		    $this->_redirect('/message/thread/write');
		}
		
		foreach ($arrValidate as &$member) {
			if ($member["folder_id"] == NULL) {
				$sqlInsertSystemFolder  = "INSERT INTO message_folder (member_id, folder_name, folder_type) ";
				$sqlInsertSystemFolder .= "VALUES (?, 'Inbox', 'system')";
				
				$stmt = $db->prepare($sqlInsertSystemFolder);
				$stmt->execute(array($member["member_id"]));
				
				$member["folder_id"] = $db->lastInsertId();
			}
		}
		
		// Grab the senders outbox id
		$sqlOutbox  = 'SELECT folder_id ';
		$sqlOutbox .= 'FROM message_folder ';
		$sqlOutbox .= 'WHERE member_id = ? AND folder_type = "system"';
		$stmtOutboxId = $db->prepare($sqlOutbox);
		$stmtOutboxId->execute(array($_SESSION['id']));
		
		$outboxId = $stmtOutboxId->fetchColumn(0);
		
		// create folder if necessary
		if (!$outboxId) {
			$sqlInsertSystemFolder  = "INSERT INTO message_folder (member_id, folder_name, folder_type) ";
			$sqlInsertSystemFolder .= "VALUES (?, 'Inbox', 'system')";
			
			$stmt = $db->prepare($sqlInsertSystemFolder);
			$stmt->execute(array($_SESSION["id"]));
			
			$outboxId = $db->lastInsertId();
		}
		
		// Save thread
		$sqlThread  = 'INSERT INTO message_thread (thread_title, thread_text, thread_timestamp, thread_rcount) ';
		$sqlThread .= 'VALUES (?, ?, unix_timestamp(), ?)';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($title, $text, count($arrValidate)));
		
		$threadId = $db->lastInsertID();
		
		// Prepare the query used to store members to db
		$sqlThreadMember  = "INSERT INTO message_thread_member (thread_id, member_id, folder_id, thread_role, "
		                  . "thread_read, thread_deleted, thread_lasttimestamp) ";
		$sqlThreadMember .= "VALUES (?, ?, ?, ?, ?, '0', unix_timestamp())";
		
		$stmtThreadMember = $db->prepare($sqlThreadMember);
		
		// Store sender
		$stmtThreadMember->execute(array($threadId, $_SESSION["id"], $outboxId, 's', "1"));
		
		// Variable to inform the user about which of the receiptants actually received the message
		$sentto = array();
		
		// Iterate through the receiptants we previously retrieved
		foreach($arrValidate as $arrMember) {
			$stmtThreadMember->execute(array($threadId, $arrMember['member_id'], 
				$arrMember['folder_id'], 'r', "0"));
			
			$sentto[] = $arrMember['member_alias'];
		}
		
		$db->commit();
		
		$msg  = 'Meddelandet skickades utan problem till följande personer:<br />';
		$msg .= implode(', ', $sentto);
		
		$_SESSION['msgid'] = $msgid;
		
		$_SESSION['flash'] = $msg;
		$this->_redirect('/message/index');
	}
	
	public function replyCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->get('threadid');
		if (!$threadId) {
			$this->_redirect('/message/index');
		}
		
		$sqlThread = 'SELECT * FROM message_thread WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$arrThread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		$stmtThread->closeCursor();
		
		if (!$arrThread) {
			$this->_redirect('/message/index');
		}
		
		$text = trim($request->get('text'));
		
		// Prepare the query used to store receiptants to db
		$sqlInsertReply  = 'INSERT INTO message_reply (thread_id, '
		                 . 'member_id, reply_timestamp, reply_text) ';
		$sqlInsertReply .= 'VALUES (?, ?, unix_timestamp(), ?);';
		
		$stmtInsertReply = $db->prepare($sqlInsertReply);
		$stmtInsertReply->execute(array($arrThread['thread_id'], $_SESSION['id'], $text));
		
		// Set the thread as unread
		$sqlSetUnread  = 'UPDATE message_thread_member SET thread_read = "0", ';
		$sqlSetUnread .= 'thread_deleted = "0", thread_lasttimestamp = unix_timestamp() ';
		$sqlSetUnread .= 'WHERE thread_id = ? AND member_id != ?';
			
		$stmtUpdate = $db->prepare($sqlSetUnread);
		$stmtUpdate->execute(array($arrThread['thread_id'], $_SESSION["id"]));
		
		$_SESSION['flash'] = 'Meddelandet har skickats.';		
		$this->_redirect('/message/thread?id='.$arrThread['thread_id']);
	}
}
