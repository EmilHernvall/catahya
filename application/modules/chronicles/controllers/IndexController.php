<?php
require_once "Catahya/Controller/Action.php";
require_once "Text.php";
require_once "TextComment.php";

/**
 * @todo Sökfunktion. Implementera eller inaktivera.
 */
class Chronicles_IndexController extends Catahya_Controller_Action
{
    
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
    	$this->_view->latest = Text::selectLatest(array(1), 30);
        $this->compile('index.phtml');
    }

    public function ListAction()
    {
    	$request = $this->getRequest();
    	$db = Zend_Registry::get('db');
    	
    	$type = $request->get('type');
    	$sort = $request->get('sort');
    	$desc = $request->get('desc');
		
		$validFields = array("text_title", "member_alias", "text_timestamp");
		
		$order = false;
		if (array_key_exists($sort, $validFields)) {
			$order = $validFields[$sort];
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
		
		$data = array();
		$text = Text::selectById($textId);
		if (!$text) {
			$this->_redirect('/');
		}
		
		$arrText = $text->getRow();
		
		$typeId = $arrText['type_id'];
    	
    	if (!$typeId) {
    		$_SESSION['flash'] = 'Felaktig text-typ.';
    		$this->_redirect('/');
    	}
    	
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($typeId));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
    	
    	if (class_exists($type['type_class'])) {
    		$class = new $type['type_class'](0, array());
    	} else {
    		$class = new Text(0, array());
    	}
    	
    	$comments = TextComment::selectAll($textId);
    	
    	$this->_view->type = $type;
    	$this->_view->textId = $textId;
    	$this->_view->class = $class;
    	$this->_view->text = $arrText;
    	$this->_view->comments = $comments;
    	$this->compile('view.phtml');
    }
    
    public function commentCommitAction()
    {
    	$request = $this->getRequest();

		if (!$_SESSION["online"]) {
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
    	
    	$this->_redirect('/chronicles/index/view?textid='.$textId.'#addcomment');
    }

}
