<?php

//session_start();

global $CONFIG;
if (!isset($CONFIG)) {
	if (file_exists("cm.CommonFunctions.php"))
		include "cm.CommonFunctions.php";
	else if (file_exists("include/cm.CommonFunctions.php"))
		include "include/cm.CommonFunctions.php";
	else if (file_exists("../include/cm.CommonFunctions.php"))
		include "../include/cm.CommonFunctions.php";
}
if (!class_exists("SQL"))
	require_once $CONFIG['includes'] . "mysqlport.php";

class Session {

	public $timeout = 3600;
	public $sessionid;
	private $variables = array(
		 "userid",
		 "ip",
		 "useragent",
		 "sessionid",
		 "lastvisit",
		 "expires"
	);

	public function __construct($sid) {
		$this->sessionid = $sid;

		// extract from database
		$this->open();

		$this->useragent = $_SERVER['HTTP_USER_AGENT'];
		$this->lastvisit = $_SERVER['REQUEST_URI'];
		$this->expires = time() + $this->timeout;
		$this->userid = $_SESSION['userid'];
		$this->ip = $_SERVER['REMOTE_ADDR'];

		// update SESSION variables
		$this->update();

		// dump current state to database
		$this->dump();
	}

	function open() {
		global $CONFIG;
		$sql = new SQL("admin");
		$row = $sql->doQueryGetFirstRow("SELECT * FROM `" . $CONFIG["db_sessionstable"] . "` WHERE `sessionid`='" . $this->sessionid . "'");
		if ($row != null) {
			foreach ($row as $key => $val)
				if ($val != "")
					$this->$key = $val;
		}
		else {
			$this->expires = time() + $this->timeout;
			$sql->doQuery("INSERT INTO `" . $CONFIG["db_sessionstable"] . "` (`sessionid`,`expires`) VALUES ('" . $this->sessionid . "','" . ($this->expires) . "')");
		}
	}

	function assert($sql) {
		global $CONFIG;
		$row = $sql->doQueryGetFirstRow("SELECT COUNT(expires) as count FROM `" . $CONFIG["db_sessionstable"] . "` WHERE `sessionid`='" . $this->sessionid . "'");
		if ($row["count"] == 0) {
			
		}
	}

	function update() {
		foreach ($this->variables as $key) {
			$_SESSION[$key] = $this->$key;
		}
		$_SESSION['lastActivity'] = date("U");
	}

	function clean() {
		global $CONFIG;
		$sql = new SQL("admin");
		$sql->doQuery("DELETE FROM `" . $CONFIG["db_sessionstable"] . "` WHERE `userid`='" . $this->userid . "'");
		foreach ($this->variables as $key)
			unset($_SESSION[$key]);
	}

	function dump() {
		global $CONFIG;
		$q = "UPDATE `" . $CONFIG["db_sessionstable"] . "` SET ";
		$count = count($this->variables);
		$i = 0;
		foreach ($this->variables as $key) {
			if ($key != "expires")
				$q.= "`$key`='" . $this->$key . "'" . ($i < $count - 1 ? "," : "");
			else
				$q .= "`$key`='" . (time() + $this->timeout) . "' " . ($i < $count - 1 ? "," : "");
			$i++;
		}
		$q .= " WHERE `sessionid`='" . $this->sessionid . "'";

		$sql = new SQL("admin");
		$ok = $sql->doQuery($q);
		if (!$ok)
			$_SESSION['error'] = $sql->errorMsg();
		return $ok;
	}

}

class USER {

	private $variables = array(
		 "userid",
		 "username",
		 "email",
		 "lastlogin",
		 "inactive"
	);
	public $session = null;
	protected $info = null;

	public function __construct() {
		if (!$_SESSION['isLoggedIn'] || empty($_SESSION['userid'])) {
			$_SESSION['userid'] = 'anon';
			$_SESSION['username'] = 'Anonymous';
			$_SESSION['isLoggedIn'] = false;
		} else {
			$this->load();
		}
		$_SESSION['lastActivity'] = time();
	}

	public static function get($username) {
		global $CONFIG;
		$sql = new SQL("reader");
		echo "SELECT * FROM `" . $CONFIG["db_userstable"] . "` WHERE `username`='$username'";
		return $sql->doQueryGetFirstRow("SELECT * FROM `" . $CONFIG["db_userstable"] . "` WHERE `username`='$username'", "array");
	}

	function load() {
		global $CONFIG;
		$this->session = new Session($_COOKIE['PHPSESSID']);
		$this->userid = $_SESSION['userid'];
		$sql = new SQL("reader");
		$row = $sql->doQueryGetFirstRow("SELECT `" . implode("`,`", $this->variables) . "` FROM `" . $CONFIG["db_userstable"] . "` WHERE `userid`='" . $this->userid . "'");
		if ($row)
			foreach ($row as $key => $val)
				$this->$key = $val;
	}

	function logout() {
		if ($this->session != null) {
			$this->session->clean();
			$this->session = null;
		}
		foreach ($this->variables as $key)
			unset($_SESSION[$key]);
		$_SESSION['isLoggedIn'] = false;
	}

