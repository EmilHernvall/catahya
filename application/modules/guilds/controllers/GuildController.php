<?php
require_once "Catahya/Controller/Action.php";

/**
 * @todo Integration med behörighetssystemet
 * @todo Radera behörigheter
 */
class Guilds_GuildController extends Catahya_Controller_Action
{
	private $_guild = array();
	private $_guildMembership = array();

	private function _getGuild($id)
	{
		$db = Zend_Registry::get("db");

		$sqlGuild  = "SELECT * FROM guild ";
		$sqlGuild .= "WHERE guild_id = ? ";

		$stmt = $db->prepare($sqlGuild);
		$stmt->execute(array($id));

		$guild = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		return $guild;
	}

	private function _getMembership($guildId, $memberId)
	{
		$db = Zend_Registry::get("db");

		$sqlMember  = "SELECT * FROM guild_member ";
		$sqlMember .= "LEFT JOIN guild_level USING (level_id) ";
		$sqlMember .= "INNER JOIN member USING (member_id) ";
		$sqlMember .= "WHERE guild_member.guild_id = ? ";
		$sqlMember .= "AND guild_member.member_id = ?";

		$stmt = $db->prepare($sqlMember);
		$stmt->execute(array($guildId, $memberId));

		$member = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		return $member;
	}
	
	private function _insertHistory($guildId, $message)
	{
		$db = Zend_Registry::get("db");
	
		$sqlHistory  = "INSERT INTO guild_history (guild_id, history_timestamp, "
		             . "history_description) ";
		$sqlHistory .= "VALUES (?, unix_timestamp(), ?)";

		$stmt = $db->prepare($sqlHistory);
		$stmt->execute(array($guildId, $message));
	}
	
	private function _sendMessage($to, $from, $title, $text)
	{
		if ($to == $from) {
			return;
		}

		$db = Zend_Registry::get("db");

		$sqlFolder  = "SELECT * FROM message_folder ";
		$sqlFolder .= "WHERE member_id = ? "
		            . "AND folder_type = 'system'";

		$stmt = $db->prepare($sqlFolder);
		$stmt->execute(array($from));
		$sender = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		if ($sender == 0) {
			throw new Exception("No sender folder.");
		}
		
		$stmt = $db->prepare($sqlFolder);
		$stmt->execute(array($to));
		$receiver = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		
		if ($receiver == 0) {
			throw new Exception("No receiver folder.");
		}

		$sqlMessage  = "INSERT INTO message_thread ( "
		             . "thread_timestamp, thread_title, "
		             . "thread_text, thread_rcount) ";
		$sqlMessage .= "VALUES (unix_timestamp(), ?, ?, 1)";

		$stmt = $db->prepare($sqlMessage);
		$stmt->execute(array($title, $text));
		
		$threadId = $db->lastInsertId();
		
		$sqlThreadMember  = "INSERT INTO message_thread_member (thread_id, "
		                  . "member_id, folder_id, thread_role, thread_read, "
						  . "thread_deleted, thread_lasttimestamp) ";
		$sqlThreadMember .= "VALUES (?, ?, ?, ?, ?, '0', unix_timestamp());";
		
		$stmtThreadMember = $db->prepare($sqlThreadMember);
		$stmtThreadMember->execute(array($threadId, $from, $sender["folder_id"], 's', '1'));
		$stmtThreadMember->execute(array($threadId, $to, $receiver["folder_id"], 'r', '0'));
	}
    
