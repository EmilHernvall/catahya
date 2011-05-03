<?php
/**
 * functions.php
 *
 * Commonly used functions.
 * Some parts of this file is dependant on the settings in init.php.
 * @package framework
 * @author Emil Hernvall <aderyn@gmail.com>
 * @version 1.0
 */

/*****************************************************************************
 * Various functions
 *****************************************************************************/

/**
 * Strip _all_ slashes
 *
 * @param string $value
 * @return string
 */
function stripslashes_deep($value, $level = 0)
{
	if ($level > 20) {
		return "";
	}

	if (!is_array($value)) {
		return stripslashes($value);
	}
	
	$result = array();
	foreach ($value as $k => $v) {
		if (is_array($v)) {
			$result[$k] = stripslashes_deep($v, $level + 1);
		} else {
			$result[$k] = stripslashes($v);
		}
	}

	return $result;
}

/**
 * Generates a password
 *
 * @return string
 */
function genPass() 
{
	$vok = "aoueiy"; // The vowels used
	$kons = "bcdfghjklmnprstvx";  // The consonants used
	$max = 3; // Nr of couples generated
	$tmp = '';
	
	for ($x = 0; $x < $max; $x++)
	{
		srand((double)microtime()*1000000);
		
		$tmp .= $kons{rand() % strlen($kons)};
		$tmp .= $vok{rand() % strlen($vok)};
	}
	
	$tmp .= rand(11, 99);
	
	return $tmp;
}

/**
 * Generates a random string
 *
 * Returns a random md5 hash that can be used as session id
 * @return string
 */
function randomString()
{
	if (isset($_SERVER['UNIQUE_ID'])) {
		$random = md5(md5($_SERVER['UNIQUE_ID']) . $_SERVER['UNIQUE_ID']);
	}
	else {
		srand(microtime(1) * 10000000);
		$random = md5(uniqid(mt_rand()));
	}
	
	return $random;
}

/**
 * Retrieve an IP
 *
 * Takes proxies into account.
 * @return string
 */
function getIp()
{
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} 
	else {
		$ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
	}
	
	return $ip;
}

/**
 * Retrieve the proxy address, if any
 *
 * @return string
 */
function getProxy()
{
	$proxy = '';
	
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$proxy = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
	} 
	
	return $proxy;
}

/**
 * Initialize a session
 *
 * This also prevents XSS attacks.
 */
function initSession()
{
	session_destroy();
	
	$sessid = randomString();
		
	session_id($sessid);
	session_start();
	
	$_SESSION['online'] = TRUE;
	$_SESSION['ip'] = getIp();
	$_SESSION['proxy'] = getProxy();
}

/**
 * Calculates someones age from their birthdate, without depending on unixtime
 *
 * @param string $date
 * @return int
 */
function getAge($date)
{
	list($tyear, $tmonth, $tday) = explode('-', $date);
	return getAgeByParams($tyear, $tmonth, $tday);
}

function getAgeByParams($tyear, $tmonth, $tday)
{
	list($nyear, $nmonth, $nday) = array(date('Y'), date('m'), date('d'));

	$age = $nyear - $tyear;
	$age -= ($tmonth > $nmonth || ($tmonth == $nmonth && $tday > $nday));

	return $age;
}

/**
 * Append http:// before a link, if it isn't already appended
 *
 * @param string $in
 * @return string
 */
function fixLink($in)
{
	$in = trim(strtolower($in));
	if ($in == '') { return ''; }
	return ((substr($in, 0, 7) == 'http://') ? $in : 'http://'.$in);
}

/**
 * 
 */
function appendAddress($url)
{
	if (substr($url, 0, strlen($url)) != $url) {
		$url = ADDRESS . $url;
	}
	
	return $url;
}

/**
 * Make emails obscure, to trick spambots.
 *
 * Replaces . with [dot] and @ with [at].
 * @param string $email
 * @return string
 */