	function login($username, $password) {
		global $CONFIG;
		if (!isset($_SESSION['login_retries']) || time() - $_SESSION['login_lasttry'] > 5 * 60) {
			$_SESSION['login_retries'] = 0;
			$_SESSION['login_lasttry'] = time();
		}
		if ($_SESSION['login_retries'] >= 5 && time() - $_SESSION['login_lasttry'] < 2 * 60) {
			$this->errormsg = "Du har opnået et maksimalt antal loginforsøg. Prøv igen senere";
			return false;
		}
		$sql = new SQL("reader");
		$sql->doQuery("SELECT * FROM `" . $CONFIG["db_userstable"] . "` WHERE `username`='$username'");

		if ($sql->num_rows == 0) {
			$this->errormsg = "Brugernavn ikke fundet";
			return false;
		}
		$row = $sql->getNextRow("object");
		if ($row->inactive == 1) {
			$this->errormsg = "Bruger er ikke aktiveret, afvent aktivering el. kontakt admin";
			return false;
		}

		if (md5($password) == $row->password) {
			$this->logout();
			$_SESSION['isLoggedIn'] = true;
			$_SESSION['userid'] = $row->userid;
			$_SESSION['username'] = $username;
			$_SESSION['email'] = $row->email;
			$_SESSION['firstname'] = $row->firstname;
			$_SESSION['surname'] = $row->surname;

			unset($_SESSION['login_retries']);
			unset($_SESSION['login_lasttry']);
			$sql->doQuery("UPDATE `" . $CONFIG["db_userstable"] . "` SET lastlogin=NOW() WHERE `username`='$username'");
			$this->load();
			return true;
		} else {
			$this->errormsg = "Indtastede password er forkert";
			$_SESSION['login_retries']++;
			return false;
		}
	}

	function register($username, $first, $last, $mail, $password) {
		global $CONFIG;
		$sql = new SQL("admin");
		$sql->doQuery("SELECT * FROM `" . $CONFIG["db_userstable"] . "` WHERE `username`='$username' OR email='$mail'");

		if ($sql->getCount() != 0) {
			$row = $sql->getNextRow("object");
			if ($row->inactive == 0) {
				$this->errormsg = "Brugernavn eller email er taget i brug";
			} else {
				$this->errormsg = "Brugernavn eksisterer eller der er allerede registreret en bruger med din email.";
			}
		} else if ($first == "") {
			$this->errormsg = "Fornavn ikke validt..";
		} else if ($last == "") {
			$this->errormsg = "Efternavn ikke validt..";
		} else if (count(explode(" ", $last)) > 1) {
			$this->errormsg = "Mellemnavne godtages ikke for efternavn..";
		} else if ($password == "" || strlen($password) < 6) {
			$this->errormsg = "Dit password skal minimum bestå af 6 karakterer";
		} else if (!preg_match("/^[a-zA-Z0-9_\.]*$/", $password)) {
			$this->errormsg = "Brugernavn må ikke indeholde mellemrum eller specialtegn (lovlige: [a-zA-Z0-9_])";
		} else if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/", strtoupper($_POST['mail']))) {
			$this->errormsg = "Anvendte email kan ikke valideres";
		} else {
			$sql->doQuery("INSERT INTO `" . $CONFIG["db_userstable"] . "` (userid,username,firstname,surname,email,password,inactive) VALUES ('" . md5($username) . "','" . $username . "','" . $first . "','" . $last . "','" . $mail . "','" . md5($password) . "','1')");
			$this->username = $username;
			return true;
		}
		return false;
	}

}

class INFO {

	// TODO: fill out stubs
	var $super = null;

	public function __construct(&$caller) {
		$this->super = $caller;
	}

	protected function getAvatar() {
		
	}

	protected function getInfo() {
		
	}

	protected function getInterests() {
		
	}

	protected function getAddr() {
		
	}

	protected function setInfoObject($userid) {
		if (!class_exists("SQL"))
			include "mysqlport.php";
	}

	public function GenerateBuizCard() {
		$html = "<table><tbody>" .
				  "<tr><td>" . $this->super->session['username'] . "</td><td></td></tr>" .
				  "<tr><td>" . $this->getInfo() . "</td><td></td></tr>" .
				  "<tr><td>Adresse: </td><td>" . $this->getInfo() . "</td></tr>" .
				  "</tbody></table>";
	}

}

#--------------------------------------------------------

function FormattedExport($Var, $debug = true) {
	# Exported object with a bit of extra formatting
	# Returns a string suitable for sending to screen
	# See also {DumpObjectAsTree}
	#--------------------------------------------------------

	global $CONFIG;
	if ($debug && $CONFIG['trace']) {

		$trace = debug_backtrace();
		$vLine = file(__FILE__);
		$fLine = $vLine[$trace[0]['line'] - 1];
		preg_match("#\\$(\w+)#", $fLine, $match);
		print_r($match);
	}
	ob_start();
	var_export($Var);
	$s = ob_get_contents();
	ob_end_clean();

	$s = str_replace('<', '&lt;', $s);
	$s = str_replace('>', '&gt;', $s);
	$s = str_replace('\n', '<br />', $s);
	$s = "<pre>$s</pre>";
	return $s;
}

?>
