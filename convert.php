<?php

function reverse_bbcode($str)
{
	$str = preg_replace('/<center>(.*?)<\/center>/is','[center]$1[/center]',$str);
	$str = preg_replace('/<i>(.*?)<\/i>/is','[i]$1[/i]',$str);
	$str = preg_replace('/<b>(.*?)<\/b>/is','[b]$1[/b]',$str);
	$str = preg_replace('/<u>(.*?)<\/u>/is','[u]$1[/u]',$str);
	$str = preg_replace('/<a href="([^"]*)"[^>]*>([^>]*)<\/a>/is','[url]$1[n]$2[/url]',$str);
	$str = preg_replace('/<img src="([^"]*)"[^>]*>/Uis','[img]$1[/img]',$str);
	$str = preg_replace('/\<hr\w*[\/]*>/is','[hr]',$str);
	$str = preg_replace('/\<br\w*[\/]*>/is',"",$str);

	return $str;
}

function flattenName($name)
{
	$name = html_entity_decode($name);
	$name = strtolower($name);
	$name = str_replace(array(" ","á","í","é","ú","å","ä","ö","ë"), 
		array("_","a","i","e","u","a","a","o","e"), $name);
	return preg_replace("/[^A-Z0-9_]+/i", "", $name);
}

$conn = mysql_connect("localhost", "root", "-") or die(mysql_error());
mysql_select_db("catahya2", $conn) or die(mysql_error());

mysql_query("SET NAMES latin1", $conn) or die(mysql_error());

echo "Catahya 1 to Catahya 2 conversion script.\n";

$clean = array();
//$clean[] = "artwork";				// done
//$clean[] = "artwork_comment";		// done
//$clean[] = "forum";					// done
//$clean[] = "forum_reply";			// done
//$clean[] = "forum_thread";			// done
//$clean[] = "group";
//$clean[] = "group_member";
//$clean[] = "guild";
//$clean[] = "guild_history";
//$clean[] = "guild_level";
//$clean[] = "guild_member";
//$clean[] = "member"; 				// done
//$clean[] = "member_character";		// done
//$clean[] = "member_guestbook";		// done
//$clean[] = "member_account";			// done
//$clean[] = "member_login";			// irrelevant
//$clean[] = "member_online";			// irrelevant
//$clean[] = "member_photo";
//$clean[] = "member_profile";		// done
//$clean[] = "member_profilevisit";	// irrelevant
//$clean[] = "member_relation";		// done
//$clean[] = "member_userdata";		// done
//$clean[] = "message_folder";		// irrelevant
//$clean[] = "message_reply";
//$clean[] = "message_thread";
//$clean[] = "message_thread_member";
$clean[] = "text";				// done
$clean[] = "text_comment";		// done
$clean[] = "text_image";		// done
$clean[] = "text_meta_book";		// done
$clean[] = "text_meta_movie";		// done
$clean[] = "text_meta_game";		// done
$clean[] = "text_meta_music";		// done

echo "Cleaning current database:\n";
foreach ($clean as $table) {
	$sql = sprintf("TRUNCATE `%s`", $table);
	mysql_query($sql, $conn) or die(mysql_error());
	echo $sql."\n";
}

