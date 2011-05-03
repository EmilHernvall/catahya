<?php

require_once 'plugin.php';

class Wiki_Plugin_News implements Wiki_Plugin 
{
	public function __construct()
	{

	}

	public function render($params)
	{
		$limit = intval($params);

		if ($limit < 1 || $limit > 100) {
			return 'Ogiltig parameter!';
		}

        $db = Zend_Registry::get('db');

        $sqlNews  = 'SELECT * FROM forum_thread ';
        $sqlNews .= 'INNER JOIN member USING (member_id) ';
        $sqlNews .= 'WHERE forum_id = 5 ';
        $sqlNews .= 'ORDER BY thread_timestamp DESC ';
        $sqlNews .= 'LIMIT '.$limit;

        $stmtNews = $db->prepare($sqlNews);
        $stmtNews->execute();

        $arrNews = $stmtNews->fetchAll(PDO::FETCH_ASSOC);

		$buffer = '';
		$buffer .= '<dl class="events">';
		$lastDate = 0;
        foreach ($arrNews as $i => $news) {
			$date = standardDate($news['thread_timestamp'], false);
			if ($date != $lastDate) {
				$buffer .= '<dd>'.standardDate($news['thread_timestamp'], false).'</dd>';
			}
			$buffer .= '<dt> - <a href="/forum/thread/'.$news['thread_id'].'">'.$news['thread_title'].'</a></dt>';
			$lastDate = $date;
        }
		$buffer .= '</dl>';

		return $buffer;
	}
}
