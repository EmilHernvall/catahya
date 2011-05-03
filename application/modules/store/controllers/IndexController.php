<?php

require_once 'Catahya/Controller/Action.php';

class Store_IndexController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
        public function indexAction()
        {
        	$db = Zend_Registry::get('db');
        	
        	$sqlStore = "SELECT *, " .
        				"   (SELECT SUM(quantity) FROM store_stock " . 
        				"    WHERE t1.product_id = product_id) AS stock " .
        	 			"FROM store_product t1 " .
						"LEFT JOIN store_thumbnail ON (product_id = access_id) " .
						"LEFT JOIN store_category ON (product_category = category_id)";
        				
        	$stmtStore = $db->prepare($sqlStore);
        	$stmtStore->execute();
        	
        	$arrStore = $stmtStore->fetchAll(PDO::FETCH_ASSOC);
        	$stmtStore->closeCursor();
        	
        	foreach ($arrStore as &$arr) {
        		if (strlen($arr['thumbnail_filename']) == 0) {
        			$arr['thumbnail_filename'] = '/images/store/thumb_na.png';
        		}
        	}
        	
        	$this->_view->products = $arrStore;
        	$this->_view->stock = "n/a";
        	
        	$this->compile('index.phtml');
        }
        
        public function postCommitAction()
        {
        	$request = $this->getRequest();
        	$this->_view->goldfish = "not so fishy!";
        	$this->compile('index.phtml');
        }
}