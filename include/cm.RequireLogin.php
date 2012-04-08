<?php
global $CONFIG;
if(!isset($CONFIG)) include_once "cm.CommonFunctions.php";
if(!class_exists("USER")) include_once "userProtocol.php";

$user = new USER();
//(!isset($user) || ( isset($user) && $user->errormsg != "" ))
if(!$_SESSION['isLoggedIn'] || $user->session->expires < time()) {
	
	header("Location: ".$CONFIG['relurl']."login.php?returnUrl=".$_SERVER['REQUEST_URI']);

} else {

	if(!preg_match("/(forms|subforms)/", $_SERVER['REQUEST_URI'])) {
		;//fb("Session isLoggedIn aqquired", "Not a form");
	}

}

?>
