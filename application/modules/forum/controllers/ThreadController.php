<?php
require_once 'TemplateController.php';

class Forum_ThreadController extends TemplateController
{
	protected function _getGuildLevel($guildId, $forumLevel)
	{
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
	
		$db = Zend_Registry::get('db');
	
		$sqlGuildAccess  = "SELECT * FROM guild_member ";
		$sqlGuildAccess .= "INNER JOIN guild_level USING (level_id) ";
		$sqlGuildAccess .= "WHERE guild_member.guild_id = ? AND member_id = ?";
		
		$stmt = $db->prepare($sqlGuildAccess);
		$stmt->execute(array($guildId, $_SESSION["id"]));
		
		$guildAccess = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$stmt->closeCursor();
		
		if (!$guildAccess) {
			$this->_redirect("/forum");
		}
			
		$table = array('member' => 0, 'moderator' => 1, 'admin' => 2);
		if ($table[$forumLevel] > $table[$guildAccess["level_access"]]) {
			$this->_redirect("/forum");
		}
		
		return $guildAccess["level_access"];
	}

	public function indexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		$page = $request->get('page');
		$last = $request->get('last');
		
		$threadId = $request->get('threadid');
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN member USING (member_id) ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$arrThread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		if (!$arrThread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($arrThread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($arrThread["guild_id"], $arrThread["forum_guildlevel"]);
		} else {
			$viewPermission = Catahya_Access::hasPermission($arrThread['access_id'], 
				Catahya_Permission_Forum::VIEW, $arrThread['access_defaultpermission']);
			if (!$viewPermission) {
				$this->_redirect("/forum");
			}
		}
		
		if ($_SESSION["online"]) {
			$sqlRead  = "SELECT * FROM forum_read ";
			$sqlRead .= "WHERE thread_id = ? "
			          . "AND member_id = ?";
					  
			$stmtRead = $db->prepare($sqlRead);
			$stmtRead->execute(array($threadId, $_SESSION["id"]));
			
			$read = $stmtRead->fetch(PDO::FETCH_ASSOC);
			
			$stmtRead->closeCursor();
			
			if ($read["read_timestamp"] < $arrThread["thread_lasttimestamp"]) {
				$sqlReplace = "REPLACE INTO forum_read VALUES (?, ?, ?)";
				
				$stmtReplace = $db->prepare($sqlReplace);
				$stmtReplace->execute(array($threadId, $_SESSION["id"], time()));
			}
		}
		
		$pageSize = 50;
		$total = ceil($arrThread['thread_replycount']/$pageSize);
		$page = $page > 0 ? $page : 1;
		$page = $page > $total ? $total : $page;
		
		$start = ($page-1) * $pageSize;
		$start = $start > $arrThread['thread_replycount'] 
		         ? $arrThread['thread_replycount'] : $start;
		$start = $start < 0 ? 0 : $start;
		
		$sqlReplies  = 'SELECT reply_id, reply_timestamp, reply_text, reply_deleted, '
		             . 'member_id, member_alias, member_flatalias, member_photo ';
		$sqlReplies .= 'FROM forum_reply ';
		$sqlReplies .= 'LEFT JOIN member USING (member_id) ';
		$sqlReplies .= 'WHERE thread_id = ? ';
		$sqlReplies .= 'ORDER BY reply_timestamp ';
		$sqlReplies .= sprintf('LIMIT %d, %d', $start, $pageSize);

		$stmtReplies = $db->prepare($sqlReplies);
		$stmtReplies->execute(array($threadId));
		
		$arrReplies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);
		
