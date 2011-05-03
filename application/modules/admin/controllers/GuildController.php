<?php

require_once 'Catahya/Controller/Action.php';

/**
 * @todo Radera gille.
 */
class Admin_GuildController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->section = 'administration';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }//function
    
    public function IndexAction() 
    {
		$db = Zend_Registry::get("db");

		$sqlSelect  = "SELECT * FROM guild ";
		$sqlSelect .= "INNER JOIN member USING (member_id) ";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute();

		$guilds = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->guilds = $guilds;
    	$this->compile('guild_index.phtml');
    }

	public function ConfirmCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->guildid);

		$sqlGuild = "SELECT * FROM guild WHERE guild_id = ?";

		$stmt = $db->prepare($sqlGuild);
		$stmt->execute(array($guildId));

		$guild = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$guild) {
			$_SESSION["flash"] = "Gillet existerar inte!";
			$this->_redirect("/admin/guild");
		}

		if ($guild["guild_confirmed"] > 0) {
			$_SESSION["flash"] = "Det här gillet är redan godk&auml;nt.";
			$this->_redirect("/admin/guild");
		}

		$db->beginTransaction();

		$sqlConfirm  = "UPDATE guild SET guild_confirmed = unix_timestamp(), "
		             . "guild_confirmedby = ? ";
		$sqlConfirm .= "WHERE guild_id = ?";

		$stmt = $db->prepare($sqlConfirm);
		$stmt->execute(array($_SESSION["id"], $guildId));

		$message = "Gillet godk&auml;ndes.";

		$sqlHistory  = "INSERT INTO guild_history (guild_id, history_timestamp, "
		             . "history_description) ";
		$sqlHistory .= "VALUES (?, unix_timestamp(), ?)";

		$stmt = $db->prepare($sqlHistory);
		$stmt->execute(array($guildId, $message));

		$sqlLevel  = "INSERT INTO guild_level (guild_id, group_id, level_name, level_access) ";
		$sqlLevel .= "VALUES (?, 0, ?, ?)";

		$stmt = $db->prepare($sqlLevel);
		$stmt->execute(array($guildId, "Grundare", "admin"));
		$levelId = $db->lastInsertId();
		$stmt->execute(array($guildId, "Medlem", "member"));

		$sqlMember  = "INSERT INTO guild_member (guild_id, member_id, level_id, member_guildtimestamp, member_guildstatement) ";
		$sqlMember .= "VALUES (?, ?, ?, unix_timestamp(), '')";

		$stmt = $db->prepare($sqlMember);
		$stmt->execute(array($guildId, $guild["member_id"], $levelId));

		$_SESSION["flash"] = "Gillet godkändes.";
		
		$title = "Ditt gille godk&auml;ndes";
		$text = "Jag godk&auml;nde precis ditt nya gille!";
		$this->_sendMessage($guild["member_id"], $_SESSION["id"], $title, $text);

		$db->commit();

		$this->_redirect("/admin/guild");
	}

	public function RejectCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$guildId = intval($request->guildid);

		$sqlGuild = "SELECT * FROM guild WHERE guild_id = ?";

		$stmt = $db->prepare($sqlGuild);
		$stmt->execute(array($guildId));

		$guild = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$guild) {
			$_SESSION["flash"] = "Gillet existerar inte!";
			$this->_redirect("/admin/guild");
		}

		if ($guild["guild_confirmed"] > 0) {
			$_SESSION["flash"] = "Det här gillet är redan godk&auml;nt.";
			$this->_redirect("/admin/guild");
		}

		$sqlDelete  = "DELETE FROM guild WHERE guild_id = ?";

		$stmt = $db->prepare($sqlDelete);
		$stmt->execute(array($guildId));

		$_SESSION["flash"] = "Gillet avslogs.";

		$title = "Ditt gille avslogs";
		$text = "Ditt f&ouml;rslag f&ouml;r ett nytt gille avslogs.";
		$this->_sendMessage($guild["member_id"], $_SESSION["id"], $title, $text);

		$this->_redirect("/admin/guild");
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
}
