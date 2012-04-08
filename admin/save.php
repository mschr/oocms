<?php

/*
 * To change DOC template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!preg_match("/(EditDoc|EditResource|delElement)/", $_SERVER['QUERY_STRING'])
//|| !preg_match("/(EditDoc|EditResource|TreeView)/", $_SERVER['HTTP_REFERER'])
) {
	header("HTTP/1.0 403 Unautorized Access");
	exit();
}

if (!isset($CONFIG))
	include "../include/cm.CommonFunctions.php";
if (!isset($user))
	include "../include/cm.RequireLogin.php";

header("Cache-Control: must-revalidate");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-type: text/html; charset=" . ($CONFIG['db_charset'] == "utf8" ? "UTF-8" : $CONFIG['db_charset']));

require_once "../include/mysqlport.php";
if (empty($_REQUEST['type']) && empty($_REQUEST['form']))
	die("Form missing");
//echo (checkReferer(($_REQUEST['type']!=""?$_REQUEST['type']:$_POST['form'])) ? "ID=\"".$_GET['id']."\"|SAVED" : "FEJL") ; 
// ALLWAYS RETURNS TRUE TODO:
if (!checkReferer(($_REQUEST['type'] != "" ? $_REQUEST['type'] : $_POST['form'])))
	die("Malfunction...");

if (isset($_POST['partial']))
	$partial = true;

/* * ************************************************************************** /
 * Editing assets
 * required [ form | id | body ]
 * optional [ mimetype | attachId | uri  | alias | comment ]
 * *************************************************************************** */
