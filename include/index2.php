<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// redir
include_once "template.php";

header("Content-type: text/html; charset=utf-8");
session_start();
ob_start();

include "include/cm.CommonFunctions.php";
$frontid = getFrontpageId();
if($frontid != "" && (!isset($_GET['cat']) || $_GET['cat'] == "")
&& (!isset($_GET['id']) || $_GET['id'] == "") ) 
{
	header("Location: index.php?OpenDoc&id=$frontid" );
}
if((!isset($_GET['cat']) || $_GET['cat'] == "") && (!isset($_GET['id']) || $_GET['id'] == "")) {
	$id = $frontid;
} else if(isset($_GET['cat']) && $_GET['cat'] != ""){
	$id = $_GET['cat'];
} else {
	$id = $_GET['id'];
}
if(!is_numeric($id)) {
	if(!class_exists("SQL")) require_once $CONFIG['includes']."mysqlport.php";
	$sql = new SQL("reader");
	$row = $sql->doQueryGetFirstRow("SELECT id,attach_id,type from `".$CONFIG['db_pagestable']."` WHERE `alias`='$id'");
	$id = $row['type'] == "page" ? $row['id'] : $row['attach_id'];
}
if(!is_numeric($id)) {
	header("Location: error.php?404&id=1000");
}
$_SESSION['activeCategory'] = $id;

require_once $CONFIG['templates']."cm.DocumentTemplate.php";
require_once $CONFIG['templates']."cm.ResourceTemplate.php";
require_once $CONFIG['templates']."cm.ProductTemplate.php";
require_once $CONFIG['includes']."cm.Menu.Vertical.php";
include_once "template.php";

if(isset($_GET['OpenDoc'])) {

	$DOC = new Document();
	$DOC->load($id);
	if(isset($row) && $row['type'] == "subpage") {
		$subid = $row['id'];
	} else if(isset($_GET['cat']) && $_GET['cat'] != "") {
		$subid = ($_GET['id']!=""?$_GET['id']:$_GET['cat']);
	} else {
		$subid = $id;
	}
	if($_GET['id'] == $_GET['cat'] || $id == $subid) {
		$BODY = &$DOC;
	} else {
		$BODY = new Document();
		$BODY->load($subid);
	}
} else if (isset($_GET['OpenProd'])) {
	$DOC = new Product();
	$DOC->load($id);
	fb($DOC);
} else {
	echo '<a rel="nofollow" href="?OpenDoc&id=1">Home</a>';
}



$menu = new VBoxMenu();
$menu->setUrlPrefix($CONFIG['opendocprefix']."&amp;id=");
$menu->setCSS(false);
$menu->load();


// id,title W type=page
$theMenu = new DocumentCollection("page");
$theTitle = $BODY->title.(!preg_match("/r[0-9][0-9]/", $BODY->alias)?" | ".$BODY->alias:"").
	($id!=$subid?" | ".$DOC->title:""). " | " . $CONFIG['sitename']. " | ".$_SERVER['SERVER_NAME'];
