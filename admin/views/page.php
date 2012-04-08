<?php
if (!isset($CONFIG))
	include dirname(__FILE__) . "/../../include/cm.CommonFunctions.php";
if (!isset($user))
	include $CONFIG['includes'] . "cm.RequireLogin.php";
?>
<style type="text/css">
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/TextColor.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/PasteFromWord.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/FindReplace.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/InsertEntity.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/Save.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/Preview.css";

	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojo/resources/dnd.css";

	.dijitEditor .dijitInputInner{
		line-height: 20px;	
	}
	.dijitToolbar .label  {
		position:relative;
		top: 3px;
		padding: 0 4px;
	}
	#pageselector {
		overflow-y: auto;overflow-x: hidden;
	}
	#pageselector .dijitTree {
		overflow: visible
	}

	.pageeditorWrapper {
		border: 1px solid #ccc;
		margin: 0 8px;
	}
	.errormsg {
		position:absolute;
		top: 31px;
		background-color: #F0FF20;
		font-size: small;
		font-variant: georgia;
		text-align: center;
		margin:0;
		font-size: 12pt;
		font-weight: 700;
		border-bottom:1px solid black;
		border-top:1px solid white;
		width: 100%;
		overflow: hidden;
	}
</style>
	<div class="paneHeader">
		Sider &gt; <?php
if ($_GET['form'] == "page")
	echo "Opsætning";
else if ($_GET['form'] == "pagetree")
	echo "Hieraki";
else if ($_GET['form'] == "pagegrouping")
	echo "Gruppering";
