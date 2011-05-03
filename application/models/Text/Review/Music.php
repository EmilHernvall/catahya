<?php

class Text_Review_Music extends Text
{
	protected $_metaFields = array('music_grade' => array('title' => 'Betyg', 'type' => 'intervall', 'min' => 1, 'max' => 5), 
	                               'music_artist' => array('title' => 'Artist', 'type' => 'text'),
	                               'music_year' => array('title' => 'Årtal', 'type' => 'text'),
	                               'music_length' => array('title' => 'Längd', 'type' => 'text'),
	                               'music_tracks' => array('title' => 'Antal spår', 'type' => 'text'));
}
