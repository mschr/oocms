<?php

function testReport() {
	$ok = true;
	$act = 0;
	$fields = array();
	foreach ($_POST as $key => $val) {
		if ($val == "")
			$fields[] = $key;
		if (strstr($key, "act_"))
			$act++;
	}
	$report = "<fieldset><legend>Opsætnings status</legend>" .
			  "<div style=\"padding-left: 20px;\">";

	$res = checkDocumentRoot($_POST['document_root']);
	if ($res != "Ok")
		$ok = false;
	$report .= "<b>DocumentRoot</b>: <span " . ($res == "Ok" ? "class=\"ok\"" : "class=\"err\"") . ">$res</span><br />";

	$res = checkFileAdminAccess($_POST['document_root']);
	if ($res != "Ok")
		$ok = false;
	$report .= "<b>FileAdmin</b>: <span " . ($res == "Ok" ? "class=\"ok\"" : "class=\"err\"") . ">$res</span><br />";

	$res = checkDbAccess($_POST['db_host'], $_POST['db_dbname'], $_POST['db_username'], $_POST['db_password']);
	if ($res != "Ok" && $ok)
		$ok = false;
	$report .= "<b>Database</b>: <span " . ($res == "Ok" ? "class=\"ok\"" : "class=\"err\"") . ">$res</span><br />";

	if ($act == 1) {
		$report .= "<b>Forms</b>: (warn)<br />&nbsp; <span class=\"warn\">Der er alene valgt formtypen 'page', er dette efter hensigten?</span><br />";
	}

	if (count($fields) > 0)
		$report .= "<b>Notifikationer</b>: (warn)<br />";
	foreach ($fields as $notset) {
		$report .= "&nbsp; <span class=\"warn\">CONFIG[$notset] er ikke angivet</span><br />";
	}

	if (!empty($_POST['domain']) && $_POST['domain'] != $_SERVER['SERVER_NAME'])
		$report .= "<b>Domæne</b>: <span class=\"warn\">Tilsvarer ikke nuværende domæne</span><br />";

	if ($ok) {
		$report .= "<div style=\"border-top: 1px solid; border-left:1px solid;border-right:1px solid\">" .
				  "<p style=\"padding: 2px 5px\">Konfigurationsfilen er klar, gennemse den her. Du kan vælge at fortsætte " .
				  "eller foretage ændringer - og derefter teste igen.<br/>" .
				  "<div style=\"text-align:right\"><form action=\"\" method=\"POST\">" .
				  "<input type=\"hidden\" name=\"writeToConfig\" value=\"" . md5("writeToConfig") . "\"/>" .
				  "<input type=\"submit\" name=\"isago\" value=\"Fortsæt; Skriv konfigurationsfil og initialiser databasen\"/>" .
				  "</div></p>" . generateConfig($_POST) . "</form></div>";
	}

	return array("status" => $ok, "html" => $report . "</div></fieldset>");
}