?>
	</div>

	<div id="leftcolumn" class="firstColumn">

		<div id="pageselectortbar"></div>
		<div id="pageselector" style=""></div>
		<div id="resourceselector"></div>
		<!--<div class="nodeInfo">
			<div class="helpNode"></div>
			<divclass="infoNode"></div>
		</div>-->
	</div>
	<div id="rightcolumn" style="display:none">
		<div id="resourceselectortbar"></div>
		<div id="resourceselector"></div>
	</div>
	<div id="centercolumn" class="lastColumn">
		<div id="pagetoolbarWrapper"></div>
		<div id="appErrorDiv" class="errormsg" style="display: none"></div>
		<?php if ($_GET['form'] == "page") { ?>
			<div id="formWrapper">

				<form id="pageform" onSubmit="return false;" action="save.php?EditDoc" method="POST">
					<table style="width:99.6%" cellspacing="0" cellpadding="0"><tbody class="mceLookFeel">
							<tr class="mceFirst mceLast">
								<td style="width:25%">
									<div class="first mceButtonStyle">
										<label>Titel:</label><br />
										<input data-dojo-type="dijit.form.TextBox" type="text" value="" name="doctitle"/>
									</div>
								</td>
								<td style="width:25%">
									<div class="mceButtonStyle">
										<label>Oprettelsesdato:</label><br />
										<span id="onfly-created" class="dateformat"></span>
									</div>
								</td>
								<td style="width:25%">
									<div class="mceButtonStyle">
										<label>Ejer:</label><br />
										<span id="onfly-creator"></span>
									</div>
								</td>
								<td style="width:25%">
									<div class="last mceButtonStyle">
										<label>Sidst ændret:</label><br />
										<span class="dateformat" id="onfly-lastmodified"></span>
									</div>
								</td>
							</tr>
							<tr class="mceLast">
								<td>
									<div class="first mceButtonStyle">
										<label>Alternativ titel:</label><br />
										<input class="urlformat" data-dojo-type="dijit.form.TextBox" type="text" value="" name="alias"/>
										<!--&nbsp;<span id="aliasinfotip"style="text-decoration: none;font-style:italic">(?)</span>
										<div data-dojo-type="dijit.Tooltip"
											  connectId="aliasinfotip"
											  label='Dette er titlen, der angives i toppen af et dokument. Der bør udfyldes en sigende alternativ titel, idét søgemaskiner indekserer præcist dette felt som top-prioritet. Hvis der ikke angives noget, anvendes titlen (duplikeret fra menuen). Dette felt vil også kunne angive URL, for mellemrum forventes en bindestreg i adressefeltet.<br />Eksempel:  <br /><span style="font-weight:700;font-size: 9pt;color:lightBlue">&nbsp; &nbsp;http://<?php echo $_SERVER['SERVER_NAME']; ?><?php echo $CONFIG['relurl']; ?>/titel-beskriver-indhold'>
										</div>
										-->
									</div> 
								</td>
								<td>
									<div class="mceButtonStyle">
										<table class="clear" cellspacing="0" cellpadding="0" style="padding:0;margin:0" width="100%" align="center"><tbody>
												<tr><td style="background-color:#CADBEE"><label>Ressourcer:</label></td><td style="background-color:#CADBEE"><label>Undersider:</label></td></tr>
												<tr><td align="center" style="background-color:#CADBEE" id="nResources">0</td><td align="center" style="background-color:#CADBEE" id="nSubPages">0</td></tr>
											</tbody></table>
									</div>
								</td>
								<td>
									<div class="mceButtonStyle">
										<table class="clear" cellspacing="0" cellpadding="0" style="padding:0;margin:0" width="100%" align="center"><tbody>
												<tr><td style="background-color:#CADBEE">
														<label>Tilknyttet til:</label>
													</td><td style="background-color:#CADBEE">
														<label>Tilstand:</label>
													</td></tr>
												<tr>
													<td align="center" style="background-color:#CADBEE" id="onfly-attachId">
														<div style="width: 85px;" id="attachidcombo"></div>
													</td>
													<td align="center" style="background-color:#CADBEE" id="onfly-isdraft">
														<select style="width: 85px;" id="isdraftcombo" data-dojo-type="dijit.form.ComboBox" data-dojo-props="">
															<option value="0">Kladde</option>
															<option value="1">Publiceret</option>
														</select>
													</td>
												</tr>
											</tbody></table>
									</div>
								</td>
								<td>
									<div class="last mceButtonStyle">
										<label>Gendannelsespkt. (v. annullér):</label><br />
										<span class="dateformat" id="onfly-restorepoint"></span>
									</div>
								</td>
							</tr>

						</tbody></table>
					<div class="pageeditorWrapper" data-dojo-type="dijit.Editor"
						  data-dojo-props="
						  plugins:[
						  '|',
						  'bold','italic','underline','|',
						  'createLink',
						  'unlink',
						  '|',
						  'insertImage',
						  'insertEntity',
						  'pastefromword',
						  '|',
						  'foreColor',
						  'hiliteColor',
						  'findreplace',
						  {
						  name: 'prettyprint',
						  entityMap: dojox.html.entities.html.concat(dojox.html.entities.latin),
						  indentBy: 3,
						  lineLength: 80,
						  xhtml: true
						  },
						  'customsave',
						  {
						  name: 'preview',
						  stylesheets: [
						  '{{dataUrl}}dojox/editor/tests/testBodySheet.css',
						  '{{dataUrl}}dojox/editor/tests/testContentSheet.css'
						  ]
						  },
						  'viewsource','|',
						  '||',
						  '|',
						  {name: 'fontName', plainText: true}, '|',
						  {name: 'fontSize', plainText: true}, '|',
						  {name: 'formatBlock', plainText: true},'|'
						  ], 
						  styleSheets:'http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojo/resources/dojo.css'"
						  id="pageformEditor">
					</div>
					<input type="hidden" name="form" value="page"/>
					<input type="hidden" name="body" />
				</form>
			</div>
		<?php } else if ($_GET['form'] == "pagetree") { ?>
			<div style="overflow-y:auto">
				<table style="width:99.6%"><tbody class="mceLookFeel">
						<tr class="mceFirst mceLast"><td style="width:100%">
								<div class="first last mceButtonStyle" style="height: auto;position: relative;">
									<b>Element i <i>Edit</i></b>:
									<span id="edititem-text"></span>
								</div>
							</td></tr>
						<tr class="mceFirst mceLast"><td style="width:100%">
								<div class="first last mceButtonStyle" style="height: auto;position: relative;">
									<b>Ressource i <i>Focus</i></b>:
									<span id="focusitem-text"></span>
								</div>
							</td></tr>
						<tr class="mceFirst mceLast"><td style="width:100%">
								<div class="first last mceButtonStyle" style="height: auto;position: relative;">
									<b>Howto</b>:
									Sider har relationer til elementer, dels til hverandre og dels til ressources (includes).<br/>
									Enhver side kan tilhøre en anden som dennes underside. Sider i top-niveauet kan have tilknyttet ressourcer såsom script eller style - og sider herunder nedarver hermed denne include.
									Sidst men ikke mindst, kan en side være gemt som kladde.<br/>
									<br/>
									Dvs. der findes fem typer, hhv 'side', 'underside', 'sidekladde', 'produkt', 'include' - hvor sidstnævnte typisk er en javascript-fil eller noget styling der ikke hører hjemme under sitets template.
									<br/>
									<br/>
									<ul style="margin:0">
										<li><b>Dobbeltklik</b> - Dobbeltklikkes på en titel under 'Dokumenter' (venstre side) startes <i>Edit</i> af elementet.</li>
										<li><b>Enkeltklik</b> - Et klik vil typisk starte træk-slip hvis knappen holdes inde, men et enkeltklik på et element i højre side, placerer ressourcer i fokus.</li>
										<li><b>Træk-Slip</b> - Når en gruppe trækkes - og slippes ned på / mellem elementer der giver 'grønt lys', udføres hhv. positionering samt tilknytning af 'indeholdende beholder'</li>
										<li><i>Op, Ned, Tilknyt</i> - Handlingerne på værktøjslinien arbejder alene på <i>Edit</i>, dvs op og ned positionerer og tilknyt tilknytter ressource der er valgt som <i>Focus</i> til sideelementet valgt i <i>Edit</i>.</li>
									</ul>
								</div>
							</td></tr>
					</tbody></table>
			</div>
		<?php } else if ($_GET['form'] == "pagegrouping") { ?>

			<div id="pagegroupingWrapper"></div>
			<script type="text/javasript">new OoCmS.pageGroups({attachTo: dojo.byId('pagegroupingWrapper')});</script>

		<?php } ?>

	</div>
