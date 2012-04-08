<?php
if (!isset($CONFIG))
	include dirname(__FILE__) . "/../../include/cm.CommonFunctions.php";
if (!isset($user))
	include $CONFIG['includes'] . "cm.RequireLogin.php";
?>
<style type="text/css">
	@import "<?= $CONFIG['relurl']; ?>/css/fileadmin.css";
	@import "/dojo-release-1.7.2-src/dojox/widget/FilePicker/FilePicker.css";
	@import "/dojo-release-1.7.2-src/dojox/form/resources/UploaderFileList.css";
</style>
<div class="paneHeader">
	Fil-håndtering
</div>

<div id="leftcolumn" class="firstColumn">
	<div class="assetstreewrapper">
		<div id="assetstree"></div>
	</div>
</div>
<div id="centercolumn" class="lastColumn">
	<div id="assetsuploaderwrapper">

	</div>

	<div style="padding: 5px;" id="formWrapper">

		<div id="assetsuploaderfilelist"></div>
		<div style="position:absolute; right: 10px; top: 5px;">
			<div id="assetsDnDopener" class="assetsDnD-title">Træk og slip her <span class="dijitReset dijitInline dijitArrowButtonInner"></span></div>
		</div>
		<!--			  data-dojo-type="dojox.form.uploader.FileList" 
					  data-dojo-props="uploaderId:'assetsuploader',headerIndex:'',headerFilename:'Filnavn', headerFilesize: 'Filstørrelse'"></div>-->
	</div>

</div>