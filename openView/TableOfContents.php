<?php
header("Cache-control:max-age=3600");
$q_string = array();
function getChildrenOfCategory($catid) {
	$col = new DocumentCollection("subpage", false);
	$col->dbSearch("type='subpage' AND attach_id='$catid'",null,null,"tocpos");
	$col->realize();
	return $col->documents;
}
function r_generateJSON($page) {
	$i = 0;
	$subs = getChildrenOfCategory($page->pageid);
	if(($subCount = count($subs)) > 0) {
		$pageJSON = $page->getDocumentObject("JSON");
		$pageJSON = substr($pageJSON, 0,strrpos($pageJSON, "\n}"));
		echo "$pageJSON,\n \"children\":\n\t[\n";
		foreach($subs as $child) {
			r_generateJSON($child);
			echo ($i++ < $subCount-1) ? ",\n" : "\n";
		}
		echo "\t]\n}";

	} else { 
		echo $page->getDocumentObject("JSON");
	}
}
function getCategoryCollection() {

	$col = new DocumentCollection("page", false);
	$clause = "type = '$type'";
	$clause .= " AND isdraft = 0";
	$col->dbSearch($clause, $maxdoc, $offset, "tocpos");
	$col->realize();
	if(($len = $col->getSize()) == 0) return null;
	return $col;
}
foreach($_GET as $ident => $value) {
	$q_string[strtolower($ident)] = strtolower($value);
}

include "../include/cm.CommonFunctions.php";
require_once $CONFIG['templates']."cm.DocumentTemplate.php";

if(isset($q_string['format']) && $q_string['format'] == "json") {
	header("Content-Type: application/x-javascript; charset=UTF-8");
	echo "{\n";
	echo ' "label": "title",'."\n";
	echo ' "identifier": "id",'."\n";
	echo ' "items": ['."\n";
	if(isset($q_string['categories'])) {
		$col = new DocumentCollection("page");
		$i = 0;
		$len = count($col->documents);
		foreach($col->documents as $doc) {
			echo "\t{title:'".$doc->title."', id:'".$doc->pageid."', type:'page'}";
			echo ($i++ < $len-1 ? ",\n": "\n");
		}
	} else if(isset($q_string['subpages'])) {
		if(isset($q_string['attach_id'])||is_numeric($q_string['attach_id'])) {
			$col = getChildrenOfCategory($q_string['attach_id']);
			$len = count($col);
			$i = 0;
			foreach($col as $page) {
				r_generateJSON($page);
				echo ($i++ < $len-1 ? ",\n": "\n");
			}
		}
	}
	echo "  ]\n";
	echo "}\n";







}
