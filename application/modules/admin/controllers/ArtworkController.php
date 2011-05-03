<?php
require_once 'Catahya/Controller/Action.php';

class Admin_ArtworkController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->pageSection = 'artwork';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
	
    public function init()
    {
    	parent::init();
    	
    	if (!Catahya_Access::hasAccess('admin')) {
    		$this->_redirect('/');
    	}
    }
    
    public function IndexAction() 
    {
    	$this->compile('artwork_index.phtml');
    }

	public function ValidateAction()
	{
		$db = Zend_Registry::get("db");

		$sqlArtwork  = "SELECT * FROM artwork ";
		$sqlArtwork .= "INNER JOIN artwork_type USING (type_id) ";
		$sqlArtwork .= "INNER JOIN artwork_subtype USING (subtype_id) ";
		$sqlArtwork .= "INNER JOIN artwork_language USING (language_id) ";
		$sqlArtwork .= "INNER JOIN member USING (member_id) ";
		$sqlArtwork .= "WHERE artwork_published = 0 ";
		$sqlArtwork .= "ORDER BY artwork.type_id, artwork_timestamp";

		$stmt = $db->prepare($sqlArtwork);
		$stmt->execute();

		$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->_view->artworks = $artworks;
		$this->compile('artwork_validate.phtml');
	}

	public function ValidateCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");

		$id = $request->id;
		if(!$id) {
			$this->_redirect("/admin/artwork");
		}

		$sqlUpdate  = "UPDATE artwork ";
		$sqlUpdate .= "SET artwork_published = unix_timestamp(), artwork_publishedby = ? ";
		$sqlUpdate .= "WHERE artwork_id = ?";

		$stmt = $db->prepare($sqlUpdate);
		$stmt->execute(array($_SESSION["id"], $id));

		$this->_redirect("/admin/artwork/validate");
	}
}