    public function compile($template, $layout = 'layout.phtml')
    {
		$db = Zend_Registry::get("db");
	
		$sqlForums  = "SELECT * FROM forum ";
		$sqlForums .= "WHERE guild_id = ?";
		
		$stmt = $db->prepare($sqlForums);
		$stmt->execute(array($this->_view->id));
		
		$forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$db = Zend_Registry::get("db");
	
		$myGuilds = array();
		if ($_SESSION["online"]) {
			$sqlGuilds  = "SELECT * FROM guild_member ";
			$sqlGuilds .= "INNER JOIN guild USING (guild_id) ";
			$sqlGuilds .= "WHERE guild_member.member_id = ? ";
			$sqlGuilds .= "ORDER BY guild_name";

			$stmt = $db->prepare($sqlGuilds);
			$stmt->execute(array($_SESSION["id"]));

			$myGuilds = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	
		$this->_view->myGuilds = $myGuilds;
		$this->_view->forums = $forums;
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

	public function IndexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$sqlHistory  = "SELECT * FROM guild_history ";
		$sqlHistory .= "WHERE guild_id = ? ";
		$sqlHistory .= "ORDER BY history_timestamp DESC ";
		$sqlHistory .= "LIMIT 30";

		$stmt = $db->prepare($sqlHistory);
		$stmt->execute(array($guild["guild_id"]));

		$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$sqlMembers  = "SELECT *, FIELD(level_access, 'member', 'moderator', 'admin') sortorder FROM guild_member ";
		$sqlMembers .= "LEFT JOIN guild_level USING (level_id) ";
		$sqlMembers .= "INNER JOIN member USING (member_id) ";
		$sqlMembers .= "WHERE guild_member.guild_id = ? ";
		$sqlMembers .= "ORDER BY sortorder DESC, level_id, member_alias ASC";

		$stmt = $db->prepare($sqlMembers);
		$stmt->execute(array($guild["guild_id"]));

		$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$member = array();
		if ($_SESSION["online"]) {
			$member = $this->_getMembership($guildId, $_SESSION["id"]);
		}

		$this->_view->id = $request->id;
		$this->_view->guild = $guild;
		$this->_view->entries = $entries;
		$this->_view->members = $members;
		$this->_view->member = $member;
		$this->compile("guild_index.phtml");
	}

	public function RequestAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->compile("guild_request.phtml");
	}

	public function RequestCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$statement = trim($request->statement);

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$sqlMember  = "INSERT INTO guild_member (guild_id, member_id, level_id, "
		            . "member_guildtimestamp, member_guildstatement) ";
		$sqlMember .= "VALUES (?, ?, 0, 0, ?)";

		$stmt = $db->prepare($sqlMember);
		$stmt->execute(array($guildId, $_SESSION["id"], $statement));
		
