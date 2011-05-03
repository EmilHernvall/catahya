<?php
require_once "Catahya/Controller/Action.php";
require_once "Text.php";
require_once "TextComment.php";
require_once "wiki.php";

class Wiki_IndexController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('wiki_menu.phtml');
        parent::compile($template, $layout);
    }

    protected function _generateWiki($wiki, $page, $action, $root = false)
    {
		$db = Zend_Registry::get("db");

		// wiki
		$sqlWiki  = "SELECT * FROM wiki ";
		$sqlWiki .= "INNER JOIN access USING (access_id) ";
		$sqlWiki .= "WHERE wiki_name = ?";
		$stmtWiki = $db->prepare($sqlWiki);
		$stmtWiki->execute(array($wiki));

		$arrWiki = $stmtWiki->fetch(PDO::FETCH_ASSOC);
		
		$stmtWiki->closeCursor();
		
		if (!$arrWiki) {
			die("No such wiki!");
		}

		if ($action == "edit" && !Catahya_Access::hasPermission($arrWiki['access_id'], 
			Catahya_Permission_Wiki::EDIT, $arrWiki['access_defaultpermission'])) {
			
			$this->_redirect("/error");
		}
		else if ($action == "create" && !Catahya_Access::hasPermission($arrWiki['access_id'], 
			Catahya_Permission_Wiki::EDIT, $arrWiki['access_defaultpermission'])) {
			
			$this->_redirect("/error");
		}
		else if (!Catahya_Access::hasPermission($arrWiki['access_id'], 
			Catahya_Permission_Wiki::VIEW, $arrWiki['access_defaultpermission'])) {
			
			$this->_redirect("/error");
		}
		
		// page
		$sqlPage  = "SELECT wiki_page.*, p.page_title parent_title, p.page_name parent_name FROM wiki_page ";
		$sqlPage .= "LEFT JOIN wiki_page p ON p.page_id = wiki_page.page_parentid ";
		$sqlPage .= "WHERE wiki_page.wiki_id = ? AND wiki_page.page_name = ?";
		$stmtPage = $db->prepare($sqlPage);
		$stmtPage->execute(array($arrWiki['wiki_id'], $page));

		$arrPage = $stmtPage->fetch(PDO::FETCH_ASSOC);

		$stmtPage->closeCursor();

		// Build menu
		$parentId = $arrPage ? $arrPage['page_id'] : 0;
		$previousParent = 0;
		$tree = array();
		$current = array();
		$qc = 0;
		while (true) {
			$sqlNav = "SELECT page_id, page_name, page_title FROM wiki_page WHERE wiki_id = ? AND page_parentid = ?";
			$stmtNav = $db->prepare($sqlNav);
			$stmtNav->execute(array($arrWiki['wiki_id'], $parentId));

			$arrNav = $stmtNav->fetchAll(PDO::FETCH_ASSOC);

			$stmtNav->closeCursor();
			$qc++;

			$current = array();
			foreach ($arrNav as $nav) {
				$current[$nav['page_id']] = $nav;
			}

			if($tree) {
				$current[$previousParent]['children'] = $tree;
			}
			$tree = $current;

			if ($parentId == 0) {
				break;
			}
			
			$sqlParent = "SELECT page_id, page_parentid, page_name, page_title FROM wiki_page WHERE wiki_id = ? AND page_id = ?";
			$stmtParent = $db->prepare($sqlParent);
			$stmtParent->execute(array($arrWiki['wiki_id'], $parentId));

			$arrParent = $stmtParent->fetch(PDO::FETCH_ASSOC);

			$stmtParent->closeCursor();
			$qc++;

			$previousParent = $parentId;
			$parentId = $arrParent['page_parentid'];
		}

		$this->_view->wikiName = $wiki;
		$this->_view->pageName = $page;
		$this->_view->wiki = $arrWiki;	
		$this->_view->name = $page;
		$this->_view->page = $arrPage;
		$this->_view->tree = $tree;
		$this->_view->root = $root;
    }
	
	public function IndexAction()
	{
		$request = $this->getRequest();
		
		list(,$wiki) = explode("/",$request->getRequestUri());
		if ($wiki == "") {
			$wiki = "start";
		}
		
		$root = !isset($request->page);
		$page = isset($request->page) ? strtolower($request->page) : 'index';
		$action = $request->getQuery("action");

		if ($action == "save") {
			$this->_forward("SaveCommit");
			return;
		}
		else if ($action == "delete") {
			$this->_forward("DeleteCommit");
			return;
		}
		
		$this->_generateWiki($wiki, $page, $action, $root);
	
		if ($action == "create") {
	        $this->compile('wiki_create.phtml');
		} else if ($action == "edit") {
	        $this->compile('wiki_edit.phtml');
		} else {
	        $this->compile('wiki_index.phtml');
		}
	}
	
	public function DeleteCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
	
		list(,$wiki) = explode("/",$request->getRequestUri());
		$root = !isset($request->page);
		$page = isset($request->page) ? $request->page : 'index';

		// wiki
		$sqlWiki  = "SELECT * FROM wiki ";
		$sqlWiki .= "INNER JOIN access USING (access_id) ";
		$sqlWiki .= "WHERE wiki_name = ?";
		$stmtWiki = $db->prepare($sqlWiki);
		$stmtWiki->execute(array($wiki));

		$arrWiki = $stmtWiki->fetch(PDO::FETCH_ASSOC);
		
		$stmtWiki->closeCursor();

		// page
		$sqlPage  = "SELECT wiki_page.*, p.page_title parent_title, p.page_name parent_name FROM wiki_page ";
		$sqlPage .= "LEFT JOIN wiki_page p ON p.page_id = wiki_page.page_parentid ";
		$sqlPage .= "WHERE wiki_page.wiki_id = ? AND wiki_page.page_name = ?";
		$stmtPage = $db->prepare($sqlPage);
		$stmtPage->execute(array($arrWiki['wiki_id'], $page));

		$arrPage = $stmtPage->fetch(PDO::FETCH_ASSOC);

		$stmtPage->closeCursor();
		
		if (!Catahya_Access::hasPermission($arrWiki['access_id'], 
			Catahya_Permission_Wiki::DELETE, $arrWiki['access_defaultpermission'])) {
			
			$this->_redirect("/error");
		}
		
		$db->beginTransaction();
		
		$sqlUpdate = "UPDATE wiki_page SET page_parentid = ? WHERE page_parentid = ?";
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($arrPage["page_parentid"], $arrPage["page_id"]));
		
		$sqlDelete = "DELETE FROM wiki_page WHERE page_id = ?";
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->execute(array($arrPage["page_id"]));
		
		$db->commit();
		
		if ($arrPage["parent_name"]) {
			$this->_redirect(sprintf("/%s/%s", $arrWiki["wiki_name"], $arrPage["parent_name"]));
		} else {
			$this->_redirect("/" . $arrWiki["wiki_name"]);
		}
	}

	public function SaveCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		list(,$wiki) = explode("/",$request->getRequestUri());
		$root = !isset($request->page);
		$page = isset($request->page) ? $request->page : 'index';
		$action = $request->getQuery("action");

		// wiki
		$sqlWiki  = "SELECT * FROM wiki ";
		$sqlWiki .= "INNER JOIN access USING (access_id) ";
		$sqlWiki .= "WHERE wiki_name = ?";
		$stmtWiki = $db->prepare($sqlWiki);
		$stmtWiki->execute(array($wiki));

		$arrWiki = $stmtWiki->fetch(PDO::FETCH_ASSOC);
		
		$stmtWiki->closeCursor();

		// page
		$sqlPage  = "SELECT wiki_page.*, p.page_name parent_name FROM wiki_page ";
		$sqlPage .= "LEFT JOIN wiki_page p ON p.page_id = wiki_page.page_parentid ";
		$sqlPage .= "WHERE wiki_page.wiki_id = ? AND wiki_page.page_name = ?";
		$stmtPage = $db->prepare($sqlPage);
		$stmtPage->execute(array($arrWiki['wiki_id'], $page));

		$arrPage = $stmtPage->fetch(PDO::FETCH_ASSOC);

		$stmtPage->closeCursor();
		
		if (!Catahya_Access::hasPermission($arrWiki['access_id'], 
			Catahya_Permission_Wiki::EDIT, $arrWiki['access_defaultpermission'])) {
			
			$this->_redirect("/error");
		}

		$create = $request->getPost("create");
		$title = trim($request->getPost("title"));
		$text = trim($request->getPost("text"));
		
		$name = "";
		if (!$create) {
			if (strlen($title) == 0) {
				$_SESSION["flash"] = "Du måste ange en titel!";
				$this->_redirect(sprintf("/%s/%s?action=edit", $arrWiki["wiki_name"], $arrPage["page_name"]));
			}
		
			$sqlUpdate  = "UPDATE wiki_page SET page_name = ?, page_title = ?, page_text = ? ";
			$sqlUpdate .= "WHERE page_id = ?";

			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array(wiki_filter($title), $title, $text, $arrPage['page_id']));

			$name = wiki_filter($title);
		} else {
			if (strlen($title) == 0) {
				$_SESSION["flash"] = "Du måste ange en titel!";
				if (!$root) {
					$this->_redirect(sprintf("/%s/%s?action=create", $arrWiki["wiki_name"], $arrPage["page_name"]));
				} else {
					$this->_redirect(sprintf("/%s?action=create", $arrWiki["wiki_name"]));
				}
			}
		
			$sqlUpdate  = "INSERT INTO wiki_page (wiki_id, page_parentid, page_name, page_title, page_text) ";
			$sqlUpdate .= "VALUES (?, ?, ?, ?, ?)";

			$stmtUpdate = $db->prepare($sqlUpdate);
			$stmtUpdate->execute(array($arrWiki['wiki_id'], 
			                           intval($root ? 0 : $arrPage['page_id']), 
			                           wiki_filter($title), 
			                           $title, 
			                           $text));

			$name = wiki_filter($title);
		}

		$this->_redirect("/" . $wiki . "/" . $name);
	}

}
