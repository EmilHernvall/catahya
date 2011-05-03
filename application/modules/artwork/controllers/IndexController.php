<?php
require_once "Catahya/Controller/Action.php";

class Artwork_IndexController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
		$db = Zend_Registry::get("db");

		$sqlTypes  = "SELECT * FROM artwork_type ";
		$stmtTypes = $db->prepare($sqlTypes);
		$stmtTypes->execute();

		$types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->types = $types;
        $this->_view->pageMenu = $this->_view->render('artwork_menu.phtml');
        parent::compile($template, $layout);
    }

	public function IndexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		$sqlArtwork  = "SELECT * FROM artwork ";
		$sqlArtwork .= "INNER JOIN member USING (member_id) ";
		$sqlArtwork .= "WHERE type_id = ? ";
		$sqlArtwork .= "ORDER BY artwork_timestamp DESC ";
		$sqlArtwork .= "LIMIT 5 ";
		
		$stmt = $db->prepare($sqlArtwork);
		
		$stmt->execute(array(1));
		$shortStories = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt->execute(array(2));
		$poems = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$this->_view->shortStories = $shortStories;
		$this->_view->poems = $poems;
		$this->compile('artwork_index.phtml');
	}

	public function ListAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		$name = $request->type;
		$page = $request->page;

		$sqlType  = "SELECT * FROM artwork_type ";
		$sqlType .= "WHERE type_name = ?";

		$stmt = $db->prepare($sqlType);
		$stmt->execute(array($name));

		$type = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$type) {
			$this->_redirect("/artwork");
		}
		
		$sqlCount  = "SELECT count(*) FROM artwork ";
		$sqlCount .= "WHERE artwork.type_id = ? AND artwork_published > 0 ";
		
		$stmt = $db->prepare($sqlCount);
		$stmt->execute(array($type["type_id"]));
		
		$count = $stmt->fetchColumn(0);
		
		$stmt->closeCursor();
		
		profile("count");
		
		$perPage = 100;
		$total = ceil($count/$perPage);
		$page = $page > 0 ? $page : 1;
		
		$start = ($page-1) * $perPage;
		$start = $start > $count - $perPage 
		         ? $count- $perPage : $start;
		$start = $start < 0 ? 0 : $start;

		$sqlSelect  = "SELECT * FROM artwork ";
		$sqlSelect .= "LEFT JOIN member USING (member_id) ";
		$sqlSelect .= "WHERE artwork.type_id = ? AND artwork_published > 0 ";
		$sqlSelect .= "ORDER BY artwork_timestamp DESC ";
		$sqlSelect .= sprintf("LIMIT %d, %d ", $start, $perPage);

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute(array($type["type_id"]));

		$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$sqlLanguage  = "SELECT * FROM artwork_language ";
		$stmtLanguage = $db->prepare($sqlLanguage);
		$stmtLanguage->execute();
		$rawLanguages = $stmtLanguage->fetchAll(PDO::FETCH_ASSOC);
		
		$languages = array();
		foreach ($rawLanguages as $language) {
			$languages[$language["language_id"]] = $language["language_title"];
		}
		
		$sqlSubtypes  = "SELECT * FROM artwork_subtype ";
		$sqlSubtypes .= "WHERE type_id = ?";
		$stmtSubtypes = $db->prepare($sqlSubtypes);
		$stmtSubtypes->execute(array($type["type_id"]));
		$rawSubtypes = $stmtSubtypes->fetchAll(PDO::FETCH_ASSOC);
		
		$subtypes = array();
		foreach ($rawSubtypes as $subtype) {
			$subtypes[$subtype["subtype_id"]] = $subtype["subtype_title"];
		}
		
		profile("query");

		$this->_view->name = $name;
		$this->_view->type = $type;
		$this->_view->languages = $languages;
		$this->_view->subtypes = $subtypes;
		$this->_view->artworks = $artworks;
		$this->_view->pageTotal = $total;
		$this->_view->pageCurrent = $page;
		$this->_view->postCount = count($artworks);
		$this->compile('artwork_list.phtml');
	}

	public function ViewAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$id = $request->id;

		$sqlSelect  = "SELECT * FROM artwork ";
		$sqlSelect .= "INNER JOIN artwork_type USING (type_id) ";
		$sqlSelect .= "INNER JOIN artwork_subtype USING (subtype_id) ";
		$sqlSelect .= "INNER JOIN artwork_language USING (language_id) ";
		$sqlSelect .= "INNER JOIN member USING (member_id) ";
		$sqlSelect .= "WHERE artwork_id = ?";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute(array($id));

		$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$artwork) {
			$this->_redirect("/artwork");
		}

		$sqlSelect  = "SELECT * FROM artwork_comment ";
		$sqlSelect .= "INNER JOIN member USING (member_id) ";
		$sqlSelect .= "WHERE artwork_id = ? ";
		$sqlSelect .= "ORDER BY comment_timestamp";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute(array($id));

		$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->artwork = $artwork;
		$this->_view->comments = $comments;
		$this->compile("artwork_view.phtml");
	}

	public function WriteAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		$name = $request->type;

		$sqlType  = "SELECT * FROM artwork_type ";
		$sqlType .= "WHERE type_name = ?";

		$stmt = $db->prepare($sqlType);
		$stmt->execute(array($name));

		$type = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$type) {
			$this->_redirect("/artwork");
		}

		$sqlSubtypes  = "SELECT * FROM artwork_subtype ";
		$sqlSubtypes .= "WHERE type_id = ? ";

		$stmt = $db->prepare($sqlSubtypes);
		$stmt->execute(array($type["type_id"]));

		$subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$sqlLanguage  = "SELECT * FROM artwork_language ";
		
		$stmt = $db->prepare($sqlLanguage);
		$stmt->execute();

		$languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (array_key_exists("errors", $_SESSION)) {
			$this->_view->errors = $_SESSION["errors"];
			unset($_SESSION["errors"]);
		} else {
			$this->_view->errors = array();
		}
		if (array_key_exists("fields", $_SESSION)) {
			$this->_view->fields = $_SESSION["fields"];
			unset($_SESSION["fields"]);
		} else {
			$this->_view->fields = array();
		}
		$this->_view->type = $type;
		$this->_view->subtypes = $subtypes;
		$this->_view->languages = $languages;
		$this->compile('artwork_write.phtml');
	}

	public function WriteCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$typeName = $request->type;
		$subtypeId = $request->subtype;
		$language = $request->language;
		$title = trim($request->title);
		$text = trim($request->text);

		$errors = array();
		if (strlen($title) < 3) {
			$errors[] = "Du m&aring;ste ange en titel, och den m&aring;ste vara minst tre tecken.";
		}

		if (strlen($text) < 20) {
			$errors[] = "Texten m&aring;ste vara &aring;tminstone 20 tecken.";
		}

		if (count($errors) > 0) {
			$fields = array("subtype" => $subtypeId, "language" => $language,
				"title" => $title, "text" => $text);

			$_SESSION["errors"] = $errors;
			$_SESSION["fields"] = $fields;

			$this->_redirect("/artwork/" . $typeName . "/write");
		}

		$sqlSubtypes  = "SELECT * FROM artwork_subtype ";
		$sqlSubtypes .= "WHERE subtype_id = ? ";

		$stmt = $db->prepare($sqlSubtypes);
		$stmt->execute(array($subtypeId));

		$subtype = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();
		
		if (!$subtype) {
			$this->_redirect("/artwork");
		}

		$sqlInsert  = "INSERT INTO artwork (member_id, type_id, subtype_id, "
		            . "language_id, artwork_timestamp, artwork_title, "
		            . "artwork_text, artwork_published, artwork_publishedby) ";
		$sqlInsert .= "VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)";

		$stmt = $db->prepare($sqlInsert);
		$stmt->execute(array($_SESSION["id"], $subtype["type_id"], $subtype["subtype_id"], 
			$language, time(), $title, $text));

		$id = $db->lastInsertId();

		$_SESSION["flash"] = "Din text har skickats till validering.";
		$this->_redirect("/artwork/" . $typeName . "/write");
	}

	public function CommentCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		if (!$_SESSION["online"]) {
			$this->_redirect("/artwork");
		}

		$artworkId = $request->artworkid;
		$title = trim($request->title);
		$text = trim($request->text);
		
		$sqlSelect  = "SELECT * FROM artwork ";
		$sqlSelect .= "INNER JOIN artwork_type USING (type_id) ";
		$sqlSelect .= "WHERE artwork_id = ?";

		$stmt = $db->prepare($sqlSelect);
		$stmt->execute(array($artworkId));

		$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

		if (!$artwork) {
			$this->_redirect("/artwork");
		}

		if (strlen($text) < 2) {
			$_SESSION["flash"] = "Du m&aring;ste skriva minst tv&aring; tecken!";
			$this->_redirect(sprintf("/artwork/%s/view/%d#comments", $artwork["type_name"], $artwork["artwork_id"]));
		}

		$sqlInsert  = "INSERT INTO artwork_comment (artwork_id, member_id, "
		            . "comment_timestamp, comment_deleted, comment_title, comment_text) ";
		$sqlInsert .= "VALUES (?, ?, unix_timestamp(), '0', ?, ?)";

		$stmt = $db->prepare($sqlInsert);
		$stmt->execute(array($artwork["artwork_id"], $_SESSION["id"], $title, $text));
		
		$msgTitle = sprintf("Kommentar på \"%s\"", $artwork["artwork_title"]);
		$msgText = "OBS! Detta är ett autogenererat systemmeddelande som skickats automatiskt.";
		$msgText .= sprintf("[citat=%s]%s[/citat]", $_SESSION["alias"], $text);
		$this->_sendMessage($artwork["member_id"], $_SESSION["id"], $msgTitle, $msgText);

		$this->_redirect(sprintf("/artwork/%s/view/%d#comments", $artwork["type_name"], $artwork["artwork_id"]));
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
		$sqlThreadMember .= "VALUES (?, ?, ?, ?, ?, ?, unix_timestamp());";
		
		$stmtThreadMember = $db->prepare($sqlThreadMember);
		$stmtThreadMember->execute(array($threadId, $from, $sender["folder_id"], 's', '1', '1'));
		$stmtThreadMember->execute(array($threadId, $to, $receiver["folder_id"], 'r', '0', '0'));
	}
}