if (isset($_POST['addResource']) || $_POST['editResource']) {

	if (!isset($_POST['form'])) {

		die("DOCTYPE UNKNOWN");
	} else if (!$partial && (!isset($_POST['mimetype']) || $_POST['mimetype'] == "")) {

		die("Type resource ikke opgivet..");
	} else if (!$partial && ($_POST['body'] == "" && $_POST['uri'] == "")) {

		if (isset($_POST['resetstate'])) {
			// delete document if ID was created
		}
		die("Dokumentet er tomt..");
	} else if (isset($_POST['uri']) && $_POST['uri'] != "" && !preg_match("/^http/", $_POST['uri'])) {

		if (preg_match("/^www/", $_POST['uri'])) {

			$_POST['uri'] = "http://" . $_POST['uri'];
		} else if (preg_match("/^(\/|\.\.)/", $_POST['uri'])) {

			$_POST['uri'] = "http://" . $CONFIG['domainname'] . $CONFIG['relurl'] . $_POST['uri'];
		} else {

			echo "Kan ikke fortolke URI/href. <br/>";
			echo "<font color=\"black\" style=\"position:relative;top:-8px;\">";
			echo "<ul style=\"list-style-type:circle;padding-left:10px;\"><li>En relativ sti konverteres til absolut ud fra ";
			echo "http://" . $CONFIG['domainname'] . $CONFIG['relurl'] . "som 'base'</li>";
			echo "<li>Brug absolutte stier hvis du er i tvivl</li>";
			echo "<li>Spørg din administrator hvis du er i tvivl</li></ul></font>";
			exit();
		}
	}
	require_once "../db_templates/cm.ResourceTemplate.php";
	$RESOURCE = new Resource();
	if ($_GET['id'] == "" || !isset($_GET['id'])) {
		$RESOURCE->create($_POST['attachId'], $_POST['mimetype']);
		$id = $RESOURCE->id;
	} else {
		$RESOURCE->load($_GET['id']);
		$id = $_GET['id'];
	}
	$RESOURCE->load($id);
	$RESOURCE->type = (!isset($_POST['form']) ? $RESOURCE->type : $_POST['form']);
	$RESOURCE->attachId = (!isset($_POST['attachId']) ? $RESOURCE->attachId : $_POST['attachId']);
	$RESOURCE->creator = (!isset($_POST['creator']) ? $RESOURCE->creator : $_POST['creator']);
	$RESOURCE->mimetype = (!isset($_POST['mimetype']) ? $RESOURCE->mimetype : $_POST['mimetype']);
	$RESOURCE->uri = (!isset($_POST['uri']) ? $RESOURCE->uri : $_POST['uri']);
	$RESOURCE->alias = (!isset($_POST['alias']) ? $RESOURCE->alias : $_POST['alias']);
	$RESOURCE->body = (!isset($_POST['body']) ? $RESOURCE->body : $_POST['body']);
	$RESOURCE->comment = (!isset($_POST['comment']) ? $RESOURCE->comment : $_POST['comment']);

	if ($RESOURCE->mimetype == "")
		die("Efterspurgte resource findes ikke..");
	$RESOURCE->updateDb();
	echo "ID=\"$id\"|";
	echo "SAVED";
	unset($RESOURCE);
	/*	 * ************************************************************************** /
	 * Editing products
	 * required [ form | id | doctitle | description | category]
	 * optional [ price | discount_price | images  | features | specifications ]
	 * *************************************************************************** */
} else if (isset($_GET['EditDoc']) && $_POST['form'] == "product" || $_POST['form'] == "category") {


	require_once "../db_templates/cm.ProductTemplate.php";

	if (!isset($_POST['doctitle']) || $_POST['doctitle'] == "") {

		die("Titlen er tom..");
	} else if (!isset($_POST['description']) || $_POST['description'] == "") {

		die("Forklarende tekst kan ikke være tomt");
	} else if ($POST['form'] == "product" && empty($_POST['category'])) {

		die("Der er ikke valgt en valid produkt-kategori");
	}

	$PRODUCT = new Product();

	if ($_GET['id'] == "" || !isset($_GET['id'])) {

		$PRODUCT->create($user, $_POST['form'], $_POST['doctitle']);
		$id = $PRODUCT->id;
	} else {

		$PRODUCT->load($_GET['id']);
		$id = $_GET['id'];
	}
	$PRODUCT->title = (isset($_POST['doctitle']) ? $_POST['doctitle'] : $PRODUCT->title);
	$PRODUCT->lastedited = time();
	$PRODUCT->price = (isset($_POST['price']) ? $_POST['price'] : $PRODUCT->price);
	$PRODUCT->discount_price = (isset($_POST['discount_price']) ? $_POST['discount_price'] : $PRODUCT->discount_price);
	$PRODUCT->images = (isset($_POST['images']) ? $_POST['images'] : $PRODUCT->images);
	$PRODUCT->description = (isset($_POST['description']) ? $_POST['description'] : $PRODUCT->description);
	$PRODUCT->features = (isset($_POST['features']) ? $_POST['features'] : $PRODUCT->features);
	$PRODUCT->specifications = (isset($_POST['specifications']) ? $_POST['specifications'] : $PRODUCT->specifications);
	$PRODUCT->category = (isset($_POST['category']) ? $_POST['category'] : $PRODUCT->category);
	$PRODUCT->updateDb();
	echo "ID=\"$id\"|";
	echo "SAVED";
	unset($PRODUCT);
	exit();

	/*	 * ************************************************************************** /
	 * Editing pages
	 * required [ form | id | title | body ]
	 * optional [ attachId | isdraft | custom_keywords | alias | editors | lasteditedby ]
	 * *************************************************************************** */
} else if (isset($_GET['EditDoc']) && $_POST['form'] != "include") {

	require_once "../db_templates/cm.DocumentTemplate.php";

	// sure this is not faulty behavior?
	if (!$partial && !isset($_POST['form'])) {

		if ($_POST['attach_to_id'] && isset($_GET['id'])) {

			$DOC = new Document();
			$DOC->load($_GET['id']);
			if ($DOC->attachId != null && preg_match("/," . $_POST['attach_to_id'] . ",/", "," . $DOC->attachId . ",")) {

				die("'" . $DOC->title . "' er allerede tilknyttet det ønskede dokument (" . $_POST['attach_to_id'] . ")");
			} else if ($DOC->title == "" || $DOC->title == null) {

				die("Tilknyt til hvilket dokument??");
			} else if ($DOC->type != "subpage") {

				die("Kan ikke tilknytte, sikr at det valgte dokument er en underside..");
			} else {

				$DOC->attachId = $_POST['attach_to_id'];
				$DOC->updateDb();
				echo "ID=\"" . $_GET['id'] . "|SAVED";
				exit();
			}
		} else
			die("DOCTYPE UNKNOWN");
	} else if (!$partial && (!isset($_POST['doctitle']) || $_POST['doctitle'] == "")) {

		die("Titlen er tom..");
	} else if (!$partial && $_POST['body'] == "") {

		if (isset($_POST['resetstate'])) {
			// delete document if ID was created
		}

		die("Dokumentet er tomt..");
	}
	if ($partial && !isset($_GET['id'])) {

		echo "Error!";
		exit();
	}
	$DOC = new Document();
	if ($_GET['id'] == "" || !isset($_GET['id'])) {

		$DOC->create($user, $_POST['form'], $_POST['doctitle']);
		$id = $DOC->pageid;
	} else {

		$DOC->load($_GET['id']);
		$id = $_GET['id'];
	}
	if ($DOC->title == "") {

		die("Efterspurgte dokument findes ikke..");
	}

	if (isset($_POST['isdraft']) && intval($_POST['isdraft']) != $DOC->isdraft) {

		// recurse all published documents attached below and also make these drafts
		function conv_drafts($doc) {
			$ids = $doc->getChildrenDocumentIds();
			foreach ($ids as $cid) {
				$chdoc = new Document($cid);
				$chdoc->isdraft = 1;
				$chdoc->attachId = "";
				$chdoc->type = "page";
				$chdoc->save();
				// recurse
				conv_drafts($chdoc);
			}
		}

		conv_drafts($DOC);
	}

	$DOC->title = isset($_POST['doctitle']) ? $_POST['doctitle'] : $DOC->title;
	$DOC->attachId = isset($_POST['attachId']) ? $_POST['attachId'] : $DOC->attachId;
	$DOC->isdraft = isset($_POST['isdraft']) ? $_POST['isdraft'] : $DOC->isdraft;
	$DOC->custom_keywords = isset($_POST['custom_keywords']) ? $_POST['custom_keywords'] : $DOC->custom_keywords;
	$DOC->type = isset($_POST['form']) ? $_POST['form'] : $DOC->type;
	$DOC->tocpos = isset($_POST['tocpos']) ? $_POST['tocpos'] : $DOC->tocpos;
	$DOC->alias = isset($_POST['alias']) && $_POST['alias'] != "" ? $_POST['alias'] : $DOC->alias;
	$DOC->body = isset($_POST['body']) ? $_POST['body'] : $DOC->body;
	$DOC->editors = isset($_POST['editors']) ? $_POST['editors'] : $DOC->editors;
	$DOC->lasteditedby = isset($_POST['lasteditedby']) ? $_POST['lasteditedby'] : $DOC->lasteditedby;
	$ok = $DOC->updateDb();
	//if(isset($_GET['id'])) $DOC->load($_GET['id']);
	//else $DOC->load($id);
	if (!$ok) {
		echo "ID=\"$id\"|FAILED; " . $_SESSION['error'];
	} else
		echo "ID=\"$id\"|" . "SAVED";
	unset($DOC);

	/*	 * ************************************************************************** /
	 * Deleting element
	 * required [ type | id ]
	 * optional [ recursive ]
	 * *************************************************************************** */
} else if (isset($_GET['delElement']) && isset($_POST['id'])) {

	if ($_POST['type'] == "include") {

		require_once "../db_templates/cm.ResourceTemplate.php";
		$el = new Resource();
	} else if ($_POST['type'] == "product" || $_POST['type'] == "category") {

		require_once "../db_templates/cm.ProductTemplate.php";
		$el = new Product();
	} else if ($_POST['type'] == "page" || $_POST['type'] == "subpage") {

		require_once "../db_templates/cm.DocumentTemplate.php";
		$el = new Document();
		// implement $_POST['recurse']
	}
	$el->load($_POST['id']);
	if ($_POST['type'] == "page" || $_POST['type'] == "subpage") {

		function collect_subpages($doc, $dataArr) {
			//var_dump($doc);
			$ids = $doc->getChildrenDocumentIds();
			foreach ($ids as $cid) {
				$chdoc = new Document($cid);
				array_push($dataArr, $chdoc);
				// recurse
				collect_subpages($chdoc, &$dataArr);
			}
		}

		$docCollection = array();
		collect_subpages($el, &$docCollection);

		if (isset($_POST['recursive']) && $_POST['recursive'] == "true") {
			// delete documents attached below
			$ok = true;
			foreach ($docCollection as $chdoc) {
				$id = $chdoc->pageid;
				$res = $chdoc->delete();
				if ($ok) {
					$ok = $res;
					echo "ID=\"$id\"|DELETED\n";
				} else {
					echo "ID=\"$id\"|DELETE FAILED\n";
					echo $_SESSION['error'] . "\n";
				}
			}
		} else {

			// recurse all documents attached below and make these drafts
			foreach ($docCollection as $chdoc) {
				$chdoc->isdraft = 1;
				$chdoc->attachId = "";
				$chdoc->type = "page";
				$chdoc->save();
			}
		}
	}

	$ok = $el->delete();

	if ($ok) {

		echo "ID=\"" . $_POST['id'] . "\"|";
		echo "DELETED";
	} else {

		echo $_SESSION['error'];
	}

	unset($el);
} else if (!empty($_REQUEST['type']) && $_REQUEST['type'] == "configure") {
	include_once $CONFIG['includes'] . "cm.Configure.php";
	$allowed = array("webmaster", "sitename", "siteowner", "keywords",
		 "description", "domain", "document_root", "relurl", "dojoroot", "dojotheme",
		 "db_host", "db_dbname", "db_username", "db_password", "db_collation", "db_tblprefix",
		 "db_charset", "db_pagestable", "db_resourcestable", "db_elementstable",
		 "db_templatetable", "db_productstable", "db_userstable", "db_sessionstable",
		 "includes", "lib", "forms", "subforms", "templates", "icons", "graphics", "fileadmin");
	foreach ($_POST as $k => $v) {
		if (!in_array($k, $allowed))
			unset($_POST[$k]);
	}
	$confText = mergedConfig($_POST);
	if(writeToConfig($confText))
		echo "SAVED";
	else echo "ERROR: Failed to write config file";
}

