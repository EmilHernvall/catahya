<?php
require_once "Catahya/Controller/Action.php";

class Settings_ProfileController extends Catahya_Controller_Action
{
    
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('menu.phtml');
        parent::compile($template, $layout);
    }

    public function IndexAction()
    {
        $db = Zend_Registry::get('db');
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
		
        $sql  = 'SELECT member_id, member_quickdesc, '
              . 'member_city, member_alias, member_name, '
              . 'member_email, member_jabber, member_msn, '
              . 'member_homepage ';
        $sql .= 'FROM member ';
		$sql .= 'INNER JOIN member_profile USING (member_id) ';
        $sql .= 'WHERE member_id = ?';

        $stmtProfile = $db->prepare($sql);
        $stmtProfile->execute( array($_SESSION['id']) );
        $arrProfile = $stmtProfile->fetch(PDO::FETCH_ASSOC);

        $stmtProfile->closeCursor();

        if ($arrProfile['member_id'] != $_SESSION['id']) {
            $_SESSION['flash'] = 'Du har inte behörighet att visa den här sidan.';
            $this->_redirect('/');
		}

        $this->_view->member = $arrProfile;
        $this->compile('profile.phtml');

    }

    public function ProfileCommitAction()
    {
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}
        
		$name = trim($request->name);
		$email = trim($request->email);
		$jabber = trim($request->jabber);
		$msn = trim($request->msn);
		$homepage = trim($request->homepage);
		$quickdesc = trim($request->quickdesc);
		$city = trim($request->city);

        $sql  = 'UPDATE member_profile ';
        $sql .= 'SET member_name = ?, member_email = ?, '
              . 'member_jabber = ?, member_msn = ?, member_homepage = ? ';
        $sql .= 'WHERE member_id = ?';
        
        $stmtUpdate = $db->prepare($sql);
        $stmtUpdate->execute(array($name, $email, $jabber, $msn, $homepage, $_SESSION['id']));

        $sql  = 'UPDATE member ';
        $sql .= 'SET member_city = ?, member_quickdesc = ? ';
        $sql .= 'WHERE member_id = ?';

        $stmtUpQuick = $db->prepare($sql);
        $stmtUpQuick->execute(array($city, $quickdesc, $_SESSION['id']));

        $_SESSION['flash'] = 'Dina kontoinställningar är sparade!';
        $this->_redirect('/settings/profile');
    }

    public function PasswordCommitAction()
    {
        $db = Zend_Registry::get('db');
        $request = $this->getRequest();
		
		if (!$_SESSION["online"]) {
			$this->_redirect("/");
		}

		$old = $request->password_old;
		$new1 = $request->password_new1;
		$new2 = $request->password_new2;

        $stmtSel = $db->prepare('SELECT member_id FROM member WHERE member_password = ? AND member_id = ?');
        $stmtSel->execute(array(md5($old), $_SESSION['id']));

        $arrPass = $stmtSel->fetchAll();
        if (count($arrPass) == 0) {
            $_SESSION['flash'] = 'Det nuvarande lösenordet var felaktigt!';
            $this->_redirect('/settings/profile');
        }

        if ($new1 != $new2) {
            $_SESSION['flash'] = 'Det nya lösenordet stämde inte överens.';
            $this->_redirect('/settings/profile');
        }

        if (strlen(trim($new1)) < 6) {
            $_SESSION['flash'] = 'Ditt lösenord måste vara minst sex tecken långt!';
            $this->_redirect('/settings/profile');
        }

        $sql  = 'UPDATE member ';
        $sql .= 'SET member_password = ? ';
        $sql .= 'WHERE member_id = ?';

        $stmtPassUp = $db->prepare($sql);
        $stmtPassUp->execute(array(md5($new1), $_SESSION['id']));

        $_SESSION['flash'] = 'Ditt lösenord är uppdaterat!';

        $this->_redirect('/settings/profile');
    }

}
