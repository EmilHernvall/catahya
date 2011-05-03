<?php

class Factory {
	static function getView() {
		$view = new Zend_View;
		$view->addScriptPath(ROOT_PATH.'/application/views/scripts');
		return $view;
	}
}