<?php
require_once "Catahya/Controller/Action.php";

class Guilds_IndexController extends Catahya_Controller_Action
{
    
    public function compile($template, $layout = 'layout.phtml')
    {
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
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$sqlGuilds  = "SELECT *, count(*) guild_membercount FROM guild ";
		$sqlGuilds .= "INNER JOIN guild_member USING (guild_id) ";
		$sqlGuilds .= "GROUP BY guild_id ";
		$sqlGuilds .= "ORDER BY guild_type, guild_name";

		$stmt = $db->prepare($sqlGuilds);
		$stmt->execute();

		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->guilds = $result;
        $this->compile('index_index.phtml');
    }

	public function CreateAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$this->compile('index_create.phtml');
	}

	public function createcommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$name = cutSafe($request->name, 100);
		$description = cutSafe($request->description, 1024);
		$requirements = $request->requirements;

		$sqlInsert  = "INSERT INTO guild (member_id, guild_name, guild_description, "
		            . "guild_requirements, guild_confirmed) ";
		$sqlInsert .= "VALUES (?, ?, ?, ?, 0)";

		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($_SESSION["id"], $name, $description, $requirements));

		$_SESSION['flash'] = 'Din ans&ouml;kan har skickats.';

		$this->_redirect("/guilds");
	}
	
}
