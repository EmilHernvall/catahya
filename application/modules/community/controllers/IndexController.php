<?php

require_once 'Catahya/Controller/Action.php';

class Community_IndexController extends Catahya_Controller_Action
{
    public function compile($template, $layout = 'layout.phtml')
    {
        $this->_view->pageMenu = $this->_view->render('community_menu.phtml');
        parent::compile($template, $layout);
    }

	public function IndexAction()
	{
		$request = $this->getRequest();
		$db = Zend_Registry::get("db");
		
		$this->compile('community_index.phtml');
	}

	public function quicksearchAction()
	{
		$db = Zend_Registry::get("db");
		$request = $this->getRequest();
		$q = trim($request->q);

		if ($q != '')  {
			// This defines the varius suffixes
			$ok_suffix = array(
				// Profile
				'pr' =>	'/profile/%s',
				// Guestbook
				'gb' =>	'/profile/%s/guestbook',
				// Relations
				'msg' => '/message/thread/write?to=%s');
			
			// Check for a suffix
			if (($suffix_pos = strpos($q,'.')) !== FALSE) {
				$suffix = substr($q,$suffix_pos+1);
				$end = $suffix_pos;
				
				if (!isset($ok_suffix[$suffix]))  {
					$suffix = 'pr';
				}
			} else {
				$end = strlen($q);
				$suffix = 'pr';
			}
			
			$id = 0;
			// A string beginning with % is an ID.
			if ($q{0} == '%')  {
				$id = substr($q, 1, $end);
			}
			// Standard handler. Looks up the alias and returns an ID.
			else {
				$alias = substr($q,0,$end);
				
				$stmt = $db->prepare('SELECT member_id, member_flatalias, member_alias FROM member WHERE member_alias LIKE ? LIMIT 1');
				$stmt->execute(array($alias));
				
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$stmt->closeCursor();
				
				if (!$result) {
					$_SESSION["flash"] = 'Användaren existerar inte!';
					$this->_redirect('/');
				}
				
				//var_dump($result);exit;
				
				$id = $result["member_flatalias"];
			}
			
			$url = sprintf($ok_suffix[$suffix],$id);
			$this->_redirect($url);
		}

		// This is the randomizer. It picks a random connected member and redirects the user to this member profile.
		else {
			$stmt = $db->prepare('SELECT online_id FROM online ORDER BY rand() LIMIT 1');
			$stmt->execute();
			
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$stmt->closeCursor();
			
			// Probably the most unnecessary validation on this site, but for some reason
			// not making it bugged me
			if ($result) {
				$this->_redirect('/profile/'.$result['online_id']);
			}
			else {
				$this->_redirect("/");
			}
		}
	}
}