function checkReferer($case) {
	// old_admin; 
	// allways true
	return true;
	$r = $_SERVER['HTTP_REFERER'];

	switch ($case) {
		case "include":
			if (preg_match("/admin\.php.*TreeView.*resources/", $r))
				return true;
			if (preg_match("/edit\.php.*type=page/", $r))
				return true;
			if (preg_match("/edit\.php.*type=include/", $r))
				return true;
			break;
		case "media":
			if (preg_match("/admin\.php.*TreeView.*resources/", $r))
				return true;
			if (preg_match("/edit\.php.*type=media/", $r))
				return true;
			if (preg_match("/edit\.php.*type=page/", $r))
				return true;
			if (preg_match("/edit\.php.*type=include/", $r))
				return true;
			break;
		case "page":
			if (preg_match("/admin\.php.*TreeView/", $r))
				return true;
			if (preg_match("/edit\.php.*type=page/", $r))
				return true;
			break;
		case "subpage":
			if (preg_match("/admin\.php.*Tree/", $r))
				return true;
			if (preg_match("/edit\.php.*type=subpage/", $r))
				return true;
			break;
		case "file":
			if (preg_match("/edit\.php.*type=upload/", $r))
				return true;
			break;
		case "dir":
			if (preg_match("/edit\.php.*type=upload/", $r))
				return true;
			break;
		case "category":
			if (preg_match("/edit\.php.*type=product/", $r))
				return true;
			if (preg_match("/admin\.php.*TreeView.*products/", $r))
				return true;
			break;
		case "product":
			if (preg_match("/edit\.php.*type=product/", $r))
				return true;
			break;
		default:
			return false;
	}
	return false;
}

?>
