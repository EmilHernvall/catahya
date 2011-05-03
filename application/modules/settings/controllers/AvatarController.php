<?php
require_once "Catahya/Controller/Action.php";

class Settings_AvatarController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
        $sql  = 'SELECT member_id, member_quickdesc, '
              . 'member_city, member_alias, member_name, '
              . 'member_email, member_jabber, member_msn, '
              . 'member_homepage ';
        $sql .= 'FROM member ';
		$sql .= 'INNER JOIN member_profile USING (member_id) ';
        $sql .= 'WHERE member_id = ?';

        $stmtProfile = $db->prepare($sql);
        $stmtProfile->execute( array($_SESSION['id']) );
        $arrProfile = $stmtProfile->fetch(PDO::FETCH_ASSOC);

        $stmtProfile->closeCursor();
		
		$sqlAvatars  = "SELECT * FROM member_avatar ";
		$sqlAvatars .= "WHERE member_id = ? ";
		$sqlAvatars .= "ORDER BY avatar_timestamp ";
		
		$stmt = $db->prepare($sqlAvatars);
		$stmt->execute(array($_SESSION["id"]));
		
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->_view->member = $arrProfile;
		$this->_view->avatars = $result;
        $this->compile('avatar.phtml');

    }
	
	public function AvatarCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		if (!array_key_exists("image", $_FILES)) {
			$this->_redirect("/settings/avatar/index");
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
			$this->_redirect("/settings/avatar/index");
		}
		
		if ($size > 500*1024) {
			$_SESSION["flash"] = "Bilden är för stor!";
			$this->_redirect("/settings/avatar/index");
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
				$this->_redirect("/settings/avatar/index");
		}
		
		if (!$image) {
			$_SESSION["flash"] = "Ogiltig filtyp!";
			$this->_redirect("/settings/avatar/index");
		}
		
		$width = imagesx($image);
		$height = imagesy($image);
		
		if ($width < 100) {
			$_SESSION["flash"] = "Bilden är för liten!";
			$this->_redirect("/settings/avatar/index");
		}
		
		if ($height < $width/2) {
			$_SESSION["flash"] = "Bilden är för platt!";
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		if ($height > 1.5*$width) {
			$_SESSION["flash"] = "Bilden är för avlång!";
			$this->_redirect("/admin/text/write?textid=" . $textId);
		}
		
		// Färdigvaliderat. Nu kör vi!
		$db->beginTransaction();
		
		$sqlUpdate  = "UPDATE member_avatar SET avatar_current = '0' ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($_SESSION["id"]));
		
		$sqlInsert  = "INSERT INTO member_avatar (member_id, avatar_name, avatar_size, "
		            . "avatar_width, avatar_height, avatar_timestamp, avatar_current) ";
		$sqlInsert .= "VALUES (?, ?, ?, ?, ?, unix_timestamp(), '1')";
		
		$stmtInsert = $db->prepare($sqlInsert);
		$stmtInsert->execute(array($_SESSION["id"], $name, $size, $width, $height));
		
		$avatarId = $db->lastInsertId();
		
		$largePath = ROOT_PATH . "/public/userdata/avatars/large/" . $avatarId . ".jpg";
		$this->_scaleImage($image, min(600, $width), $largePath);
		
		$mediumPath = ROOT_PATH . "/public/userdata/avatars/100/" . $avatarId . ".jpg";
		$this->_scaleImage($image, 100, $mediumPath);
		
		$smallPath = ROOT_PATH . "/public/userdata/avatars/50/" . $avatarId . ".jpg";
		$this->_scaleImage($image, 50, $smallPath);
		
		$fullPath = ROOT_PATH . "/public/userdata/avatars/fullsize/" . $avatarId . ".jpg";
		imagejpeg($image, $fullPath, 90);
		
		@unlink($tmpName);
		
		$sqlUpdate  = "UPDATE member SET member_photo = ?, member_photostatus = '2' ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($avatarId . ".jpg", $_SESSION["id"]));
		
		$db->commit();
		
		$_SESSION['photo'] = $avatarId . ".jpg";
		$_SESSION["flash"] = "Uppladdningen lyckades!";
		
		$this->_redirect("/settings/avatar/index");
	}
	
	public function SwitchCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
		$avatarId = $request->avatarid;
		
		$sqlUpdate  = "UPDATE member_avatar SET avatar_current = IF(avatar_id = ?, '1', '0') ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($avatarId, $_SESSION["id"]));
		
		$sqlUpdate  = "UPDATE member SET member_photo = ?, member_photostatus = '2' ";
		$sqlUpdate .= "WHERE member_id = ?";
		
		$stmtUpdate = $db->prepare($sqlUpdate);
		$stmtUpdate->execute(array($avatarId . ".jpg", $_SESSION["id"]));
		
		$_SESSION['photo'] = $avatarId . ".jpg";
		
		$this->_redirect("/settings/avatar/index");
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
