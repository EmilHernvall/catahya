<?php
require_once "Catahya/Controller/Action.php";
require_once 'Zend/Json/Encoder.php';

class Register_UserinfoController extends Catahya_Controller_Action
{
    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
        
		if (array_key_exists('errors', $_SESSION)) {
			$this->_view->errors = $_SESSION["errors"];
			unset($_SESSION["errors"]);
		} else {
			$this->_view->errors = array();
		}
		
		if (array_key_exists('fields', $_SESSION)) {
			$this->_view->fields = $_SESSION["fields"];
			unset($_SESSION["fields"]);
		} else {
			$this->_view->fields = array();
		}
		
        $this->compile('userinfo.phtml');
    }
	
	public function LookupAliasAction()
	{
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		$alias = trim($request->alias);
		$flatAlias = safeAlias($alias);
		
		$error = "";
		$valid = true;
		if (strlen($flatAlias) < 3) {
			$error = "Ditt användarnamn innehåller för få vanliga tecken (a-z, 0-9).";
			$valid = false;
		}
		// taken
		else {
			$sqlAlias = "SELECT count(*) FROM member WHERE member_alias = ? OR member_flatalias = ?";
			
			$stmtAlias = $db->prepare($sqlAlias);
			$stmtAlias->execute(array($alias, $flatAlias));
			
			$count = $stmtAlias->fetchColumn(0);
			
			if ($count > 0) {
				$error = "Ditt användarnamn är redan upptaget, eller är för likt ett användarnamn som redan används.";
				$valid = false;
			}
			
			$stmtAlias->closeCursor();
		}
		
		$result = array();
		$result["valid"] = $valid;
		$result["error"] = $error;
		
		define("JSON", true);
		echo Zend_Json_Encoder::encode($result);
	}
    
    public function UserinfoCommitAction() 
	{
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		$errors = array();
		
		// alias:
		$alias = trim($request->alias);
		$flatAlias = safeAlias($alias);
		
		// characters
		if (preg_match("/[<>&]+/i", $alias)) {
			$errors["alias"] = "Ditt användarnamn får inte innehålla &lt;, &gt; eller &amp;.";
		}
		// length
		else if (strlen($alias) < 3 || strlen($alias) > 20) {
			$errors["alias"] = "Ditt användarnamn får inte vara kortare än 3 tecken eller längre än 20 tecken.";
		}
		// make sure there is something left after removing special chars
		else if (strlen($flatAlias) < 3) {
			$errors["alias"] = "Ditt användarnamn innehåller för få vanliga tecken (a-z, 0-9).";
		}
		// taken
		else {
			$sqlAlias = "SELECT count(*) FROM member WHERE member_alias = ? OR member_flatalias = ?";
			
			$stmtAlias = $db->prepare($sqlAlias);
			$stmtAlias->execute(array($alias, $flatAlias));
			
			$count = $stmtAlias->fetchColumn(0);
			
			if ($count > 0) {
				$errors[] = "Ditt användarnamn är redan upptaget, eller är för likt ett användarnamn som redan används.";
			}
			
			$stmtAlias->closeCursor();
		}
		
		// password:
		$password = $request->pass;
		$passvalid = $request->passvalid;
		
		// length
		if (strlen($password) < 6) {
			$errors[] = "Ditt lösenord måste vara minst sex tecken långt.";
		}
		// equal
		else if (strcmp($password, $passvalid) != 0) {
			$errors[] = "Dina angedda lösenord matchar inte.";
		}
		
		// e-mail:
		$email = trim($request->email);
		$hideemail = (bool)$request->hideemail;
		
		// valid
		$emailRegexp = '/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)$/i';
		if (!preg_match($emailRegexp, $email, $matches)) {
			$errors[] = "Din e-post är inte giltig.";
		}
		else if (strlen($email) > 250) {
			$errors[] = "Din e-post är för lång.";
		}
		// lookup
		else {
			list($user, $domain) = explode("@", $email);
		
			if (!getmxrr($domain, $results)) {
				$errors[] = "Din e-post är inte giltig.";
			}
		}
		
		// birthdate:
		$birthdate = trim($request->birthdate);
		
		// numeric
		$year = 0;
		$shortYear = 0;
		$month = 0;
		$day = 0;
		$nr = 0;
		$age = 0;
		
		$ssnRegexp = '/^((19|20)(\d{2}))(\d{2})(\d{2})-(\d{4})$/i';
		if (!preg_match($ssnRegexp, $birthdate, $matches)) {
			$errors[] = "Ditt personnummer är inte giltigt.";
		}
		else {
			// range
			$year = (int)$matches[1];
			$shortYear = (int)$matches[3];
			$month = (int)$matches[4];
			$day = (int)$matches[5];
			$nr = (int)$matches[6];
		
			$age = getAgeByParams($year, $month, $day);
			if ($age > 130) {
				$errors[] = "Ditt personnummer är inte giltigt.";
			}
			
			// last digit
			$digits = sprintf("%02d%02d%02d%04d", $shortYear, $month, $day, $nr);

			$str = "";
			for ($i = 0; $i < strlen($digits); $i++) {
				$n = intval($digits[$i]) * (($i&1) == 1 ? 1 : 2);
				$str .= $n;
			}

			$sum = 0;
			for ($i = 0; $i < strlen($str); $i++) {
				$sum += intval($str[$i]);
			}

			if ($sum % 10 > 0) {
				$errors[] = "Ditt personnummer är inte giltigt.";
			}
		}
		
		// gender:
		$gender = $request->gender;
		
		// male or female
		$genders = array('female' => 0, 'male' => 1);
		if (!array_key_exists($gender, $genders)) {
			$errors[] = "Du har fel kön.";
		}
		else {
			if ($nr != 0) {
				$sDigits = sprintf("%04d", $nr);
				$genderDigit = (int)$sDigits[2];
				if ($genderDigit % 2 != $genders[$gender]) {
					$errors[] = "Du har fel kön.";
				}
			}
		}
		
		// name:
		$name = trim($request->name);
		$hidename = (bool)$request->hidename;
		
		// length
		if (strlen($name) < 2) {
			$errors[] = "Ditt namn är för kort.";
		} else if (strlen($name) > 50) {
			$errors[] = "Ditt namn är för långt.";
		}
		
		// surname:
		$surname = $request->surname;
		$hidesurname = $request->hidesurname;
		
		// length
		if (strlen($surname) < 2) {
			$errors[] = "Ditt efternamn är för kort.";
		} else if (strlen($surname) > 50) {
			$errors[] = "Ditt efternamn är för långt.";
		}
		
		// address:
		$address = $request->address;
		
		// length
		if (strlen($address) < 2) {
			$errors[] = "Din adress är för kort.";
		} else if (strlen($address) > 200) {
			$errors[] = "Din adress är för lång.";
		}
		
		// zipcode:
		$zipcode = str_replace(" ", "", $request->zipcode);
		
		// format
		if (!preg_match("/\d{5}/", $zipcode)) {
			$errors[] = "Ditt post-nummer är ogiltigt.";
		}
		
		// city:
		$city = $request->city;
		$hidecity = $request->hidecity;
		
		// length
		if (strlen($city) < 2) {
			$errors[] = "Angiven ort är för kort.";
		} else if (strlen($city) > 20) {
			$errors[] = "Angiven ort är för lång.";
		}
		
		// country:
		$country = $request->country;
		
		// length
		if (strlen($country) > 40) {
			$errors[] = "Angivet land är för lång.";
		}
		
		// phone:
		$phone = $request->phone;
		
		// format
		if (!preg_match("/\d{2,4}-\d{5,8}/", $phone)) {
			$errors[] = "Ditt telefon-nummer är inte giltigt.";
		}
		
		// other messages
		$othermsg = $request->othermsg;
		
		// return if there are any errors
		if (count($errors) > 0) {
		
			$fields = array('alias' => $alias, 'email' => $email, 'hideemail' => $hideemail,
				'birthdate' => $birthdate, 'gender' => $gender, 'name' => $name, 
				'hidename' => $hidename, 'surname' => $surname, 
				'hidesurname' => $hidesurname, 'address' => $address, 'zipcode' => $zipcode,
				'city' => $city, 'hidecity' => $hidecity, 'country' => $country,
				'phone' => $phone, 'othermsg' => $othermsg);

			$_SESSION["errors"] = $errors;
			$_SESSION["fields"] = $fields;
			
			$this->_redirect("/register/userinfo");
		}
		
		$db->beginTransaction();
		
		// insert into member
		$sqlInsertMember  = "INSERT INTO member (member_alias, member_flatalias, member_password, "
		                  . "member_gender, member_age, member_status, member_online, member_photo, "
						  . "member_photostatus, member_quickdesc, member_city) ";
		$sqlInsertMember .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmtInsertMember = $db->prepare($sqlInsertMember);
		$stmtInsertMember->execute(array($alias, $flatAlias, md5($password), $gender, $age, 
			"unverified", "0", "0.jpg", "1", "", $hidecity ? $city : ""));
			
		$memberId = $db->lastInsertId();
			
		// insert into member_userdata
		$sqlInsertUserdata  = "INSERT INTO member_userdata (member_id, theme_id, member_lastlogin, "
		                    . "member_memberdate, member_minonline, member_logintotal, member_visitstotal, "
							. "member_gbrecv, member_gbsent) ";
		$sqlInsertUserdata .= "VALUES (?, 1, 0, unix_timestamp(), 0, 0, 0, 0, 0)";
		
		$stmtInsertUserdata = $db->prepare($sqlInsertUserdata);
		$stmtInsertUserdata->execute(array($memberId));
		
		// insert into member_profile
		$profileName = "";
		if (!$showname) {
			$profileName = $name;
		}
		
		if (!$showsurname) {
			$profileName .= " " . $surname;
		}
		
		$profileEmail = "";
		if (!$hideemail) {
			$profileEmail = $email;
		}
		
		$profileBirthdate = sprintf("%04d-%02d-%02d", $year, $month, $day);
		
		$sqlInsertProfile  = "INSERT INTO member_profile (member_id, member_name, member_email, member_birthdate, "
		                   . "member_jabber, member_msn, member_homepage, member_note, member_presentation) ";
		$sqlInsertProfile .= "VALUES (?, ?, ?, ?, '', '', '', '', '')";
		
		$stmtInsertProfile = $db->prepare($sqlInsertProfile);
		$stmtInsertProfile->execute(array($memberId, $profileName, $profileEmail, $profileBirthdate));
		
		// insert into member_account
		$sqlInsertAccount  = "INSERT INTO member_account (member_id, member_accountstatus, "
		                   . "member_timestamp, member_firstname, member_surname, member_address, "
						   . "member_zipcode, member_city, member_country, member_phonenr, "
						   . "member_ssn, member_email) ";
		$sqlInsertAccount .= "VALUES (?, 'pending', 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmtInsertAccount = $db->prepare($sqlInsertAccount);
		$stmtInsertAccount->execute(array($memberId, $name, $surname, $address, $zipcode, $city, $country, $phone,
			$birthdate, $email));
		
		// insert into message_folder
		$sqlInsertFolder  = "INSERT INTO message_folder (member_id, folder_name, folder_type) ";
		$sqlInsertFolder .= "VALUES (?, 'Meddelanden', 'system')";
		
		$stmt = $db->prepare($sqlInsertFolder);
		$stmt->execute(array($memberId));
		
		// insert into member_character
		$sqlInsertCharacter  = "INSERT INTO member_character (member_id, character_race, character_class, "
		                     . "character_alignment, character_description) ";
		$sqlInsertCharacter .= "VALUES (?, ?, ?, ?, ?)";
		
		$stmt = $db->prepare($sqlInsertCharacter);
		$stmt->execute(array($memberId, "Människa", "Krigare", "Neutral", ""));
		
		$db->commit();
		
		// skicka bekräftelsemail
		$mail = new Zend_Mail("utf-8");
		$mail->setFrom('webmaster@catahya.net', 'Catahya.net');
		$mail->addTo($email, $name . " " . $surname);
		$mail->setSubject('Välkommen till Catahya!');
		
		$text = "Hej!\n\n";
		$text .= "Gå hit: http://ca2.c0la.se/register/done/confirm?id=" . $memberId . "\n\n";
		$text .= "Cheers.\n";
		$text .= "Catahya";
		
		$mail->setBodyText($text);
		$mail->send();
		
		// redirect
		$this->_redirect("/register/done");
    }
}