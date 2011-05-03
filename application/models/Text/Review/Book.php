<?php

class Text_Review_Book extends Text
{
	protected $_metaFields = array('book_grade' => array('title' => 'Betyg', 'type' => 'intervall', 'min' => 1, 'max' => 5), 
	                               'book_author' => array('title' => 'Författare', 'type' => 'text'),
	                               'book_series' => array('title' => 'Serie', 'type' => 'text'),
	                               'book_volume' => array('title' => 'Volym', 'type' => 'text'));
}