function obscureEmail($email)
{
	return strtr($email, array('.' => '[dot]', '@' => '[at]'));
}

/*****************************************************************************
 * Debug functions
 *****************************************************************************/

/**
 * Formats a trace back as a table
 *
 * @param string $trace
 * @return string
 */
function traceAsTable($trace)
{
	$traceback = '';
	$traceback .= '<table border="1">';
		$traceback .= '<tr>';
			$traceback .= '<td><b>file</b></td>';
			$traceback .= '<td><b>line</b></td>';
			$traceback .= '<td><b>function</b></td>';
		$traceback .= '</tr>';
		foreach($trace as $t)
		{
			$traceback .= '<tr>';
				if (isset($t['file'])) {
					$traceback .= '<td>'.$t['file'].'</td>';
				}
				if (isset($t['line'])) {
					$traceback .= '<td>'.$t['line'].'</td>';
				}
				$traceback .= '<td>'.$t['function'].'</td>';
			$traceback .= '</tr>';
		}
	$traceback .= '</table>';	

	return $traceback;
}

/**
 * Simple profiling
 *
 * Outputs a comment with the current time elapsed since the
 * page started generation, along with a profiling point description.
 * @param string $point
 */
function profile($point)
{
	echo '<!-- ' . $point . ': ' . (microtime(1)-STARTTIME) . 's -->'."\r\n";
}

/**
 * Makes an entry into log/dev.log
 *
 * @param string $msg
 */
function devLog($msg)
{
	$output = ' -- ' . date('Y-m-d H:i:s (U)')."\r\n";
	$output .= ' -- GET '.$_SERVER['REQUEST_URI'].' by '.getIp()."\r\n";
	$output .= $msg;
	file_put_contents(ROOT_PATH . '/log/dev.log', $output, FILE_APPEND);
}

/*****************************************************************************
 * HTML/XML helper functions
 *****************************************************************************/

/**
 * Cleans up the HTML provided using tidy
 *
 * @param string $in
 * @return string
 */
function tidyClean($in)
{
	$tidy = new tidy();
	$tidy->parseString($in, ROOT_PATH . '/functions/tidyconfig.conf');
	$tidy->cleanRepair();
	$in = (string)$tidy;
	
	return $in;
}

/**
 * Decode HTML entities
 *
 * @param string $in
 * @return string
 */
function decodeHtmlEntities($in, $ignoreReserved = TRUE)
{
	$trans = array_flip(get_html_translation_table(HTML_ENTITIES));
	
	// We don't want these chars decoded. Trust me.
	if ($ignoreReserved)
	{
		unset($trans['&amp;']);
		unset($trans['&gt;']);
		unset($trans['&lt;']);
		unset($trans['&quot;']);
	}
	
	return strtr($in, $trans);
}

/**
 * Filter out invalid entities
 *
 * Filters out entities that isn't in get_html_translation_table(HTML_ENTITIES)
 * @param string $data
 * @return string
 */
function filterInvalidEntities($data)
{
   preg_match_all('/&([^#;])+;/', $data, $matches);
   $filter = create_function('$var','return !array_search($var, get_html_translation_table(HTML_ENTITIES));');
   $unwanted = array_filter($matches[0], $filter);
   return str_replace($unwanted,'',$data);
}

/**
 * Creates an simpleXml-objekt from file and throws an exception on failure
 *
 * @param string $path
 * @return object simpleXml
 */
function createSxml($path)
{
	if (!file_exists($path)) {
		throw new generalException($path . " could not be found.");
	}
	
	$sxml = @simplexml_load_file($path);
	if (!$sxml) {
		throw new xml_exception('Failed to load file: ' . $path, 0);
	}
	
	return $sxml;
}

/*****************************************************************************
 * Input data formating functions
 *****************************************************************************/

/**
 * Cuts the string at $len, but makes sure it doesn't break up any entities
 * in the process. If so, it cuts the string right before the broken entity
 *
 * @param string $str
 * @param int $len
 * @return string
 */
