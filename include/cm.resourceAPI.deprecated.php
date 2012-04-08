<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$id = isset($_REQUEST['pageid'])? $_REQUEST['pageid'] : $_REQUEST['unid'];
if($id == null || $id == "") exit();

include "../db_templates/cm.ResourceTemplate.php";
$Resource = new Resource();

if(isset($_GET['getResource'])) {
	$Resource->load($id);
	if(isset($_GET['format']) && $_GET['format'] == "json") $format = "JSON";
	else $format = "HTMLFORM";
	if($format == "JSON") {
		header("Content-type: application/x-json");
		header("Cache-control: must-revalidate");
		header("Cache-control: no-cache");
		header("Pragma: no-cache");

		echo "({\n";
		echo ' "resourceId" : "'.$Resource->getUNID(),'",'."\n";
		echo ' "pageId" : "'.$Resource->pageid,'",'."\n";
		echo ' "type" : "'.$Resource->type,'",'."\n";
		echo ' "uri" : "'.$Resource->uri,'",'."\n";
		echo ' "body" : "'.$Resource->body,'",'."\n";
		echo ' "comment" : "'.$Resource->comment,'",'."\n";
		echo ' "dimension" : "'.$Resource->dimension,'",'."\n";
		echo ' "position" : "'.$Resource->position,'"'."\n";
		echo "})\n\n";
	} else {
		// TODO - or...?
	}

} else if(isset($_POST['addResource']) || isset($_POST['editResource'])) {


	if( isset($_POST['addResource']) && $_POST['addResource'] == "true" ) {
		$Resource->create($id, $_POST['position'], $_POST['type'], $_POST['uri'], $_POST['comment']);
		if($_POST['type'] == 'media' || $_POST['type'] == 'image')
		{
			$Resource->dimension = $_POST['width']."x".$_POST['height'];
			$Resource->updateDb();
		}
	}
	else {
		$Resource->load($id);
		$Resource->comment = $_POST['comment'];
		$Resource->uri = $_POST['uri'];
		if($_POST['type'] == 'media' || $_POST['type'] == 'image')
		{
			$Resource->dimension = $_POST['width']."x".$_POST['height'];
		}
		$Resource->updateDb();

	}

	echo "SAVED";
} else if($_POST['delResource'] == "true") {
	//&& isset($_SERVER['REMOTE_USER']) && $_SERVER['REMOTE_USER'] != "") {
	// must be from edit.php with a page loaded
	if(strstr($_SERVER['HTTP_REFERER'], "admin/edit.php?EditDoc") === false) exit();
	echo "ID: $id";
	$Resource->delete($_POST['unid']);
	echo "DELETED";
}
?>
