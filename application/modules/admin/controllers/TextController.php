<?php
require_once 'Catahya/Controller/Action.php';
require_once 'Text.php';

class Admin_TextController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$db = Zend_Registry::get('db');
    	
    	$this->_view->section = 'administration';
    	
    	$sqlTypes = 'SELECT * FROM text_type';
    	$stmtTypes = $db->prepare($sqlTypes);
    	$stmtTypes->execute();
    	
    	$types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
    	
    	foreach ($types as $key => $type) {
    		if (!Catahya_Access::hasPermission($type['access_id'],
    		                                   Catahya_Permission_Text::WRITE)) {

				unset($types[$key]);
			}
    	}
    	
    	$this->_view->types = $types;
    	$this->_view->pageSection = 'text';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
    public function IndexAction() 
    {
    	$this->compile('text_index.phtml');
    }
    
    /*public function ConvertAction()
    {
    	$db = Zend_Registry::get('db');
    	
    	$stmt = $db->prepare('SELECT * FROM catahya_net.re_skiv');
    	$stmt->execute();
    	
    	$arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    	
    	foreach ($arr as $row) {
    		$meta = array('rating' => $row['betyg'], 
    		              'artist' => $row['artist'], 
    		              'year' => $row['release'], 
    		              'length' => $row['width'], 
    		              'tracks' => $row['tracks'], 
    		              'image' => $row['bild']);
    		
    		Text::insert(4, 
    		             $row['uID'], 
    		             strtotime($row['datum']), 
    		             $row['titel'], 
    		             $row['text'], 
    		             $meta);
    	}
    }*/
    
    public function OverviewAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$typeId = $request->get('typeid');
    	if (!$typeId) {
    		$_SESSION['flash'] = 'Felaktig text-typ.';
    		$this->_redirect('/admin/text');
    	}
    	
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($typeId));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
    	
    	$arrUnpublishedTexts = false;
    	if (Catahya_Access::hasPermission($type['access_id'],
		                                  Catahya_Permission_Text::CONFIRM)) {
    		$arrUnpublishedTexts = Text::selectUnpublishedByTypeId($typeId);
    	}
		
		$id = $_SESSION["id"];
    	if (Catahya_Access::hasPermission($type['access_id'],
		                                  Catahya_Permission_Text::FULL)) {
			$id = 0;
		}
    	$arrTexts = Text::selectByTypeId($typeId, $id);
    	
    	$this->_view->type = $type;
    	$this->_view->texts = $arrTexts;
    	$this->_view->unpublishedTexts = $arrUnpublishedTexts;
    	$this->compile('text_overview.phtml');
    }
    
    public function WriteAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$typeId = $request->get('typeid');
		$textId = $request->get('textid');
		
		$data = array();
		$arrText = array();
		if ($textId) {
			$text = Text::selectById($textId);
			if (!$text) {
				$this->_redirect('/admin/text');
			}
			
			$arrText = $text->getRow();
			$data['timestamp'] = $arrText['text_timestamp'] ? date('Y-m-d H:i:s', $arrText['text_timestamp']) : '';
			$data['title'] = $arrText['text_title'];
			$data['pretext'] = $arrText['text_pretext'];
			$data['showpretext'] = $arrText['text_showpretext'];
			$data['text'] = $arrText['text_text'];
			$data['image'] = $arrText["image_id"];
			$data['gallery'] = $arrText["text_gallery"];
			
			if ($arrText["type_metatable"]) {
				$meta = $text->selectMeta();
				$data["meta"] = $meta;
			} else {
				$data["meta"] = array();
			}
			
			$typeId = $arrText['type_id'];
		}
    	
    	if (!$typeId) {
    		$_SESSION['flash'] = 'Felaktig text-typ.';
    		$this->_redirect('/admin/text');
    	}
    	
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($typeId));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
    	
		if (!Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::WRITE)) {

			$this->_redirect('/admin/text');
		}
		
		if ($arrText && $arrText['member_id'] != $_SESSION['id'] &&
		    !Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::CONFIRM)) {

			$this->_redirect('/admin/text');
		}
		
    	if (class_exists($type['type_class'])) {
    		$class = new $type['type_class'](0, array());
    	} else {
    		$class = new Text(0, array());
    	}
		
		$images = array();
		if ($textId) {
			$sqlImages = "SELECT * FROM text_image WHERE text_id = ?";
			
			$stmtImages = $db->prepare($sqlImages);
			$stmtImages->execute(array($textId));
			
			$images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
		}
    	
    	$this->_view->type = $type;
    	$this->_view->textId = $textId;
    	$this->_view->class = $class;
		$this->_view->images = $images;
    	$this->_view->errors = array_key_exists('errors', $_SESSION) ? $_SESSION['errors'] : array();
    	$this->_view->data = array_key_exists('data', $_SESSION) ? $_SESSION['data'] : $data;
    	$this->compile('text_write.phtml');
    	
    	if (array_key_exists('errors', $_SESSION)) {
	    	unset($_SESSION['errors']);
	    	unset($_SESSION['data']);
    	}
    }
    
    public function WriteCommitAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
    	$typeId = $request->get('typeid');
    	$textId = $request->get('textid');
		
		$db->beginTransaction();
    	
		$objText = false;
		$arrText = array();
		if ($textId) {
			$objText = Text::selectById($textId);
			if (!$objText) {
				$this->_redirect('/admin/text');
			}
			$arrText = $objText->getRow();
			$typeId = $arrText['type_id'];
		}
    	
    	$sqlType = 'SELECT * FROM text_type WHERE type_id = ? ';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($typeId));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
    	
    	if (!$type) {
    		$this->_redirect('/admin/text');
    	}
    	
		if (!Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::WRITE)) {

			$this->_redirect('/admin/text');
		}
		
		if ($arrText && $arrText['member_id'] != $_SESSION['id'] &&
		    !Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::CONFIRM)) {

			$this->_redirect('/admin/text');
		}
    	
    	$title = $request->title;
    	$pretext = $request->pretext;
    	$showpretext = (int)$request->showpretext;
    	$image = (int)$request->image;
    	$gallery = (int)$request->gallery;
    	$text = $request->text;
    	$meta = $request->meta;
    	
    	$errors = array();
    	
    	if (!strlen($title)) {
    		$errors[] = 'title';
    	}
    	
    	if (!strlen($text)) {
    		$errors[] = 'text';
    	}
    	
    	if (count($errors)) {
    		$_SESSION['errors'] = $errors;
    		$_SESSION['data'] = array('title' => $title,
    		                          'text' => $text,
    		                          'meta' => $meta);
			$this->_redirect('/admin/text/write?typeid=' . $typeId);
    	}
    	
    	if ($objText) {
    		$objText->update($typeId, 
							 $image,
    		                 $title,
    		                 $text,
							 $pretext,
							 $showpretext,
							 $gallery);
    	} else {
    		$objText = Text::insert($typeId, $_SESSION['id'], 0, 0, $title, $text, $pretext, $showpretext, $gallery);
			$arrText = $objText->getRow();
    	}
		
		$objText->updateMeta($meta);
		
		$db->commit();
    	
    	$this->_redirect('/admin/text/write?textid=' . $arrText["text_id"]);
    }
	
    public function PublishAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
		$textId = $request->get('textid');

		$text = Text::selectById($textId);
		if (!$text) {
			$this->_redirect('/admin/text');
		}
		
		$arrText = $text->getRow();
		
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($arrText["type_id"]));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
		
		$publishedBy = array();
		if ($arrText["text_publishedby"]) {
			$sqlPublishedBy = "SELECT * FROM member WHERE member_id = ?";
			$stmtPublishedBy = $db->prepare($sqlPublishedBy);
			$stmtPublishedBy->execute(array($arrText["text_publishedby"]));
			
			$publishedBy = $stmtPublishedBy->fetch(PDO::FETCH_ASSOC);
			
			$stmtPublishedBy->closeCursor();
		}
		
    	$this->_view->type = $type;
    	$this->_view->text = $arrText;
		$this->_view->publishedBy = $publishedBy;
    	$this->compile('text_publish.phtml');
    }
	
	public function PublishCommitAction()
	{
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
		$textId = $request->get('textid');

		$text = Text::selectById($textId);
		if (!$text) {
			$this->_redirect('/admin/text');
		}
		
		$arrText = $text->getRow();
		
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($arrText["type_id"]));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
		
		if (!Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::CONFIRM)) {

			$this->_redirect('/admin/text');
		}
		
		$published = $request->published;
		$timestamp = $published ? strtotime($request->timestamp) : 0;
		
		$sqlPublish  = "UPDATE text SET text_timestamp = ?, text_published = ?, text_publishedby = ? ";
		$sqlPublish .= "WHERE text_id = ?";
		
		$stmtPublish = $db->prepare($sqlPublish);
		$stmtPublish->execute(array($timestamp, $published, $_SESSION["id"], $textId));
		
		$this->_redirect("/admin/text/overview?typeid=" . $arrText["type_id"]);
	}
	
	public function DeleteCommitAction()
	{
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	
		$textId = $request->get('textid');

		$text = Text::selectById($textId);
		if (!$text) {
			$this->_redirect('/admin/text');
		}
		
		$arrText = $text->getRow();
		
    	$sqlType  = 'SELECT * FROM text_type ';
    	$sqlType .= 'WHERE type_id = ?';
    	$stmtType = $db->prepare($sqlType);
    	$stmtType->execute(array($arrText["type_id"]));
    	
    	$type = $stmtType->fetch(PDO::FETCH_ASSOC);
    	
    	$stmtType->closeCursor();
		
		if ($_SESSION["id"] != $arrText["member_id"] &&
			!Catahya_Access::hasPermission($type['access_id'],
		                                   Catahya_Permission_Text::DELETE)) {

			$this->_redirect('/admin/text');
		}
		
		$sqlDelete  = "DELETE FROM text ";
		$sqlDelete .= "WHERE text_id = ?";
		
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->execute(array($textId));
		
		$this->_redirect("/admin/text/overview?typeid=" . $arrText["type_id"]);
	}
	
	public function ImageUploadCommitAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$textId = $request->textid;
		if (!$textId) {
			$this->_redirect("/admin/text");
		}
		
		if (!array_key_exists("image", $_FILES)) {
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		$title = $request->title;
		$description = $request->description;
		$gallery = (int)$request->gallery;
		
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
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		if ($size > 500*1024) {
			$_SESSION["flash"] = "Bilden är för stor!";
			$this->_redirect("/admin/text/write?textid=" . $textId);
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
				$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		if (!$image) {
			$_SESSION["flash"] = "Ogiltig filtyp!";
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		$width = imagesx($image);
		$height = imagesy($image);
		
		if ($width < 100) {
			$_SESSION["flash"] = "Bilden är för liten!";
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		$db->beginTransaction();
		
		// Färdigvaliderat. Nu kör vi!
		$sqlInsert  = "INSERT INTO text_image (text_id, member_id, image_timestamp, "
		            . "image_size, image_name, image_title, image_description, "
					. "image_gallery, image_width, image_height) ";
		$sqlInsert .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($textId, $_SESSION["id"], time(), $size, $name, $title,
			$description, $gallery, $width, $height));
			
		$imageId = $db->lastInsertId();
		
		$largePath = ROOT_PATH . "/public/userdata/text/large/" . $imageId . ".jpg";
		$this->_scaleImage($image, min(600, $width), $largePath);
		
		$smallPath = ROOT_PATH . "/public/userdata/text/thumbs/" . $imageId . ".jpg";
		$this->_scaleImage($image, 100, $smallPath);
		
		$fullPath = ROOT_PATH . "/public/userdata/text/fullsize/" . $imageId . ".jpg";
		imagejpeg($image, $fullPath, 90);
		
		$db->commit();
		
		@unlink($tmpName);
		
		$_SESSION["flash"] = "Uppladdning lyckades!";
		
		$this->_redirect("/admin/text/write?textid=" . $textId);
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
	
	public function DeleteImageCommitAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		
		$imageId = $request->imageid;
		$textId = $request->textid;
		
		$db->beginTransaction();
		
		$sqlDelete = "DELETE FROM text_image WHERE image_id = ?";
		$stmtDelete = $db->prepare($sqlDelete);
		$stmtDelete->execute(array($imageId));
		
		$sqlUpdate = "UPDATE text SET image_id = 0 WHERE image_id = ?";
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($imageId));
		
		$db->commit();
		
		$this->_redirect("/admin/text/write?textid=" . $textId);
	}
}
