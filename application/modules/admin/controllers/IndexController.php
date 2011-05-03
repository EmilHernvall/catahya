<?php
require_once 'Catahya/Controller/Action.php';

class Admin_IndexController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml') 
    {
    	$this->_view->section = 'administration';
    	$this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }//function
	
    public function init()
    {
    	parent::init();
    	
    	if (!Catahya_Access::hasAccess('admin')) {
    		$this->_redirect('/');
    	}
    }
    
    public function IndexAction() 
    {
    	$this->compile('index.phtml');
    }
}