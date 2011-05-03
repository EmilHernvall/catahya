<?php
require_once 'Catahya/Controller/Action.php';

class Message_IndexController extends Catahya_Controller_Action 
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
		
		$folderId = (int)$request->getParam('folderid');
		$page = (int)$request->getParam('page');
		
		$folder = array();
		if (!$folderId) {
			$sqlFolder  = 'SELECT folder_id, folder_name, folder_type ';
			$sqlFolder .= 'FROM message_folder ';
			$sqlFolder .= 'WHERE member_id = ? AND folder_type = "system"';
			$stmtFolder = $db->prepare($sqlFolder);
			$stmtFolder->execute(array($_SESSION['id']));
			
			$folder = $stmtFolder->fetch(PDO::FETCH_ASSOC);
			
			$stmtFolder->closeCursor();
			
			if (!$folder) {
				$sqlInsertSystemFolder  = "INSERT INTO message_folder (member_id, folder_name, folder_type) ";
				$sqlInsertSystemFolder .= "VALUES (?, 'Inbox', 'system')";
				
				$stmt = $db->prepare($sqlInsertSystemFolder);
				$stmt->execute(array($_SESSION["id"]));
				
				$folder = array('folder_id' => $db->lastInsertId(), 'folder_name' => 'Inbox', 'folder_type' => 'system');
			}
		} else {
			$sqlFolder  = 'SELECT folder_id, folder_name, folder_type ';
			$sqlFolder .= 'FROM message_folder ';
			$sqlFolder .= 'WHERE folder_id = ?';
			$stmtFolder = $db->prepare($sqlFolder);
			$stmtFolder->execute(array($folderId));
			
			$folder = $stmtFolder->fetch(PDO::FETCH_ASSOC);
			
			$stmtFolder->closeCursor();
		}
		
		$sqlFolders = 'SELECT folder_id, folder_name, folder_type FROM message_folder WHERE member_id = ?';
		$stmtFolders = $db->prepare($sqlFolders);
		$stmtFolders->execute(array($_SESSION['id']));
		
		$folders = $stmtFolders->fetchAll(PDO::FETCH_ASSOC);
		
		$sqlPageCount  = "SELECT count(*) FROM message_thread_member ";
		$sqlPageCount .= "WHERE folder_id = ? AND thread_deleted = '0'";
		
		$stmtPageCount = $db->prepare($sqlPageCount);
		$stmtPageCount->execute(array($folder['folder_id']));
		
		$postCount = $stmtPageCount->fetchColumn(0);
		
		$stmtPageCount->closeCursor();
		
		$perPage = 100;
		$total = ceil($postCount/$perPage);
		$page = $page > 0 ? $page : 1;
		
		$start = ($page-1) * $perPage;
		$start = $start > $postCount - $perPage 
		         ? $postCount - $perPage : $start;
		$start = $start < 0 ? 0 : $start;
		
		$sqlMemberSubquery  = "SELECT group_concat(concat(member_id,',',"
		                    . "member_flatalias,',',member_alias) SEPARATOR ':') ";
		$sqlMemberSubquery .= "FROM message_thread_member b ";
		$sqlMemberSubquery .= "INNER JOIN member USING (member_id) ";
		$sqlMemberSubquery .= "WHERE b.thread_id = a.thread_id "
		                    . "AND b.thread_role = IF(a.thread_role = 's', 'r', 's')";
		
		$sqlSelect  = "SELECT thread_id, thread_role, thread_read, thread_title, "
		            . "thread_timestamp, thread_rcount, thread_lasttimestamp, "
					. "(".$sqlMemberSubquery.") members ";
		$sqlSelect .= "FROM message_thread_member a ";
		$sqlSelect .= "INNER JOIN message_thread USING (thread_id) ";
		$sqlSelect .= "WHERE folder_id = ? AND thread_deleted = '0' ";
		$sqlSelect .= "ORDER BY thread_lasttimestamp DESC ";
		$sqlSelect .= sprintf("LIMIT %d, %d", $start, $perPage);
		
		$stmtMessages = $db->prepare($sqlSelect);
		$stmtMessages->execute(array((int)$folder['folder_id']));

		$messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($messages as &$message) {
			$members = explode(":", $message["members"]);
			$members2 = array();
			foreach ($members as $member) {
				$split = explode(",", $member);
				if (count($split) != 3) {
					continue;
				}
				list($id, $flatAlias, $alias) = $split;
				$members2[] = array('member_id' => $id, 'member_flatalias' => $flatAlias, 
					'member_alias' => $alias);
			}
			$message["members"] = $members2;
		}
		
		//var_dump($messages);exit;
		
		$this->_view->folder = $folder;
		$this->_view->folders = $folders;
		$this->_view->messages = $messages;
		
		$this->_view->totalPages = $total;
		$this->_view->currentPage = $page;
		$this->_view->postCount = $postCount;
		
		$this->compile('index.phtml');
	}
		
	public function moveDeleteCommitAction()
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$marks = $request->get('mark');
		if (!$marks) {
			$this->_redirect('/message/index');
		}
		
		$type = $request->get('type');
		$newfolderid = (int)$request->get('newfolderid');
		$ids = implode(",", array_map("intval", array_keys($marks)));
		
		// Delete a thread
		if ($type == 'Ta bort') {

			$sqlUpdate  = 'UPDATE message_thread_member ';
			$sqlUpdate .= 'SET thread_deleted = "1", thread_read = "1" ';
			$sqlUpdate .= sprintf('WHERE thread_id IN (%s) AND member_id = ?', $ids);
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($_SESSION["id"]));

			$_SESSION['flash'] = 'Inläggen har raderats.';
			$this->_redirect('/message/index');
		} 
		
		// Move a thread
		elseif ($type == 'Flytta' && $newfolderid) {
			
			$sqlUpdate  = 'UPDATE message_thread_member ';
			$sqlUpdate .= 'SET folder_id = ? ';
			$sqlUpdate .= sprintf('WHERE thread_id IN (%s) AND member_id = ?', $ids);
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($newfolderid, $_SESSION["id"]));
			
			$_SESSION['flash'] = 'Inläggen har raderats.';
			$this->_redirect('/message/index');
		}

	}
}
