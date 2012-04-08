<?php
header("Pragma: public");
header("Expires: 0"); // set expiration time
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
$q_string = array();
foreach($_GET as $ident => $value) {
	$q_string[strtolower($ident)] = strtolower($value);
}
$maxdoc = (!isset($q_string['count']) ? null : $q_string['count']);
$offset = (!isset($q_string['start']) ? null : $q_string['start']);
$since =  (!isset($q_string['datetime']) ? null : $q_string['datetime']);
$since_column =  (!isset($q_string['datetime_col']) ? null : $q_string['datetime_col']);
$id = (!isset($q_string['id']) ? (!isset($q_string['searchid']) ? null : $q_string['searchid']) : $q_string['id']);
$mimetype =   (!isset($q_string['searchmime']) ? null : $q_string['searchmime']);
$attachedTo = (!isset($q_string['searchdoc']) ? null : $q_string['searchdoc']);

include "../include/cm.CommonFunctions.php";
require_once $CONFIG['templates']."cm.ResourceTemplate.php";

if($q_string['format'] == "json") {

	header("Content-Type: application/x-javascript; charset=UTF-8");
	//if(!preg_match("/MSIE/", $_SERVER['HTTP_USERAGENT'])) header("Content-Type: application/x-javascript; charset=UTF8");
	if($attachedTo != null || $mimetype!= null || $since!=null || $id!=null)  {
		$col = getCollection($id, $maxdoc, $offset, $mimetype, $since, $attachedTo);
		$rCount = count($col->resources);
	} else {
		$col = new ResourceCollection(false);
		$rCount = $col->dbSearch("1");
	}
	$i = 0;
	echo "{ identifier: 'id',".
			"label: 'comment',".
			"items:\n[\n";
	foreach($col->resources as $res) {
		echo $res->getDocumentObject();
		echo ($i++ < $rCount - 1) ? ",\n": "\n";
	}
	echo "]\n}";
} else if($q_string['format'] == "contents") {
	if(!isset($id)) {
		echo '{"error":"unspecified identifier"}';
	}
	header("Content-Type: text/html; charset=UTF-8");
	$res = new Resource();
	$res->load($id);
	if($res->body != null && $res->body!="") {
		echo $res->body;
	} else if($res->uri != null && $res->uri!="") {
		echo file_get_contents($res->uri);
	}
}

function getCollection($id, $maxdoc, $offset, $type, $since, $attachedTo) {

	$col = new ResourceCollection(false);
	$clause = "";
	if($since != null) {
		$clause .= (($clause!="") ? " AND":"")." UNIX_TIMESTAMP(".
		($since_column == null ? "lastmodified":"$since_column").") > $since";
	}
	if($attachedTo != null) {
		$clause .= (($clause!="") ? " AND":"").
			" (`attach_id` = '$attachedTo' ".
			"   OR `attach_id` LIKE '$attachedTo,%'".
			"   OR `attach_id` LIKE '%,$attachedTo,%'".
			"   OR `attach_id` LIKE '%,$attachedTo')";
	}
	if($id != null) {
		$clause .= (($clause!="") ? " AND":"")." id = $id";
	}
	if($type != null) {
		$clause .=(($clause!="") ? " AND":"")." mimetype = '$type'";
	}

	$len = $col->dbSearch($clause, $maxdoc, $offset);
	$col->realize();
	return $col;
	if($len == 0) exit();
	echo "{\n";
	echo ' "id" : "id",'."\n";
	echo ' "label": "comment",'."\n";
	echo ' "items": ['."\n";
	$i = 0;
	foreach($col->resources as $res) {
		echo $res->getDocumentObject();
		echo ($i++ < $len - 1) ? ",\n": "\n";
	}
	echo "]\n}";
}
?>
