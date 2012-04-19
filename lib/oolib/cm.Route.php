<?php

function route_to_location($url) {
	header("Location: $url");
	flush();
	exit;
}

function route_get_pagecategory_id() {
	
}

function route_get_frontpage_id() {
	global $CONFIG;
	$sql = new SQL("reader");
	$ok = $sql->doQuery("SELECT id as pageid FROM `" . $CONFIG['db_pagestable'] . "`" .
			  " WHERE `type`='page' AND `isdraft`='0' ORDER BY `tocpos` ASC LIMIT 1");
	if (!$ok)
		echo "Failed looking up front page, maybe database settings are wrong or no front exists?";
	if ($sql->getCount() > 0)
		return $sql->getNextRow("object")->pageid;
	else {
		route_to_location("admin/?action=page");
	}
}

function route_is_head_request() {
	return empty($_GET['cat']) && empty($_GET['id']);
}

function route_get_request_type() {
	return (isset($_REQUEST['OpenDoc']) ? "page" : (isset($_REQUEST['OpenProd']) ? "product" : "unknown"));
}

function route_get_body_id() {
	global $CONFIG;
	if (!is_numeric($_REQUEST['id'])) {
		$sql = new SQL("reader");
		$row = $sql->doQueryGetFirstRow("SELECT id from `{$CONFIG['db_pagestable']}`" .
				  " WHERE `alias`='{$_REQUEST['id']}'", "object");
		if ($sql->getCount() > 0)
			return $row->id;
	} else {
		return $_REQUEST['id'];
	}
	return -1;
}
function route_get_product_id() {
	global $CONFIG;
	if (!is_numeric($_REQUEST['id'])) {
		$sql = new SQL("reader");
		$row = $sql->doQueryGetFirstRow("SELECT id from `{$CONFIG['db_productstable']}`" .
				  " WHERE `title`='{$_REQUEST['id']}' OR `keywords` LIKE '{$_REQUEST['id']}'", "object");
		if ($sql->getCount() > 0)
			return $row->id;
	} else {
		return $_REQUEST['id'];
	}
	return -1;
}

function route_get_body_preview(Document &$document) {
	foreach($document->get_attributes() as $attr) {
		if(!empty($_POST[$attr])) $document->$attr = $_POST[$attr];
	}
}
function route_is_preview_request() {
	return !empty($_GET['previewfetch']) && $_GET['previewfetch'] == 1;
}
function route_is_async_request() {
	return !empty($_GET['subpagefetch']) && $_GET['subpagefetch'] == 1;
}

?>
