<?php
if (!isset($CONFIG))
	include dirname(__FILE__) . "/../../include/cm.CommonFunctions.php";
if (!isset($user)) {
	include $CONFIG['includes'] . "cm.RequireLogin.php";
}
$iscategoryform = empty($_GET['form']) || $_GET['form'] != "product";
?>
<style type="text/css">
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/Save.css";
	@import "http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojox/editor/plugins/resources/css/Preview.css";
	.dijitEditor .dijitInputInner{
		line-height: 20px;	
	}
	/*	.nodeInfo {
			height: 206px;
			font-size: 100%;
		}
		.nodeInfo .infoNode {
			height: 100%;
		}
		.nodeInfo span {
			font-size: 100% !important;
			font-style: normal !important;
		}
	*/
	.dijitToolbar .label  {
		position:relative;
		top: 3px;
		padding: 0 4px;
	}
	#productselector {
		overflow-y: auto;overflow-x: hidden;
	}
	#productselector .dijitTree {
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
<div id="productformwrapper" style="height: 100%;">
	<div class="paneHeader">Produkter &gt; <?= (!$iscategoryform ? "Opsætning" : "Gruppering"); ?></div>

	<div id="leftcolumn" class="firstColumn">
		<div id="productselectortbar"></div>
		<div id="productselector" style="">selector</div>
	</div>
	<div id="centercolumn">
		<div id="producttoolbarWrapper"></div>
		<?php if (!$iscategoryform) { ?>
			<div id="productformWrapper">
				<form id="productform" onSubmit="return false;" action="save.php?EditDoc" method="POST">
					<table style="width:99.6%"><tbody class="mceLookFeel">
							<tr class="mceFirst mceLast">
								<td style="width:25%">
									<div style="height:70px;min-width:240px" class="mceButtonStyle first">
										<div style="float:left;padding-left: 4px;">
											<label>Oprindelig pris:</label><br />
											<input dojoType="dijit.form.TextBox" type="text" value="" name="price" style="width: 75px;"/>
										</div>
										<!--
													<div style="float:right; padding-right: 4px;">
													<label>Tilbuds pris:</label><br />
													<input dojoType="dijit.form.TextBox" type="text" value=""  name="discount_price" style="width: 75px;"/>
													</div>
										-->
										<div style="clear:both;"></div>
								</td>
								<td style="width:25%">
									<div style="height:70px;" class="mceButtonStyle">
										<label>Produktkategori:</label><br />
										<input dojoType="dijit.form.TextBox" readonly="true" title="Vælg i træ nederst til venstre" type="text" value="<?php echo $DOC->category; ?>" name="category" />
									</div>
								</td>
								<td style="width:50%" valign="top">
									<div style="height:70px;width:auto;white-space:normal" class="mceButtonStyle last">
										<label title="Opsæt ved click på miniaturer herunder">Billeder:</label> &nbsp;&nbsp;
										<textarea id="images" class="dijitTextAreaReadOnly dijitTextArea" style="width: 95%;height: 51px; font-size:small;overflow:hidden;cursor:not-allowed !important;" readonly="readonly" rows="3" name="images" ></textarea>
										<div class="clear"></div>
									</div>

								</td>
							</tr>
						
							<tr>
								<td colspan="3" >
									<div class="producteeditorWrapper" data-dojo-type="dijit.Editor"
										  data-dojo-props="
										  plugins:[
										  '|',
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
										  {name: 'formatBlock', plainText: true},'|',
										  ]"
										  id="productformEditor">
									</div>
									<input type="hidden" name="form" value="page"/>
									<input type="hidden" name="body" />
								</td>
							</tr>

						</tbody></table>
				</form>
			</div>

		<?php } else { ?>

		<?php } ?>




	</div>
</div>