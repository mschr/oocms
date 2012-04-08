<?php
session_start();
if (!class_exists("USER"))
	include "include/userProtocol.php";
$user = new USER();
$error = array();
$complete = false;
if (isset($_POST['request'])) {
	if (empty($_POST['user'])) {
		$error[] = "user";
	}
	if (empty($_POST['pass'])) {
		$error[] = "pass";
	}
	if (empty($_POST['captcha'])) {
		$error[] = "captcha";
	}
	if (empty($_POST['firstname'])) {
		$error[] = "firstname";
	}
	if (empty($_POST['mail'])) {
		$error[] = "mail";
	}
	if (empty($_POST['captcha']) || $_POST['captcha'] != $_SESSION['security_code']) {
		$error[] = "captcha";
	}
	if (isset($_POST['user']) && isset($_POST['pass'])) {
		$complete = $user->register($_POST['user'], $_POST['firstname'], $_POST['surname'], $_POST['mail'], $_POST['pass']);
		if ($complete) {
			if (!isset($CONFIG))
				include_once "include/config.inc.php";
			require_once $CONFIG['lib'] . "swiftmailer/swift_required.php";
			$mail = $CONFIG['webmaster'];

			$subject = "Ny bruger registreret på " . $_SERVER['SERVER_NAME'] . " i OoCmS systemet";
			$body = "<h3>Hej Webmaster</h3>Der er registreret en ny bruger og afventer aktivering. Vi har på forhånd verificeret, at brugeren ikke har forsøgt at registrere sig før (brugernavne og emails er unikke). <br />For at aktivere brugeren er det blot at klikke på dette link, bruger får derefter via mail en notificering omkring login derefter kan anvendes.";
			$body .= '<a href="http://' . $_SERVER['SERVER_NAME'] . $CONFIG['relurl'] . "admincontact.php?activate=" . md5($_POST['user']) . '">Aktiver bruger</a><br/>Oplysninger brugeren indsendte:<br />';
			$body .= FormattedExport($_POST);

			$message = Swift_Message::newInstance()
					  ->setSubject($subject)
					  ->setFrom(array("no-reply@" . $_SERVER['SERVER_NAME'] => 'OoCmS'))
					  ->setTo(array($mail => $CONFIG['siteowner']))
					  ->setBody("<html><head><title>$subject</title></head><body>$body</body>", 'text/html', 'utf-8');
			$message->getHeaders()->addTextHeader("MIME-Version", '1.0');
			$message->getHeaders()->addTextHeader("X-Mailer", "PHP/" . phpversion());
			$transport = Swift_SmtpTransport::newInstance('mail.msigsgaard.dk')
					  ->setPort(587)
//					  ->setPort(443)
//					  ->setEncryption('ssl')
					  ->setUsername('morten@msigsgaard.dk')
					  ->setPassword('r4mail&sv.');
			$mailer = Swift_Mailer::newInstance($transport);
			$result = $mailer->send($message);
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<style type="text/css">
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
			.errormsg {
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
				background-image:url(ico/glossy_3d_blue_shield.png);
				background-position:10px 60px;
				background-repeat:no-repeat;
				border:2px outset LightBlue;
				height:730px;
				margin:2px 17%;
				padding:0;
				position:relative;
				min-width: 640px;
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
			td.err {
				color: red;
			}
		</style>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<title>Register @ <?php echo $_SERVER['SERVER_NAME']; ?></title>
	</head>
	<body style="height: 100%">
		<table style="width:100%;height:100%"><tbody><tr>
					<td style="height:100%"><div class="login">
							<div class="line">
								<div style="<?php echo ($user->errormsg != "" ? "display:block" : "display:none"); ?>" class="errormsg"><?php echo $user->errormsg; ?></div>
								<div class="notify">
									<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
										<span class="headline-1">Registration af bruger</span>
										<span class="headline-2">Registration af bruger</span>
										<span class="headline-3">Registration af bruger</span>
									</div>
									<div class="description">
										<?php if ($complete == false) { ?>
											<form method="post" action="">
												<table class="logintable"><tbody>
														<?php
														if (in_array("firstname", $error)) {
															echo "<tr><td colspan=\"2\" class=\"err\">" .
															(in_array("firstname", $error) ? "Der skal angives mindst et fornavn" : "") .
															"</td></tr>";
														}
														?><tr><td>Fornavn(e):</td><td>Efternavn:</td></tr>
														<tr>
															<td><input type="text" name="firstname" value="<?php echo $_REQUEST['firstname']; ?>"/></td>
															<td><input type="text" name="surname" value="<?php echo $_REQUEST['surname']; ?>"/></td>
														</tr><?php
													if (in_array("user", $error) || in_array("pass", $error)) {
														echo "<tr><td class=\"err\">" . (in_array("user", $error) ? "Indtastning krævet" : "") . "</td>" .
														"<td class=\"err\">" . (in_array("pass", $error) ? "Indtastning krævet" : "") . "</td></tr>";
													}
														?><tr><td>Ønsket brugernavn:</td><td>Password (min. 6 karakterer):</td></tr>
														<tr>
															<td><input type="text" name="user" value="<?php echo $_REQUEST['user']; ?>" /></td>
															<td><input type="password" name="pass" /></td>
														</tr><?php
													if (in_array("mail", $error)) {
														echo "<tr><td colspan=\"2\" class=\"err\">Invalid email-adresse</td></tr>";
													}
														?><tr><td colspan="2">Email adresse:</td></tr>
														<tr>
															<td colspan="2"><input type="text" size="35" name="mail" value="<?php echo $_REQUEST['mail']; ?>" /></td>
														</tr>
														<tr><td colspan="2">&thinsp;</td></tr>

														<tr>
															<td style="border: 2px solid black; background-image:url(gfx/topDown-gradient.png); text-align:center">
																<img style="border: 3px groove blue" src="captcha.php" alt="#####"/>
															</td>
															<td>
																<table><tbody><?php
													if (in_array("captcha", $error)) {
														echo "<tr><td class=\"err\">Fejl i indtastning</td></tr>";
													}
														?><tr>
																			<td>Valider billedtekst:</td>
																		</tr>
																		<tr><td><input type="text" value="" name="captcha"/></td>
																		</tr><tr>
																			<td style="text-align:center"><input type="submit" name="request" value="Indsend registration"/></td>
																		</tr></tbody></table>
															</td></tr>
													</tbody></table>
											</form>
											<div style="position:relative; left: -110px;width:500px">
												<div class="bottomheadline" style="position:relative; height:30px;margin-top:15px;">
													<span class="headline-1">Hvorfor registrere</span>
													<span class="headline-2">Hvorfor registrere</span>
													<span class="headline-3">Hvorfor registrere</span>
												</div>
												For at kunne deltage i opsætning af sitets indhold er dette trin nødvendigt. Systemet giver for hver ændring en opdatering i den pågældende sides metadata. Disse data anvendes først og fremmest som notifikation til dig og dine kolleger, men også i det enkelte dokument i html-sidens metadata. Dette giver muligheder for anvendelse som f.eks. copyrighting, krydsreferencer mellem sites.
											</div>
										<?php } else { // if($complete == false) ?>
											Tak for din registration. Dine data er modtaget og vil blive behandlet, så dit login vil blive validt.<br /><br />
											Webmaster for dit site har modtaget en mail omkring din registration og når bruger er aktiveret, vil du få adgang til sitets backend.<br /><br />
											Noter dig dette password, da det er dig alene, der kender indholdet af det. Det vil fremover ikke kunne trækkes ud som clear-text.<br />
											:<br/>
											<fieldset style="color: rgb(40,40,40);"><legend>Login oplysninger</legend>
												Brugernavn: <?php echo $_POST['user']; ?><br />
												Password: <?php echo $_POST['pass']; ?><br />
												Mail-adresse: <?php echo $_POST['mail']; ?><br />
											</fieldset>
										<?php } // if($complete == false) ?>
									</div>
								</div>
								<div class="tail">
									<a class="notifyanchor" href="admincontact.php">Kontakt webmaster</a>
								</div>
								<div class="footer">
									Copyrights mSigsgaard web-udvikling 2009-2014 - All rights served
								</div>
							</div>
						</div></td>
				</tr></tbody></table>
	</body></html>

