<?php
require_once 'TemplateController.php';

/**
 * @todo Lästa/olästa inlägg
 */
class Forum_ForumController extends TemplateController
{
	public function indexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		$forumId = $request->get('id');
		$page = $request->get('page');
		
		if (!$forumId) {
			$_SESSION['flash'] = 'forumid saknas.';
			$this->_redirect('/forum');
		}
		
		$sqlForum  = 'SELECT * FROM forum ';
		$sqlForum .= 'LEFT JOIN access USING (access_id) ';
		$sqlForum .= 'WHERE forum_id = ?';
		$stmtForum = $db->prepare($sqlForum);
		$stmtForum->execute(array($forumId));
		
		$arrForum = $stmtForum->fetch(PDO::FETCH_ASSOC);
		
		$stmtForum->closeCursor();
		
		if (!$arrForum) {
			$_SESSION['flash'] = 'Forumet existerar inte!';
			$this->_redirect('/forum');
		}
		
		$guildLevel = false;
		if ($arrForum["guild_id"]) {
			if (!$_SESSION["online"]) {
				$this->_redirect("/forum");
			}
		
			$sqlGuildAccess  = "SELECT * FROM guild_member ";
			$sqlGuildAccess .= "INNER JOIN guild_level USING (level_id) ";
			$sqlGuildAccess .= "WHERE guild_member.guild_id = ? AND member_id = ?";
			
			$stmt = $db->prepare($sqlGuildAccess);
			$stmt->execute(array($arrForum["guild_id"], $_SESSION["id"]));
			
			$guildAccess = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt->closeCursor();
			
			if (!$guildAccess) {
				$this->_redirect("/forum");
			}
			
			$table = array('member' => 0, 'moderator' => 1, 'admin' => 2);
			if ($table[$arrForum["forum_guildlevel"]] > $table[$guildAccess["level_access"]]) {
				$this->_redirect("/forum");
			}
			
			$guildLevel = $guildAccess["level_access"];
		} else {
			$viewPermission = Catahya_Access::hasPermission($arrForum['access_id'], 
				Catahya_Permission_Forum::VIEW, $arrForum['access_defaultpermission']);
			if (!$viewPermission) {
				$this->_redirect("/forum");
			}
		}
		
		$pageSize = 25;
		$total = ceil($arrForum['forum_threadcount']/$pageSize);
		$page = $page > 0 ? $page : 1;
		$page = $page > $total ? $total : $page;
		
		$start = ($page-1) * $pageSize;
		$start = $start > $arrForum['forum_threadcount'] 
		         ? $arrForum['forum_threadcount'] : $start;
		$start = $start < 0 ? 0 : $start;
		
		$params = array();
		$sqlThreads  = 'SELECT forum_thread.thread_id, thread_title, thread_replycount, '
                     . 'thread_lasttimestamp, thread_sticky, thread_locked, member.*, '
                     . 'thread_lastmemberid, b.member_alias as thread_lastalias, '
					 . 'b.member_flatalias as thread_lastflatalias ';
					 
		if ($_SESSION["online"]) { 
			$sqlThreads .= ', COALESCE(read_timestamp, 0) read_timestamp ';
		} else {
			$sqlThreads .= ', 0 read_timestamp ';
		}
		$sqlThreads .= 'FROM forum_thread ';
		$sqlThreads .= 'LEFT JOIN member USING (member_id) ';
		$sqlThreads .= 'LEFT JOIN member b ON thread_lastmemberid = b.member_id ';
		
		if ($_SESSION["online"]) {
			$sqlThreads .= 'LEFT JOIN forum_read ON forum_thread.thread_id = forum_read.thread_id '
			             . 'AND forum_read.member_id = ? ';
			$params[] = $_SESSION["id"];
		}
		
		$sqlThreads .= 'WHERE forum_id = ? AND thread_deleted = "0" ';
		$sqlThreads .= 'ORDER BY thread_sticky DESC, thread_lasttimestamp DESC ';
		$sqlThreads .= sprintf('LIMIT %d, %d', $start, $pageSize);
		
		$params[] = $forumId;
		$stmtThreads = $db->prepare($sqlThreads);
		$stmtThreads->execute($params);
		
		$arrThreads = $stmtThreads->fetchAll(PDO::FETCH_ASSOC);
		
		$this->_view->forum = $arrForum;
		$this->_view->threads = $arrThreads;
		$this->_view->pageTotal = $total;
		$this->_view->pageCurrent = $page;
		$this->_view->guildLevel = $guildLevel;
		$this->compile('forum.phtml');
	}
}
