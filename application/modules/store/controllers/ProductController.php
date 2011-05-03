<?php
require_once 'Catahya/Controller/Action.php';

class Store_ProductController extends Catahya_Controller_Action 
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function indexAction()
    {
    	$db = Zend_Registry::get('db');
    	$request = $this->getRequest();
    	$productId = $request->get('id');

        $sqlStore = "SELECT *, " .
        			"   (SELECT SUM(quantity) FROM store_stock " . 
        			"    WHERE t1.product_id = product_id) AS stock " .
        	 		"FROM store_product t1 " .
					"LEFT JOIN store_thumbnail ON (product_id = access_id) " .
					"LEFT JOIN store_category ON (product_category = category_id) " .
        			"WHERE product_id = ?";

    	$stmtStore = $db->prepare($sqlStore);
    	$stmtStore->execute(array($productId));

    	$arrStore = $stmtStore->fetch(PDO::FETCH_ASSOC);
    	$stmtStore->closeCursor();
    	
    	$sqlImages  = "SELECT * FROM store_image ";
    	$sqlImages .= "WHERE product_id = ? ";
    	$sqlImages .= "ORDER BY image_path DESC;";
    	
    	$stmtImages = $db->prepare($sqlImages);
    	$stmtImages->execute(array($productId));
    	
    	$arrImages = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
    	$stmtImages->closeCursor();
    	
    	$this->_view->product = $arrStore;
    	$this->_view->images = $arrImages;
    	$this->compile('product.phtml');
    }
}