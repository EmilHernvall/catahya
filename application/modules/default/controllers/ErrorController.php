<?php

require_once 'Catahya/Controller/Action.php';

class ErrorController extends Catahya_Controller_Action 
{
	public function indexAction()
	{
		$request = $this->getRequest();
		header('HTTP/1.1 404');
		$this->compile('error.phtml');
	}

	public function errorAction()
	{
		$request = $this->getRequest();
		header('HTTP/1.1 404');
		$this->compile('error.phtml');
	}
}