		$this->_view->thread = $arrThread;
		$this->_view->replies = $arrReplies;
		$this->_view->pageCurrent = $page;
		$this->_view->pageTotal = $total;
		$this->_view->pageSize = $pageSize;
		$this->_view->last = $last;
		$this->_view->guildLevel = $guildLevel;
		$this->compile('thread.phtml');
	}
	
	public function postCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');

		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$forumId = (int)$request->get('forumid');
		$title = trim($request->get('title'));
		$text = trim($request->get('text'));
		
		if (strlen($title) == 0) {
			$this->_redirect("/forum/" . $forumId);
		}
		
		$sqlForum  = 'SELECT * FROM forum ';
		$sqlForum .= 'LEFT JOIN access USING (access_id) ';
		$sqlForum .= 'WHERE forum_id = ?';
		$stmtForum = $db->prepare($sqlForum);
		$stmtForum->execute(array($forumId));
		
		$arrForum = $stmtForum->fetch(PDO::FETCH_ASSOC);
		
		$stmtForum->closeCursor();
		
		if (!$arrForum) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($arrForum["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($arrForum["guild_id"], $arrForum["forum_guildlevel"]);
		} else {
			$writePermission = Catahya_Access::hasPermission($arrForum['access_id'], 
				Catahya_Permission_Forum::WRITE, $arrForum['access_defaultpermission']);
			if (!$writePermission) {
				$this->_redirect("/forum");
			}
		}
		
		$sqlInsert  = 'INSERT INTO forum_thread (forum_id, member_id,'
					. 'thread_timestamp, thread_title, thread_text, thread_lasttimestamp, '
					. 'thread_lastmemberid) ';
		$sqlInsert .= 'VALUES (?, ?, unix_timestamp(), ?, ?, unix_timestamp(), ?)';
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($forumId, $_SESSION['id'], $title, $text, $_SESSION["id"]));
		
		$threadId = $db->lastInsertId();
		
		$sqlUpdate  = 'UPDATE forum SET forum_threadcount = forum_threadcount + 1, '
					. 'forum_lastthreadid = ?, forum_lastmemberid = ?, '
					. 'forum_lasttimestamp = unix_timestamp() ';
		$sqlUpdate .= 'WHERE forum_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($threadId, $_SESSION['id'], $forumId));
		
		$cachePath = ROOT_PATH . "/cache/threads.cache";
		@unlink($cachePath);
		
		// Fixa så man kommer till tråden!
		$_SESSION['flash'] = 'Ditt inlägg har skapats!';
		$this->_redirect(sprintf('/forum/%d', $forumId));
	}
	
	public function PostReplyCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');

		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
	
		$threadId = $request->get('threadid');
		$text = trim($request->get('text'));
	
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN member USING (member_id) ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$arrThread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		$stmtThread->closeCursor();
		
		if (!$arrThread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($arrThread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($arrThread["guild_id"], $arrThread["forum_guildlevel"]);
		} else {
			$replyPermission = Catahya_Access::hasPermission($arrThread['access_id'], 
				Catahya_Permission_Forum::REPLY, $arrThread['access_defaultpermission']);
			if (!$replyPermission) {
				$this->_redirect('/forum/thread/' . $arrThread["thread_id"]);
			}
		}
		
		if ($arrThread["thread_locked"]) {
			$this->_redirect('/forum/thread/' . $arrThread["thread_id"]);
		}
		
		$sqlInsert  = 'INSERT INTO forum_reply (thread_id, member_id,'
					. 'reply_timestamp, reply_text) ';
		$sqlInsert .= 'VALUES (?, ?, unix_timestamp(), ?)';
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($threadId, $_SESSION['id'], $text));
		
		$replyId = $db->lastInsertId();
		
		$sqlUpdate  = 'UPDATE forum_thread SET thread_replycount = thread_replycount + 1, '
					. 'thread_lastmemberid = ?, thread_lasttimestamp = unix_timestamp() ';
		$sqlUpdate .= 'WHERE thread_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($_SESSION['id'], $threadId));
		
		$sqlUpdate  = 'UPDATE forum SET forum_replycount = forum_replycount + 1, '
					. 'forum_lastthreadid = ?, forum_lastmemberid = ?, '
					. 'forum_lasttimestamp = unix_timestamp() ';
		$sqlUpdate .= 'WHERE forum_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($threadId, $_SESSION['id'], $arrThread['forum_id']));
		
		$cachePath = ROOT_PATH . "/cache/threads.cache";
		@unlink($cachePath);
		
		$_SESSION['flash'] = 'Ditt inlägg har skapats!';
		$this->_redirect(sprintf('/forum/thread/%d', $threadId));
	}
	
	public function DeleteCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');

		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = (int)$request->get('threadid');
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$deletePermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::DELETE, $thread['access_defaultpermission']);
			if (!$deletePermission && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$sqlDeleteThread = "UPDATE forum_thread SET thread_deleted = '1' WHERE thread_id = ?";
		$stmtDeleteThread = $db->prepare($sqlDeleteThread);
		$stmtDeleteThread->execute(array($threadId));
		
		$this->_redirect("/forum/" . $thread["forum_id"]);
	}
	
	public function DeleteReplyCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');

		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$replyId = (int)$request->get('replyid');
		
		$sqlReply  = 'SELECT forum_reply.*, forum.*, access.* FROM forum_reply ';
		$sqlReply .= 'INNER JOIN forum_thread USING (thread_id) ';
		$sqlReply .= 'INNER JOIN forum USING (forum_id) ';
		$sqlReply .= 'LEFT JOIN access USING (access_id) ';
		$sqlReply .= 'WHERE reply_id = ?';
		$stmtReply = $db->prepare($sqlReply);
		$stmtReply->execute(array($replyId));
		
		$reply = $stmtReply->fetch(PDO::FETCH_ASSOC);
		if (!$reply) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($reply["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($reply["guild_id"], $reply["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$deletePermission = Catahya_Access::hasPermission($reply['access_id'], 
				Catahya_Permission_Forum::DELETE, $reply['access_defaultpermission']);
			if (!$deletePermission && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect("/forum/thread/" . $reply["thread_id"]);
			}
		}
		
		$sqlDeleteReply = "UPDATE forum_reply SET reply_deleted = '1' WHERE reply_id = ?";
		$stmtDeleteReply = $db->prepare($sqlDeleteReply);
		$stmtDeleteReply->execute(array($replyId));
		
		$this->_redirect("/forum/thread/" . $reply["thread_id"]);
	}
	
	public function EditAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->threadid;
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::EDIT, $thread['access_defaultpermission']);
			if (!$editPermission && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$this->_view->thread = $thread;
		$this->compile('edit.phtml');
	}
	
	public function EditCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->threadid;
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::EDIT, $thread['access_defaultpermission']);
			if (!$editPermission && $_SESSION["id"] != $thread["member_id"]) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$title = trim($request->title);
		$text = trim($request->text);
		
		$text .= "\r\n\r\n";
		$text .= "[i]Redigerad " . date("Y-m-d H:i") . " av " . $_SESSION["alias"] . ".[/i]";
		
		$sqlUpdate  = "UPDATE forum_thread ";
		$sqlUpdate .= "SET thread_title = ?, thread_text = ? ";
		$sqlUpdate .= "WHERE thread_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($title, $text, $threadId));
		
		$this->_redirect("/forum/thread/" . $threadId);
	}
	
	public function EditReplyAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$replyId = $request->replyid;
		if (!$replyId) {
			$this->_redirect('/forum');
		}
		
		$sqlReply  = 'SELECT forum_reply.*, forum.*, access.* FROM forum_reply ';
		$sqlReply .= 'INNER JOIN forum_thread USING (thread_id) ';
		$sqlReply .= 'INNER JOIN forum USING (forum_id) ';
		$sqlReply .= 'LEFT JOIN access USING (access_id) ';
		$sqlReply .= 'WHERE reply_id = ?';
		$stmtReply = $db->prepare($sqlReply);
		$stmtReply->execute(array($replyId));
		
		$reply = $stmtReply->fetch(PDO::FETCH_ASSOC);
		
		if (!$reply) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($reply["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($reply["guild_id"], $reply["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($reply['access_id'], 
				Catahya_Permission_Forum::EDIT, $reply['access_defaultpermission']);
			if (!$editPermission && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect("/forum/thread/" . $reply["thread_id"]);
			}
		}
		
		$this->_view->reply = $reply;
		$this->compile('editreply.phtml');
	}
	
	public function EditReplyCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$replyId = $request->replyid;
		if (!$replyId) {
			$this->_redirect('/forum');
		}
		
		$sqlReply  = 'SELECT forum_reply.*, forum.*, access.* FROM forum_reply ';
		$sqlReply .= 'INNER JOIN forum_thread USING (thread_id) ';
		$sqlReply .= 'INNER JOIN forum USING (forum_id) ';
		$sqlReply .= 'LEFT JOIN access USING (access_id) ';
		$sqlReply .= 'WHERE reply_id = ?';
		$stmtReply = $db->prepare($sqlReply);
		$stmtReply->execute(array($replyId));
		
		$reply = $stmtReply->fetch(PDO::FETCH_ASSOC);
		
		if (!$reply) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($reply["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($reply["guild_id"], $reply["forum_guildlevel"]);
			if ($guildLevel == "member" && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($reply['access_id'], 
				Catahya_Permission_Forum::EDIT, $reply['access_defaultpermission']);
			if (!$editPermission && $_SESSION["id"] != $reply["member_id"]) {
				$this->_redirect("/forum/thread/" . $reply["thread_id"]);
			}
		}
		
		$text = trim($request->text);
		
		$text .= "\r\n\r\n";
		$text .= "[i]Redigerad " . date("Y-m-d H:i") . " av " . $_SESSION["alias"] . ".[/i]";
		
		$sqlUpdate  = "UPDATE forum_reply ";
		$sqlUpdate .= "SET reply_text = ? ";
		$sqlUpdate .= "WHERE reply_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($text, $replyId));
		
		$this->_redirect("/forum/thread/".$reply["thread_id"]);
	}
	
	public function MoveAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->threadid;
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN member USING (member_id) ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member") {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::MOVE, $thread['access_defaultpermission']);
			if (!$editPermission) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$sqlForums  = 'SELECT forum.*, access.*, guild_member.level_id FROM forum ';
		$sqlForums .= 'LEFT JOIN access USING (access_id) ';
		$sqlForums .= 'LEFT JOIN guild_member ON forum.guild_id = guild_member.guild_id AND guild_member.member_id = ? ';
		$sqlForums .= 'ORDER BY forum.guild_id, category_id ';
		$stmtForums = $db->prepare($sqlForums);
		$stmtForums->execute(array($_SESSION["id"]));
		
		$forums = array();
		foreach ($stmtForums->fetchAll(PDO::FETCH_ASSOC) as $forum) {
			if ($forum['guild_id'] && !$forum['level_id']) {
				continue;
			}
			
			$permission = !Catahya_Access::hasPermission($forum['access_id'], 
				Catahya_Permission_Forum::VIEW, $forum['access_defaultpermission']);
			if ($forum['access_id'] && $permission) {

				continue;
			}
			
			$forums[$forum["forum_id"]] = $forum;
		}
		
		$this->_view->thread = $thread;
		$this->_view->forums = $forums;
		$this->compile('move.phtml');
	}
	
	public function MoveCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->threadid;
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member") {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::MOVE, $thread['access_defaultpermission']);
			if (!$editPermission) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$forumId = $request->forumid;
		
		$db->beginTransaction();
		
		$sqlUpdate  = "UPDATE forum_thread ";
		$sqlUpdate .= "SET forum_id = ? ";
		$sqlUpdate .= "WHERE thread_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($forumId, $threadId));
		
		$sqlUpdateForum  = "UPDATE forum ";
		$sqlUpdateForum .= "SET forum_threadcount = forum_threadcount + ?, "
		                 . "forum_replycount = forum_replycount + ? ";
		$sqlUpdateForum .= "WHERE forum_id = ?";
		
		$stmtUpdateForum = $db->prepare($sqlUpdateForum);
		
		// source
		$stmtUpdateForum->execute(array(-1, -$thread["thread_replycount"], $thread["forum_id"]));
		// target
		$stmtUpdateForum->execute(array(1, $thread["thread_replycount"], $forumId));
		
		$db->commit();
		
		$this->_redirect("/forum/thread/" . $threadId);
	}	
	
	public function FlagCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$threadId = $request->threadid;
		if (!$threadId) {
			$this->_redirect('/forum');
		}
		
		$sqlThread  = 'SELECT * FROM forum_thread ';
		$sqlThread .= 'INNER JOIN member USING (member_id) ';
		$sqlThread .= 'INNER JOIN forum USING (forum_id) ';
		$sqlThread .= 'LEFT JOIN access USING (access_id) ';
		$sqlThread .= 'WHERE thread_id = ?';
		$stmtThread = $db->prepare($sqlThread);
		$stmtThread->execute(array($threadId));
		
		$thread = $stmtThread->fetch(PDO::FETCH_ASSOC);
		
		if (!$thread) {
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($thread["guild_id"]) {
			$guildLevel = $this->_getGuildLevel($thread["guild_id"], $thread["forum_guildlevel"]);
			if ($guildLevel == "member") {
				$this->_redirect('/forum');
			}
		} else {
			$editPermission = Catahya_Access::hasPermission($thread['access_id'], 
				Catahya_Permission_Forum::EDIT, $thread['access_defaultpermission']);
			if (!$editPermission) {
				$this->_redirect("/forum/thread/" . $thread["thread_id"]);
			}
		}
		
		$flag = $request->flag;
		
		$sqlUpdate  = "UPDATE forum_thread ";
		if ($flag == "sticky") {
			$sqlUpdate .= "SET thread_sticky = IF(thread_sticky = '1', '0', '1') ";
		} else if ($flag == "locked") {
			$sqlUpdate .= "SET thread_locked = IF(thread_locked = '1', '0', '1') ";
		}
		$sqlUpdate .= "WHERE thread_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($threadId));
		
		$this->_redirect("/forum/thread/" . $threadId);
	}	
}