function cutSafe($str, $len)
{
	if ($len > strlen($str)) {
	        return $str;
	}
	
	$cut = substr($str,0,$len);
	
	$amp = strrpos($cut, '&');
	$stop = strrpos($cut, ';');
	
	if ($amp > $stop) {
	        $cut = substr($cut,0,$amp);
	}
	
	return $cut;
}

/**
 * Truncate a long word
 *
 * Cuts a string at $len, and adds ... to indicate truncation.
 * Used by urlToLink()
 * @param string $word Word to be truncated.
 * @param int $len Maximum length before truncation.
 * @return string
 */
function dotTruncate($word, $len)
{
	if ($len) {
		return (strlen($word ) > $len) ? cutSafe($word, $len).'...' : $word;
	}
	else {
		return $word;
	}
}

/**
 * Convert plain URLs to links
 *
 * Uses regexp. Depens on dotTruncate().
 * @param string $input
 * @param int $len Maximum length of the link text.
 * @return string
 */
function urlToLink($input, $len = 75, $target = "_blank")
{
	//$charlist = '~%@!\(\)\[\]\/a-zA-Z0-9_.,\*\+\\\\-';
	
	//die($input);
	
	$uri = '/(?<!")https?:\/\/([a-z0-9][a-z0-9-]*[a-z0-9]\.)+([a-z]{2,6})\/?'.
	       '((?<=\/)(([~%!@\\\(\)\$a-zA-Z0-9_.,-]+\/?))*'.
	         '((?<=\/)[~%!@\\\(\)\$a-zA-Z0-9_.,\*\+-]+\.[~%!@\\\(\)\$a-zA-Z0-9_.,\*\+-]+)?'.
	         '(\?([~%@!\\\(\)\[\]\/a-zA-Z0-9_.,\*\+-]+=[~%@!\\\(\)\[\]\/a-zA-Z0-9_.,\*\+-]*(&amp;|&)?)*)?'.
	         '(#[a-z0-9][.a-z0-9_-]*)?)?(?=\b)/ie';
	
	return preg_replace($uri, '"<a href=\"$0\" rel=\"nofollow\" target=\"' . $target . '\">" . dotTruncate( "$0",$len ) . "</a>"', $input);
} 

/**
 * Wordwraps everything but links
 *
 * Uses a regexp. Depends on wordwrap().
 * @param string $string String to be wrapped.
 * @param int $width Maximum length of a single word.
 * @param string $break The character that delimits a new word.
 * @return string
 */
function customWordwrap($string, $width = 75, $break = ' ')
{
	$pattern = '/(^|\s)(?!(?:http:\/\/(?:www\.)?|www\.))([\w??????!?.,-]{'. $width .',})(\s|$)/e';
	return preg_replace( $pattern, '"$1".wordwrap( "$2", $width, $break, true )."$3"', $string );
}

/**
 * Wrapper function for cutSafe and htmlspecialchars. Escapes a string, and avoid
 * cutting entities.
 *
 * @param string $str
 * @param int $len
 * @return string
 */
function xmlSafe($str, $len)
{
	if (!$str) {
		return $str;
	}
	
	return cutSafe(htmlspecialchars($str),$len);
}

/**
 * Calls customWordwrap, urlToLink and nl2br.
 *
 * Formats form data to be displayed. Should be called before information
 * is stored to database.
 * @param string $in
 * @param int $wordwrap Wrapping point
 * @return string
 */
function preformat($in, $wordwrap = 50)
{
	if (!$in) {
		return $in;
	}
	
	$in = htmlspecialchars($in);
	
	if ($wordwrap > 0) {
	    $in = customWordwrap($in, $wordwrap, ' ');
	}
		
	$in = urlToLink($in, $wordwrap);
	$in = nl2br($in);
	
	return $in;
}

