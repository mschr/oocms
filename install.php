<?php
header("Content-Type: text/html;charset=UTF-8");
include_once "include/cm.Configure.php";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<style type="text/css">
span.err {
	color: red;
}
span.warn {
	color: #AFAF05;
}
span.ok {
	color: green;
}

html, body {
	height: 100%;
	width: 100%;
	margin:0;
	padding:0;
}
body {
	background-attachment:scroll;
	background-color:transparent;
	background-image:url(gfx/topDown-gradient.png);
	background-position:left top;
	background-repeat:repeat-x;
}
fieldset table td {
	color: grey;
	font-size:small;
	font-family:verdana;
	padding:8px 0;
}
.errormsg {
	position:absolute;
	background-color: #F0FF20;
	font-size: small;
	font-variant: georgia;
	text-align: center;
	top: 0px;
	margin:0;
	font-size: 12pt;
	font-weight: 700;
	border-bottom:1px solid black;
	border-top:1px solid white;
	width: 100%;
}
.notify {
	color:blue;
	font-family:tahoma,verdana;
	font-size:16pt;
	margin-left:217px;
	text-transform:capitalize;
	margin-top:30px;
	min-width: 420px;
}
.line {
	background-image:url(ico/glossy_3d_blue_power.png);
	background-position:10px 60px;
	background-repeat:no-repeat;
	border:2px outset LightBlue;
	margin:2px 17%;
	padding:0;
	position:relative;
	min-width: 625px;
}
.description {
	color:gray;
	font-family:times New Roman;
	font-size:13pt;
	padding-left:30px;
	padding-right:30px;
	text-decoration:none !important;
	text-transform:none;
	position:relative;
}
.logintable {
	padding-top: 18px;
}
.logintable td {
	font-family: georgia;
	font-size: 12pt;
	font-weight: 700
}
.logintable input {
	margin-bottom: 5px;
}
.tail {
	position:absolute;
	font-size: small;
	font-variant: georgia;
	text-align: right;
	bottom: 50px;
	right: 25px;
	margin:0;
	width: 100%;
}
.footer {
	position:absolute;
	background-color: rgb(142,142,165);
	font-size: small;
	font-variant: georgia;
	text-align: center;
	bottom: 0px;
	margin:0;
	color: whiteSmoke;
	width: 100%;
}
.topheadline {
	font-family:arial;
	font-size:24pt;
	color: lightBlue;
}
.bottomheadline {
	font-family:arial;
	font-size:13pt;
	color: black;
}
.headline-1 {
	z-index:15;
	position:absolute;
}
.headline-2 {
	color:darkGray; 
	top:1px;left:1px;
	filter:alpha(opacity=60);opacity:0.6;
	z-index:10;
	position:absolute;
}
.headline-3{
	color:gray;
	top:2px;left:2px;
	filter:alpha(opacity=30);opacity:0.3;
	z-index:5;
	position:absolute;
}
</style>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<script type="text/javascript" src="/dojo-release-1.7.2-src/dojo/dojo.js" data-dojo-config="parseOnLoad: true"></script>
			<link rel="stylesheet" href="/dojo-release-1.7.2-src/dojo/resources/dojo.css" />
		<link rel="stylesheet" href="/dojo-release-1.7.2-src/dijit/themes/nihilo/nihilo.css" />

	<title>OoCmS Install Tool @ <?php echo $_SERVER['SERVER_NAME']; ?></title>
</head>
<body class="nihilo" style="margin-top:15px">
	<table style="width:100%;height:100%"><tbody><tr>
			<td style="height:100%"><div class="login">
	<div class="line">
	<div class="notify" style="margin-bottom: 50px;">

			<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
				<span class="headline-1">OoCmS Install tool</span>
				<span class="headline-2">OoCmS Install tool</span>
				<span class="headline-3">OoCmS Install tool</span>
			</div>
				<div class="description">
<?php

if(isset($_POST['writeToConfig']) && $_POST['writeToConfig'] == md5("writeToConfig") && isset($_POST['isago'])) {
	writeToConfig($_POST['configuration']);
	include "include/config.inc.php";
}

if(isset($_POST['isago'])) {
	if(file_exists("include/config.inc.php")) 
		include "include/config.inc.php";

	if(!isset($CONFIG['db_host']) || !isset($CONFIG['db_collation'])
	|| !isset($CONFIG['db_username']) || !isset($CONFIG['db_password']) 
	|| !isset($CONFIG['db_dbname']) || !isset($CONFIG['db_tblprefix']) ) {
		echo "<div style=\"text-align:right;\"><span class=\"err\">Konfigurationsfil findes ikke</span></div>";
	} else {
		$ok = true;
		$report = "<fieldset style=\"\"><legend>Opsætnings status</legend>".
			"<div style=\"padding-left: 20px;\">";
		$report .= "<div>".
				"<p style=\"padding: 2px 5px\">Konfigurationsfil er skrevet. Forsæt nu med at populere databasens tabeller.<br />";
		$res = checkDbAccess($CONFIG['db_host'], $CONFIG['db_dbname'], $CONFIG['db_username'],$CONFIG['db_password']);
		if($res != "Ok") {
			$ok = false;
			echo "<b>Database</b>: <span ".($res == "Ok" ? "class=\"ok\"" : "class=\"err\"").">$res</span><br />";
		}

		if($ok) {
			$report .= "<div>".
				"<p style=\"padding: 2px 5px\">Bemærk venligst, at eksisterende tables ikke overskrives eller opdateres. Opgraderer du version, kan dbupdates skabe problemer".
				"<div style=\"text-align:right\"><form action=\"\" method=\"POST\">".
				"<input type=\"submit\" name=\"db_isago1\" value=\"Initialiser databasen\"/>".
				"</div></p></form></div>";
			
		}
		echo $report . "</div></fieldset>";
		
		
	}
} 
else if(isset($_POST['db_isago1'])) {
	$res = populateDb();
	if($res == false) {
		echo "<b>Fatal fejl</b>: <span class=\"err\">Det lykkedes ikke at oprette database strukturen</span><br />";
	} else {
		echo "<div>".
			"<p style=\"padding: 2px 5px\">Tabeller fyldt ud<br/>".
			"<div style=\"text-align:right\"><form action=\"\" method=\"POST\">".
			"<input type=\"submit\" name=\"db_isago2\" value=\"Fortsæt; Opret designelementer\"/>".
			"</div></p></form></div>";
	}
	echo "</div></fieldset>";
} 
else if(isset($_POST['db_isago2'])) {
	$res = 	populateDesign();
	if($res == false) {
		echo "<b>Fatal fejl</b>: <span class=\"err\">Det lykkedes ikke indsætte data</span><br /><br /><br /><br /><br />";
	} else {
		echo "<div>".
			"<p style=\"padding: 2px 5px\">Designelementer oprettet<br/>".
			"<div>Tillykke, systemet er nu opsat. <br />".
			"Vil du på et senere tidspunkt ændre kan sitet sættes op under administrations-siden.<br />".
			"Fortsæt nu med brugeropsætning<br /><br />".
			"<form action=\"register.php\" method=\"GET\">".
			"<input type=\"submit\" value=\"Gå til bruger opsætning\"/><br />".
			"</div></p></form></div>";
	}
	echo "</div></fieldset>";
} else echo setupForm();
//var_dump($_POST);
?>
							</div>
						</form>
					</div>
					<div class="footer">
						Copyrights mSigsgaard web-udvikling 2009-2014 - All rights served
					</div>
				</div>
			</div></td>
	</tr></tbody></table>
</body></html>
