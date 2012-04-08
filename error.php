<?php
$errno = $_GET['id'];
$returnUrl = $_GET['returnUrl'];
if ($errno == 401) {
	header("Location: login.php?returnUrl=" . $_GET['returlUrl']);
}
$icons = array(
	 '404' => "glossy_3d_blue_web.png",
	 '500' => "glossy_3d_blue_orbs2_098.png",
	 '1000' => "clean3d_blue_015.png",
	 '1001' => "glossy_3d_blue_orbs2_080.png",
	 '1002' => "clean3d_blue_134.png",
	 '1005' => "glossy_3d_blue_shield.png",
	 '1006' => "icontexto-webdev-cancel-128x128.png"
);

$messages = array(
	 '404' => "Forespurgte side findes ikke.. Vi har kravlet frem, tilbage, ned, op og igennem alle tilgængelige elementer men fandt intet lignende din forespørgsel",
	 '500' => "Sitet har udført en ulovlig handling, siden er ikke tilgængelig",
	 '1000' => "Element er ikke tilgængeligt eller dokument-kategori og id stemmer ikke overens",
	 '1001' => "...",
	 '1002' => "...",
	 '1005' => "Login ikke korrekt, kontroller brugernavn og adgangskode",
	 '1006' => "Serveren er under vedligeholdelse eller dens adgang til database/filsystem er ikke tilstrækkelig"
);
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" href="<?= $CONFIG['relurl']; ?>css/oocms.css" />
		<style type="text/css">
			.errorpage div.line {
				background-repeat: no-repeat; 
				background-image: url(ico/<?php echo $icons[$errno]; ?>);
				height: auto;
			}
		</style>
		<title>Ooops!</title>
	</head>
	<body class="errorpage" style="height: 100%">
		<table width="100%" height="100%"><tbody><tr>
					<td height="100%"><div class="error">
							<div class="line">
								<div class="notify">
									<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
										<span class="headline-1">OoCmS Websitemanager</span>
										<span class="headline-2">OoCmS Websitemanager</span>
										<span class="headline-3">OoCmS Websitemanager</span>
									</div>
									Der opstod en fejl!<br/>
									<div class="description"><br/><br/>
										<?php echo $messages[$errno]; ?><br/><br/><br/><br/>
										<p align="right" style="padding-right: 20px;">
											<a class="notifyanchor" href="<?php echo $returnUrl; ?>">Prøv igen</a>
										</p>
									</div>
								</div>
							</div>
						</div></td>
					<?php
					?>
				</tr></tbody></table>
	</body></html>
