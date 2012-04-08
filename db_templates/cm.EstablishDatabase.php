<?php

$CONFIG['document_root'] = "/var/www/vhosts/msigsgaard.dk/subdomains/web/httpdocs/";
// TODO: other naming for docroot
$CONFIG['relurl'] = "/";

$CONFIG['db_host'] = 'localhost';
$CONFIG['db_dbname'] = 'b53936_www-data';
$CONFIG['db_username'] = 'b5393_admin';
$CONFIG['db_password'] = 'Y2HhYnx6u9xhxUyD';

$CONFIG['db_pagestable'] = 'pages';
$CONFIG['db_resourcestable'] = 'resources';
$CONFIG['db_elementstable'] = 'elements';
$CONFIG['db_templatetable'] = 'templatepresets';

$CONFIG['webmaster'] = "admin@msigsgaard.dk";

$CONFIG['activeforms'] = array("page", "subpage", "include", "upload");
function setupForm() {
ob_start();
?>
	<form action="">
		<fieldset><legend>Deployment specifikke detaljer</legend>
		<table><tbody>
		<tr>
			<td>Placering af 'index.php'</td><td><input name="document_root" type="text" size="32"/></td><td>Den absolutte sti på server til installationen</td>
		</tr><tr>
			<td>Installations relative URL</td><td><input name="relurl" type="text" size="32"/></td><td>Den relative sti (fra første '/' til 'index.php')</td>
		</tr><tr>

		</tbody></table>
		</fieldset>
		<fieldset><legend>Database opsætning</legend>
		<table><tbody>
		<tr>
			<td>DB User (reader):</td><td><input name="db_user_reader" type="text" size="15"/></td><td>Fx. db_reader, kan være samme som admin</td>
		</tr><tr>
			<td>DB Pass (reader):</td><td><input name="db_pass_reader" type="text" size="15"/></td><td></td>
		</tr><tr>
			<td>DB User (admin):</td><td><input name="db_user_admin" type="text" size="15"/></td><td>Fx. db_admin, kan være samme som reader hvis reader har fulde rettigheder</td>
		</tr><tr>
			<td>DB Pass (admin):</td><td><input name="db_pass_admin" type="text" size="15"/></td><td></td>
		</tr><tr>
			<td>Table prefix:</td><td><input name="prefix" type="text" size="15"/></td><td>Fx: <b>proj1</b>_pages</td>
		</tr><tr>
			<td>Collation:</td><td>
				<select name="tbl_collation" dir="ltr" xml:lang="en">
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
		</tr><tr>
			<td></td><td colspan="2"><input type="submit"></td>
		</tr>
	</tbody></table>
	<script type="text/javascript">
		var coll = '<?php
			echo (!empty($_POST['tbl_collation'])?$_POST['tbl_collation']:"utf8_danish_ci");
		?>'
		var prefix = '<?php
			echo (!empty($_POST['prefix'])?$_POST['prefix']:"oocms");
		?>'
		for(var i in document.forms[0].tbl_collation.options) {
			if(document.forms[0].tbl_collation.options[i].value == coll)
				document.forms[0].tbl_collation.options[i].selected = true;
		}
		document.forms[0].prefix.value = prefix;
	</script>
	</fieldset>
<?php
	return ob_get_clean();
}
function createIfNotExists($prefix, $collation) {

	$sql = file_get_contents("create.sql");
	$encoding = explode("_", $collation);
	$sql = preg_replace("/###PREFIX###/s", $prefix, $sql);
	$sql = preg_replace("/###CHARSET###/s", $encoding[0], $sql);
	$sql = preg_replace("/###COLLATION###/s", $collation, $sql);
	echo "<pre>$sql</pre>";
	$SQL = new SQL("admin");


}
	$coll = (!empty($_POST['tbl_collation'])?$_POST['tbl_collation']:"utf8_danish_ci");
	$prefix = (!empty($_POST['prefix'])?$_POST['prefix']:"oocms");
header("Content-Type: text/html;charset=UTF-8");
echo setupForm();
createIfNotExists($prefix, $coll);





?>
