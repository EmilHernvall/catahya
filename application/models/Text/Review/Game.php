<?php

class Text_Review_Game extends Text
{
	protected $_metaFields = array('game_grade' => array('title' => 'Betyg', 'type' => 'intervall', 'min' => 1, 'max' => 5), 
	                               'game_type' => array('title' => 'Typ', 'type' => 'text'),
	                               'game_distributor' => array('title' => 'Distributör', 'type' => 'text'));
}