function setupForm() {
	global $CONFIG;
	if (isset($_POST['report'])) {
		$report = testReport();
		$isago = $report['status'];
		$html = $report['html'];
		$report = null;
	}
	ob_start();
	echo $html;
	$root_guess = dirname(__FILE__);
	$root_guess = empty($CONFIG['document_root']) ? substr($root_guess, 0, strrpos($root_guess, '/')) : $CONFIG['document_root'];
	$dojo_guess = empty($CONFIG['dojoroot']) ? "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/" : $CONFIG['dojoroot'];
	$domain_guess = $_SERVER['SERVER_NAME'];
	$path_guess = empty($CONFIG['relurl']) ? dirname($_SERVER["REQUEST_URI"]) : $CONFIG['relurl'];
	$webmaster_guess = empty($CONFIG['webmaster']) ? "admin@" . $_SERVER['SERVER_NAME'] : $CONFIG['webmaster'];
	$db_host = isset($_POST['db_host']) ? $_POST['db_host'] : $CONFIG['db_host'];
	$db_dbname = isset($_POST['db_dbname']) ? $_POST['db_dbname'] : $CONFIG['db_dbname'];
	$db_username = isset($_POST['db_username']) ? $_POST['db_username'] : $CONFIG['db_username'];
	$db_password = isset($_POST['db_password']) ? $_POST['db_password'] : $CONFIG['db_password'];
	$tbl_tblprefix = isset($_POST['db_tblprefix']) ? $_POST['db_tblprefix'] : $CONFIG['db_tblprefix'];
	$db_charset = isset($_POST['db_charset']) ? $_POST['db_charset'] : $CONFIG['db_charset'];
	$db_collation = (!empty($_POST['db_collation']) ? $_POST['db_collation'] : (!empty($CONFIG['db_collation']) ? $CONFIG['db_collation'] : "utf8_danish_ci"));
	$db_charset = (!empty($_POST['db_charset']) ? $_POST['db_charset'] : preg_replace("/_.*/", "", $db_collation));

	$siteowner = isset($_POST['siteowner']) ? $_POST['siteowner'] : $CONFIG['siteowner'];
	$sitename = isset($_POST['sitename']) ? $_POST['sitename'] : $CONFIG['sitename'];
	$keywords = isset($_POST['keywords']) ? $_POST['keywords'] : $CONFIG['keywords'];
	$description = isset($_POST['description']) ? $_POST['description'] : $CONFIG['description'];
	?>$CONFIG["css"]			= "/oocms/css/";
	$CONFIG["dojoroot"]			= "/dojo/";
	<script>
		dojo.require("dijit.form.FilteringSelect")
		dojo.require("dijit.form.Button")
		dojo.require("dijit.form.Form")
	</script>
	<div data-dojo-type="dijit.form.Form" id="setupform" data-dojo-id="setupform" name="setupform"
		  encType="multipart/form-data" action="" method="post" onsubmit="return this.validate()">

		<fieldset><legend>Deployment specifikke detaljer</legend>
			<table><tbody>
					<tr>
						<td style="width: 175px">Placering af 'index.php'</td>
						<td style="width: 205px"><input name="document_root" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= (isset($_POST['document_root']) ? $_POST['document_root'] : $root_guess) ?>"/></td>
						<td>Den absolutte sti på server til installationen</td>
					</tr><tr>
						<td style="width: 175px">Placering af Dojo Toolkit</td>
						<td><input name="dojoroot" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= (isset($_POST['dojoroot']) ? $_POST['dojoroot'] : $dojo_guess) ?>"/></td>
						<td>Dojo anvendes i base-moduler, og er tilgængeligt som base for tema-udviklere.</td>
					</tr><tr>
						<td style="width: 175px">Domæne</td>
						<td><input name="domain" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= (isset($_POST['domain']) ? $_POST['domain'] : $domain_guess) ?>"/></td>
						<td>Dette domæne anvendes i konstruktion af url's</td>
					</tr><tr>
						<td style="width: 175px">Installations relative URL</td>
						<td><input name="relurl" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= (isset($_POST['relurl']) ? $_POST['relurl'] : $path_guess) ?>"/></td>
						<td>Den relative sti ('/' hvis installationen ligger i øverste folder på server)</td>
					</tr><tr>
						<td style="width: 175px">Webmasters email</td>
						<td><input name="webmaster" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= (isset($_POST['webmaster']) ? $_POST['webmaster'] : $webmaster_guess) ?>"/></td>
						<td>Denne email modtager fejlrapporter og skal aktiveringslink ved oprettelse af brugere</td>
					</tr>
				</tbody></table>
		</fieldset>

		<fieldset><legend>Database opsætning</legend>
			<table><tbody>
					<tr>
						<td style="width: 175px">DB Host:</td>
						<td style="width: 205px"><input name="db_host" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= $db_host; ?>"/></td>
						<td>Server for database</td>
					</tr><tr>
						<td style="width: 175px">DB Navn:</td>
						<td style="width: 205px"><input name="db_dbname" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= $db_dbname; ?>"/></td>
						<td>Navn på database</td>
					</tr><tr>
						<!--
									<td style="width: 175px">DB User (reader):</td>
									<td style="width: 205px"><input name="db_user_reader" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= $db_username; ?>"/></td>
									<td>Fx. db_reader, kan være samme som admin</td>
								</tr><tr>
									<td style="width: 175px">DB Pass (reader):</td><td><input name="db_pass_reader" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= $db_password; ?>"/></td><td></td>
								</tr><tr>
						-->
						<td style="width: 175px">DB User (admin):</td><td><input name="db_username" data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="text" value="<?= $db_username; ?>"/></td><td>Fx. db_admin, kan være samme som reader hvis reader har fulde rettigheder</td>
					</tr><tr>
						<td style="width: 175px">DB Pass (admin):</td><td><input name="db_password"  data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" type="password" value=""/></td><td></td>
					</tr><tr>
						<td style="width: 175px">Table prefix:</td>
						<td><input name="db_tblprefix" data-dojo-type="dijit.form.TextBox" type="text" value="<?= (!empty($db_tblprefix) ? $db_tblprefix : "oocms") ?>"/></td>
						<td>Fx: <b>proj1</b>_pages</td>
					</tr><tr>
						<td style="width: 175px">Collation:</td><td>
							<input name="db_charset" type="hidden" value="<?= $db_charset ?>" id="changecharset"/>
							<select data-dojo-type="dijit.form.FilteringSelect" name="db_collation" value="<?= $db_collation ?>"
									  onchange="document.getElementById('changecharset').value = this.getValue().substring(0, this.getValue().indexOf('_'));"> 
								<option value=""> </option>
								<optgroup title="ARMSCII-8 Armenian" label="armscii8">
									<option title="Armenian, Binary" value="armscii8_bin">armscii8_bin</option>
									<option title="Armenian, case-insensitive" value="armscii8_general_ci">armscii8_general_ci</option>
								</optgroup>
								<optgroup title="US ASCII" label="ascii">
									<option title="West European (multilingual), Binary" value="ascii_bin">ascii_bin</option>
									<option title="West European (multilingual), case-insensitive" value="ascii_general_ci">ascii_general_ci</option>
								</optgroup>
								<optgroup title="Big5 Traditional Chinese" label="big5">
									<option title="Traditional Chinese, Binary" value="big5_bin">big5_bin</option>
									<option title="Traditional Chinese, case-insensitive" value="big5_chinese_ci">big5_chinese_ci</option>
								</optgroup>
								<optgroup title="Binary pseudo charset" label="binary">
									<option title="Binary" value="binary">binary</option>
								</optgroup>
								<optgroup title="Windows Central European" label="cp1250">
									<option title="Central European (multilingual), Binary" value="cp1250_bin">cp1250_bin</option>
									<option title="Croatian, case-insensitive" value="cp1250_croatian_ci">cp1250_croatian_ci</option>
									<option title="Czech, case-sensitive" value="cp1250_czech_cs">cp1250_czech_cs</option>
									<option title="Central European (multilingual), case-insensitive" value="cp1250_general_ci">cp1250_general_ci</option>
								</optgroup>
								<optgroup title="Windows Cyrillic" label="cp1251">
									<option title="Cyrillic (multilingual), Binary" value="cp1251_bin">cp1251_bin</option>
									<option title="Bulgarian, case-insensitive" value="cp1251_bulgarian_ci">cp1251_bulgarian_ci</option>
									<option title="Cyrillic (multilingual), case-insensitive" value="cp1251_general_ci">cp1251_general_ci</option>
									<option title="Cyrillic (multilingual), case-sensitive" value="cp1251_general_cs">cp1251_general_cs</option>
									<option title="Ukrainian, case-insensitive" value="cp1251_ukrainian_ci">cp1251_ukrainian_ci</option>
								</optgroup>
								<optgroup title="Windows Arabic" label="cp1256">
									<option title="Arabic, Binary" value="cp1256_bin">cp1256_bin</option>
									<option title="Arabic, case-insensitive" value="cp1256_general_ci">cp1256_general_ci</option>
								</optgroup>
								<optgroup title="Windows Baltic" label="cp1257">
									<option title="Baltic (multilingual), Binary" value="cp1257_bin">cp1257_bin</option>
									<option title="Baltic (multilingual), case-insensitive" value="cp1257_general_ci">cp1257_general_ci</option>
									<option title="Lithuanian, case-insensitive" value="cp1257_lithuanian_ci">cp1257_lithuanian_ci</option>
								</optgroup>
								<optgroup title="DOS West European" label="cp850">
									<option title="West European (multilingual), Binary" value="cp850_bin">cp850_bin</option>
									<option title="West European (multilingual), case-insensitive" value="cp850_general_ci">cp850_general_ci</option>
								</optgroup>
								<optgroup title="DOS Central European" label="cp852">
									<option title="Central European (multilingual), Binary" value="cp852_bin">cp852_bin</option>
									<option title="Central European (multilingual), case-insensitive" value="cp852_general_ci">cp852_general_ci</option>
								</optgroup>
								<optgroup title="DOS Russian" label="cp866">
									<option title="Russian, Binary" value="cp866_bin">cp866_bin</option>
									<option title="Russian, case-insensitive" value="cp866_general_ci">cp866_general_ci</option>
								</optgroup>
								<optgroup title="SJIS for Windows Japanese" label="cp932">
									<option title="Japanese, Binary" value="cp932_bin">cp932_bin</option>
									<option title="Japanese, case-insensitive" value="cp932_japanese_ci">cp932_japanese_ci</option>
								</optgroup>
								<optgroup title="DEC West European" label="dec8">
									<option title="West European (multilingual), Binary" value="dec8_bin">dec8_bin</option>
									<option title="Swedish, case-insensitive" value="dec8_swedish_ci">dec8_swedish_ci</option>
								</optgroup>
								<optgroup title="UJIS for Windows Japanese" label="eucjpms">
									<option title="Japanese, Binary" value="eucjpms_bin">eucjpms_bin</option>
									<option title="Japanese, case-insensitive" value="eucjpms_japanese_ci">eucjpms_japanese_ci</option>
								</optgroup>
								<optgroup title="EUC-KR Korean" label="euckr">
									<option title="Korean, Binary" value="euckr_bin">euckr_bin</option>
									<option title="Korean, case-insensitive" value="euckr_korean_ci">euckr_korean_ci</option>
								</optgroup>
								<optgroup title="GB2312 Simplified Chinese" label="gb2312">
									<option title="Simplified Chinese, Binary" value="gb2312_bin">gb2312_bin</option>
									<option title="Simplified Chinese, case-insensitive" value="gb2312_chinese_ci">gb2312_chinese_ci</option>
								</optgroup>
								<optgroup title="GBK Simplified Chinese" label="gbk">
									<option title="Simplified Chinese, Binary" value="gbk_bin">gbk_bin</option>
									<option title="Simplified Chinese, case-insensitive" value="gbk_chinese_ci">gbk_chinese_ci</option>
								</optgroup>
								<optgroup title="GEOSTD8 Georgian" label="geostd8">
									<option title="Georgian, Binary" value="geostd8_bin">geostd8_bin</option>
									<option title="Georgian, case-insensitive" value="geostd8_general_ci">geostd8_general_ci</option>
								</optgroup>
								<optgroup title="ISO 8859-7 Greek" label="greek">
									<option title="Greek, Binary" value="greek_bin">greek_bin</option>
									<option title="Greek, case-insensitive" value="greek_general_ci">greek_general_ci</option>
								</optgroup>
								<optgroup title="ISO 8859-8 Hebrew" label="hebrew">
									<option title="Hebrew, Binary" value="hebrew_bin">hebrew_bin</option>
									<option title="Hebrew, case-insensitive" value="hebrew_general_ci">hebrew_general_ci</option>
								</optgroup>
								<optgroup title="HP West European" label="hp8">
									<option title="West European (multilingual), Binary" value="hp8_bin">hp8_bin</option>
									<option title="English, case-insensitive" value="hp8_english_ci">hp8_english_ci</option>
								</optgroup>
								<optgroup title="DOS Kamenicky Czech-Slovak" label="keybcs2">
									<option title="Czech-Slovak, Binary" value="keybcs2_bin">keybcs2_bin</option>
									<option title="Czech-Slovak, case-insensitive" value="keybcs2_general_ci">keybcs2_general_ci</option>
								</optgroup>
								<optgroup title="KOI8-R Relcom Russian" label="koi8r">
									<option title="Russian, Binary" value="koi8r_bin">koi8r_bin</option>
									<option title="Russian, case-insensitive" value="koi8r_general_ci">koi8r_general_ci</option>
								</optgroup>
								<optgroup title="KOI8-U Ukrainian" label="koi8u">
									<option title="Ukrainian, Binary" value="koi8u_bin">koi8u_bin</option>
									<option title="Ukrainian, case-insensitive" value="koi8u_general_ci">koi8u_general_ci</option>
								</optgroup>
								<optgroup title="cp1252 West European" label="latin1">
									<option title="West European (multilingual), Binary" value="latin1_bin">latin1_bin</option>
									<option title="Danish, case-insensitive" value="latin1_danish_ci">latin1_danish_ci</option>
									<option title="West European (multilingual), case-insensitive" value="latin1_general_ci">latin1_general_ci</option>
									<option title="West European (multilingual), case-sensitive" value="latin1_general_cs">latin1_general_cs</option>
									<option title="German (dictionary), case-insensitive" value="latin1_german1_ci">latin1_german1_ci</option>
									<option title="German (phone book), case-insensitive" value="latin1_german2_ci">latin1_german2_ci</option>
									<option title="Spanish, case-insensitive" value="latin1_spanish_ci">latin1_spanish_ci</option>
									<option selected="selected" title="Swedish, case-insensitive" value="latin1_swedish_ci">latin1_swedish_ci</option>
								</optgroup>
								<optgroup title="ISO 8859-2 Central European" label="latin2">
									<option title="Central European (multilingual), Binary" value="latin2_bin">latin2_bin</option>
									<option title="Croatian, case-insensitive" value="latin2_croatian_ci">latin2_croatian_ci</option>
									<option title="Czech, case-sensitive" value="latin2_czech_cs">latin2_czech_cs</option>
									<option title="Central European (multilingual), case-insensitive" value="latin2_general_ci">latin2_general_ci</option>
									<option title="Hungarian, case-insensitive" value="latin2_hungarian_ci">latin2_hungarian_ci</option>
								</optgroup>
								<optgroup title="ISO 8859-9 Turkish" label="latin5">
									<option title="Turkish, Binary" value="latin5_bin">latin5_bin</option>
									<option title="Turkish, case-insensitive" value="latin5_turkish_ci">latin5_turkish_ci</option>
								</optgroup>
								<optgroup title="ISO 8859-13 Baltic" label="latin7">
									<option title="Baltic (multilingual), Binary" value="latin7_bin">latin7_bin</option>
									<option title="Estonian, case-sensitive" value="latin7_estonian_cs">latin7_estonian_cs</option>
									<option title="Baltic (multilingual), case-insensitive" value="latin7_general_ci">latin7_general_ci</option>
									<option title="Baltic (multilingual), case-sensitive" value="latin7_general_cs">latin7_general_cs</option>
								</optgroup>
								<optgroup title="Mac Central European" label="macce">
									<option title="Central European (multilingual), Binary" value="macce_bin">macce_bin</option>
									<option title="Central European (multilingual), case-insensitive" value="macce_general_ci">macce_general_ci</option>
								</optgroup>
								<optgroup title="Mac West European" label="macroman">
									<option title="West European (multilingual), Binary" value="macroman_bin">macroman_bin</option>
									<option title="West European (multilingual), case-insensitive" value="macroman_general_ci">macroman_general_ci</option>
								</optgroup>
								<optgroup title="Shift-JIS Japanese" label="sjis">
									<option title="Japanese, Binary" value="sjis_bin">sjis_bin</option>
									<option title="Japanese, case-insensitive" value="sjis_japanese_ci">sjis_japanese_ci</option>
								</optgroup>
								<optgroup title="7bit Swedish" label="swe7">
									<option title="Swedish, Binary" value="swe7_bin">swe7_bin</option>
									<option title="Swedish, case-insensitive" value="swe7_swedish_ci">swe7_swedish_ci</option>
								</optgroup>
								<optgroup title="TIS620 Thai" label="tis620">
									<option title="Thai, Binary" value="tis620_bin">tis620_bin</option>
									<option title="Thai, case-insensitive" value="tis620_thai_ci">tis620_thai_ci</option>
								</optgroup>
								<optgroup title="UCS-2 Unicode" label="ucs2">
									<option title="Unicode (multilingual), Binary" value="ucs2_bin">ucs2_bin</option>
									<option title="Czech, case-insensitive" value="ucs2_czech_ci">ucs2_czech_ci</option>
									<option title="Danish, case-insensitive" value="ucs2_danish_ci">ucs2_danish_ci</option>
									<option title="Esperanto, case-insensitive" value="ucs2_esperanto_ci">ucs2_esperanto_ci</option>
									<option title="Estonian, case-insensitive" value="ucs2_estonian_ci">ucs2_estonian_ci</option>
									<option title="Unicode (multilingual), case-insensitive" value="ucs2_general_ci">ucs2_general_ci</option>
									<option title="Hungarian, case-insensitive" value="ucs2_hungarian_ci">ucs2_hungarian_ci</option>
									<option title="Icelandic, case-insensitive" value="ucs2_icelandic_ci">ucs2_icelandic_ci</option>
									<option title="Latvian, case-insensitive" value="ucs2_latvian_ci">ucs2_latvian_ci</option>
									<option title="Lithuanian, case-insensitive" value="ucs2_lithuanian_ci">ucs2_lithuanian_ci</option>
									<option title="Persian, case-insensitive" value="ucs2_persian_ci">ucs2_persian_ci</option>
									<option title="Polish, case-insensitive" value="ucs2_polish_ci">ucs2_polish_ci</option>
									<option title="West European, case-insensitive" value="ucs2_roman_ci">ucs2_roman_ci</option>
									<option title="Romanian, case-insensitive" value="ucs2_romanian_ci">ucs2_romanian_ci</option>
									<option title="Slovak, case-insensitive" value="ucs2_slovak_ci">ucs2_slovak_ci</option>
									<option title="Slovenian, case-insensitive" value="ucs2_slovenian_ci">ucs2_slovenian_ci</option>
									<option title="Traditional Spanish, case-insensitive" value="ucs2_spanish2_ci">ucs2_spanish2_ci</option>
									<option title="Spanish, case-insensitive" value="ucs2_spanish_ci">ucs2_spanish_ci</option>
									<option title="Swedish, case-insensitive" value="ucs2_swedish_ci">ucs2_swedish_ci</option>
									<option title="Turkish, case-insensitive" value="ucs2_turkish_ci">ucs2_turkish_ci</option>
									<option title="Unicode (multilingual), case-insensitive" value="ucs2_unicode_ci">ucs2_unicode_ci</option>
								</optgroup>
								<optgroup title="EUC-JP Japanese" label="ujis">
									<option title="Japanese, Binary" value="ujis_bin">ujis_bin</option>
									<option title="Japanese, case-insensitive" value="ujis_japanese_ci">ujis_japanese_ci</option>
								</optgroup>
								<optgroup title="UTF-8 Unicode" label="utf8">
									<option title="Unicode (multilingual), Binary" value="utf8_bin">utf8_bin</option>
									<option title="Czech, case-insensitive" value="utf8_czech_ci">utf8_czech_ci</option>
									<option title="Danish, case-insensitive" value="utf8_danish_ci">utf8_danish_ci</option>
									<option title="Esperanto, case-insensitive" value="utf8_esperanto_ci">utf8_esperanto_ci</option>
									<option title="Estonian, case-insensitive" value="utf8_estonian_ci">utf8_estonian_ci</option>
									<option title="Unicode (multilingual), case-insensitive" value="utf8_general_ci">utf8_general_ci</option>
									<option title="Hungarian, case-insensitive" value="utf8_hungarian_ci">utf8_hungarian_ci</option>
									<option title="Icelandic, case-insensitive" value="utf8_icelandic_ci">utf8_icelandic_ci</option>
									<option title="Latvian, case-insensitive" value="utf8_latvian_ci">utf8_latvian_ci</option>
									<option title="Lithuanian, case-insensitive" value="utf8_lithuanian_ci">utf8_lithuanian_ci</option>
									<option title="Persian, case-insensitive" value="utf8_persian_ci">utf8_persian_ci</option>
									<option title="Polish, case-insensitive" value="utf8_polish_ci">utf8_polish_ci</option>
									<option title="West European, case-insensitive" value="utf8_roman_ci">utf8_roman_ci</option>
									<option title="Romanian, case-insensitive" value="utf8_romanian_ci">utf8_romanian_ci</option>
									<option title="Slovak, case-insensitive" value="utf8_slovak_ci">utf8_slovak_ci</option>
									<option title="Slovenian, case-insensitive" value="utf8_slovenian_ci">utf8_slovenian_ci</option>
									<option title="Traditional Spanish, case-insensitive" value="utf8_spanish2_ci">utf8_spanish2_ci</option>
									<option title="Spanish, case-insensitive" value="utf8_spanish_ci">utf8_spanish_ci</option>
									<option title="Swedish, case-insensitive" value="utf8_swedish_ci">utf8_swedish_ci</option>
									<option title="Turkish, case-insensitive" value="utf8_turkish_ci">utf8_turkish_ci</option>
									<option title="Unicode (multilingual), case-insensitive" value="utf8_unicode_ci">utf8_unicode_ci</option>
								</optgroup>
							</select>
						</td><td>karaktersæt for indhold i db</td>
					</tr>
				</tbody></table>
		</fieldset>

		<fieldset><legend>Templating og system opsætning</legend>
			<table><tbody>
					<!--
					<tr>
		
					<td style="width: 175px">Aktive forms/elementer</td>
					<td style="width: 205px">
						
						<input title="page" id="page" name="act_page" type="checkbox" 
							checked="checked" readonly="readonly" onclick="return false"/>
						<label title="page" for="page">Kategori-sider</label><br />
						<input title="subpage" id="subpage" title="subpage" name="act_subpage" type="checkbox" 
					<?php if (isset($_POST['act_subpage']))
						echo "checked=\"checked\""; ?>/>
						<label title="subpage" for="subpage">Under-sider</label><br />
						<input title="media" id="media" name="act_media" type="checkbox" 
					<?php if (isset($_POST['act_media']))
						echo "checked=\"checked\""; ?>/>
						<label title="media" for="media">Mediaelementer</label><br />
						<input title="include" id="include" name="act_include" type="checkbox" 
					<?php if (isset($_POST['act_include']))
						echo "checked=\"checked\""; ?>/>
						<label title="include" for="include">Inkluderbare ressourcer</label><br />
						<input title="upload" id="upload" name="act_upload" type="checkbox" 
					<?php if (isset($_POST['act_upload']))
						echo "checked=\"checked\""; ?>/>
						<label title="upload" for="upload">Fil uploads</label>
		
					</td>
					<td>Hvilke har predefinerede typer forms brugerne skal præsenteres for. I templating kan anvendes 'page', 'subpage', 'media', 'include' som har et view-interface (json), og derfor er AJAX venlige. Se index.example.php for detaljer.</td>
				</tr>--><tr>
						<td style="width: 175px">Ejer af domæne</td>
						<td><input name="siteowner" data-dojo-type="dijit.form.TextBox" type="text" value="<?= $siteowner; ?>"/></td>
						<td>Firma eller person med copyrights til indhold på sitet</td>
					</tr><tr>
						<td style="width: 175px">Navn på site</td>
						<td><input name="sitename" data-dojo-type="dijit.form.TextBox" type="text" value="<?= $sitename; ?>"/></td>
						<td>'Globalt' navn for sitet. Indgår i søgemaskiner og titler</td>
					</tr><tr>
						<td style="width: 175px">Site specifikke 'keywords'</td>
						<td><input name="keywords" data-dojo-type="dijit.form.TextBox" type="text" value="<?= $keywords; ?>"/></td>
						<td>(SEO) Disse nøgleord bruges globalt. Når et sideelement publiceres genereres og adderes ligeledes nøgleord på baggrund af dets indhold</td>
					</tr><tr>
						<td style="width: 175px">Site specifik 'description'</td>
						<td><input name="description" data-dojo-type="dijit.form.TextBox" type="text" value="<?= $description; ?>"/></td>
						<td>(SEO) Beskrivelse af sitets formål og indhold</td>
					</tr>
				</tbody></table>
		</fieldset>

		<br />
		<div style="text-align:center">
			Test dine indstillinger: <button data-dojo-type="dijit.form.Button" type="submit" name="report" value="Test opsætning">Test opsætning</button><br />
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function populateDb() {
	global $CONFIG;
	if (!isset($CONFIG))
		include dirname(__FILE__) . "/../include/config.inc.php";
	echo "<fieldset><legend>Database opsætning</legend>" .
	"<div style=\"padding-left: 20px;\">";
	$sql = file_get_contents($CONFIG['templates'] . "create.sql");
	$encoding = explode("_", $CONFIG['db_collation']);
	$sql = preg_replace("/###PREFIX###/s", $CONFIG['db_tblprefix'], $sql);
	$sql = preg_replace("/###CHARSET###/s", $encoding[0], $sql);
	$sql = preg_replace("/###COLLATION###/s", $CONFIG['db_collation'], $sql);

	$link = mysql_connect($CONFIG['db_host'], $CONFIG['db_username'], $CONFIG['db_password']);
	if ($link === false) {
		echo "<b>Database</b>: <span class=\"err\">Link til databasen ikke oprettet</span><br />";
		return false;
	}

	if (!mysql_select_db($CONFIG['db_dbname'])) {
		echo "<b>Database</b>: <span class=\"err\">Databasen " . $CONFIG['db_dbname'] . " er ukendt eller ikke tilgængelig</span><br />";
		return false;
	}
//	echo "<pre>$sql</pre>";
	foreach (explode(";\n", $sql) as $q) {
		$tbloff = strpos($q, '`');
		$table = substr($q, $tbloff, strpos($q, "`", $tbloff + 1) - $tbloff);
		if ($table == "")
			continue;

		$res = mysql_query($q);
		if (strstr(mysql_error(), "empty"))
			continue;
		if ($res == false) {
			return "<b>Database</b>: <span class=\"err\">Fejl under udførsel af query (" . mysql_error() . ")</span><br />";
		}
		echo "<b>Database</b>: <span class=\"ok\">Opretter $table .. .. Ok</span><br />";
	}
	mysql_close($link);
	return true;
}

function populateDesign() {
	global $CONFIG;
	if (!isset($CONFIG))
		include dirname(__FILE__) . "/../include/config.inc.php";
	$sql = file_get_contents($CONFIG['templates'] . "elements.sql");
	$sql = preg_replace("/###PREFIX###/s", $CONFIG['db_tblprefix'], $sql);
	$encoding = explode("_", $CONFIG['db_collation']);
	$link = mysql_connect($CONFIG['db_host'], $CONFIG['db_username'], $CONFIG['db_password']);
	if ($link === false) {
		echo "<b>Database</b>: <span class=\"err\">Link til databasen ikke oprettet</span><br />";
		return false;
	}

	if (!mysql_select_db($CONFIG['db_dbname'])) {
		echo "<b>Database</b>: <span class=\"err\">Databasen " . $CONFIG['db_dbname'] . " er ukendt eller ikke tilgængelig</span><br />";
		return false;
	}
	mysql_query("TRUNCATE TABLE `" . $CONFIG['db_elementstable'] . "`");
	mysql_query("SET NAMES UTF8");
	foreach (explode(";\n", $sql) as $q) {
		$res = mysql_query($q);
		if ($res == false && !strstr(mysql_error(), "empty")) {
			echo "<b>Database</b>: <span class=\"err\">Fejl under udførsel af query (" . mysql_error() . ")</span><br />";
			return false;
		}
	}
	mysql_close($link);
	return true;
}

function writeToConfig($conf) {
//	if(file_exists("include/config.inc.php"))
//		unlink("include/config.inc.php");
	$fp = fopen(dirname(__FILE__) . "/config.inc.php", "w");
	if (!$fp)
		return false;
	$ok = fwrite($fp, "<" . "?php\n// ** WARNING, AUTO GENERATED CONTENTS **\n// DO NOT CHANGE THIS FILE MANUALLY\n" .
			  preg_replace("/(\\\\\"|\\\')/si", "'", $conf) . "?" . ">");
	fclose($fp);
	return $ok != false;
}

function checkDbAccess($host, $dbname, $user, $pass) {

	$link = mysql_connect($host, $user, $pass);
	if ($link === false)
		return "Access denied for server: " . (empty($host) ? "<missing>" : $host);
	if (!mysql_selectdb($dbname))
		return "Access denied for database: " . (empty($dbname) ? "&lt;missing&gt;" : $dbname);
	mysql_close($link);
	return "Ok";
}

function checkDocumentRoot($root) {
	$root = ( strrpos($root, "/") == strlen($root) - 1 ? $root : $root . "/");
	if (is_dir($root) && is_file($root . "index.php") && is_file($root . "include/cm.CommonFunctions.php")) {
		return "Ok";
	}
	return "DocumentRoot does not appear to be a OoCmS installation";
}

function checkFileAdminAccess($root) {
	error_reporting(0);

	$root = ( strrpos($root, "/") == strlen($root) - 1 ? $root : $root . "/");
	$fp = fopen($root . "fileadmin/test", "w");
	if (!$fp)
		return "Access denied for directory '" . (empty($root) ? "<missing>" : $root) . "fileadmin'";
	fclose($fp);
	unlink($root . "fileadmin/test");
	return "Ok";
}

function makeTemporarySnapshot() {

	function tab2space($text, $spaces = 8) {
		$lines = explode("\n", $text);
		foreach ($lines as $line) {
			while (false !== $tab_pos = strpos($line, "\t")) {
				$start = substr($line, 0, $tab_pos);
				$tab = str_repeat(' ', $spaces - $tab_pos % $spaces);
				$end = substr($line, $tab_pos + 1);
				$line = $start . $tab . $end;
			}
			$result[] = $line;
		}
		return implode("\n", $result);
	}

	global $CONFIG;
	$phps = mergedConfig(array("db_username" => "********", "db_password" => "************"));
	$file = $CONFIG['document_root'] . "fileadmin/". md5($phps) . ".phps";
	$fp = fopen($file, "w");
	fwrite($fp, "<" . "?php\n// ** WARNING, AUTO GENERATED CONTENTS **\n// DO NOT CHANGE THIS FILE MANUALLY\n");
	fwrite($fp, preg_replace("/(\\\\\"|\\\')/si", "'", tab2space($phps)) . "?" . ">");
	fclose($fp);
	header("Cache-Control: no-cache, must-revalidate;max-age=0");
	header("Location: {$CONFIG['fileadmin']}" . md5($phps) . ".phps");
	flush();
	sleep(5);
	unlink($file);
}

function mergedConfig($conf) {
	global $CONFIG;
	foreach ($CONFIG as $key => $value) {
		if (isset($conf[$key])) {
			// keep value but maintain order as pr $CONFIG
			$value = $conf[$key];
			unset($conf[$key]);
		}
		$conf[$key] = $value;
	}

	$conf["opendocprefix"] = "{$conf["relurl"]}?OpenDoc";
	$conf["openprodprefix"] = "{$conf["relurl"]}?OpenProd";
	$conf["editdocprefix"] = "{$conf["relurl"]}admin/?EditDoc&action=page";
	$setup = "";
	foreach ($conf as $key => $value) {
		$szKey = '$CONFIG["' . $key . '"]';
		$tabequivalents = floor(strlen($szKey) / 8);
		for ($i = $tabequivalents; $i < 5; $i++)
			$szKey .= "\t";
		$setup .= $szKey . '= "' . $conf[$key] . '";' . "\n";
	}
	return $setup;
}

function generateConfig($conf) {
	/*
	  // old_admin
	  $forms = "array(";
	  if($conf['act_page']=="on") $forms .= "'page',";
	  if($conf['act_subpage']=="on") $forms .= "'subpage',";
	  if($conf['act_media']=="on") $forms .= "'media',";
	  if($conf['act_include']=="on") $forms .= "'include',";
	  if($conf['act_upload']=="on") $forms .= "'upload',";
	  $forms = preg_replace("/,$/", "", $forms) . ")";
	 */
	if (!preg_match("/\/$/", $conf['document_root']))
		$conf['document_root'] = $conf['document_root'] . "/";
	if (!preg_match("/\/$/", $conf['relurl']))
		$conf['relurl'] = $conf['relurl'] . "/";
	if (!empty($conf['db_tblprefix']))
		$conf['db_tblprefix'].="_";
	$setup = '/****** Site settings ********/' . "\n" .
			  '$CONFIG["webmaster"]    = "' . $conf['webmaster'] . '";' . "\n" .
			  '$CONFIG["sitename"]     = "' . $conf['sitename'] . '";' . "\n" .
			  '$CONFIG["siteowner"]    = "' . $conf['siteowner'] . '"; ' . "\n" .
			  '$CONFIG["keywords"]     = "' . $conf['keywords'] . '"; ' . "\n\n" .
			  '$CONFIG["description"]  = "' . $conf['description'] . '"; ' . "\n\n" .
			  // old_admin
			  // '$CONFIG["active_forms"] = '.$forms.'; '."\n\n".
			  '/****** Deployment specs ********/' . "\n" .
			  '$CONFIG["domain"]       = "' . $conf['domain'] . '";' . "\n" .
			  '$CONFIG["document_root"]= "' . $conf['document_root'] . '";' . "\n" .
			  '$CONFIG["dojoroot"]     = "' . $conf['dojo_root'] . '";' . "\n" .
			  '$CONFIG["relurl"]       = "' . $conf['relurl'] . '";' . "\n\n" .
			  '/****** Database setup ********/' . "\n" .
			  '$CONFIG["db_host"]      = "' . $conf['db_host'] . '";' . "\n" .
			  '$CONFIG["db_dbname"]    = "' . $conf['db_dbname'] . '";' . "\n" .
			  '$CONFIG["db_username"]  = "' . $conf['db_username'] . '"; ' . "\n" .
			  '$CONFIG["db_password"]  = "' . $conf['db_password'] . '";' . "\n" .
			  '$CONFIG["db_collation"] = "' . $conf['db_collation'] . '";' . "\n" .
			  '$CONFIG["db_tblprefix"] = "' . $conf['db_tblprefix'] . '";' . "\n" .
			  '$CONFIG["db_charset"]   = "' . $conf['db_charset'] . '";' . "\n" .
			  '' . "\n" .
			  '$CONFIG["db_pagestable"]     = "' . $conf['db_tblprefix'] . 'pages";' . "\n" .
			  '$CONFIG["db_resourcestable"] = "' . $conf['db_tblprefix'] . 'resources";' . "\n" .
			  '$CONFIG["db_elementstable"]  = "' . $conf['db_tblprefix'] . 'elements";' . "\n" .
			  '$CONFIG["db_templatetable"]  = "' . $conf['db_tblprefix'] . 'templatepresets";' . "\n" .
			  '$CONFIG["db_productstable"]  = "' . $conf['db_tblprefix'] . 'products";' . "\n" .
			  '$CONFIG["db_userstable"]  = "' . $conf['db_tblprefix'] . 'users";' . "\n" .
			  '$CONFIG["db_sessionstable"]  = "' . $conf['db_tblprefix'] . 'sessions";' . "\n" .
			  '// TODO: fixup scripts with these definitions instead of hardcoded uris' . "\n" .
			  '$CONFIG["includes"]  = $CONFIG["document_root"] . "include/";' . "\n" .
			  '$CONFIG["lib"]       = $CONFIG["document_root"] . "lib/";' . "\n" .
			  '$CONFIG["forms"]     = $CONFIG["document_root"] . "admin/forms/";' . "\n" .
			  '$CONFIG["subforms"]  = $CONFIG["document_root"] . "admin/subforms/";' . "\n" .
			  '$CONFIG["templates"] = $CONFIG["document_root"] . "db_templates/";' . "\n\n" .
			  '$CONFIG["css"]       = $CONFIG["document_root"] . "css/";' . "\n" .
			  '$CONFIG["icons"]     = $CONFIG["relurl"] . "ico/";' . "\n" .
			  '$CONFIG["graphics"]  = $CONFIG["relurl"] . "gfx/";' . "\n" .
			  '$CONFIG["fileadmin"] = $CONFIG["relurl"] . "fileadmin/";' . "\n" .
			  '$CONFIG["opendocprefix"] = $CONFIG["relurl"] . "?OpenDoc";' . "\n" .
			  '$CONFIG["openprodprefix"] = $CONFIG["relurl"] . "?OpenProd";' . "\n" .
			  '$CONFIG["editdocprefix"] = $CONFIG["relurl"] . "admin/?EditDoc&action=page";' . "\n";

	return '<textarea name="configuration" readonly="readonly" rows="10" style="width: 99%;">' . $setup . '</textarea>';
}
?>
