<?php
/*
* sample ?editdoc=[preset];title;newpagetitle;parentdoc;toppageid;step;prepare
* sample ?editdoc=[preset];title;newpagetitle,[form];formalias,[type];page/embed/txt
*/
//if(!isset($_GET['EditDoc']) && !isset($_GET['EditResource'])) {
//	header("Location: /admin");
//	exit();
//}
if(!isset($CONFIG))
	include "../include/cm.CommonFunctions.php";
if(!isset($user))
	include "../include/cm.RequireLogin.php";

require_once $CONFIG['templates']."cm.ResourceTemplate.php";
require_once $CONFIG['templates']."cm.DocumentTemplate.php";
require_once $CONFIG['templates']."cm.ProductTemplate.php";
require_once $CONFIG['templates']."cm.DesignElements.php";

//require_once "../include/userProtocol.php";


$id = $_GET['id'];
$type = $_GET['type'];
$title = $_GET['title'];
$form = (isset($_GET['type']) ? $_GET['type'] : "");

if($form == "" && isset($_GET['EditDoc'])) {
	header("Location: edit.php");
}
//$USER = new USER();
//$USER->updateSession();
if($type == "include"||$type == "media") {
	$DOC = new Resource();
} else if($type == "product") {
	$DOC = new Product();
} else $DOC=new Document();


if($id == "" || !isset($id)) {
	$action = "Opret";
} else {
	$action = "Redigér";
	$DOC->load($id);
}
if(isset($_GET['preset'])) {
	$list = split(",", $_GET['preset']);
	foreach($list as $entry) {
		list($ident, $value) = split(";", $entry);
		if($form == "product") $DOC->set($ident,$value);
		else $DOC->$ident = $value;
/*
		if($ident == "attach_id") {
			$DOC->attachId = $value;
		} else if($ident == "title") {
			$DOC->title = $value;
		} else if($ident == "editors") {
			$DOC->editors = $value;
		} else if($ident == "alias") {
			$DOC->alias = $value;
		} else if($ident == "body") {
			$DOC->body = $value;
		} else if($ident == "isdraft") {
			$DOC->isdraft = $value;
		} else if($ident == "category") {
			$DOC->category = $value;
		}
*/
	}
}
header("Content-type: text/html;charset=UTF-8");
?>

<html>
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<script type="text/javascript" src="/dojo_tk/dojo/dojo.js" djConfig="parseOnLoad:true"></script>

		<script type="text/javascript">
			gPage = {
			baseURI : "<?php echo preg_replace("/\/$/", "", $CONFIG['relurl']);?>"
			}
		</script>
		<script src="/dojo_tk/layers.php?usecache=1&shrinksafe=1&layers=dojo.base,dijit.base,dijit.form.extra,dojox.widget.Dialog" type="text/javascript"></script>
<!--		<script language="javascript" type="text/javascript" src="../tiny_mce/tiny_mce.js"></script>-->
		<script language="javascript" type="text/javascript" src="../tiny_mce/tiny_mce_gzip.js"></script>
		<script type="text/javascript">
			/* TODO: FIXME LANGUAGE?!*/
			tinyMCE_GZ.init({
				plugins : "safari,pagebreak,spellchecker,style,layer,"+
					"table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,"+
					"insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,"+
					"visualchars,nonbreaking,xhtmlxtras,template",
				themes : 'advanced',
				languages : 'da',
				language : 'da',
				disk_cache : true,
				debug : false
			});
		</script>

		<script language="javascript" type="text/javascript" src="../include/prototyping.js"></script>
		<script language="javascript" type="text/javascript" src="../include/pageeditor.js"></script>
		<link rel="stylesheet" href="../css/editor.css" />
		<link rel="stylesheet" href="/OoCmS/dojo_tk/dojox/widget/Dialog/Dialog.css" />
	</head>
	<body class="nihilo">
		<?php
		if($id != "" && ($DOC->title == null && $DOC->mimetype == null)) {
			// if id is requested, but no entry of that id exists, alert
			?>
		<script type="text/javascript">
			var cb = function () { window.history.back(); };
			var d = openNotify("Fejl..", _ExtendHTML.notifyTemplates.editErr, [ {
					id:'canceloption',
					cb:cb,
					classes:'dijitEditorIcon dijitEditorIconUndo'
				}
			]);
			dojo.connect(d, "hide", cb);
		</script>
		<?php
		exit();
	}
	if( !isset($_GET['EditDoc']))
	{
		include "subforms/designelements.php";
		exit();
	} else {

		include $CONFIG['forms']."$form.php";
	}
	?>


	</body>
</html>
