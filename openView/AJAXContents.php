<?php
if(!isset($_GET['id']))
{
	header("HTTP 403 Unauthorized Access");
	exit();
}
header("Cache-control: max-age=28800");
header("Last-Modified: ".$doc['lastmodified']."");
header("Content-Type: text/html;charset=UTF-8");
session_start();

include "../include/cm.CommonFunctions.php";
require_once $CONFIG['templates']."cm.DocumentTemplate.php";
require_once $CONFIG['includes']."cm.TraceActivity.php";

	$doc = new Document($_GET['id']);
	if(strlen($doc->body) == 0) die("Page not found");
	echo "<h1 id=\"doc_alias\" class=\"botPrio\">". ucfirst(preg_match("/[rp][0-9][0-9][0-9][0-9]/", $doc->alias) ? $doc->title : $doc->alias) . "</h1>";
	echo "<h2 id=\"doc_title\" class=\"botSubprio\">" . $doc->title . "</h2>";
	echo $doc->body."<script type=\"text/javascript\">eval(\"gDocument = ".$doc->getDocumentObject("JSON")."\");</script>";

	$track = unserialize($_SESSION['tO']);
	if($track === false) {
		$track = new TraceActivity();
	}
	$track->setCurView($doc->type);
	// categories not served through ajax..
	//$track->setCurCategory($doc->attachId != "" ? $doc->attachId : $doc->pageid);
	$track->setCurPage($doc->pageid, $doc->title);

	$_SESSION['tO'] = serialize($track);
	unset($doc);
	unset($track);

?>
