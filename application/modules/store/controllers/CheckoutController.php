<?php
require_once 'Catahya/Controller/Action.php';

class Store_CheckoutController extends Catahya_Controller_Action 
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }
    
	public function indexAction()
	{
		$request = $this->getRequest();
		$productId = $request->get('id');
		
		
		$this->compile('checkout.phtml');
	}
}