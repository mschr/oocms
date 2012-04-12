<?php
require_once "../include/cm.Menu.Buttons.php";
include "edit.template.php";
$mData = new MenuData();
$entry = new MenuItem("Gem",
	"javascript:jumpStep('save')",
	"Gem og afslut redigering - eller vælg yderligere afsluttende handling");
$mData->addTopLevel($entry);

$entry = new MenuItem("Annullér..",
	"javascript:jumpStep('cancel')",
	"Annullér det redigerede ".($DOC->created != "" ? "(siden ".$DOC->lastmodified.")" : ""));

$mData->addTopLevel($entry);

$Menu = new ButtonMenu($mData);




?>
<script type="text/javascript" >

//	dojo.addOnLoad(function() {
//		dojo.query("img", dojo.byId('majorImg').parentNode).connect("onclick", attachProductImage);;
//	});
//	function noDelimChars(e){
//			if(!e) e = window.event;
//			if(e.keyCode == 220 || e.keyCode == 61) return false;
//	}

</script>
<?php
top_left();
?>
	<table cellspacing="0" class="sample" border="1" style="width:100%">
		<thead class="o2k7SkinBlack">
			<tr>
				<th>Kategori</th>
			</tr>
		</thead>
		<tbody>
			<tr><td>
				<?
					include "subforms/productsCatTree.php";
				?>
			</td></tr>
		</tbody>
	</table>
<?
meta();
?>
<tr class="mceFirst mceLast" valign="top" style="height: 80px;">
	<td class="o2k7Skin" style="">
		<div style="height:70px;min-width:240px" class="mceButtonStyle first">
			<div style="float:left;padding-left: 4px;">
			<label>Oprindelig pris:</label><br />
			<input dojoType="dijit.form.TextBox" type="text" value="<?php echo ($DOC->price?$DOC->price:0); ?>" name="price" style="width: 75px;"/>
			</div>
<!--
			<div style="float:right; padding-right: 4px;">
			<label>Tilbuds pris:</label><br />
			<input dojoType="dijit.form.TextBox" type="text" value="<?php echo ($DOC->discount_price?$DOC->discount_price:0); ?>"  name="discount_price" style="width: 75px;"/>
			</div>
-->
			<div style="clear:both;"></div>
		</div>
	</td><td>
		<div style="height:70px;" class="mceButtonStyle">
			<label>Produktkategori:</label><br />
			<input dojoType="dijit.form.TextBox" readonly="true" title="Vælg i træ nederst til venstre" type="text" value="<?php echo $DOC->category; ?>" name="category" />
		</div>
	</td><td colspan="2" valign="top">
		<div style="height:70px;width:auto;white-space:normal" class="mceButtonStyle last">
			<label title="Opsæt ved click på miniaturer herunder">Billeder:</label> &nbsp;&nbsp;
			<textarea id="images" class="dijitTextAreaReadOnly dijitTextArea" style="width: 95%;height: 51px; font-size:small;overflow:hidden;cursor:not-allowed !important;" readonly="readonly" rows="3" name="images" ><?php echo $DOC->images;?></textarea>
		</div>
		<div style="clear:both;"></div>
	</td>
</tr>
<table class="mceLayout simpleSkin" width="100%" cellpadding="2" cellspacing="0"><tbody class="mceLookFeel"><tr>
			<td align="center">
				<textarea id="DocumentBody" name="description"><?php echo htmlspecialchars($DOC->description); ?></textarea>
			</td>
			<td class="o2k7Skin" style="">
				<div class="mceButtonStyle first last" style="text-align: center;height: auto;width:auto; padding: 30px 0;margin: 15px;"><label>Klik på billede for valg</label><br />
					<?php
						$imgFiles = explode("\n", $DOC->images);

						for($i = 0; $i < 4; $i++)
							if(strlen($imgFiles[$i]) < 2 || !$imgFiles[$i])
								$imgFiles[$i] = "/fileadmin/products/nothumb.jpg";
					?>
					<img id="majorImg" alt="No thumbnail" src="<?php echo $CONFIG['relurl'];?>thumbnail.php?file=<?php echo $imgFiles[0];?>&amp;h=80&amp;w=75"/>
					<img id="detailImg1" alt="No thumbnail" src="<?php echo $CONFIG['relurl'].'thumbnail.php?file='.$imgFiles[1].'&amp;h=60&amp;w=50';?>"/>
					<img id="detailImg2" alt="No thumbnail" src="<?php echo $CONFIG['relurl'];?>thumbnail.php?file=<?php echo $imgFiles[2];?>&amp;h=60&amp;w=50"/>
					<img id="detailImg3" alt="No thumbnail" src="<?php echo $CONFIG['relurl'];?>thumbnail.php?file=<?php echo $imgFiles[3];?>&amp;h=60&amp;w=50"/>
				</div>
			</td>
</tr></tbody></table>
<table class="meta mceLayout Enabled" style="height: 120px;width:100%" cellspacing="0" cellpadding="0" border="0">
	<tbody class="mceLookFeel">
		<tr style="height: 75px;">
			<td valign="top" >
				<div class="mceButtonStyle first last" style="width:auto;text-align: center;height: auto;margin-bottom:12px;margin-top:12px;padding: 50px;">
					<div style="float:left; width: 49%;text-align:left">
						<label>
							Features
							<span id="featinfotip" style="text-decoration: none;">(info)</span>
						</label>
						<textarea dojoType="dijit.form.Textarea" name="features" 
							style="width:97%;vertical-align:top"><?php echo $DOC->features;?></textarea>
					</div>
					<div style="float:right; width: 49%;text-align:left">
						<label>
							Specifikationer
							<span id="specinfotip"  style="text-decoration: none;">(info)</span>
						</label>
						<textarea dojoType="dijit.form.Textarea" name="specifications" 
							style="width:97%;vertical-align:top"><?php echo $DOC->specifications;?></textarea>
					</div>
					<div style="clear:both;"></div>
					<script type="text/javascript">
					// <![CDATA[
					new dijit.Tooltip({
						connectId: ["featinfotip"],
						label: 'Feltet udfyldes efter følgende princip:<br /><br />&nbsp;<i>Rækker</i>: &nbsp;Hver <b>linie</b> tilsvarer en række i visningen.'
					});
					new dijit.Tooltip({
						connectId: ["specinfotip"],
						label: 'Feltet udfyldes efter følgende princip:<br /><br />&nbsp;<i>Rækker</i>: &nbsp;Hver <b>linie</b> tilsvarer en række i visningen.<br /><br />&nbsp;<i>Søjler</i>: &nbsp;Label står til venstre og værdi til højre, søjler<br />&nbsp; &nbsp;adskilles med <b>"="</b><br /><br />&nbsp; Eksempel på en linie:<br/>&nbsp; &nbsp;<font color=#CECECE>Omdrejninger pr minut</font><b style=font-size:1.1em;> = </b><font color=#DFDFCE>2500rpm</font>'
					});
					// ]]>
					</script>

				</div>
			</td>
		</tr>
	</tbody>
</table>