/*
echo "Transferring users: ";

$sqlInsertMember  = "INSERT INTO member (member_id, member_alias, member_flatalias, member_password, "
                  . "member_gender, member_age, member_status, member_online, "
                  . "member_photo, member_photostatus, member_quickdesc, member_city) ";
$sqlInsertMember .= "SELECT id, UserName, UserName, Password, "
                  . "IF(Kon = 'Kvinna', 'female', 'male'), "
                  . "(YEAR(CURDATE())-YEAR(personnr)) - (RIGHT(CURDATE(),5)<RIGHT(personnr,5)), "
                  . "'active', '0', '0.jpg', '1', '', STad ";
$sqlInsertMember .= "FROM catahya1.medlem ";
$sqlInsertMember .= "ORDER BY id ";

mysql_query($sqlInsertMember, $conn) or die(mysql_error());
echo "Inserted " . mysql_affected_rows($conn) . " users into member.\n";

echo "Processing member_alias and member_flatalias: ";
$sqlSelect  = "SELECT * FROM member";
$result = mysql_query($sqlSelect) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	$sqlUpdate  = "UPDATE member ";
	$sqlUpdate .= "SET member_alias = '%s', member_flatalias = '%s', member_city = '%s' ";
	$sqlUpdate .= "WHERE member_id = %d";

	$i = 0;
	do {
		if ($i > 0) {
			$flatAlias = $arr["member_flatalias"] . "_" . $i;
		} else {
			$flatAlias = $arr["member_flatalias"];
		}
		$flatAlias = flattenName($flatAlias);
		$sql = sprintf($sqlUpdate, html_entity_decode(trim($arr["member_alias"])), 
			$flatAlias, html_entity_decode($arr["member_city"]), $arr["member_id"]);
		//echo $sql."\n";
		$i++;
	} while (!mysql_query($sql, $conn));

}

echo "Completed.\n";

echo "Transferring account info to member_account: ";
$sqlInsertApproved  = "INSERT INTO member_account (member_id, account_timestamp, account_confirmed, "
                    . "account_confirmedby, account_firstname, account_surname, account_address, "
                    . "account_zipcode, account_city, account_country, account_phonenr, account_ssn, "
                    . "account_email) ";
$sqlInsertApproved .= "SELECT id, unix_timestamp(MedlemDatum), unix_timestamp(MedlemDatum), godkandav, "
                    . "Fnamn, Enamn, Adress, PostNr, Stad, IF(utland=1,'Utland',''), Telefon, "
                    . "concat(replace(PersonNr,'-',''),'-',PersonNrCtrl), Epost ";
$sqlInsertApproved .= "FROM catahya1.medlem ";
$sqlInsertApproved .= "ORDER BY id";

mysql_query($sqlInsertApproved, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Removing entities from account data: ";
$sqlSelect  = "SELECT * FROM member_account";
$result = mysql_query($sqlSelect, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	$sqlUpdate  = "UPDATE member_account SET ";
	$sqlUpdate .= "account_firstname = '%s', ";
	$sqlUpdate .= "account_surname = '%s', ";
	$sqlUpdate .= "account_address = '%s', ";
	$sqlUpdate .= "account_city = '%s' ";
	$sqlUpdate .= "WHERE member_id = %d";

	mysql_query(sprintf($sqlUpdate, 
		mysql_real_escape_string(html_entity_decode($arr["account_name"])), 
		mysql_real_escape_string(html_entity_decode($arr["account_surname"])), 
		mysql_real_escape_string(html_entity_decode($arr["account_address"])), 
		mysql_real_escape_string(html_entity_decode($arr["account_city"])), 
		$arr["account_id"])) or die(mysql_error());
}
echo "Completed.\n";

echo "Transferring character to member_character: ";
$sqlInsertCharacter  = "INSERT INTO member_character (member_id, character_race, character_class, "
                     . "character_alignment, character_description) ";
$sqlInsertCharacter .= "SELECT id, Kras, Kklass, Kalignment, Ktext FROM catahya1.medlem ";
$sqlInsertCharacter .= "ORDER BY id";

mysql_query($sqlInsertCharacter, $conn) or die(mysql_error());
echo "Completed.\n";

echo "Removing entities from character description: ";
$sqlSelect  = "SELECT * FROM member_character";
$result = mysql_query($sqlSelect, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	$sqlUpdate  = "UPDATE member_character SET character_description = '%s' ";
	$sqlUpdate .= "WHERE member_id = %d";

	mysql_query(sprintf($sqlUpdate, mysql_real_escape_string(html_entity_decode(nl2br($arr["character_description"]))), 
		$arr["member_id"])) or die(mysql_error());
}
echo "Completed.\n";

echo "Importing profile: ";
$sqlInsertProfile  = "INSERT INTO member_profile (member_id, member_name, member_email, "
                   . "member_birthdate, member_jabber, member_msn, "
                   . "member_homepage, member_note, member_presentation) ";
$sqlInsertProfile .= "SELECT id, Fnamn, "
                   . "IF(hideEpost=1,'-',Epost), PersonNr, "
                   . "'', MSN, Hemsida, '', Text FROM catahya1.medlem ORDER BY id";
mysql_query($sqlInsertProfile, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Removing entities from profile data: ";
$sqlSelect  = "SELECT * FROM member_profile";
$result = mysql_query($sqlSelect, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	$sqlUpdate  = "UPDATE member_profile SET ";
	$sqlUpdate .= "member_name = '%s' ";
	$sqlUpdate .= "WHERE member_id = %d";

	mysql_query(sprintf($sqlUpdate, 
		mysql_real_escape_string(html_entity_decode($arr["member_name"])), 
		$arr["member_id"])) or die(mysql_error());
}
echo "Completed.\n";

echo "Transferring userdata: ";
$sqlInsertUserdata  = "INSERT INTO member_userdata (member_id, theme_id, member_lastlogin, "
                    . "member_memberdate,  member_minonline, member_logintotal, member_visitstotal, "
                    . "member_gbrecv, member_gbsent) ";
$sqlInsertUserdata .= "SELECT id, 1, 0, unix_timestamp(MedlemDatum), "
                    . " 0, Slogin, 0, 0, 0 FROM catahya1.medlem ORDER BY id";

mysql_query($sqlInsertUserdata, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Transferring relations: ";
$sql = "SELECT * FROM member";
$qry = mysql_query($sql, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($qry)) {
	$sqlInsertRelations  = "INSERT INTO member_relation (relation_memberid1, relation_memberid2, "
	                     . "relation_action, relation_irl, relation_timestamp, relation_approved) ";
	$sqlInsertRelations .= "SELECT MedlemID, VanID, '', '0', unix_timestamp(), '1' FROM catahya1.vanner ";
	$sqlInsertRelations .= "WHERE MedlemID IN (SELECT VanID FROM catahya1.`vanner` "
    	                 . "WHERE MedlemID = %d) ";
	$sqlInsertRelations .= "AND VanID = %d";
	mysql_query(sprintf($sqlInsertRelations, $arr["member_id"], $arr["member_id"]), $conn);

	$sqlInsertRelations  = "INSERT INTO member_relation (relation_memberid1, relation_memberid2, "
    	                 . "relation_action, relation_irl, relation_timestamp, relation_approved) ";
	$sqlInsertRelations .= "SELECT VanID, MedlemID, '', '0', unix_timestamp(), '1' FROM catahya1.vanner ";
	$sqlInsertRelations .= "WHERE MedlemID IN (SELECT VanID FROM catahya1.`vanner` "
    	                 . "WHERE MedlemID = %d) ";
	$sqlInsertRelations .= "AND VanID = %d";
	mysql_query(sprintf($sqlInsertRelations, $arr["member_id"], $arr["member_id"]), $conn);
}

echo "Completed.\n";

echo "Transferring guestbook posts: ";
$sqlGuestbook  = "INSERT INTO member_guestbook (guestbook_from, guestbook_to, guestbook_timestamp, "
               . "guestbook_msg, guestbook_secret, guestbook_read, guestbook_answered) ";
$sqlGuestbook .= "SELECT FromID, ToID, unix_timestamp(Datum), text, '0', '1', '1' ";
$sqlGuestbook .= "FROM catahya1.gastbok ORDER BY ID";

mysql_query($sqlGuestbook, $conn) or die(mysql_error());

echo "Done.\n";

echo "Removing entities from guestbook data: ";
$sqlSelect  = "SELECT * FROM member_guestbook";
$result = mysql_query($sqlSelect, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	$sqlUpdate  = "UPDATE member_guestbook SET ";
	$sqlUpdate .= "guestbook_msg = '%s' ";
	$sqlUpdate .= "WHERE guestbook_id = %d";

	mysql_query(sprintf($sqlUpdate, 
		mysql_real_escape_string(html_entity_decode($arr["guestbook_msg"])), 
		$arr["guestbook_id"])) or die(mysql_error());
}
echo "Completed.\n";

echo "Updating member_gbrecv and member_gbsent: ";

$sqlUpdate  = "UPDATE member_userdata a ";
$sqlUpdate .= "SET member_gbrecv = (SELECT COUNT(*) "
            . "FROM member_guestbook b WHERE b.guestbook_to = a.member_id), "; 
$sqlUpdate .= "member_gbsent = (SELECT COUNT(*) "
            . "FROM member_guestbook b WHERE b.guestbook_from = a.member_id) "; 

mysql_query($sqlUpdate, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Importing forums: ";

$sqlInsertForums  = "INSERT INTO forum (forum_id, category_id, guild_id, access_id, "
                  . "forum_name, forum_description, forum_threadcount, forum_replycount, "
                  . "forum_lastthreadid, forum_lastmemberid, forum_lasttimestamp, "
                  . "forum_guildlevel) ";
$sqlInsertForums .= "SELECT forumid, IF(priv=1,3,1), 0, IF(priv=1,7,3), fname, ftext, 0, 0, 0, 0, 0, 'member' ";
$sqlInsertForums .= "FROM catahya1.allforum_forum ";

mysql_query($sqlInsertForums, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Transferring threads: ";

$sqlInsertThreads  = "INSERT INTO forum_thread (thread_id, forum_id, member_id, thread_timestamp, "
                   . "thread_title, thread_text, thread_deleted, thread_sticky, thread_lastmemberid, "
                   . "thread_lasttimestamp, thread_replycount) ";
$sqlInsertThreads .= "SELECT tid, tforumid, tMemberID, unix_timestamp(tDate), tName, tPost, "
                   . "'0', IF(tKlistrad = 1, '1', '0'), tMemberID, unix_timestamp(tDate), 0 ";
$sqlInsertThreads .= "FROM catahya1.allforum_tread ";

mysql_query($sqlInsertThreads, $conn) or die(mysql_error());

echo "Completed.\n";
 
echo "Transferring replies: ";

$sqlInsertReplies  = 'INSERT INTO forum_reply (reply_id, thread_id, member_id, reply_timestamp, '
                   . 'reply_text, reply_deleted) ';
$sqlInsertReplies .= "SELECT id, tid, MemberID, unix_timestamp(Datum), Post, '0' ";
$sqlInsertReplies .= "FROM catahya1.allforum_answer ";

mysql_query($sqlInsertReplies, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Creating news forum with id 5: ";

$sqlNewsForum  = "INSERT INTO forum (forum_id, category_id, guild_id, access_id, forum_name, "
               . "forum_description) ";
$sqlNewsForum .= "VALUES (5, 1, 0, 15, 'Nyheter', 'Här postas alla catahyas nyheter!')";

mysql_query($sqlNewsForum, $conn);

echo "Completed.\n";

echo "Transferring news to forum: ";

$sqlNews = "SELECT * FROM catahya1.main_nyheter ORDER BY ID";
$qryNews = mysql_query($sqlNews);
while ($arrNews = mysql_fetch_assoc($qryNews)) {
	$sqlCreateThread  = "INSERT INTO forum_thread (forum_id, member_id, thread_timestamp, "
	                  . "thread_title, thread_text) ";
	$sqlCreateThread .= "VALUES (%d, %d, %d, '%s', '%s')";

	mysql_query(sprintf($sqlCreateThread, 5, $arrNews["MedlemID"], strtotime($arrNews["Datum"]),
		mysql_real_escape_string($arrNews["Rubrik"]), 
		mysql_real_escape_string(reverse_bbcode($arrNews["Text"]))),
		$conn) or die(mysql_error());

	$newId = mysql_insert_id($conn);

	$sqlComments = sprintf("SELECT * FROM catahya1.kom_nyhet WHERE NyhetID = %d ORDER BY ID", $arrNews["ID"]);
	$qryComments = mysql_query($sqlComments, $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlCreateReply  = "INSERT INTO forum_reply (thread_id, member_id, reply_timestamp, "
		                 . "reply_text) ";
		$sqlCreateReply .= "VALUES (%d, %d, %d, '%s')";

		mysql_query(sprintf($sqlCreateReply, $newId, $arrComment["MedlemID"], 
			strtotime($arrComment["Datum"]), 
			mysql_real_escape_string(html_entity_decode($arrComment["Text"]))), 
			$conn) or die(mysql_error());
	}
}

echo "Completed.\n";
*/