function outputSimple($in, $wordwrap = 50)
{
	if (!$in) {
		return $in;
	}
	
	$in = htmlspecialchars($in);
	
	if ($wordwrap > 0) {
	    $in = customWordwrap($in, $wordwrap, ' ');
	}
		
	$in = preg_replace('/\[[^\]]+\]/', '', $in);
	$in = urlToLink($in, $wordwrap);
	$in = nl2br($in);
	
	return $in;
}

function outputTruncated($in, $truncate, $wordwrap = 50)
{
	if (!$in) {
		return $in;
	}
	
	$in = preg_replace('/\[[^\]]+\]/', '', $in);
	$in = dotTruncate($in, $truncate);
	$in = htmlspecialchars($in);
	
	if ($wordwrap > 0) {
	    $in = customWordwrap($in, $wordwrap, ' ');
	}
	
	while (strpos($in, "\r\n\r\n\r\n") !== false) {
		$in = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $in);
	}
	
	$in = nl2br($in);
	
	return $in;
}

function outputParagraph($in, $wordwrap = 50)
{
	if (!$in) {
		return $in;
	}
	
	$in = preg_replace('/\[[^\]]+\]/', '', $in);
	$in = htmlspecialchars($in);
	$in = urlToLink($in, $wordwrap);
	$in = cutSafe($in, strpos($in, "\n"));
	
	if ($wordwrap > 0) {
	    $in = customWordwrap($in, $wordwrap, ' ');
	}
	
	return $in;
}

function outputFormat($in, $advanced = true, $wordwrap = 50)
{
	if (!$in) {
		return $in;
	}
	
	$in = htmlspecialchars($in);
	
	if ($wordwrap > 0) {
	    $in = customWordwrap($in, $wordwrap, ' ');
	}
		
	$in = bbcode($in, $advanced);
	$in = urlToLink($in, $wordwrap);
	$in = nl2br($in);
	
	return $in;
}

/*
 * Returns a less detailed information about stock supplies
 * @param int $input
 */
function stockString($input)
{
	if ($input > 50) {
		return ">50";
	} elseif ($input > 10) {
		return "10 - 50";
	} elseif ($input > 0) {
		return "<10";
	} else {
		return "Slut";
	}
}

/*****************************************************************************
 * Common callback functions
 *****************************************************************************/

/**
 * Format a unix timestamp
 *
 * @param string $timestamp Unix timestamp
 * @param bool $includetime When true, the date also includes the time of day
 * @return string
 */
function standardDate($timestamp, $includetime = TRUE)
{
	if ($timestamp == 0)
		return 'Aldrig';

	list($cyear, $cmonth, $cday, $chour, $cminute) = explode(' ',date('Y m d H i'));
	list($yyear, $ymonth, $yday) = explode(' ',date('Y m d', time() - 3600*24));
	list($year, $month, $day, $hour, $minute) = explode(' ',date('Y m d H i', $timestamp));
	
	$date = '';
	
	if ($year == $cyear && $month == $cmonth && $day == $cday) {
		$date .= 'Idag ';
	} elseif ($yyear == $cyear && $month == $ymonth && $day == $yday) {
		$date .= 'Igår ';
	} else {
		$months = array(1 => 'jan','feb','mar','apr','maj','jun',
		                'jul','aug','sep','okt','nov','dec');
		
		$date .= (int)$day.' '.$months[(int)$month].' ';
	}
		
	if ($year != $cyear) {
		$date .= $year.' ';
	}
	
	if ($includetime) {
		$date .= ' kl '.$hour.':'.$minute;
	}
	
	return $date;
}

/**
 * Alias for standardDate, that excludes the time of day
 *
 * @param string $timestamp Unix timestamp
 * @return string
 */
function standardDateNoTime($timestamp)
{
	return standardDate($timestamp, FALSE);
}

/**
 * Returns a date formatted for use with rss 2
 *
 * @param unknown_type $timestamp
 * @return unknown
 */
