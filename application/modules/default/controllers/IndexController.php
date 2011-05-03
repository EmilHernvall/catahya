<?php

require_once 'Catahya/Controller/Action.php';
require_once 'application/modules/wiki/controllers/IndexController.php';
require_once 'Zend/Json/Encoder.php';

class IndexController extends Wiki_IndexController 
{
	public function init()
	{
		parent::init();
		$this->_view->addScriptPath(ROOT_PATH . '/application/modules/wiki/views');
	}
	
	public function ErrorAction()
	{
		throw new Exception("Erikfel!");
	}

	public function IndexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		$root = !isset($request->page);
		$page = isset($request->page) ? strtolower($request->page) : 'index';
		$action = $request->getQuery("action");

		if ($action == "save") {
			$this->_forward("SaveCommit");
			return;
		}
		
		$this->_generateWiki("start", $page, $action, $root);
		
		$sqlSelect  = "SELECT * FROM (";
		$sqlSelect .= "SELECT * FROM (SELECT * FROM `text` WHERE type_id = 2 ORDER BY text_timestamp DESC LIMIT 1) a ";
		$sqlSelect .= "UNION SELECT * FROM (SELECT * FROM `text` WHERE type_id = 3 ORDER BY text_timestamp DESC LIMIT 1) b ";
		$sqlSelect .= "UNION SELECT * FROM (SELECT * FROM `text` WHERE type_id = 4 ORDER BY text_timestamp DESC LIMIT 1) c ";
		$sqlSelect .= "UNION SELECT * FROM (SELECT * FROM `text` WHERE type_id = 5 ORDER BY text_timestamp DESC LIMIT 1) d ";
		$sqlSelect .= ") tmp ";
		$sqlSelect .= "INNER JOIN text_type USING (type_id) ";
		$sqlSelect .= "INNER JOIN member USING (member_id) ";
		$sqlSelect .= "ORDER BY text_timestamp DESC";
		
		$stmt = $db->prepare($sqlSelect);
		$stmt->execute();
		