$sqlInsertText  = "INSERT INTO text (type_id, member_id, text_timestamp, "
				. "text_title, text_pretext, text_text, text_showpretext) ";
$sqlInsertText .= "VALUES (%d, %d, %d, '%s', '%s', '%s', '%s')";

echo "Transferring book reviews: ";

$sqlSelectBooks = "SELECT * FROM catahya1.re_bok ORDER BY ID";
$qrySelectBooks = mysql_query($sqlSelectBooks, $conn) or die(mysql_error());
while ($arrBook = mysql_fetch_assoc($qrySelectBooks)) {

	mysql_query(sprintf($sqlInsertText, 2, $arrBook["uID"], strtotime($arrBook["datum"]),
		mysql_real_escape_string(html_entity_decode($arrBook["titel"])), "",
		mysql_real_escape_string(html_entity_decode($arrBook["recension"])), "0"), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();
	
	$sqlMeta  = "INSERT INTO text_meta_book (text_id, book_grade, book_author, book_series, book_volume) ";
	$sqlMeta .= "VALUES (%d, %d, '%s', '%s', %d)";
	
	mysql_query(sprintf($sqlMeta, $id, 
		intval($arrBook["betyg"]), 
		mysql_real_escape_string(html_entity_decode($arrBook["efternamn"])),
		mysql_real_escape_string(html_entity_decode($arrBook["serie"])),
		intval($arrBook["nr"])), $conn) or die(mysql_error());
		
	if ($arrBook["bild"]) {
		$image = $arrBook["bild"];
		if (strpos($image, "http://") !== 0) {
			$image = "http://www.catahya.net/bilder/" . $image . ".jpg";
		}
		
		$sqlImage  = "INSERT INTO text_image (text_id, member_id, image_timestamp, image_size, "
		           . "image_name, image_title, image_description, image_gallery, image_width, image_height) ";
		$sqlImage .= "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', %d, %d)";
		
		mysql_query(sprintf($sqlImage, $id, $arrBook["uID"], strtotime($arrBook["datum"]), 0, $image, "", "", "0", 0, 0),
			$conn) or die(mysql_error());
	}
		
	$sqlSelectComments  = "SELECT * FROM catahya1.kom_recension ";
	$sqlSelectComments .= "WHERE recensionID = %d AND typ = 'litteratur' ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrBook["ID"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO text_comment (text_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring movie reviews: ";

$sqlSelectMovies = "SELECT * FROM catahya1.re_film ORDER BY ID";
$qrySelectMovies = mysql_query($sqlSelectMovies, $conn) or die(mysql_error());
while ($arrMovie = mysql_fetch_assoc($qrySelectMovies)) {
	mysql_query(sprintf($sqlInsertText, 3, $arrMovie["uID"], strtotime($arrMovie["datum"]),
		mysql_real_escape_string(html_entity_decode($arrMovie["titel"])), "",
		mysql_real_escape_string(html_entity_decode($arrMovie["text"])), "0"), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();
	
	$sqlMeta  = "INSERT INTO text_meta_movie (text_id, movie_grade, movie_director, movie_year, movie_length, movie_actors) ";
	$sqlMeta .= "VALUES (%d, %d, '%s', %d, %d, '%s')";
	
	mysql_query(sprintf($sqlMeta, $id, 
		intval($arrMovie["betyg"]), 
		mysql_real_escape_string(html_entity_decode($arrMovie["regi"])),
		intval($arrMovie["produktion"]),
		intval($arrMovie["width"]),
		mysql_real_escape_string(html_entity_decode($arrMovie["acters"]))), $conn) or die(mysql_error());

	if ($arrMovie["bild"]) {
		$image = $arrMovie["bild"];
		if (strpos($image, "http://") !== 0) {
			$image = "http://www.catahya.net/bilder/" . $image . ".jpg";
		}
		
		$sqlImage  = "INSERT INTO text_image (text_id, member_id, image_timestamp, image_size, "
		           . "image_name, image_title, image_description, image_gallery, image_width, image_height) ";
		$sqlImage .= "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', %d, %d)";
		
		mysql_query(sprintf($sqlImage, $id, $arrMovie["uID"], strtotime($arrMovie["datum"]), 0, $image, "", "", "0", 0, 0),
			$conn) or die(mysql_error());
	}
		
	$sqlSelectComments  = "SELECT * FROM catahya1.kom_recension ";
	$sqlSelectComments .= "WHERE recensionID = %d AND typ = 'film' ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrMovie["ID"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO text_comment (text_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring game reviews: ";

$sqlSelectGames = "SELECT * FROM catahya1.re_spel ORDER BY ID";
$qrySelectGames = mysql_query($sqlSelectGames, $conn) or die(mysql_error());
while ($arrGame = mysql_fetch_assoc($qrySelectGames)) {

	mysql_query(sprintf($sqlInsertText, 4, $arrGame["uID"], strtotime($arrGame["datum"]),
		mysql_real_escape_string(html_entity_decode($arrGame["titel"])), "",
		mysql_real_escape_string(html_entity_decode($arrGame["text"])), "0"), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();
	
	$sqlMeta  = "INSERT INTO text_meta_game (text_id, game_grade, game_type, game_distributor) ";
	$sqlMeta .= "VALUES (%d, %d, '%s', '%s')";
	
	mysql_query(sprintf($sqlMeta, $id, 
		intval($arrGame["betyg"]), 
		mysql_real_escape_string(html_entity_decode($arrGame["typ"])),
		mysql_real_escape_string(html_entity_decode($arrGame["tmark"]))), $conn) or die(mysql_error());

	// bild
	// screen1 + screen1text
	// screen2 + screen2text
	// screen3 + screen3text
	$images = array($arrGame["bild"] => "",
		$arrGame["screen1"] => $arrGame["screen1text"],
		$arrGame["screen2"] => $arrGame["screen2text"],
		$arrGame["screen3"] => $arrGame["screen3text"]);
	
	foreach ($images as $image => $description) {
		if (strpos($image, "http://") !== 0) {
			$image = "http://www.catahya.net/bilder/" . $image . ".jpg";
		}
		
		$sqlImage  = "INSERT INTO text_image (text_id, member_id, image_timestamp, image_size, "
		           . "image_name, image_title, image_description, image_gallery, image_width, image_height) ";
		$sqlImage .= "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', %d, %d)";
		
		mysql_query(sprintf($sqlImage, $id, $arrGame["uID"], strtotime($arrGame["datum"]), 0, $image, "", $description, 
			$description != "" ? "1" : "0", 0, 0),
			$conn) or die(mysql_error());
	}
		
	$sqlSelectComments  = "SELECT * FROM catahya1.kom_recension ";
	$sqlSelectComments .= "WHERE recensionID = %d AND typ = 'spel' ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrGame["ID"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO text_comment (text_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring music reviews: ";

$sqlSelectMusic = "SELECT * FROM catahya1.re_skiv ORDER BY ID";
$qrySelectMusic = mysql_query($sqlSelectMusic, $conn) or die(mysql_error());
while ($arrMusic = mysql_fetch_assoc($qrySelectMusic)) {

	mysql_query(sprintf($sqlInsertText, 5, $arrMusic["uID"], strtotime($arrMusic["datum"]),
		mysql_real_escape_string(html_entity_decode($arrMusic["titel"])), "",
		mysql_real_escape_string(html_entity_decode($arrMusic["text"])), "0"), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();
	
	$sqlMeta  = "INSERT INTO text_meta_music (text_id, music_grade, music_artist, music_year, music_tracks, music_length) ";
	$sqlMeta .= "VALUES (%d, %d, '%s', %d, %d, %d)";
	
	mysql_query(sprintf($sqlMeta, $id, 
		intval($arrMusic["betyg"]), 
		mysql_real_escape_string(html_entity_decode($arrMusic["artist"])),
		intval($arrMusic["release"]),
		intval($arrMusic["tracks"]),
		intval($arrMusic["width"])), $conn) or die(mysql_error());
		
	if ($arrMusic["bild"]) {
		$image = $arrMusic["bild"];
		if (strpos($image, "http://") !== 0) {
			$image = "http://www.catahya.net/bilder/" . $image . ".jpg";
		}
		
		$sqlImage  = "INSERT INTO text_image (text_id, member_id, image_timestamp, image_size, "
		           . "image_name, image_title, image_description, image_gallery, image_width, image_height) ";
		$sqlImage .= "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', %d, %d)";
		
		mysql_query(sprintf($sqlImage, $id, $arrMusic["uID"], strtotime($arrMusic["datum"]), 0, $image, "", "", "0", 0, 0),
			$conn) or die(mysql_error());
	}

	$sqlSelectComments  = "SELECT * FROM catahya1.kom_recension ";
	$sqlSelectComments .= "WHERE recensionID = %d AND typ = 'musik' ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrMusic["ID"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO text_comment (text_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring chronicles: ";

$sqlSelectChronicle = "SELECT * FROM catahya1.main_artiklar ORDER BY ID";
$qrySelectChronicle = mysql_query($sqlSelectChronicle, $conn) or die(mysql_error());
while ($arrChronicle = mysql_fetch_assoc($qrySelectChronicle)) {
	
	mysql_query(sprintf($sqlInsertText, 1, $arrChronicle["medlemid"], strtotime($arrChronicle["datum"]),
		mysql_real_escape_string(html_entity_decode($arrChronicle["rubrik"])), 
		mysql_real_escape_string(reverse_bbcode(html_entity_decode($arrChronicle["fortext"]))), 
		mysql_real_escape_string(reverse_bbcode(html_entity_decode($arrChronicle["text"]))), "1"), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();

	$sqlSelectComments  = "SELECT * FROM catahya1.kom_artikel ";
	$sqlSelectComments .= "WHERE ArtikelID = %d ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrChronicle["id"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO text_comment (text_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["MedlemID"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring Johans articles: ";

$sqlSelectJohan = "SELECT * FROM catahya1.forfattare WHERE kategori = 2";
$qrySelectJohan = mysql_query($sqlSelectJohan, $conn) or die(mysql_error());
while ($arrJohan = mysql_fetch_assoc($qrySelectJohan)) {
	
	$text = str_replace("\r\n","",$arrJohan["text"]);
	$text = str_replace("<BR><BR>","\r\n\r\n",$text);
	
	$meta = "";
	mysql_query(sprintf($sqlInsertText, 7, 661767, time(),
		mysql_real_escape_string(html_entity_decode($arrJohan["namn"])), 
		"", 
		mysql_real_escape_string(reverse_bbcode(html_entity_decode($text))),
		mysql_real_escape_string($meta), "0"), $conn) or die("text: " .mysql_error());
}

echo "Completed.\n";

echo "Transferring words of wisdom: ";

$sqlTransferWisdom  = "INSERT INTO text (type_id, member_id, text_timestamp, text_title, text_pretext, "
                   . "text_text) ";
$sqlTransferWisdom .= "SELECT 8, medlemid, unix_timestamp(), namn, '', text FROM catahya1.harordet";
// TODO: Transfer top 5 lists.

mysql_query($sqlTransferWisdom, $conn) or die(mysql_error());

echo "Completed.\n";

/*
echo "Transferring short stories: ";

$sqlInsertArtwork  = "INSERT INTO artwork (member_id, subtype_id, language_id, artwork_timestamp, "
                   . "artwork_title, artwork_text, artwork_published, artwork_publishedby) ";
$sqlInsertArtwork .= "VALUES (%d, %d, %d, %d, '%s', '%s', %d, %d)";

$sqlSelectPoems = "SELECT * FROM catahya1.novell_t ORDER BY ID";
$qrySelectPoems = mysql_query($sqlSelectPoems, $conn) or die(mysql_error());
while ($arrPoem = mysql_fetch_assoc($qrySelectPoems)) {
	
	$meta = "";
	mysql_query(sprintf($sqlInsertArtwork, $arrPoem["uID"], 1, 1, strtotime($arrPoem["datum"]),
		mysql_real_escape_string(html_entity_decode($arrPoem["titel"])), 
		mysql_real_escape_string(html_entity_decode($arrPoem["text"])),
		strtotime($arrPoem["datum"]), 11727), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();

	$sqlSelectComments  = "SELECT * FROM catahya1.kom_novell ";
	$sqlSelectComments .= "WHERE novellID = %d ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrPoem["id"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO artwork_comment (artwork_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Transferring poems: ";

$sqlSelectPoems = "SELECT * FROM catahya1.novell_d ORDER BY ID";
$qrySelectPoems = mysql_query($sqlSelectPoems, $conn) or die(mysql_error());
while ($arrPoem = mysql_fetch_assoc($qrySelectPoems)) {
	
	$meta = "";
	mysql_query(sprintf($sqlInsertArtwork, $arrPoem["uID"], 3, 1, strtotime($arrPoem["datum"]),
		mysql_real_escape_string(html_entity_decode($arrPoem["titel"])), 
		mysql_real_escape_string(html_entity_decode($arrPoem["text"])),
		strtotime($arrPoem["datum"]), 11727), $conn) or die("text: " .mysql_error());

	$id = mysql_insert_id();

	$sqlSelectComments  = "SELECT * FROM catahya1.kom_dikter ";
	$sqlSelectComments .= "WHERE novellID = %d ";
	$sqlSelectComments .= "ORDER BY id";
	$qryComments = mysql_query(sprintf($sqlSelectComments, $arrPoem["id"]), $conn) or die(mysql_error());
	while ($arrComment = mysql_fetch_assoc($qryComments)) {
		$sqlInsertComment  = "INSERT INTO artwork_comment (artwork_id, member_id, comment_timestamp, "
		                   . "comment_deleted, comment_title, comment_text) ";
		$sqlInsertComment .= "VALUES (%d, %d, %d, '0', '%s', '%s')";

		$sql = sprintf($sqlInsertComment, $id, $arrComment["medlemid"], strtotime($arrComment["Datum"]),
			"", mysql_real_escape_string(html_entity_decode($arrComment["Text"])));
		mysql_query($sql, $conn) or die($sql . "\n" . mysql_error());
	}	
}

echo "Completed.\n";

echo "Creating folders and transferring messages: ";

$sqlMembers  = "SELECT * FROM member ";
$qryMembers = mysql_query($sqlMembers, $conn) or die(mysql_error());
while ($arrMember = mysql_fetch_assoc($qryMembers)) {
	
	$sqlCreateFolder  = "INSERT INTO message_folder (member_id, folder_name, folder_type) ";
	$sqlCreateFolder .= "VALUES (%d, '%s', '%s')";
	
	mysql_query(sprintf($sqlCreateFolder, $arrMember["member_id"], "Meddelanden", "system"), $conn) or die(mysql_error());
	mysql_query(sprintf($sqlCreateFolder, $arrMember["member_id"], "Arkiv", "user"), $conn) or die(mysql_error());
}

$sqlTransferMessages  = "INSERT INTO message_thread (thread_id, thread_title, thread_text, thread_timestamp, thread_rcount) ";
$sqlTransferMessages .= "SELECT ID, Rubrik, Text, unix_timestamp(Datum), 1 ";
$sqlTransferMessages .= "FROM catahya1.meddelande ";

mysql_query($sqlTransferMessages, $conn) or die(mysql_error());

$start = 0;
$limit = 1000;
while (true) {
	$sqlSelect = sprintf("SELECT * FROM message_thread WHERE thread_id > %d ORDER BY thread_id LIMIT %d", $start, $limit);
	$qrySelect = mysql_query($sqlSelect, $conn) or die(mysql_error());
	while ($arr = mysql_fetch_assoc($qrySelect)) {
	
		$start = $arr["thread_id"];
		
		$sqlUpdate = "UPDATE message_thread SET thread_title = '%s', thread_text = '%s' WHERE thread_id = %d";
		
		$meta = "";
		mysql_query(sprintf($sqlUpdate, 
			mysql_real_escape_string(html_entity_decode($arr["thread_title"])), 
			mysql_real_escape_string(html_entity_decode($arr["thread_text"])), 
			$arr["thread_id"]), $conn) or die("text: " .mysql_error());
	}
	
	if (mysql_num_rows($qrySelect) != $limit) {
		break;
	}
	
	mysql_free_result($qrySelect);
}

$sqlTransferMessages  = "INSERT IGNORE INTO message_thread_member (thread_id, member_id, folder_id, thread_role, thread_read, "
                      . "thread_deleted, thread_lasttimestamp) ";
$sqlTransferMessages .= "SELECT ID, FromID, ";
$sqlTransferMessages .= "(SELECT folder_id FROM message_folder WHERE member_id = FromID AND folder_type = 'user'), ";
$sqlTransferMessages .= "'s', '1', '0', unix_timestamp(Datum) ";
$sqlTransferMessages .= "FROM catahya1.meddelande WHERE FromID != 0";

mysql_query($sqlTransferMessages, $conn) or die(mysql_error());

$sqlTransferMessages  = "INSERT IGNORE INTO message_thread_member (thread_id, member_id, folder_id, thread_role, thread_read, "
                      . "thread_deleted, thread_lasttimestamp) ";
$sqlTransferMessages .= "SELECT ID, ToID, ";
$sqlTransferMessages .= "(SELECT folder_id FROM message_folder WHERE member_id = ToID AND folder_type = 'user'), ";
$sqlTransferMessages .= "'r', '1', '0', unix_timestamp(Datum) ";
$sqlTransferMessages .= "FROM catahya1.meddelande WHERE ToID != 0";

mysql_query($sqlTransferMessages, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Transferring guilds: ";

$sqlClans = "SELECT * FROM catahya1.klan ORDER BY id";
$qryClans = mysql_query($sqlClans) or die(mysql_error());
while ($arrClan = mysql_fetch_assoc($qryClans)) {
	
	$sqlInsertGuild  = "INSERT INTO guild (guild_id, member_id, guild_name, guild_description, "
	                 . "guild_text, guild_requirements, guild_confirmed, guild_confirmedby) ";
	$sqlInsertGuild .= "VALUES (%d, %d, '%s', '%s', '%s', '%s', %d, %d)";
	
	mysql_query(sprintf($sqlInsertGuild, $arrClan["id"], $arrClan["grundareID"], 
		mysql_real_escape_string($arrClan["namn"]),
		mysql_real_escape_string(html_entity_decode($arrClan["beskrivning"])),
		mysql_real_escape_string(html_entity_decode($arrClan["text"])),
		mysql_real_escape_string(html_entity_decode($arrClan["krav"])),
		strtotime($arrClan["skapad"]), 11727), $conn) or die(mysql_error());

	$sqlInsertLevel  = "INSERT INTO guild_level (guild_id, group_id, level_name, level_access) ";
	$sqlInsertLevel .= "VALUES (%d, %d, '%s', '%s')";
	
	$levels = array();
	
	mysql_query(sprintf($sqlInsertLevel, $arrClan["id"], 0,
		mysql_real_escape_string(html_entity_decode($arrClan["grad1"])),
		"admin"), $conn) or die(mysql_error());
		
	$levels[1] = mysql_insert_id();
		
	mysql_query(sprintf($sqlInsertLevel, $arrClan["id"], 0,
		mysql_real_escape_string(html_entity_decode($arrClan["grad2"])),
		"member"), $conn) or die(mysql_error());
		
	$levels[2] = mysql_insert_id();
		
	mysql_query(sprintf($sqlInsertLevel, $arrClan["id"], 0,
		mysql_real_escape_string(html_entity_decode($arrClan["grad3"])),
		"member"), $conn) or die(mysql_error());
		
	$levels[3] = mysql_insert_id();
		
	mysql_query(sprintf($sqlInsertLevel, $arrClan["id"], 0,
		mysql_real_escape_string(html_entity_decode($arrClan["grad4"])),
		"member"), $conn) or die(mysql_error());
		
	$levels[4] = mysql_insert_id();
	
	mysql_query(sprintf($sqlInsertLevel, $arrClan["id"], 0,
		"Medlem",
		"member"), $conn) or die(mysql_error());
		
	$levels[5] = mysql_insert_id();
		
	$sqlSelectMember = sprintf("SELECT * FROM catahya1.klanm WHERE klanID = %d ORDER BY id", $arrClan["id"]);
	$qrySelectMember = mysql_query($sqlSelectMember, $conn) or die(mysql_error());
	while ($arrMember = mysql_fetch_assoc($qrySelectMember)) {
		
		$sqlInsertMember  = "INSERT INTO guild_member (guild_id, member_id, level_id, "
		                  . "member_guildtimestamp, member_guildstatement) ";
		$sqlInsertMember .= "VALUES (%d, %d, %d, %d, '%s')";
		
		$level = (int)$arrMember["grad"] > 0 ? $levels[(int)$arrMember["grad"]] : $levels[5];
		mysql_query(sprintf($sqlInsertMember, $arrMember["klanID"], $arrMember["medlemID"],
			$level, strtotime($arrMember["datum"]), 
			mysql_real_escape_string(html_entity_decode($arrClan["ansokText"]))), $conn) or die(mysql_error());
	}
	
	$sqlHistory  = "INSERT INTO guild_history (guild_id, history_timestamp, history_description) ";
	$sqlHistory .= "VALUES (%d, unix_timestamp(), '%s')";
	
	mysql_query(sprintf($sqlHistory, $arrClan["id"], 
		mysql_real_escape_string($arrClan["namn"] . " importerades till Catahya 2. Yay för Aderyn! <3")),
		$conn) or die(mysql_error());
		
	$sqlCreateForum  = "INSERT INTO forum (category_id, guild_id, access_id, forum_name, forum_description) ";
	$sqlCreateForum .= "VALUES (%d, %d, %d, '%s', '%s')";
	
	mysql_query(sprintf($sqlCreateForum, 0, $arrClan["id"], 0, 
		mysql_real_escape_string($arrClan["namn"]), ""), $conn) or die(mysql_error());
		
	$forumId = mysql_insert_id();
	
	$sqlSelectThreads = sprintf("SELECT * FROM catahya1.klanft WHERE klanID = %d ORDER BY tID", $arrClan["id"]);
	$qrySelectThreads = mysql_query($sqlSelectThreads, $conn) or die(mysql_error());
	while ($arrThread = mysql_fetch_assoc($qrySelectThreads)) {
		
		$sqlInsertThread  = "INSERT INTO forum_thread (forum_id, member_id, "
		                  . "thread_timestamp, thread_title, thread_text) ";
		$sqlInsertThread .= "VALUES (%d, %d, %d, '%s', '%s')";
		
		mysql_query(sprintf($sqlInsertThread, $forumId, $arrThread["tUserID"], strtotime($arrThread["tDate"]),
			mysql_real_escape_string($arrThread["tName"]), 
			mysql_real_escape_string($arrThread["tText"])), $conn) or die(mysql_error());
			
		$threadId = mysql_insert_id();
			
		$sqlSelectReplies = sprintf("SELECT * FROM catahya1.klanfa WHERE atID = %d ORDER BY aID", $threadId);
		$qrySelectReplies = mysql_query($sqlSelectReplies) or die(mysql_error());
		while ($arrReply = mysql_fetch_assoc($qrySelectReplies)) {
		
			$sqlInsertReply  = "INSERT INTO forum_reply (thread_id, member_id, reply_timestamp, reply_text) ";
			$sqlInsertReply .= "VALUES (%d, %d, %d, '%s')";
			
			mysql_query(sprintf($sqlInsertReply, $threadId, $arrReply["aUserID"], strtotime($arrReply["aDate"]),
				mysql_real_escape_string($arrReply["aText"])), $conn) or die(mysql_error());
		}
		
	}
}

echo "Completed.\n";

echo "Updating thread last fields: ";

$sqlUpdateThreads  = "UPDATE forum_thread a ";
$sqlUpdateThreads .= "SET thread_lastmemberid = (SELECT member_id FROM forum_reply b "
                   . "WHERE b.thread_id = a.thread_id ORDER BY reply_timestamp DESC LIMIT 1), ";
$sqlUpdateThreads .= "thread_lasttimestamp = (SELECT reply_timestamp FROM forum_reply b "
                   . "WHERE b.thread_id = a.thread_id ORDER BY reply_timestamp DESC LIMIT 1), ";
$sqlUpdateThreads .= "thread_replycount = (SELECT count(thread_id) FROM forum_reply b "
                   . "WHERE b.thread_id = a.thread_id) ";

mysql_query($sqlUpdateThreads, $conn) or die(mysql_error());

echo "Completed.\n";

echo "Update forum last fields: ";

$sqlUpdateForums  = "UPDATE forum a ";
$sqlUpdateForums .= "SET forum_lastthreadid = (SELECT thread_id FROM forum_thread b "
                   . "WHERE b.forum_id = a.forum_id ORDER BY thread_lasttimestamp DESC LIMIT 1), ";
$sqlUpdateForums .= "forum_lastmemberid = (SELECT thread_lastmemberid FROM forum_thread b "
                   . "WHERE b.forum_id = a.forum_id ORDER BY thread_lasttimestamp DESC LIMIT 1), ";
$sqlUpdateForums .= "forum_lasttimestamp = (SELECT thread_lasttimestamp FROM forum_thread b "
                   . "WHERE b.forum_id = a.forum_id ORDER BY thread_lasttimestamp DESC LIMIT 1), ";
$sqlUpdateForums .= "forum_threadcount = (SELECT count(thread_id) FROM forum_thread b "
                   . "WHERE b.forum_id = a.forum_id), ";
$sqlUpdateForums .= "forum_replycount = (SELECT sum(thread_replycount) FROM forum_thread b "
                   . "WHERE b.forum_id = a.forum_id) ";

mysql_query($sqlUpdateForums, $conn) or die(mysql_error());

echo "Completed.\n";


class SpecialGuild {
	public $name;
	public $forums;
	public $category;
	public $groups;
}

class GuildGroup {
	public $oldId;
	public $newName;
	public $level;
	public $description;
	public $newId;
	
	public function __construct($oldId, $newName, $level, $description = "") {
		$this->oldId = $oldId;
		$this->newName = $newName;
		$this->level = $level;
		$this->description = $description;
	}
}

$qryMax = mysql_query("SELECT max(guild_id) m FROM guild", $conn) or die(mysql_error());
$arrMax = mysql_fetch_assoc($qryMax);

echo sprintf("Importing special guilds (current max: %d): ", $arrMax["m"]);

$guilds = array();

$guild = new SpecialGuild;
$guild->name = "Cános";
$guild->forums = array(55);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(5, "Cáno", "admin");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Skribentrådet";
$guild->forums = array(47);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(5, "Cáno", "member");
$guild->groups[] = new GuildGroup(18, "Ingolmo", "member");
$guild->groups[] = new GuildGroup(19, "Vinyatir", "member");
$guild->groups[] = new GuildGroup(0, "Skribentansvarig", "admin");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Styrelsen";
$guild->forums = array(22, 57);
$guild->category = "official";
$guild->groups = array();
$guild->groups[] = new GuildGroup(1, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(0, "Vice Ordförande", "admin");
$guild->groups[] = new GuildGroup(0, "Kassör", "moderator");
$guild->groups[] = new GuildGroup(0, "Vice Kassör", "moderator");
$guild->groups[] = new GuildGroup(0, "Sekreterare", "moderator");
$guild->groups[] = new GuildGroup(0, "Vice Sekreterare", "moderator");
$guild->groups[] = new GuildGroup(4, "Ledamot", "moderator");
$guild->groups[] = new GuildGroup(0, "Suppleant", "moderator");
$guild->groups[] = new GuildGroup(2, "Revisor", "moderator");
$guild->groups[] = new GuildGroup(0, "Revisorssuppleant", "moderator");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Presidiet";
$guild->forums = array(31);
$guild->category = "official";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(27, "Ledamot", "moderator");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Meritgillet";
$guild->forums = array(32);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(29, "Ansvarig", "admin");
$guild->groups[] = new GuildGroup(0, "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Revisorer";
$guild->forums = array(19);
$guild->category = "official";
$guild->groups = array();
$guild->groups[] = new GuildGroup(2, "Revisor", "admin");
$guild->groups[] = new GuildGroup(0, "Medlem", "moderator");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Lägergruppen";
$guild->forums = array(11);
$guild->category = "general";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Lägergeneral", "admin");
$guild->groups[] = new GuildGroup(65, "Medlem", "moderator");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Konventsgruppen";
$guild->forums = array(60);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(93, "Ordförande", "admin");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Väktarrådet";
$guild->forums = array(65);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Registeransvarig", "admin");
$guild->groups[] = new GuildGroup(20, "Väktare", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Kodarrådet";
$guild->forums = array(6);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Kodaransvarig", "admin");
$guild->groups[] = new GuildGroup(21, "Teknisk administratör", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Moderatorrådet";
$guild->forums = array(21);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Moderatoransvarig", "admin");
$guild->groups[] = new GuildGroup(6, "Moderator", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Hemsiderådet";
$guild->forums = array(20);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(76, "Hemsideansvarig", "admin");
$guild->groups[] = new GuildGroup(array(77, 75, 31), "Medlem", "member");
$guild->groups[] = new GuildGroup(88, "Gillesmästare", "member");
$guild->groups[] = new GuildGroup(79, "Litteraturmästare", "member");
$guild->groups[] = new GuildGroup(84, "Alstermästare", "member");
$guild->groups[] = new GuildGroup(0, "Forumansvarig", "member");
$guild->groups[] = new GuildGroup(0, "Kodarsamordnare", "member");
$guild->groups[] = new GuildGroup(21, "Tekniska administratör", "member");
$guild->groups[] = new GuildGroup(0, "Registeransvarig", "member");
$guild->groups[] = new GuildGroup(20, "Väktare", "member");
$guild->groups[] = new GuildGroup(0, "Skribentsamordnare", "member");
$guild->groups[] = new GuildGroup(73, "Språkrådets ordförande", "member");
$guild->groups[] = new GuildGroup(0, "Länkmästare", "member");
$guild->groups[] = new GuildGroup(0, "Butiksmästare", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Lajvrådet";
$guild->forums = array(1,25);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(61, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(array(60,62), "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Kanalgruppen";
$guild->forums = array(26);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(24, "Medlem", "admin");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Verklighetsrådet";
$guild->forums = array(2);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(63, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(array(64,22), "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Antologirådet";
$guild->forums = array(64,3);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(67, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(array(66,68), "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Språkrådet";
$guild->forums = array(8);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(73, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(array(74,72), "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Valberedningen";
$guild->forums = array(4);
$guild->category = "official";
$guild->groups = array();
$guild->groups[] = new GuildGroup(3, "Valberedare", "admin");
$guild->groups[] = new GuildGroup(0, "Suppleant", "admin");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Novellprisjuryn";
$guild->forums = array(9);
$guild->category = "general";
$guild->groups = array();
$guild->groups[] = new GuildGroup(69, "Jurymedlem", "admin");
$guilds[] = $guild;


$guild = new SpecialGuild;
$guild->name = "Operatörsgruppen";
$guild->forums = array(61);
$guild->category = "general";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Founder", "admin");
$guild->groups[] = new GuildGroup(94, "IRC-Operatör", "member");
$guilds[] = $guild;


$guild = new SpecialGuild;
$guild->name = "Årsmötesgruppen";
$guild->forums = array(30);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Huvudarrangör", "admin");
$guild->groups[] = new GuildGroup(28, "Arrangör", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Lembasgruppen";
$guild->forums = array(68);
$guild->category = "catahya";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Förstekock", "admin");
$guild->groups[] = new GuildGroup(96, "Kock", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Äntringsgruppen";
$guild->forums = array(10);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(95, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(0, "Ledamot", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Lajvchattsgruppen";
$guild->forums = array(58);
$guild->category = "hidden";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Ordförande", "admin");
$guild->groups[] = new GuildGroup(23, "Medlem", "member");
$guilds[] = $guild;

$guild = new SpecialGuild;
$guild->name = "Uppslagsverksgruppen";
$guild->forums = array();
$guild->category = "general";
$guild->groups = array();
$guild->groups[] = new GuildGroup(0, "Chefredaktör", "admin");
$guild->groups[] = new GuildGroup(0, "Redaktör", "moderator");
$guild->groups[] = new GuildGroup(0, "Skribent", "member");
$guilds[] = $guild;

mysql_query("START TRANSACTION", $conn) or die(mysql_error());
foreach ($guilds as $guild) {
	$sqlInsertGuild  = "INSERT INTO guild (member_id, guild_type, guild_name, guild_description, "
	                 . "guild_text, guild_requirements, guild_confirmed, guild_confirmedby) ";
	$sqlInsertGuild .= "VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d, %d)";
	
	mysql_query(sprintf($sqlInsertGuild, 0, 
		$guild->category,
		mysql_real_escape_string($guild->name),
		mysql_real_escape_string(html_entity_decode("")),
		mysql_real_escape_string(html_entity_decode("")),
		mysql_real_escape_string(html_entity_decode("")),
		time(), 11727), $conn) or die(mysql_error());
		
	$guildId = mysql_insert_id();
	
	$sqlUpdateForum  = "UPDATE forum SET guild_id = %d, access_id = 0 WHERE forum_id = %d";
	foreach ($guild->forums as $forum) {
		mysql_query(sprintf($sqlUpdateForum, $guildId, $forum), $conn) or die(mysql_error());
	}

	$sqlInsertLevel  = "INSERT INTO guild_level (guild_id, group_id, level_name, level_access) ";
	$sqlInsertLevel .= "VALUES (%d, %d, '%s', '%s')";
	
	foreach ($guild->groups as &$group) {
		mysql_query(sprintf($sqlInsertLevel, $guildId, 0,
			mysql_real_escape_string(html_entity_decode($group->newName)),
			$group->level), $conn) or die(mysql_error());
			
		$group->newId = mysql_insert_id();
		
		if ($group->oldId == 0) {
			continue;
		}
		
		$oldIds = is_array($group->oldId) ? $group->oldId : array($group->oldId);
		$trans = array('member' => 0, 'moderator' => 1, 'admin' => 2);
		
		$sqlOld  = "SELECT mid FROM catahya1.medlem_grupp ";
		$sqlOld .= "WHERE gid IN (" . implode(",", $oldIds) . ") ";
		$sqlOld .= "GROUP BY mid";
		
		$qryOld = mysql_query($sqlOld, $conn) or die(mysql_error());
		while ($arrOld = mysql_fetch_assoc($qryOld)) {
		
			$sqlCurrent  = "SELECT * FROM guild_member ";
			$sqlCurrent .= "INNER JOIN guild_level USING (level_id) ";
			$sqlCurrent .= "WHERE member_id = %d AND guild_member.guild_id = %d ";
			
			$qryCurrent = mysql_query(sprintf($sqlCurrent, $arrOld["mid"], $guildId), $conn) or die(mysql_error());
			if (mysql_num_rows($qryCurrent)) {
			
				$arrCurrent = mysql_fetch_assoc($qryCurrent);
			
				if ($trans[$group->level] < $trans[$arrCurrent["level_access"]]) {
					continue;
				}
				
				$sqlUpdate  = "UPDATE guild_member SET level_id = %d ";
				$sqlUpdate .= "WHERE guild_id = %d AND member_id = %d";
				
				mysql_query(sprintf($sqlUpdate, $group->newId, $guildId, $arrOld["mid"]), $conn) or die(mysql_error());
				
				continue;
			}
				
			$sqlInsert  = "INSERT INTO guild_member (guild_id, member_id, level_id, member_guildtimestamp, member_guildstatement) ";
			$sqlInsert .= "VALUES (%d, %d, %d, unix_timestamp(), '')";
			
			mysql_query(sprintf($sqlInsert, $guildId, $arrOld["mid"], $group->newId), $conn) or die($sqlInsert . "\n" . mysql_error());
		}
	}

	$sqlHistory  = "INSERT INTO guild_history (guild_id, history_timestamp, history_description) ";
	$sqlHistory .= "VALUES (%d, unix_timestamp(), '%s')";
	
	mysql_query(sprintf($sqlHistory, $guildId, 
		mysql_real_escape_string($guild->name . " importerades till Catahya 2. Yay för Aderyn! <3")),
		$conn) or die(mysql_error());
}
mysql_query("COMMIT", $conn) or die(mysql_error());

echo "Completed.\n";
*/

/*
clean:
DELETE FROM guild WHERE guild_id > 288;
DELETE FROM guild_history WHERE guild_id > 288;
DELETE FROM guild_level WHERE guild_id > 288;
DELETE FROM guild_member WHERE guild_id > 288;
*/