function rss2Date($timestamp)
{
	return date('r', $timestamp);
}

/**
 * Returns a date formatted for use with atom
 *
 * @param int $timestamp
 * @return string
 */
function atomDate($timestamp)
{
	return date(DATE_ATOM, $timestamp);
}

/**
 * Standard number format
 *
 * @param int $number
 * @return string
 */
function standardNumberFormat($number)
{
	return number_format($number, 0, '.', ' ');	
}

/**
 * Standard file size format
 *
 * Reformats it to the appropriate prefix
 * @param int $bytes
 * @return string
 */
function standardFileSize($bytes)
{
	$units = array('kB', 'MB', 'GB', 'TB', 'PB');
	
	$i = 0;
	while ($bytes > 1024) 
	{
		$bytes /= 1024;
		$unit = $units[$i];
		$i++;
	}
	
	$bytes = number_format($bytes, 2, '.', ' ');
	
	return sprintf('%d %s', $bytes, $unit);	
}

function safeLink($str)
{
	if (strpos($str, "http://") !== 0 && $str[0] != "/") {
		$str = "http://" . $str;
	}
	
	return $str;
}

function bbcode($str, $advanced)
{
	//echo $currentElement->nodeName."\n";
	//$str = tidyClean($str);
	
	$str = preg_replace('/\[b\](.*?)\[\/b\]/is','<span style="font-weight: bold;">$1</span>',$str);
	$str = preg_replace('/\[i\](.*?)\[\/i\]/is','<span style="font-style: italic;">$1</span>',$str);
	$str = preg_replace('/\[u\](.*?)\[\/u\]/is','<span style="text-decoration: underline;">$1</span>',$str);
	$str = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/ise','"<a href=\"" . safeLink("$1") . "\">$2</a>"',$str);
	$str = preg_replace('/\[url\](.*?)\[n\](.*?)\[\/url\]/ise','"<a href=\"" . safeLink("$1") . "\">$2</a>"',$str);
	$str = preg_replace('/\[url\](.*?)\[\/url\]/ise','"<a href=\"" . safeLink("$1") . "\">$1</a>"',$str);
	$str = preg_replace('/\[c=([^\]]*)\](.*?)\[\/c\]/is','<span style="color: $1">$2</span>',$str);
	$str = preg_replace('/\[citat\](.*?)\[\/citat\]/is','<div style="font-weight: bold; margin-bottom: 3px;">Citat:</div><blockquote style="border: 1px solid #949494; margin: 0; padding: 5px;">$1</blockquote>',$str);
	$str = preg_replace('/\[citat=([^\]]*)\](.*?)\[\/citat\]/is','<div style="font-weight: bold; margin-bottom: 3px;">$1 skrev:</div><blockquote style="border: 1px solid #949494; margin: 0; padding: 5px;">$2</blockquote>',$str);
	
	if ($advanced) {
		$str = preg_replace('/\[center\](.*?)\[\/center\]/is','<div style="text-align: center;">$1</div>',$str);
		$str = preg_replace('/\[right\](.*?)\[\/right\]/is','<div style="text-align: right;">$1</div>',$str);
		$str = preg_replace('/\[img\](.*?)\[\/img\]/is','<img src="$1" style="border: 0" />',$str);
		$str = preg_replace('/\[hr]/is','<hr />',$str);
	}

	return $str;
}

function safeAlias($name)
{
	$name = html_entity_decode($name);
	$name = strtolower($name);
	$name = str_replace(array(" ","á","í","é","ú","å","ä","ö","ë"), 
		array("_","a","i","e","u","a","a","o","e"), $name);
	return preg_replace("/[^A-Z0-9_]+/i", "", $name);
}

function flash()
{
	if (!array_key_exists('flash', $_SESSION)) {
		return;
	}
		
	echo '<p class="flash">' . $_SESSION["flash"] . '</p>';
	unset($_SESSION["flash"]);
}
