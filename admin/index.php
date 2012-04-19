<?php
if (!isset($CONFIG))
	include dirname(__FILE__) . "/../include/cm.CommonFunctions.php";
if (!isset($user))
	include "../include/cm.RequireLogin.php";

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" && preg_match("/admin\//", $_SERVER['HTTP_REFERER'])) {
	echo "<h2><b>Load Exception, fetch-url not properly set, this contents can only be retreived via a full page load</b></h2>";
	exit;
}

require_once $CONFIG['lib'] . "oolib/cm.Theme.php";
$dtheme = (empty($CONFIG['dojotheme']) ? "claro" : $CONFIG['dojotheme']);

//$dtrunk = "//ajax.googleapis.com/ajax/libs/dojo/1.7.2/";
// nihilo soria tundra claro << tundra++
?>
<!DOCTYPE html>
<html>
	<head
	<?= Theme::renderAdminHTMLHead(); ?>
</head>
<body class="<?= $dtheme ?>" style="display:none">
	<div id="border" data-dojo-type="dijit.layout.BorderContainer">
		<div data-dojo-props="splitter: false,region: 'top'" data-dojo-type="dijit._WidgetBase" style="top:0px !important" class="header paneHeader adminTop">
			<script>
				(function() {
					var node = document.getElementsByTagName('head')[0].childNodes[0];
					while(node && node.tagName != "TITLE") (node = node.nextSibling);
					document.writeln(node.innerHTML)
				})();
			</script>
		</div>
		<div data-dojo-type="dijit._WidgetBase" style="width: 180px;"
			  data-dojo-props="splitter:true, region:'left'" id="mainleftColumn"
			  class="dijitBorderContainer-child">
			<div class="paneHeader">
				<div class="headertext">Funktioner</div>
			</div>
			<div id="adminmenuTreeNode"></div>
		</div>
		<!--		<div id="mainContentPane" style="padding: 0; margin: 0" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="parseOnLoad: false, splitter: true,region: 'center'">
					sample
				</div>-->
		<div data-dojo-type="dijit._WidgetBase" data-dojo-props="splitter: false,region: 'bottom'" class="footer">
			Copyrights mSigsgaard web-udvikling 2009-2014 - All rights served
		</div>
	</div>


</body>


</html>