		$this->_redirect("/guilds/" . $guildId);
	}

	public function MembersAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlMembers  = "SELECT * FROM guild_member ";
		$sqlMembers .= "LEFT JOIN guild_level USING (level_id) ";
		$sqlMembers .= "INNER JOIN member USING (member_id) ";
		$sqlMembers .= "WHERE guild_member.guild_id = ?";
		$sqlMembers .= "ORDER BY member_alias";

		$stmt = $db->prepare($sqlMembers);
		$stmt->execute(array($guild["guild_id"]));

		$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$sqlPending  = "SELECT * FROM guild_member ";
		$sqlPending .= "INNER JOIN member USING (member_id) ";
		$sqlPending .= "WHERE guild_id = ? AND member_guildtimestamp = 0 ";

		$stmt = $db->prepare($sqlPending);
		$stmt->execute(array($guildId));

		$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$sqlLevels  = "SELECT * FROM guild_level ";
		$sqlLevels .= "WHERE guild_id = ?";

		$stmt = $db->prepare($sqlLevels);
		$stmt->execute(array($guildId));

		$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->_view->members = $members;
		$this->_view->pending = $pending;
		$this->_view->levels = $levels;
		$this->compile("guild_members.phtml");
	}

	public function ConfirmCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$memberId = $request->memberid;
		$do = $request->do;
		$levelId = intval($request->level);

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}
		
		$db->beginTransaction();

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$title = "";
		$text = "";
		if ($do == "accept") {
			$sqlConfirm  = "UPDATE guild_member ";
			$sqlConfirm .= "SET level_id = ?, member_guildtimestamp = unix_timestamp() ";
			$sqlConfirm .= "WHERE guild_id = ? AND member_id = ?";

			$stmt = $db->prepare($sqlConfirm);
			$stmt->execute(array($levelId, $guildId, $memberId));
			
			$sqlMember  = "SELECT * FROM member ";
			$sqlMember .= "WHERE member_id = ?";
			
			$stmt = $db->prepare($sqlMember);
			$stmt->execute(array($memberId));
			
			$member = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt->closeCursor();
			
			$message = $member["member_alias"] . " antogs som medlem.";
			$this->_insertHistory($guildId, $message);
			
			$title = "Din ansökan till " . $guild["guild_name"] . " har beviljats";
			$text = "Välkommen som medlem!";
		} else {
			$sqlDelete  = "DELETE FROM guild_member ";
			$sqlDelete .= "WHERE guild_id = ? AND member_id = ?";

			$stmt = $db->prepare($sqlDelete);
			$stmt->execute(array($guildId, $memberId));
			
			$title = "Din ansökan till " . $guild["guild_name"] . " har avslagits";
			$text = "Tyvärr måste vi meddela att din medlemsansökan har avslagits.";
		}
		
		$this->_sendMessage($memberId, $_SESSION["id"], $title, $text);
		
		$db->commit();
		
		$this->_redirect("/guilds/" . $guildId . "/members");
	}
	
	public function RejectCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$memberId = $request->memberid;

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}
		
		$db->beginTransaction();

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlDelete  = "DELETE FROM guild_member ";
		$sqlDelete .= "WHERE guild_id = ? AND member_id = ?";

		$stmt = $db->prepare($sqlDelete);
		$stmt->execute(array($guildId, $memberId));
		
		$title = "Ditt medlemskap i " . $guild["guild_name"] . " har upphört";
		$text = "Du är nu inte längre medlem.";
		
		$this->_sendMessage($memberId, $_SESSION["id"], $title, $text);
		
		$db->commit();
		
		$_SESSION["flash"] = "Utkastandet är slutfört.";
		$this->_redirect("/guilds/" . $guildId . "/members");
	}

	public function LevelAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlLevels  = "SELECT * FROM guild_level ";
		$sqlLevels .= "LEFT JOIN `group` USING (group_id) ";
		$sqlLevels .= "WHERE guild_id = ?";

		$stmt = $db->prepare($sqlLevels);
		$stmt->execute(array($guildId));

		$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$sqlGroups  = "SELECT * FROM `group`";
		
		$stmt = $db->prepare($sqlGroups);
		$stmt->execute();
		
		$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->_view->levels = $levels;
		$this->_view->groups = $groups;
		$this->compile("guild_level.phtml");
	}

	public function LevelAddCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$name = trim($request->name);
		$access = trim($request->access);

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlLevel  = "INSERT INTO guild_level (guild_id, group_id, "
		           . "level_name, level_access) ";
		$sqlLevel .= "VALUES (?, 0, ?, ?)";

		$stmt = $db->prepare($sqlLevel);
		$stmt->execute(array($guildId, $name, $access)); 
		
		$this->_redirect("/guilds/" . $guildId . "/level");
	}
	
	public function LevelEditAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$levelId = $request->levelid;
		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlLevels  = "SELECT * FROM guild_level ";
		$sqlLevels .= "WHERE level_id = ?";

		$stmt = $db->prepare($sqlLevels);
		$stmt->execute(array($levelId));

		$level = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$sqlGroups  = "SELECT * FROM `group`";
		
		$stmt = $db->prepare($sqlGroups);
		$stmt->execute();
		
		$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->_view->level = $level;
		$this->_view->groups = $groups;
		$this->compile("guild_level_edit.phtml");
	}
	
	public function LevelEditCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$levelId = $request->levelid;
		$name = $request->name;
		$access = $request->access;
		$groupId = $request->group;

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$params = array();
		
		$sqlUpdate  = "UPDATE guild_level ";
		$sqlUpdate .= "SET ";
		if ($groupId != "") {
			$sqlUpdate .= "group_id = ?, ";
			$params[] = $groupId;
		}
		$sqlUpdate .= "level_name = ?, level_access = ? ";
		$sqlUpdate .= "WHERE level_id = ?";

		$params[] = $name;
		$params[] = $access;
		$params[] = $levelId;
		
		$stmt = $db->prepare($sqlUpdate);
		$stmt->execute($params);
		
		$member = $this->_getMembership($guildId, $memberId);
		
		$_SESSION["flash"] = "Behörigheten har ändrats.";
		$this->_redirect("/guilds/" . $guildId . "/level");
	}

	public function ChangeLevelAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->id);
		$memberId = intval($request->memberid);

		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlLevels  = "SELECT * FROM guild_level ";
		$sqlLevels .= "WHERE guild_id = ?";

		$stmt = $db->prepare($sqlLevels);
		$stmt->execute(array($guildId));

		$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$sqlMember  = "SELECT * FROM guild_member ";
		$sqlMember .= "INNER JOIN member USING (member_id) ";
		$sqlMember .= "WHERE guild_id = ? AND member_id = ?";

		$stmt = $db->prepare($sqlMember);
		$stmt->execute(array($guildId, $memberId));

		$thisMember = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->_view->thisMember = $thisMember;
		$this->_view->levels = $levels;
		$this->compile("guild_changelevel.phtml");
	}

	public function ChangeLevelCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$memberId = $request->memberid;
		$access = trim($request->access);

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlUpdate  = "UPDATE guild_member ";
		$sqlUpdate .= "SET level_id = ? ";
		$sqlUpdate .= "WHERE guild_id = ? AND member_id = ? ";

		$stmt = $db->prepare($sqlUpdate);
		$stmt->execute(array($access, $guildId, $memberId));
		
		$member = $this->_getMembership($guildId, $memberId);
		
		$message = $member["member_alias"] . " har nu titeln " . $member["level_name"] . ".";
		$this->_insertHistory($guildId, $message);
		
		$this->_redirect("/guilds/" . $guildId . "/members");
	}
	
	public function ForumAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->id);

		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->compile("guild_forum.phtml");
	}
	
	public function ForumCreateCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = $request->id;
		$name = trim($request->name);
		$description = trim($request->description);
		$level = $request->level;

		if (!array_key_exists("id", $_SESSION)) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlInsert  = "INSERT INTO forum (guild_id, forum_name, forum_description, forum_guildlevel) ";
		$sqlInsert .= "VALUES (?, ?, ?, ?)";
		
		$stmt = $db->prepare($sqlInsert);
		$stmt->execute(array($guildId, $name, $description, $level));
		
		$this->_redirect("/guilds/" . $guildId . "/forum");
	}
	
    public function ForumEditAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$guildId = $request->id;
    	$forumId = $request->forumid;
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$sqlForum  = 'SELECT * FROM forum ';
		$sqlForum .= 'WHERE forum_id = ? ';
		
		$stmtForum = $db->prepare($sqlForum);
		$stmtForum->execute(array($forumId));
		
		$forum = $stmtForum->fetch(PDO::FETCH_ASSOC);
		
		$stmtForum->closeCursor();
    	
    	$this->_view->forum = $forum;
    	$this->compile('guild_forum_edit.phtml');
    }
    
    public function ForumEditCommitAction()
    {
		$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$forumId = $request->forumid;
    	$name = $request->name;
    	$description = $request->description;
    	$level = $request->level;
		
		$sqlForum  = "SELECT * FROM forum ";
		$sqlForum .= "INNER JOIN guild USING (guild_id) ";
		$sqlForum .= "WHERE forum_id = ?";
		
		$stmtForum = $db->prepare($sqlForum);
		$stmtForum->execute(array($forumId));
		
		$forum = $stmtForum->fetch(PDO::FETCH_ASSOC);
		if (!$forum) {
			$this->_redirect("/");
		}
    	
		$sqlUpdate  = 'UPDATE forum SET forum_name = ?, '
					. 'forum_description = ?, forum_guildlevel = ? ';
		$sqlUpdate .= 'WHERE forum_id = ?';
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($name, $description, $level, $forumId));
			
		$_SESSION['flash'] = 'Forumet har uppdaterats.';
    	$this->_redirect(sprintf('/guilds/%d/forum', $forum["guild_id"]));
    }
	
    public function ForumDeleteCommitAction()
    {
		$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$forumId = $request->forumid;
    	$name = $request->name;
    	$description = $request->description;
    	$level = $request->level;
		
		$sqlForum  = "SELECT * FROM forum ";
		$sqlForum .= "INNER JOIN guild USING (guild_id) ";
		$sqlForum .= "WHERE forum_id = ?";
		
		$stmtForum = $db->prepare($sqlForum);
		$stmtForum->execute(array($forumId));
		
		$forum = $stmtForum->fetch(PDO::FETCH_ASSOC);
		if (!$forum) {
			$this->_redirect("/");
		}
		
		$db->beginTransaction();
		
		$sqlDeleteReplies  = 'DELETE FROM forum_reply ';
		$sqlDeleteReplies .= 'WHERE thread_id = '
		                   . '(SELECT thread_id FROM forum_thread '
		                   . 'WHERE forum_id = ?)';
		
		$stmt = $db->prepare($sqlDeleteReplies);
		$stmt->execute(array($forumId));
		
		$sqlDeleteThreads  = 'DELETE FROM forum_thread ';
		$sqlDeleteThreads .= 'WHERE forum_id = ?';
		
		$stmt = $db->prepare($sqlDeleteThreads);
		$stmt->execute(array($forumId));
    	
		$sqlDeleteForum  = 'DELETE FROM forum ';
		$sqlDeleteForum .= 'WHERE forum_id = ?';
		
		$stmt = $db->prepare($sqlDeleteForum);
		$stmt->execute(array($forumId));
		
		$db->commit();
			
		$_SESSION['flash'] = 'Forumet har raderats.';
    	$this->_redirect(sprintf('/guilds/%d/forum', $forum["guild_id"]));
    }
	
	public function EditAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->id);

		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->compile("guild_edit.phtml");
	}
	
	public function EditCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->id);

		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}
		
		$description = cutSafe($request->description, 1024);
		$text = $request->text;
		$requirements = $request->requirements;
		
		$sqlUpdate  = "UPDATE guild SET guild_description = ?, "
		            . "guild_text = ?, guild_requirements = ? ";
		$sqlUpdate .= "WHERE guild_id = ? ";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($description, $text, 
			$requirements, $guildId));
			
		if (Catahya_Access::hasAccess("guild_admin")) {
			$type = $request->type;
		
			$sqlUpdate  = "UPDATE guild SET guild_type = ? ";
			$sqlUpdate .= "WHERE guild_id = ? ";
			
			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($type, $guildId));
		}
		
		$_SESSION["flash"] = "Ändringarna har sparats!";
		$this->_redirect(sprintf("/guilds/%d/edit", $guildId));
	}
	
	public function LogotypeAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->id);

		if (!$_SESSION["online"]) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}

		$member = $this->_getMembership($guildId, $_SESSION["id"]);
		if ($member["level_access"] != "admin" && !Catahya_Access::hasAccess("guild_admin")) {
			$this->_redirect("/guilds/" . $guildId);
		}

		$this->_view->id = $guildId;
		$this->_view->guild = $guild;
		$this->_view->member = $member;
		$this->compile("guild_logotype.phtml");
	}
	
	public function LogotypeCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$guildId = intval($request->id);
		
		$guild = $this->_getGuild($guildId);
		if (!$guild) {
			$this->_redirect("/guilds");
		}
		
		if (!array_key_exists("image", $_FILES)) {
			$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		$name = $_FILES["image"]["name"];
		$type = $_FILES["image"]["type"];
		$tmpName = $_FILES["image"]["tmp_name"];
		$error = $_FILES["image"]["error"];
		$size = $_FILES["image"]["size"];
		
		switch ($error) {
			case UPLOAD_ERR_INI_SIZE:
				$_SESSION["flash"] = "Du försökte ladda upp en för stor fil. (1)";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$_SESSION["flash"] = "Du försökte ladda upp en för stor fil. (2)";
				break;
			case UPLOAD_ERR_PARTIAL:
				$_SESSION["flash"] = "Uppladdningen avbröts.";
				break;
			case UPLOAD_ERR_NO_FILE:
				$_SESSION["flash"] = "Ingen fil angavs.";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$_SESSION["flash"] = "Serverfel. (1)";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$_SESSION["flash"] = "Serverfel. (1)";
				break;
			case UPLOAD_ERR_EXTENSION:
				$_SESSION["flash"] = "Serverfel. (1)";
				break;
		}
		
		if ($error != UPLOAD_ERR_OK) {
			$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		if ($size > 500*1024) {
			$_SESSION["flash"] = "Bilden är för stor!";
			$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		switch ($type) {
			case "image/jpeg":
			case "image/pjpeg":
				$image = @imagecreatefromjpeg($tmpName);
				break;
			case "image/png":
				$image = @imagecreatefrompng($tmpName);
				break;
			default:
				$_SESSION["flash"] = "Ogiltig filtyp!";
				$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		if (!$image) {
			$_SESSION["flash"] = "Ogiltig filtyp!";
			$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		$width = imagesx($image);
		$height = imagesy($image);
		
		if ($width < 100) {
			$_SESSION["flash"] = "Bilden är för liten!";
			$this->_redirect("/guilds/".$guildId."/logotype");
		}
		
		// Färdigvaliderat. Nu kör vi!
		$db->beginTransaction();
		
		$largePath = ROOT_PATH . "/public/userdata/guild/large/" . $guildId . ".jpg";
		$this->_scaleImage($image, min(600, $width), $largePath);
		
		$thumbPath = ROOT_PATH . "/public/userdata/guild/thumb/" . $guildId . ".jpg";
		$this->_scaleImage($image, 100, $thumbPath);

		$fullPath = ROOT_PATH . "/public/userdata/guild/fullsize/" . $guildId . ".jpg";
		imagejpeg($image, $fullPath, 90);
		
		@unlink($tmpName);
		
		$sqlUpdate  = "UPDATE guild SET guild_haslogo = '1' ";
		$sqlUpdate .= "WHERE guild_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($guildId));
		
		$db->commit();
		
		$_SESSION["flash"] = "Uppladdningen lyckades!";
		$this->_redirect("/guilds/".$guildId."/logotype");
	}
	
	protected function _scaleImage($img, $newWidth, $path)
	{
		$oldWidth = imagesx($img);
		$oldHeight = imagesy($img);
		
		$newHeight = $oldHeight / $oldWidth * $newWidth;
		
		$newImg = imagecreatetruecolor($newWidth, $newHeight);
		
		imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);
		
		imagejpeg($newImg, $path, 90);
	}
}