		$this->_view->reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
		if ($action == "create") {
	        $this->compile('wiki_create.phtml');
		} else if ($action == "edit") {
	        $this->compile('wiki_edit.phtml');
		} else {
			if ($page == "index") {
				$this->compile('index.phtml');
			} else {
				$this->compile('wiki_index.phtml');
	        }
		}
	}
	
	public function styleAction()
	{
		$request = $this->getRequest();
		$_SESSION["style"] = $request->style;
		$this->_redirect($_SERVER["HTTP_REFERER"]);
	}
	
	public function statusAction()
	{
		define("JSON", true);
		//header('Content-type: text/x-json');
		
		$this->_view->info["logintime"] = date("H:i", $this->_view->info["logintime"]);
	
		$data["online"] = $_SESSION["online"];
		$data["info"] = $this->_view->info;
		
		if ($_SESSION["online"]) {
			$data["id"] = $_SESSION["id"];
			$data["alias"] = $_SESSION["alias"];
			$data["status"] = $this->_view->status;
			$data["relations"] = $this->_view->relations;
		}

		echo Zend_Json_Encoder::encode($data);
	}
	
	protected function _loginEvent($error_code, $extra = 0)
	{
		$request = $this->getRequest();
		$message = FALSE;
		
		switch($error_code)
		{
			/* OK */
			case 200:
				$redir = $request->has('afterlogin') ? $request->afterlogin : '/';
				$this->_redirect($redir);
				exit;
				break;
			
			/* No Content */
			case 204:
				$message = 'Inget användarnamn eller lösenord angivet.';
				break;
			
			/* Unathorized */
			case 203:
			case 401:
				$message = 'Felaktigt användarnamn eller lösenord.';
				break;
			
			/* Forbidden */
			case 403:
				switch($extra)
				{
					case 'E_UNVRFY':
						$message = 'Användaren är inte godkänd ännu.';
						break;
					case 'E_ADMDEL':
						$message = 'Användaren är avstängd!';
						break;
					case 'E_USRDEL':
						$message = 'Användaren är deaktiverad.';
						break;
				}
				break;
		}
	
		// Output a message to the user
		if ($message) {
			$_SESSION['flash'] = $message;
			$this->_redirect('/');
		}
	}
	
	public function loginCommitAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get('db');
		
		if (($request->has('username') && $request->has('password')) || array_key_exists('login_id', $_SESSION)) { 
			
			/*var_dump($request->username);
			var_dump($request->password);
			exit;*/

			$sqlLogoutUpdate  = "UPDATE member_online, member_userdata, member ";
			$sqlLogoutUpdate .= "SET member_online = '0', ";
			$sqlLogoutUpdate .= "member_minonline = member_minonline + "
			                  . "round((unix_timestamp() - member_lastlogin)/60) ";
			$sqlLogoutUpdate .= "WHERE member_userdata.member_id = member_online.member_id "
			                  . "AND member.member_id = member_online.member_id "
			                  . "AND member_online.member_active < unix_timestamp() - 900";
			$db->query($sqlLogoutUpdate);
			
			// Logout inactive users. Place here for visibility. Should be moved later.
			$sqlEmptyOnline = 'DELETE FROM member_online WHERE member_active < unix_timestamp() - 900';
			$db->query($sqlEmptyOnline);
			
			// Replace by $sqlLogoutUpdate above.
			//$sqlUpdateMember  = 'UPDATE member ';
			//$sqlUpdateMember .= 'SET member_online = "0" WHERE member_online = "1" ';
			//$sqlUpdateMember .= 'AND member_id NOT IN (SELECT online_id FROM online)';
			//$db->query($sqlUpdateMember);
			
			// Start the login sequence
			$params = array();
			
			$sqlSelect  = 'SELECT ';
			
			// member
			$sqlSelect .= 'member_id, member_alias, member_flatalias, member_gender, member_age, ' 
			            . 'member_status, member_photo, ';
			// theme
			$sqlSelect .= 'theme_name, ';
			// member_profile
			$sqlSelect .= 'member_birthdate, ';
			// member_userdata
			$sqlSelect .= 'member_memberdate, member_lastlogin, ';
			// member_account
			$sqlSelect .= 'member_accountstatus, member_timestamp ';
			
			$sqlSelect .= 'FROM member ';
			$sqlSelect .= 'INNER JOIN member_userdata USING (member_id) ';
			$sqlSelect .= 'INNER JOIN member_profile USING (member_id) ';
			$sqlSelect .= 'INNER JOIN member_account USING (member_id) ';
			$sqlSelect .= 'INNER JOIN theme USING (theme_id) ';
			
			if (array_key_exists('login_id', $_SESSION)) {
				$params[] = $_SESSION["login_id"];
				
				$sqlSelect .= 'WHERE member_id = ?';
			} else {
				$params[] = $request->username;
				$params[] = md5($request->password);
				
				$sqlSelect .= 'WHERE member_alias = ? AND member_password = ?';
			}
			
			$stmtMember = $db->prepare($sqlSelect);
			$stmtMember->execute($params);
			$arrMember = $stmtMember->fetch(PDO::FETCH_ASSOC);
			$stmtMember->closeCursor();
			
			if ($arrMember) {
				switch ($arrMember['member_status']) {
					case "inactivated": // User deactivated by admin
						$this->_loginEvent(403, 'E_ADMDEL');
						break;
					case "discontinued": // User deactivated by user
						$this->_loginEvent(403, 'E_USRDEL');
						break;
					case "unverified": // User deactivated by user
						$this->_loginEvent(403, 'E_UNVRFY');
						break;
					case "active": // User Okay
						break;
					default: // Shouldn't happen
						break;			
				}
				
				if ($arrMember["member_accountstatus"] == "invalid") {
				
					// Force the user to correct his/her account details.
					$_SESSION["tmp_id"] = $arrMember["member_id"];
					$this->_redirect('/register/account/invalid');
				
				}
				
				if (time() - $arrMember["member_memberdate"] > 30*86400 
					&& $arrMember["member_timestamp"] == 0) {
				
					// Ask if the member wants to continue his/her membership.
					$_SESSION["tmp_id"] = $arrMember["member_id"];
					$this->_redirect('/register/account/confirm?type=1');
				}
				
				$sqlRenewal  = "SELECT MAX(renewal_timestamp) renewal_timestamp ";
				$sqlRenewal .= "FROM member_renewal";
				
				$stmtRenewal = $db->prepare($sqlRenewal);
				$stmtRenewal->execute();
				
				$renewal = $stmtRenewal->fetchColumn(0);
				
				$stmtRenewal->closeCursor();
				
				if ($renewal - $arrMember["member_timestamp"] > 0 
					&& $arrMember["member_timestamp"] != 0) {
				
					// Ask if the member wants to renew the membership.
					$_SESSION["tmp_id"] = $arrMember["member_id"];
					$this->_redirect('/register/account/confirm?type=1');
				}
				
				/*
				 * The session id that will be used.
				 * It's needed before initializing the session,
				 * and therefore it's generated here.
				 *
				 * if mod_unique exists, use hmac-md5.
				*/
				if($request->has('UNIQUE_ID')) {
					$sessid = md5(md5($request->UNIQUE_ID).$request->UNIQUE_ID);
				}
				else {
					$sessid = md5(uniqid(mt_rand()));
				}
				
				/*
				 * This routine ensures that the time online is calculated correctly.
				 * It deals with a previous problem that caused a loss of all time online when
				 * logging in from one computer, while still idling on another.
				 * Very annoying when i check my guestbook from school for example.
				*/

				$sqlOnline = 'SELECT 1 FROM member_online WHERE member_id = ?';
				$stmt = $db->prepare($sqlOnline);
				$stmt->execute(array($arrMember['member_id']));
				$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
					
				if (count($res)) {
					$sqlUpdate  = 'UPDATE member_userdata ';
					$sqlUpdate .= 'SET member_minonline = ' 
					            . 'member_minonline + ' 
					            . 'round((unix_timestamp() - member_lastlogin)/60) ';
					$sqlUpdate .= 'WHERE member_id = ?';
					$stmt = $db->prepare($sqlUpdate);
					$stmt->execute(array($arrMember['member_id']));
				}
				
				$sqlReplace  = 'REPLACE INTO member_online (member_id, member_active, '
				             . 'member_sessionid, member_away) ';
				$sqlReplace .= 'VALUES (?, unix_timestamp(), ?, 0);';
				$stmtOnline = $db->prepare($sqlReplace);
				$stmtOnline->execute(array($arrMember['member_id'],$sessid));
		
				/*
				 * member_online
				 */
				$sqlUpdate  = 'UPDATE member  ';
				$sqlUpdate .= 'SET member_online = "1" ';
				$sqlUpdate .= 'WHERE member_id = ?';
				$stmt = $db->prepare($sqlUpdate);
				$stmt->execute(array($arrMember['member_id']));
				
				/*
				 * Update userdata_lastlogin
				*/
				$sqlUpdate  = 'UPDATE member_userdata ';
				$sqlUpdate .= 'SET member_lastlogin = unix_timestamp() ';
				$sqlUpdate .= 'WHERE member_id = ?';
				$stmt = $db->prepare($sqlUpdate);
				$stmt->execute(array($arrMember['member_id']));
				
				/*
				 * Handles login-abuse.
				 * On silverplanet there were people that constantly logged in just to
				 * top the list of most logins.
				 *
				 * Timeout is 3600s.
				*/
				if ((time() - $arrMember['member_lastlogin']) > 3600) 
				{
					/*
					 * Calculate the members age.
					 * This is were the update is done after someone
					 * has celebrated their birthday.
					*/
					$age = getage(substr($arrMember['member_birthdate'], 0, 10));
		
					/*
					 * Update userdata and member with the login-, age- and activity-values.
					*/
					$sqlUserdata  = 'UPDATE member, member_userdata ';
					$sqlUserdata .= 'SET member_age = ?, member_online = "1", '
					              . 'member_logintotal = member_logintotal + 1 ';
					$sqlUserdata .= 'WHERE member.member_id = ? '
					              . 'AND member_userdata.member_id = member.member_id';
			    	$stmt = $db->prepare($sqlUserdata);
			    	$stmt->execute(array($age, $arrMember['member_id']));
				
					/*
					 * Algoritm to get the users ip behind proxys. 
					 *
					 * I found this from php.net somewhere in the user comments.
					 * I've modified it to fit our needs, though.
					*/
					$resolution = $request->has('resolution') ? $request->resolution : '';
					
					$sqlLogin  = 'INSERT INTO member_login (member_id, login_ip, '
					           . 'login_proxyip, login_useragent, login_resolution, '
					           . 'login_timestamp) ';
					$sqlLogin .= 'VALUES(?, ?, ?, ?, ?, unix_timestamp());';
					$stmt = $db->prepare($sqlLogin);
					$stmt->execute(array($arrMember['member_id'], 
					                     getIp(), 
					                     $request->REMOTE_ADDR, 
					                     $request->HTTP_USER_AGENT, 
					                     $resolution));	
				}
				
				session_destroy();
				session_unset();
				
				session_id($sessid);
				session_start();
						
				$_SESSION['id'] = $arrMember['member_id'];
				$_SESSION['alias'] = $arrMember['member_alias'];
				$_SESSION['flatalias'] = $arrMember['member_flatalias'];
				$_SESSION['gender'] = $arrMember['member_gender'];
				$_SESSION['age'] = $arrMember['member_age'];
				$_SESSION['theme'] = $arrMember['theme_name'];
				$_SESSION['photo'] = $arrMember['member_photo'];
				$_SESSION['online'] = TRUE;
				$_SESSION['logintime'] = time();
				
				
				/*
				 * This variable adds valuable security against
				 * cross site scripting attacks through cookies.
				 * It is used in init.php to verify the real ip.
				*/
				$_SESSION['ip'] = $request->REMOTE_ADDR;
				
				/*
				 * Handles the logout- and idle-routines.
				*/
				$_SESSION['active'] = time();
				$_SESSION['realactivity'] = time();
				$_SESSION['idle'] = 0;
		
				/*
				 * Temporary ugly-hack to avoid harrasing
				 * 
				 * Apparently it is used to hide user in  
				 * the latest visitor-link in the profile.
				*/
				$_SESSION['leavemealone'] = (($_SESSION['id'] == 1 || $_SESSION['id'] == 2) ? true : false);
				
				/*
				 * Access and permissions
				*/
				/*$sqlGroupPermissions  = 'SELECT access_id, access_name, access_defaultpermission, access_group.access_permission FROM access ';
  				$sqlGroupPermissions .= 'INNER JOIN access_group USING (access_id) ';
				$sqlGroupPermissions .= 'INNER JOIN group_member USING (group_id) ';
				$sqlGroupPermissions .= 'WHERE member_id = ?';*/
				
				$sqlGuildPermissions  = "SELECT access_id, access_name, access_defaultpermission, access_group.access_permission ";
				$sqlGuildPermissions .= "FROM access ";
				$sqlGuildPermissions .= "INNER JOIN access_group USING (access_id) ";
				$sqlGuildPermissions .= "INNER JOIN guild_level USING (group_id) ";
				$sqlGuildPermissions .= "INNER JOIN guild_member USING (level_id) ";
				$sqlGuildPermissions .= "WHERE member_id = ? ";

				$sqlGroupPermissions  = "SELECT access_id, access_name, access_defaultpermission, access_group.access_permission ";
				$sqlGroupPermissions .= "FROM access ";
				$sqlGroupPermissions .= "INNER JOIN access_group USING (access_id) ";
				$sqlGroupPermissions .= "INNER JOIN group_member USING (group_id) ";
				$sqlGroupPermissions .= "WHERE member_id = ? ";

				$sqlPermissions  = "SELECT access_id, access_name, "
								 . "BIT_OR(access_defaultpermission) access_defaultpermission, "
								 . "BIT_OR(access_permission) access_permission ";
				$sqlPermissions .= "FROM ( ";
				$sqlPermissions .= $sqlGuildPermissions;
				$sqlPermissions .= "UNION ";
				$sqlPermissions .= $sqlGroupPermissions;
				$sqlPermissions .= ") tmp ";
				$sqlPermissions .= "GROUP BY access_id";
				
				$stmtGroupPermissions = $db->prepare($sqlPermissions);
				$stmtGroupPermissions->execute(array($arrMember['member_id'], $arrMember['member_id']));
				
				$arrGroupPermissions = $stmtGroupPermissions->fetchAll(PDO::FETCH_ASSOC);
				
				Catahya_Access::init($arrGroupPermissions);
				
				/*
				 * The user wanted to have autologin for some time.
				 * Remember to change the key in default.php too!
				*/
				/*if(isset($_POST['autologin']) && !empty($_POST['autologin']))
				{
					require ROOT_PATH . '/includes/functions/blowfish.php';
					$blowfish_key = '3f2f1e7a59848aa51871711eca22bbb75e7f87ec564f61a06599ca7f7909a834';
					
					$data = serialize(array( 'username' => $_REQUEST['username'], 'password' => $_REQUEST['password']));
					$enc = PMA_blowfish_encrypt( $data, $blowfish_key);
					
					setcookie('somename_autologin', $enc, strtotime('+3 weeks'), '/');
				}*/
				
				//clearStatus();
				
				/*
				 * Ok, user can log in.
				 * 200 = OK
				*/
				$this->_loginEvent(200);
			}
			
			/*
			 * Username or password is incorrect.
			 * 203 = Not authoritative Information
			*/
			$this->_loginEvent(203);
		}
		
		/*
		 * No username or password specified.
		 * 204 == 'No Content'
		*/
		$this->_loginEvent(204);

	}
	
	public function logoutCommitAction()
	{
		$db = Zend_Registry::get('db');
		
		$stmt = $db->prepare("DELETE FROM member_online WHERE member_id = ?");
		$stmt->execute(array($_SESSION['id']));
		
		$sql  = 'UPDATE member_userdata, member ';
		$sql .= 'SET member_online = "0", '
		      . 'member_minonline = '
		      . 'member_minonline + round((unix_timestamp() - member_lastlogin)/60) ';
		$sql .= 'WHERE member.member_id = ? AND member_userdata.member_id = member.member_id';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION['id']));
		
		session_unset();
		session_destroy();
		
		$this->_redirect('/');
	}
}
