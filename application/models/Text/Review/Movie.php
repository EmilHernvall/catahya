<?php

class Text_Review_Movie extends Text
{
	protected $_metaFields = array('movie_grade' => array('title' => 'Betyg', 'type' => 'intervall', 'min' => 1, 'max' => 7), 
	                               'movie_director' => array('title' => 'Regissör', 'type' => 'text'),
	                               'movie_year' => array('title' => 'Årtal', 'type' => 'text'),
	                               'movie_length' => array('title' => 'Längd', 'type' => 'text'),
	                               'movie_actors' => array('title' => 'Skådespelare', 'type' => 'text'));
}
