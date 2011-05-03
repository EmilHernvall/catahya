<?php

require_once 'Zend/Controller/Action.php';

class Catahya_Controller_Action extends Zend_Controller_Action
{
	protected $_view = null;
	
	public function init() 
	{
		$db = Zend_Registry::get('db');
		$request = $this->getRequest();

		$this->_view = new Zend_View;
		$this->_view->addScriptPath(TEMPLATE_PATH);
		$this->_view->addScriptPath(ROOT_PATH . '/application/modules/' . 
			$request->getModuleName() . '/views');
		
		$this->_view->info = getInfo();
		if ($_SESSION['online']) {
			$sqlUpdate  = "UPDATE member_online SET member_active = unix_timestamp() ";
			if (!($request->getModuleName() == "default" && $request->getControllerName() == "index" 
				&& $request->getActionName() == "status")) {
				$sqlUpdate .= ", member_realactivity = unix_timestamp() ";
			}
			$sqlUpdate .= "WHERE member_id = ?";

			$stmt = $db->prepare($sqlUpdate);
			$stmt->execute(array($_SESSION["id"]));

			$this->_view->relations = getRelations();
			$this->_view->status = getStatus();
		}
		$this->_view->css = array();
		$this->_view->js = array();
		$this->_view->title = 'Catahya';
		$this->_view->navigation = array();

		$cachePath = ROOT_PATH . "/cache/threads.cache";
		if (!file_exists($cachePath)) {
			$sqlThreads  = "SELECT thread_id, thread_title FROM `forum_thread` ";
			$sqlThreads .= "INNER JOIN forum USING (forum_id) ";
			$sqlThreads .= "INNER JOIN access USING (access_id) ";
			$sqlThreads .= "WHERE access_defaultpermission & 1 > 0 ";
			$sqlThreads .= "ORDER BY thread_lasttimestamp DESC ";
			$sqlThreads .= "LIMIT 10 ";

			$stmtThreads = $db->prepare($sqlThreads);
			$stmtThreads->execute();

			$arrThreads = $stmtThreads->fetchAll(PDO::FETCH_ASSOC);
			
			file_put_contents($cachePath, serialize($arrThreads));
		} else {
			$arrThreads = unserialize(file_get_contents($cachePath));
		}

		$this->_view->lastForumThreads = $arrThreads;
		
		$sidebarGuilds = array();
		if ($_SESSION["online"]) {
			$sqlGuilds  = "SELECT guild_id, guild_name, guild_type ";
			$sqlGuilds .= "FROM guild ";
			$sqlGuilds .= "INNER JOIN guild_member USING (guild_id) ";
			$sqlGuilds .= "WHERE guild_member.member_id = ? ";
			$sqlGuilds .= "ORDER BY guild_type, guild_name ";
			
			$stmt = $db->prepare($sqlGuilds);
			$stmt->execute(array($_SESSION["id"]));
			
			$sidebarGuilds = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		
		$this->_view->sidebarGuilds = $sidebarGuilds;
	}
	
	public function compile($template, $layout = 'layout.phtml') 
	{
		if ($layout) {
			$this->_view->content = $this->_view->render($template);
			echo $this->_view->render($layout);
		} else {
			echo $this->_view->render($template);
		}
	}
}
