<?php
require_once "Catahya/Controller/Action.php";

class Register_RegisterController extends Catahya_Controller_Action
{   
    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
        
        $this->compile('register.phtml');
    }
}
