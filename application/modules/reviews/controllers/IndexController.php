<?php
require_once "Catahya/Controller/Action.php";
require_once "Text.php";
require_once "TextComment.php";

class Reviews_IndexController extends Catahya_Controller_Action
{
    
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
    	$this->_view->latest = Text::selectLatest(array(2,3,4,5), 30);
        $this->compile('index.phtml');

    }

    public function ListAction()
    {
    	$request = $this->getRequest();
    	$db = Zend_Registry::get('db');
    	
    	$type = $request->get('type');
		$sort = $request->get('sort');
		$desc = $request->get('desc');
		
		$validFields = array();
		$validFields["books"] = array("text_title", "text_timestamp","member_alias", "book_author",  "book_grade");
		$validFields["movies"] = array("text_title", "text_timestamp", "member_alias", "movie_director", "movie_grade");
		$validFields["music"] = array("text_title", "text_timestamp", "member_alias", "music_artist", "music_grade");
		$validFields["games"] = array("text_title", "text_timestamp", "member_alias", "game_distributor", "game_grade");
		
		if (!array_key_exists($type, $validFields)) {
			$this->_redirect("/");
		}
		
		$order = false;
		if (array_key_exists($sort, $validFields[$type])) {
			$order = $validFields[$type][$sort];
		} else {
			$order = "text_timestamp";
			$desc = true;
		}
		
    	$texts = Text::selectByTypeId($type, 0, $order, $desc);

    	$this->_view->texts = $texts;
    	$this->compile($type.'_list.phtml');
    }
    
    public function ViewAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
		$textId = $request->get('textid');

		$obj = Text::selectById($textId);
		if (!$obj) {
			$this->_redirect('/reviews');
		}
		
		$text = $obj->getRow();
		$metaFields = $obj->getMetaFields();
		$meta = $obj->selectMeta();
    	
    	$comments = TextComment::selectAll($textId);
		
		$gallery = array();
		if ($text["text_gallery"] == "1") {
			$sqlGallery = "SELECT * FROM text_image WHERE text_id = ? AND image_gallery = '1'";
			$stmtGallery = $db->prepare($sqlGallery);
			$stmtGallery->execute(array($textId));
			
			$gallery = $stmtGallery->fetchAll(PDO::FETCH_ASSOC);
		}
    	
    	$this->_view->textId = $textId;
    	$this->_view->text = $text;
    	$this->_view->metaFields = $metaFields;
    	$this->_view->meta = $meta;
    	$this->_view->comments = $comments;
		$this->_view->gallery = $gallery;
    	$this->compile('view.phtml');
    }
    
    public function commentCommitAction()
    {
    	$request = $this->getRequest();
    	
		if (!$_SESSION['online']) {
			$this->_redirect("/");
		}
		
    	$textId = $request->textid;
		if (!$textId) {
			$this->_redirect("/");
		}
		
    	$title = trim($request->title);
    	$text = trim($request->text);
    	
    	if (strlen($text) > 0) {
    		TextComment::insert($textId, $_SESSION['id'], time(), $title, $text);
    	}
    	
    	$this->_redirect('/reviews/index/view?textid='.$textId.'#addcomment');
    }
}
