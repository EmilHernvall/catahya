<?php
require_once 'Catahya/Controller/Action.php';

class TemplateController extends Catahya_Controller_Action 
{
	public function compile($template, $layout = 'layout.phtml') 
	{
		$db = Zend_Registry::get('db');
		
		$sqlForums  = 'SELECT * FROM forum ';
		$sqlForums .= 'INNER JOIN forum_category USING (category_id) ';
		$sqlForums .= 'LEFT JOIN member ON member_id = forum_lastmemberid ';
		$sqlForums .= 'LEFT JOIN access USING (access_id) ';
		$sqlForums .= 'WHERE guild_id = 0 ';
		$sqlForums .= 'ORDER BY category_name, forum_name ';
		$stmtForums = $db->prepare($sqlForums);
		$stmtForums->execute();

		$result = $stmtForums->fetchAll(PDO::FETCH_ASSOC);
		
		$forums = array();
		foreach ($result as $forum) {
			if ($forum['access_id'] &&
			    !Catahya_Access::hasPermission($forum['access_id'], 
			                                   Catahya_Permission_Forum::VIEW, 
			                                   $forum['access_defaultpermission'])) {

				continue;
			}
			
			$forums[] = $forum;
		}

		$guildForums = array();
		if ($_SESSION["online"]) {
			$sqlSelect  = 'SELECT forum_id, forum_name, guild_member.guild_id, guild_name FROM `guild_member` ';
			$sqlSelect .= 'INNER JOIN guild USING (guild_id) ';
			$sqlSelect .= 'INNER JOIN guild_level USING (level_id) ';
			$sqlSelect .= 'INNER JOIN forum ON forum.guild_id = guild_member.guild_id ';
			$sqlSelect .= 'WHERE guild_member.member_id = ? ';
			$sqlSelect .= 'AND FIELD(forum_guildlevel, "member", "moderator", "admin") <= '
			            . 'FIELD(level_access, "member", "moderator", "admin") ';
			$sqlSelect .= 'ORDER BY guild_member.guild_id';

			$stmt = $db->prepare($sqlSelect);
			$stmt->execute(array($_SESSION["id"]));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$guildForums = array();
			foreach ($result as $row) {
				$guildForums[$row["guild_id"]]["name"] = $row["guild_name"];
				$guildForums[$row["guild_id"]]["forums"][$row["forum_id"]] = $row["forum_name"];
			}
		}
		
		$this->_view->menuForums = $forums;
		$this->_view->guildForums = $guildForums;
		$this->_view->pageMenu = $this->_view->render('menu.phtml');
		parent::compile($template, $layout);
	}
}