if(isset($_GET['OpenDoc'])) {
	list($media, $body, $includes) = getCategory($DOC);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:st1="urn:schemas-microsoft-com:office:smarttags">
	<head>
		<title><?php echo $title; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="keywords" content="<?php echo $CONFIG['keywords'].",".$DOC->keywords; ?>" />
		<meta name="copyright" content="RIGHT BACK TRADING ApS @@@ 2008 ultim8 Fitness Ltd UK. All rights reserved" />
		<link href="css/template.css" rel="stylesheet" type="text/css" />
		<link href="css/yp.css" rel="stylesheet" type="text/css" />
		<link rel="shortcut icon" href="favicon.jpg" />
		<!--[if lt IE 7]>
			<script defer type="text/javascript" src="png.js"></script>
		<![endif]-->

		<meta name="description" content="<?php echo $CONFIG['description'];?>"/>
		<meta name="ROBOTS" content="ALL"/>
		<meta name="DC.Title" content="<?php echo $theTitle;?>"/>
		<meta name="DC.Date" content="<?php echo date('Y-m-d', time()-259200);?>"/>
		<meta name="DC.Coverage" content="Danmark,Nordjylland,Aalborg,Vejgård"/>
		<meta name="DC.Type" content="Service"/>
		<meta name="DC.Creator" content="mSigsgaard.dk"/>
		<meta name="DC.Contributor" content="<?php echo $CONFIG['siteowner'];?>"/>
		<meta name="DC.Publisher" content="<?php echo $CONFIG['sitename'];?>"/>
		<meta name="DC.Identifier" content="http://<?php echo $_SERVER['SERVER_NAME']; ?>"/>
		<meta name="DC.Rights" content="copyright <?php echo date('Y').'- '.$CONFIG['sitename'];?>. all rights reserved."/>
		<!-- Sitemapping -->
		<link rel="contents" href="sitemap.php?cat=<?php echo $id;?>"/>
		<link rel="start" href="index.php?OpenDoc&amp;id=<?php echo $frontid;?>"/>
		<link rel="index" href="index.php?OpenDoc&amp;id=<?php echo $id;?>"/>
		<link rel="section" href="index.php?OpenDoc&amp;cat=<?php echo $id;?>"/>
		<!-- Scripting -->
		<script src="/dojo_tk/dojo/dojo.js" type="text/javascript" djconfig="parseOnLoad:true" ></script>
		<script src="include/prototyping.js" type="text/javascript"></script>
		<script src="include/menulib.js" type="text/javascript"></script>
		<link rel="stylesheet" href="css/menus.css"/>
		<script type="text/javascript">
			var gDocument = <?php echo $DOC->getDocumentObject("JSON"); ?>;
			/*dojo.query(".item-c").forEach(function(d) { 
				if(/wide/.test(d.parentNode.parentNode.className)) 
					d.style.width="144px"; else d.style.width="120px";
			});*/
			function edsc(keyEvent) {
				if(keyEvent.keyChar != "E" || !keyEvent.shiftKey || !keyEvent.ctrlKey) return;

				if( confirm("Rediger dokument?") ){
					document.location.href = "admin/edit.php?EditDoc&type="+gDocument.type+"&id="+gDocument.id;
				}

			}
			dojo.addOnLoad(function() {
				initMenuElements();
				dojo.connect(document, "onkeypress", window, "edsc");

			});
		</script>
		<?php echo $includes;?>
	</head>
	<body>
	  <div id="master_main">
		<div id="master_header<?php if($DOC->pageid != 1) echo '2';?>">
			<div id="menus<?php if($DOC->pageid != 1) echo '2';?>">
				<div id="home_product">
					<div style="text-align:left;padding-left:5px;margin-bottom:50px;">
						<a href="http://www.vibration-plates.com/france/index.php">
							<img src="images/france.gif" width="30" vspace="2" alt="France" title="France" />
						</a> &nbsp;
						<a href="http://www.vibration-plates.com/">
							<img src="images/uk.gif" width="30" vspace="2" alt="UK" title="United Kingdom" />
						</a>
					</div>
					<div>
						<table class="menuFloat" cellspacing="0" cellpadding="0"><tbody><tr>
							<td class="menuButtonLeft"></td>
							<td class="narrow menuButtonRepeat">
								<div class="vbox-wrap"><div id="" class="item-c" style="color: rgb(255, 255, 255); height: 16px;">
									<a class="itemref" href="index.php" lvl="0">Forside</a>
								</div></div>
							</td><td class="menuButtonRight"/>
						</tr></tbody></table>
						<table class="menuFloat" cellspacing="0" cellpadding="0"><tbody><tr>
							<td class="menuButtonLeft"></td>
							<td class="narrow menuButtonRepeat">
								<?php echo $menu->generateFromDb(4); ?>
							</td><td class="menuButtonRight"/>
						</tr></tbody></table>
								<?php
									$menu->setUrlPrefix($CONFIG['openprodprefix']."&amp;id=");
									$COL = new ProductCollection();
									$COL->dbSearch("`type`='category'", 3, null, "rating");
									
									

									$entry = new MenuItem("Modeller", "?OpenProd&id=");
									$entry->addChild(new MenuItem("PRODTITLE", "LINKSTO"));

									$mData = new MenuData();
									$mData->addTopLevel($entry);

									echo "<table class=\"menuFloat\" cellspacing=\"0\" cellpadding=\"0\">".
										"<tbody><tr><td class=\"menuButtonLeft\"></td>";
									echo "<td class=\"narrow menuButtonRepeat\">";
									echo $menu->generateFromData($mData);
									echo "</td><td class=\"menuButtonRight\"></td></tr></tbody></table>\n";
									$menu->setUrlPrefix($CONFIG['opendocprefix']."&amp;id=");
/*
$mData = new MenuData();
$entry = new MenuItem("Publicer",
	"javascript:jumpStep('publish')",
	"Gem og afslut redigering - eller vælg yderligere afsluttende handling");
$entry->addChild(new MenuItem("Gem som kladde", 
	"javascript:jumpStep('draft')")
);
$mData->addTopLevel($entry);

$entry = new MenuItem("Annullér..",
	"javascript:jumpStep('cancel_all')",
	"Annullér det redigerede ".($DOC->created != "" ? "(siden ".$DOC->lastmodified.")" : ""));

$mData->addTopLevel($entry);
$Menu = new ButtonMenu($mData);
									echo $menu->generateFromDb(4); 
*/
								?>
							</td><td class="menuButtonRight"/>
						</tr></tbody></table>
						<?php
							for($i = 1; $i < 4; $i++) {
								echo "<table class=\"menuFloat\" cellspacing=\"0\" cellpadding=\"0\">".
									"<tbody><tr><td class=\"menuButtonLeft\"></td>";
								echo "<td class=\"narrow menuButtonRepeat\">".
									$menu->generateFromDb($theMenu->documents[$i]->pageid).
									"</td><td class=\"menuButtonRight\"></td></tr></tbody></table>\n";
							}
						?></div>
					<div class="clear"></div><?php 
						if($_GET['id'] == 1)
							echo '<div id="machine"><img alt="Vibration Plate Vægttab" src="'.
								'images/machine.jpg" /></div>';
				?></div><!-- end home_product -->
				
				
				<div id="right_wrap">
					<div id="site_logo"><img alt="Right Back Trading Aps" src="images/logo.jpg" /></div>
					<div id="right_menu">
						<table style="width:100%">
							<tbody><tr>
									<td align="right">
										&thinsp;<a rel=nofollow" href="http://www.rightback.dk">I Praksis</a>
									</td>
								</tr>
						</tbody></table>
						<div style="margin-bottom:10px;width:392px;margin-top:50px"><?php
							for($i = 4; $i < 6; $i++) {
								echo "<table class=\"menuFloat\" cellspacing=\"0\" cellpadding=\"0\">".
									"<tbody><tr><td class=\"menuButtonLeft\"></td>";
								echo "<td class=\"wide menuButtonRepeat\">".
									$menu->generateFromDb($theMenu->documents[$i]->pageid).
									"</td><td class=\"menuButtonRight\"></td></tr></tbody></table>\n";
							}
						?></div>
						<div style="width:392px;"><?php
							for($i = 6; $i < count($theMenu->documents); $i++) {
								echo "<table class=\"menuFloat\" cellspacing=\"0\" cellpadding=\"0\">".
									"<tbody><tr><td class=\"menuButtonLeft\"></td>";
								echo "<td class=\"wide menuButtonRepeat\">".
									$menu->generateFromDb($theMenu->documents[$i]->pageid).
									"</td><td class=\"menuButtonRight\"></td></tr></tbody></table>\n";
							}
						?></div>
					</div><!-- end right_menu -->
				</div><!-- end right_wrap -->
<?php if($DOC->pageid == 1) {?>
<div id="welcome">
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
<tbody>
<tr>
<td><img width="10" height="11" alt="" src="/images/b1.png"/></td>
<td><img width="475" height="11" alt="" src="/images/b2.jpg"/></td>
<td><img width="11" height="11" alt="" src="/images/b3.png"/></td>
</tr>
<tr>
<td valign="top"><img width="10" height="342" alt="" src="/images/b4.jpg"/></td>
<td valign="top" align="left" style="background-image: url(/images/wlcomebg.jpg); background-repeat: repeat-x;" class="contents">
<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;">
<tbody>
<tr>
<td style="background-image: url(/images/wlcomebg.jpg); background-repeat: repeat-x;"><br/> <br/>
<p style="margin: 0cm 0cm 0pt;" class="MsoNormal">Godt helbred og et attraktivt udseende er vigtigt for os alle, og med en travl livsstil kan vise sig svært at finde denne balance. Ultim8 Fitness er en af Storbritanniens førende eksperter i vibrationer øvelse plade teknologi. Vibrations øvelser er en bevist fitness-teknologi, hvor du bruger en energisk vibrerende plade til at stimulere dine muskler og kondenserer en 90 minutter træning i bare 15 minutter. De vibrationer stimulus har også en hel række andre gunstige virkninger for vægttab, cellulite nedskæring, sport, sundhed, skønhed, anti-aging og generelle velbefindende.</p>
<p>Ultim8 Fitness er en ambitiøs virksomhed med et eksperthold af fitness-fagfolk, der deler de samme mål. Den at være på forkant med denne teknologi. Vi betaler ikke for kendis påtegninger, så vi er i stand til at afspejle, at der i prisen på vores udstyr. Vi går ikke på kompromis med kvalitet og <strong> garanti </strong>, at vores plader er så stærke som markedets førende powered plader eller dine penge og levering omkostninger refunderet.<strong> Garanteret, risikofrit.! </strong></p>
</td>
</tr>
</tbody>
</table>
</td>
<td><img width="11" height="342" alt="" src="/images/b5.jpg"/></td>
</tr>
<tr>
<td><img width="10" height="9" alt="" src="/images/b6.png"/></td>
<td><img width="475" height="9" alt="" src="/images/b7.jpg"/></td>
<td><img width="11" height="9" alt="" src="/images/b8.png"/></td>
</tr>
</tbody>
</table>
</div>
<?php } ?>
			</div><!-- end menus -->
		</div><!-- end master_header -->
<div class="clear"></div>
		<div id="master_middle">
			<div id="master_leftbar">
				<div style="width:130px;float:left;margin-left:20px;">
					<table  border="0"  cellpadding="0" cellspacing="0">
						<tr>
							<td ><img src="images/m1.jpg" alt="" /></td>
							<td  ><img src="images/m2.jpg" alt="" /></td>
							<td  ><img src="images/m3.jpg" alt="" /></td>
						</tr><tr>
							<td><img src="images/m4.jpg" alt="" /></td>
							<td valign="top"><table style="width:100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td align="center" class="subheading">
											NYHEDSBREV
										</td>
									</tr><tr>
										<td><img src="images/spacer.gif" height="10" alt=""/></td>
									</tr><tr>
										<td>
											<form id="newletterfrm" name="newletterfrm" method="post" action="newsletter.php">
												<table width="200" border="0" cellpadding="2" cellspacing="2"><tbody>
														<tr>
															<td colspan="2" align="left" class="mainBold">
																Tilmeld email adresse
															</td>
														</tr><tr>
															<td width="39%" align="left" class="whiteBold" style="padding-left:5px;">
																<input name="n_email" size="16" type="text" class="bigtextfield" />
															</td>
															<td width="39%" align="left" class="whiteBold">
																<input name="image" type="image" src="images/go_butt.jpg" align="middle"  />
															</td>
														</tr>
												</tbody></table>
											</form>
										</td>
									</tr>
							</table></td>
							<td><img src="images/m5.jpg" alt="" /></td>
						</tr><tr>
							<td><img src="images/m6.jpg" alt="" /></td>
							<td><img src="images/m7.jpg" alt="" /></td>
							<td><img src="images/m8.jpg" alt="" /></td>
						</tr>
					</table>
					<img src="images/spacer.gif" height="20" alt="" />
					<table  border="0"  cellpadding="0" cellspacing="0">
						<tr>
							<td><img src="images/m1.jpg" alt="" /></td>
							<td><img src="images/m2.jpg" alt="" /></td>
							<td><img src="images/m3.jpg" alt="" /></td>
						</tr><tr>
							<td><img src="images/m4.jpg" alt="" /></td>
							<td valign="top"><table style="width:100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td align="center" class="subheading"><?php echo $_SESSION['lang'] == "da" ? "TILBAGEMELDINGER":"TESTIMONIALS";?></td>
									</tr><tr>
										<td><img src="images/spacer.gif" height="10" alt="" /></td>
									</tr><tr>
										<td><link href="css/yp.css" rel="stylesheet" type="text/css" />
											<table width="200" border="0" cellpadding="2" cellspacing="2" >
												<tr>
													<td colspan="2" align="left" class="contents">
														<?php random_testemonial();?>
													</td>
												</tr>
											</table>
										</td>
									</tr>
							</table></td>
							<td><img src="images/m5.jpg" alt="" /></td>
						</tr><tr>
							<td><img src="images/m6.jpg" alt="" /></td>
							<td><img src="images/m7.jpg" alt="" /></td>
							<td><img src="images/m8.jpg" alt="" /></td>
						</tr>
					</table>
		
				</div>
			</div><!-- end master_leftbar -->
			<div id="master_contents" class="contents">
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%"><tbody>
						<tr><td class="contents"><img height="10" src="images/spacer.gif" alt=""/></td></tr>
						<?php if($DOC->pageid != 1) { ?>
							<tr><td class="heading"><div id="underline"><?php echo $DOC->title;?></div></td></tr>
						<?php } ?>
						<tr><td class="contents">

							<?php echo $body; ?>

						</td></tr>
						<tr><td width="53%" valign="top" class="blue_content"> </td></tr>
				</tbody></table>
			</div>
			<table style="width:100%" cellpadding="2" cellspacing="2">
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td class="footer_text" align="center">
						<a href="./resources/index.php"><b>Resources</b></a>&nbsp;|&nbsp;
						<a href="custom_page.php?pgid=14"><b>Terms &amp; Conditions</b></a>&nbsp;|&nbsp;
						<a href="sitemap.php" ><b>Site Map</b></a>&nbsp;|&nbsp;
						<a href="testimonials.php" ><b>Testimonials</b></a>&nbsp;|&nbsp;
						<a href="custom_page.php?pgid=3" ><b>FAQ</b></a>&nbsp;|&nbsp;
						<a href="news.php" ><b>News</b></a>&nbsp;|&nbsp;
						<a href="science_research.php" ><b>Science &amp; Research</b></a>&nbsp;|&nbsp;
						<a href="contact.php" ><b>Contact Us</b></a>&nbsp;|&nbsp;
						<a href="custom_page.php?pgid=8"><b>Counter Indications- Dangers</b></a>&nbsp;|&nbsp;
						<a href="newsletter_frm.php"><b>News Letter Sign Up</b></a>
						<br/><br/>
					Copyright &copy; 2008-2009 vibtation-plates.com Int. Coptyright. All rights reserved.</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				</tr>
			</table>
		</div><!-- end master_middle -->
		</div>
	</body>
</html>
<?php

//contents($DOC->title, $DOC->getHTML());
flushlog();
exit;



























//FIXME: trigger ved id=1 ?! lookup første ID 
//if(count($DOC->title) < 1 || count($BODY->title) < 1 || ($id != $subid && $BODY->attachId != $id)) {
//	header("Location: error.php?id=1000&returnUrl=".urlencode($CONFIG['relurl']."?OpenDoc&amp;id=".$DOC->pageid));
//}
//echo "cat: $id, page: $subid";
//require_once "include/userProtocol.php";
//require_once "include/mysqlport.php";

//$user = new USER();
//$user->updateSession();

$theTitle = $BODY->title.(!preg_match("/r[0-9][0-9]/", $BODY->alias)?" | ".$BODY->alias:"").
	($id!=$subid?" | ".$DOC->title:""). " | " . $CONFIG['sitename']. " | ".$_SERVER['SERVER_NAME'];
$theMenu = new DocumentCollection("page");
if(isset($_GET['OpenDoc'])) {
	list($head, $body, $resources) = getCategory($DOC);
}
if($DOC == NULL) {
	$DOC = new Document();
	$BODY = &$DOC;
	$BODY->body = "Ingen eksisterende sider!";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title><?php echo $theTitle; ?></title>
		<link rel="shortcut icon" href="gfx/rbfavicon.ico"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="Content-Language" content="da-DK"/>
		<!-- Robots contents description -->
		<meta name="keywords" content="<?php echo $CONFIG['keywords'] . ",".$BODY->keywords;?>"/>
		<meta name="description" content="<?php echo $CONFIG['description'];?>"/>
		<meta name="ROBOTS" content="ALL"/>
		<meta name="DC.Title" content="<?php echo $theTitle;?>"/>
		<meta name="DC.Date" content="<?php echo date('Y-m-d', time()-259200);?>"/>
		<meta name="DC.Coverage" content="Danmark,Nordjylland,Aalborg,Vejgård"/>
		<meta name="DC.Type" content="Service"/>
		<meta name="DC.Creator" content="mSigsgaard.dk"/>
		<meta name="DC.Contributor" content="<?php echo $CONFIG['siteowner'];?>"/>
		<meta name="DC.Publisher" content="<?php echo $CONFIG['sitename'];?>"/>
		<meta name="DC.Identifier" content="http://<?php echo $_SERVER['SERVER_NAME']; ?>"/>
		<meta name="DC.Rights" content="copyright <?php echo date('Y').'- '.$CONFIG['sitename'];?>. all rights reserved."/>
		<!-- Sitemapping -->
		<link rel="contents" href="sitemap.php?cat=<?php echo $id;?>"/>
		<link rel="start" href="index.php?OpenDoc&amp;id=<?php echo $frontid;?>"/>
		<link rel="index" href="index.php?OpenDoc&amp;id=<?php echo $id;?>"/>
		<link rel="section" href="index.php?OpenDoc&amp;cat=<?php echo $id;?>"/>
		<!-- Scripting -->
		<script src="/dojo_tk/dojo/dojo.js" type="text/javascript" djconfig="parseOnLoad:true" ></script>
		<script src="include/prototyping.js" type="text/javascript"></script>
		<script src="include/pageviewer.js" type="text/javascript"></script>
		<script type="text/javascript">
			Page.getInstance();
			var gPage = { baseURI : '<?php echo $CONFIG['relurl'];?>' }
			var gDocument = <?php echo $DOC->getDocumentObject("JSON"); ?>;

			var gSubDocument =  <?php echo $BODY->getDocumentObject("JSON"); ?>;
			dojo.addOnLoad(function() {
				
				//else dojo.removeClass(dijit.byId('TOCTree').rootNode.getChildren()[0].rowNode, "dijitTreeNodeSelected");
				
			});
		</script>
		<!-- May lazy load -->
		<script defer="defer" src="/dojo_tk/layers.php?usecache=1&amp;layers=dojo.base,dijit.base,dijit.form.base,dojox.widget.FisheyeLite,dojo.data.ReadStore,dijit.Tree,OoCmS.Widgets" type="text/javascript"></script>
		<!-- StyleSheets -->
		<link rel="stylesheet" href="/dojo_tk/dojox/layout/resources/ScrollPane.css"/>
		<link rel="stylesheet" href="/dojo_tk/dijit/themes/nihilo/nihilo.css"/>
		<link rel="stylesheet" href="css/standard.css"/>
		<!--[if IE]>
			<link rel="stylesheet" href="css/standard-quirks.css"/>
		<![endif]-->
	</head>
	<body class="nihilo ui">

		<div id="headWrap">
			<?php echo $resources; ?>
			<div id="tocBox">
<!--
				<div style="float:left;width: 10px;height:60px" class="borderFillL"></div>
				<div style="float:right;width: 10px;height:60px;" class="borderFillR"></div>
				<div style="margin:0 10px;">### CONTENTS ###</div>
				<div style="clear:both;"></div>
				<div style="float:left; width:10px;height:10px;" class="borderBL"></div>
				<div style="float:right; width:10px;height:10px" class="borderBR"></div>
				<div style="margin-right:10px;margin-left:10px;height:10px;width:665px" class="borderFillB"></div>
				<div style="clear:both;"></div>
				<script>node = dojo.byId("tocBox");node.style.width = w + "px";
				dojo.query(".borderFillB", node).style("width", w-20+"px")</script>
-->
				<table id="tocTable" align="center" cellspacing="0" cellpadding="0"><tbody>
				  <tr>
					<td class="borderFillL"><img src="gfx/transparent.gif" alt="" height="10" width="10"/></td>
					<td class="borderTableFill" style="padding:0;margin:0">
						<div id="menu">
							<ul>
								<?php
								$theMenu->realize();
								foreach($theMenu->documents as $category) {
									if($category->isdraft) continue;
									echo '<li'.($_SESSION['activeCategory']==$category->pageid || $_SESSION['activeCategory']==$category->alias ? ' class="active"' : '').">\n";
									echo ' <a href="index.php?OpenDoc&amp;id='.$category->pageid.'" title="'.$category->alias.'">'.$category->title."</a>\n";
									echo "</li>\n";
								}
								?>
							</ul>
						</div>
						</td><td class="borderFillR"></td>
				  </tr><tr>
						<td class="borderBL"></td>
						<td class="borderFillB"></td>
						<td class="borderBR"><img src="gfx/transparent.gif" alt="" height="10" width="10"/></td>
				  </tr>
				</tbody></table>
				<script type="text/javascript">
					/* fixed TOC width */
					function fixwidthMenus()
					{
						var w = 0, b = dojo.body(), c = 0
						dojo.query("li a", "menu").forEach(function(li) {
						 w += dojo.coords(li).w + 4;c++;
						});
						dojo.byId("tocTable").style.width = w + "px";
						return; /** **/
					}
					fixwidthMenus();
				</script>
				<div style="clear:both;"></div>
			</div>
		</div>
		<div id="bodyWrap" class="contents">
			<div id="colWrapper">
				<div id="leftCol" class="leftCol">Products</div>
				<div id="rightCol" class="rightCol">
					<img src="gfx/transparent.gif" height="40" alt=""/><br/>
					<div class="subToc">
						<a id="blurme"></a>
						<div dojoType="dojo.data.ItemFileReadStore" jsId="TOCStore"
							 url="openView/TableOfContents.php?format=json&amp;subpages&amp;attach_id=<?php echo $DOC->pageid; ?>">
							<script type="dojo/connect" event="fetch">
								//<!--
								var _interval = null, childrenCount = 0;
								var focus = function() {
									var tree = dijit.byId('TOCTree');
									if(tree.rootNode.getChildren().length == 0) return
									var x = 0;
									for(var i in tree._itemNodeMap) x++;
									if(x > childrenCount) {
										childrenCount = x;
										return;
									}
									clearInterval(_interval)
									var node = tree._itemNodeMap[<?php echo $subid; ?>];
									if(node) {

										while(node=node.getParent())
											if(node != tree.rootNode) node.expand()
										tree.focusNode(tree._itemNodeMap[<?php echo $subid; ?>]);
										dojo.byId('blurme').focus();
									}
									else dojo.removeClass(tree.rootNode.getChildren()[0].rowNode, "dijitTreeNodeSelected");
									dojo.byId('TOCTree').style.cssText = "";
								}
								_interval = setInterval(focus, 125);
								// -->
							</script>
						</div>
						<div dojoType="OoCmS.TableOfContents" id="TOCTree" store="TOCStore" query="{type:'subpage'}"
							 labelAttr="title" childrenAttr="children" absoluteWidth="195" style="opacity:0;filter:alpha(opacity=0)">
							<script type="dojo/method" event="byId" args="id,node">
								//<!--
								if(node == null) node = this.rootNode;

								if(!node.isTreeNode) return null;
								if(node.item.id && node.item.id[0] == id) {
									return node;
								}
								var possibleNode = null;
								var children = node.getChildren();
								for(var i = 0; i < children.length; i++) {
									if(children[i].item.id && children[i].item.id[0] == id) {
										return children[i];
									}
									if(children[i].isExpandable) {console.log('hier' + children[i].item.id);
										possibleNode = this.byId(id, children[i]);
										if(possibleNode == null) continue;
										return possibleNode;
									}
								}
								// -->
							</script>
						</div>
					</div>
				</div>
				<div id="centerCol" class="centerCol">
					<center><?php //echo $head; ?></center>
					<div class="contentsBody" style="position:relative;display: block;">
						<div class="contentsInner"><div dojoType="OoCmS.ContentPane"
							 crossFadeDuration="1600" id="contentswrap" href="" loadIcon="'gfx/anim.gif'">

							
<?php 
								echo "<h1 id=\"doc_alias\" class=\"botPrio\">". ucfirst(preg_match("/[rp][0-9][0-9][0-9][0-9]/", $BODY->alias) ? $BODY->title : $BODY->alias) . "</h1>";
								echo "<h2 id=\"doc_title\" class=\"botSubprio\">" . $BODY->title . "</h2>";
								echo $BODY->body;
							?>
						</div></div>
					</div>
				</div>

				<div style="clear:both"></div>
			</div>
		</div>
<div id="footWrap">
	<div class="LowerGraphicsLayer">
		<div class="right"></div>
		<div class="edge"></div>
		<div style="clear:both"></div>
	</div>
	<div class="historyContainer">
		<center>
			<table width="485" cellpadding="0" cellspacing="0" align="center"><tbody>
			<tr>
						<!--<td style="display:none;width:34px"><a onclick="Page.History.resetCategory();"><img src="gfx/path-home.png"/></a></td>
						<td style="display:none;width:34px"><a onclick="Page.History.goBack();"><img src="gfx/path-back.png"/></a></td>-->
						<td>
							<!-- 10px rounded left-path -->
							<div class="crumbsLeft"><img src="gfx/transparent.gif" width="10" alt=""/></div>
							<!-- path-filling -->
							<div class="crumbsCenter" id="breadcrumbs">
								<div dojoType="OoCmS.HistoryCrumbs" jsId="'History'" orientation="horizontal" style="width: 460;height: 28px; overflow:hidden">
									<?php echo breadcrumbs(); ?>
								</div>
							</div>
							<!-- 10px rounded right-path -->
							<div class="crumbsRight"><img src="gfx/transparent.gif" width="10" alt=""/></div>
							<div style="clear:both;"></div>
						</td>
						<!--<td style="display:none;width:34px"><a onclick="Page.History.goFwd();"><img src="gfx/path-forward.png"/></a></td>-->
					</tr>
			</tbody></table>
		</center>
	</div>
	<div class="footerNavigation">
		<address style="float:right;padding-right: 30px;">v/Niels Peter B. Carstens, Hadsundvej 80, 9000 Aalborg, Tlf. 98 12 43 11</address>
		<ul class="navlinks">
			<li><a href="index.php?OpenDoc">Forside</a></li>
			<li><a href="sitemap.php">SiteMap</a></li>
			<li><a href="sitemap.php">Kontakt</a></li>
		</ul>
	</div>
</div>

		<!--	<div style="position:absolute; right: 0;top: -100px;">
				<span style="float:right;border-left:1px solid;border-top: 1px solid;display:block;">
					<img src="gfx/fortier.gif" height="120"/>
				</span>
				<div style="font-size:9pt; line-height: 14px;padding-right: 5px; color: rgb(69,45,55); font-weight: 700; font-family:georgia;width: 185px;float:right; text-align: right;">
					<br/><font color=green>Prøve version</font><br/><br/><br/>v/Niels Peter B. Carstens<br/>Hadsunvej 80<br/>9000 Aalborg<br/>Tlf. 98 12 43 11
				</div>
				<span style="clear:both;"></span>
			</div>
-->
		<!--<script src="/dojo_tk/layers.php?usecache=1&layers=dojox.widget.FisheyeLite,dojo.data.ReadStore,dijit.Tree,OoCmS.Widgets" type="text/javascript"></script>-->

	</body>
</html>

