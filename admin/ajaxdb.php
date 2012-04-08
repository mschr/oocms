<?php
session_start();
if(!preg_match("/(EditDoc|EditResource|TreeView)/", $_SERVER['HTTP_REFERER'])) {
	header("HTTP 403 Unautorized Access");
	exit();
}

if(!isset($CONFIG))
	include "../include/cm.CommonFunctions.php";
if(!isset($user))
	include "../include/cm.RequireLogin.php";
header("Content-type: text/html; charset=".$CONFIG['db_charset']);

if(isset($_POST['createfolder']) && $_POST['createfolder'] == "true") {
	$in = $_POST['createin'];
	$as = $_POST['createas'];
	$dir = $CONFIG['uploadfolder'].substr($createin, strpos($createin, "fileadmin/")+strlen("fileadmin/")).$as;;
	mkdir($dir) or die("FAILED");
	echo "SUCCESS";
} else if(isset($_POST['movefile']) && $_POST['movefile'] == "true") {
	$to = $_POST['movetopath'];
	$from = $_POST['movefrompath'];
} else if(isset($_POST['deletefile']) && $_POST['deletefile'] == "true") {
	$relpath = substr($_POST['deletepath'],strpos($_POST['deletepath'], "/", 2)+1);
	unlink($CONFIG['document_root'].$relpath);
} else if(isset($_POST['toggleposition']) && $_POST['toggleposition'] == "true") {
	require_once $CONFIG['templates']."cm.DocumentTemplate.php";
	if(!isset($_POST['id'])) die("No ID supplied");
	if(!isset($_POST['direction'])) die("No direction supplied");
//	ob_start();
	$doc = new Document();
	$doc->load($_POST['id']);
	$doc->recalculatePosition($_POST['direction']);
//	$res = ob_get_clean();
	if(strstr($res, "Nothing to swap")) {
		echo "NOP, $res";
	}
	else 
		echo ($doc->save() ? "DONE" : "FAILED|".$_SESSION['error']);
} else if(isset($_POST['dbmaintenence']) && $_POST['dbmaintenence'] > 500) {
	require_once($CONFIG['templates']."cm.DocumentTemplate.php");
	if(intval($_POST['dbmaintenence']) == 511) {
		$db = new Database();
		$db->updateSorting();
		echo "Sorted\n";
		$db->updateIndex(true);
		echo "Indexed\n";
		// FIXUP
	} else if(intval($_POST['dbmaintenence']) == 747) {
		$db = new Database();
		$db->updateKeywords(true);
		// KEYWORDS
	}

}
?>

