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
	<head>
		<?= Theme::renderAdminHTMLHead(); ?>
	</head>
	<body class="<?= $dtheme ?>">

		<div class="header paneHeader adminTop">
			<script>
				(function() {
					var node = document.getElementsByTagName('head')[0].childNodes[0];
					while(node && node.tagName != "TITLE") (node = node.nextSibling);
					document.writeln(node.innerHTML)
				})();
			</script>
		</div>
		<div id="outerWrap" class="colmask">
			<div class="colmid">
				<div class="colright" style="margin-left: -200px">
					<div class="col1wrap">
						<div class="col1pad" style="margin-left: 200px">
							<div class="col1">
								<div class="adminBox"><!-- Column 1 start -->
									<div id="mainContentPane" style="padding: 0; margin: 0"
										  data-dojo-type="dojox.layout.ContentPane" 
										  data-dojo-props="title: 'Intet valgt', parseOnLoad: false"></div>
									<div class="clear"></div>
									<!-- Column 1 end --></div>
							</div>
						</div>
					</div>
					<div class="col2" style="left:0;">
						<div class="adminBox"><!-- Column 2 start -->
							<div class="paneHeader" onclick="this.parentNode.className = (this.parentNode.className == ''?'folded':'');alert('re-layout ctrl-frames')">
								<div class="headertext">Funktioner</div>
							</div>
							<div id="adminmenuTreeNode"></div>
							<!-- Column 2 end --></div>

					</div>
					<div id="col3" style="display:none">
						<!-- Column 3 start -->
						<!-- Column 3 end -->
					</div>
					<!--					<div id="appErrorDiv" class="apperror"></div>-->

				</div>
			</div>
		</div>
	</div>
	<div class="footer">
		Copyrights mSigsgaard web-udvikling 2009-2014 - All rights served

	</div>

</body>


</html>
