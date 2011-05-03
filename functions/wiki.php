<?php

function wiki_filter($name)
{
	$name = utf8_decode($name);
	$name = html_entity_decode($name);
	$name = strtolower($name);
	$name = str_replace(array(" ","á","í","é","ú","å","ä","ö","ë"), 
		array("_","a","i","e","u","a","a","o","e"), $name);
	return preg_replace("/[^A-Z0-9_]+/i", "", $name);
}

function wiki_format($in, $wiki)
{
    $in = htmlspecialchars($in);

	$wrapper = true;
	if (preg_match_all('/\[pragma:([^\]]*)\]/i', $in, $matches)) {
		foreach($matches[1] as $match) {
			switch ($match) {
				case 'noWrapper':
					$wrapper = false;
					break;
			}
		}
		$in = preg_replace('/\[pragma:([^\]]*)\]\r\n/i', '', $in);
	}

	if (preg_match_all('/\[plugin:(.*)\((.*)\)\]/', $in, $matches)) {
		require_once(ROOT_PATH.'/application/modules/wiki/plugins/plugin.php');
		foreach ($matches[1] as $i => $plugin) {
			$file = ROOT_PATH.'/application/modules/wiki/plugins/' . ucfirst($plugin) . ".php";
			require_once($file);
			$name = 'Wiki_Plugin_' . ucfirst($plugin);

			$buffer = '';
			if (class_exists($name)) {
				$class = new $name;
				if ($class instanceof Wiki_Plugin) {
					$buffer = $class->render($matches[2][$i]);
				}
			}

			$in = str_replace($matches[0][$i], $buffer, $in);
		}
	}

    if (preg_match('/\[content\]/iU', $in)) {
        preg_match_all('/=([^=]+)=\r\n/iU', $in, $matches);
		
        $buf = '<div style="font-size: 18px; font-weight: bold; border-bottom: 1px solid #ccc; margin-bottom: 0; padding: 0;">Inneh&aring;ll</div>';
        $buf .= '<ol>';
        if (count($matches[1])) {
            foreach ($matches[1] as $match) {
                $buf .= '<li><a href="#'.str_replace(' ','_',$match).'">'.$match.'</a></li>';
            }
        }
        $buf .= '</ol>';

        $in = preg_replace('/\[content\]/iU', $buf, $in);
    }

	// columns
	$in = preg_replace('/\[column_left\]\r\n([\s\S]*)\[\/column_left\]\r\n/Um', '<div class="leftColumn">$1</div>', $in);
	$in = preg_replace('/\[column_right\]\r\n([\s\S]*)\[\/column_right\]/Um', '<div class="rightColumn">$1</div>', $in);

    // sub and sup
    $in = preg_replace('/\^\s*(.*)\s*\^/iU', '<sup>$1</sup>', $in);
    $in = preg_replace('/,,\s*(.*)\s*,,/iU', '<sub>$1</sub>', $in);

    // b, u and i
    $in = preg_replace('/\'\'\'\s*(.*)\s*\'\'\'/iU', '<span style="font-weight: bold;">$1</span>', $in);
    $in = preg_replace('/\'\'\s*(.*)\s*\'\'/iU', '<span style="font-style: italic;">$1</span>', $in);
    $in = preg_replace('/__\s*(.*)\s*__/iU', '<span style="text-decoration: underline;">$1</span>', $in);

    // headings
    $in = preg_replace('/===\s*(.*)\s*===\s*\r\n/iU', '<div style="font-size: 14px; font-weight: bold;">$1</div>', $in);
    $in = preg_replace('/==\s*(.*)\s*==\s*\r\n/iU', '<div style="font-size: 16px; font-weight: bold;">$1</div>', $in);
    $in = preg_replace('/=\s*(.*)\s*=\s*\r\n/iUe', '"<div style=\"font-size: 18px; font-weight: bold; border-bottom: 1px solid #ccc; margin-bottom: 0; padding: 0;\" id=\"".str_replace(" ","_","$1")."\">"."$1"."</div>"', $in);

	// links
	$in = preg_replace('/\[\[([^\]]*)\|([^\]]*)\]\]/iUe', '"<a href=\"/'.$wiki.'/".wiki_filter("$1")."\">$2</a> "', $in);
	$in = preg_replace('/(?<!\!)\[\[([^\]]*?)\]\]/iUe', '"<a href=\"/'.$wiki.'/".wiki_filter("$1")."\">$1</a> "', $in);
	$in = preg_replace('/\[url\](.*?)\[\/url\]/iUe', '"<a href=\"$1\" target=\"_blank\">".dotTruncate("$1", 30)."</a>"', $in);
	$in = preg_replace('/\[url=([^\]]*)\](.*?)\[\/url\]/iU', '<a href="$1" target="_blank">$2</a>', $in);
	
	// img
	$in = preg_replace('/\[img=([^\]]*)\]/iUe', '"<img src=\"".htmlspecialchars("$1")."\" /> "', $in);
	$in = preg_replace('/\[floatimg=([^\]]*)\]/iUe', '"<img src=\"".htmlspecialchars("$1")."\" style=\"float: right;\"/> "', $in);

	if ($wrapper) {
		$in = '<div class="content">'.$in.'</div>';
	}

    return nl2br($in);
}

function wiki_drawMenuTree($tree, $wikiName, $selected, $level = 0)
{
	printf('<ul class="%s">'.PHP_EOL, !$level ? 'navMenu' : 'subMenu');
	foreach ($tree as $node) {
		printf('<li>'.PHP_EOL);
			printf('<a href="/%s/%s" style="%s">%s</a>'.PHP_EOL, 
			       $wikiName, 
			       $node['page_name'], 
			       ($selected == $node['page_id'] ? 'font-weight: bold;' : ''),
			       htmlspecialchars($node['page_title']));
			if (array_key_exists('children', $node)) {
				wiki_drawMenuTree($node['children'], $wikiName, $selected, $level+1);
			}
		printf('</li>'.PHP_EOL);
	}
	printf('</ul>'.PHP_EOL);
}
