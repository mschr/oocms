<?php
session_start();
if (!class_exists("USER"))
	include "include/userProtocol.php";
global $user;
if (!isset($user)) {
	$user = new USER();
}
if (isset($_POST['user']) && isset($_POST['pass'])) {


	if ($user->login($_POST['user'], $_POST['pass'])) {
		$redir = $_REQUEST['returnUrl'];
		if ($redir == "DONTREDIR") {
			echo "SUCCESS";
			exit;
		}
		if ($redir == "")
			$redir = "index.php";
		header("Location: $redir");
		exit;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<!-- iamloginpage -->
	<head>
		<?php
		if (isset($_GET['requestPassword']) && !empty($_POST['user'])) {
			$user->errormsg = "Der er sendt en email til din konto med oplysninger";
			if (!isset($CONFIG))
				include_once "include/config.inc.php";
			$data = USER::get($_POST['user']);
			if ($data != NULL) {
				var_dump($data);
				$mail = $data['email'];
				$_SESSION['resetseed'] = md5("" . date("U"));
				$subject = "Nulstilling af password";
				$body = "<h3>Kære " . $data['firstname'] . " " . $data['surname'] . "</h3>" .
						  "<p>Du har anmodet om at få nulstillet dit password, for at komme videre skal du nu følge " .
						  "nedenstående link - som vil generere et midlertidigt password for dig.<br />" .
						  "Umiddelbart efter dette er gjort, får du mulighed for at ændre passwordet efter ønske.<br />" .
						  "Prøv at besøge dette link, bookmark det eventuelt for senere anvendelse:<br />" .
						  "</p><p>&nbsp; Forsæt her : <a href=\"http://" . $_SERVER['SERVER_NAME'] . $CONFIG['relurl'] .
						  "login.php?resetPassword=" . $data['userid'] . "&amp;seed=" . $_SESSION['resetseed'] . "\">Nulstil</a></p>" .
						  "<p>NB: Dette link er kun validt for din browser, har du i mellemtiden lukket browseren ned, " .
						  "skal linket genereres påny!</p>" .
						  "<p>Har du henvendelser i form af spørgsmål eller rettelser til sitet, " .
						  "er følgende mail indbox for webmaster for dit site: " .
						  "<a href=\"mailto:" . $CONFIG['webmaster'] . "\">" . $CONFIG['webmaster'] . "</a></p>";
				require_once $CONFIG['lib'] . "swiftmailer/swift_required.php";
				$message = Swift_Message::newInstance()
						  ->setSubject($subject)
						  ->setFrom(array("morten@msigsgaard.dk" => 'OoCmS'))
//						  ->setFrom(array("no-reply@" . $_SERVER['SERVER_NAME'] => 'OoCmS'))
						  ->setTo(array($mail => $data['firstname'] . " " . $data['surname']))
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
				echo "$mail =>" . $data['firstname'] . " " . $data['surname'];
			} else {
				$user->errormsg = "Brugernavn kunne ikke genkendes";
			}
		} else if (isset($_GET['resetPassword'])) {
			if (!isset($CONFIG))
				include_once "include/config.inc.php";
			if (!class_exists("SQL"))
				include_once "include/mysqlport.php";

			if ($_SESSION['resetseed'] != $_GET['seed']) {
				$user->errormsg = "Du har bedt om nulstilling af password, men forespørgslen udløb.";
				unset($_GET['resetPassword']);
			} else if (!empty($_POST['pass'])) {

				$sql = new SQL("admin");
				$data = $sql->doQueryGetFirstRow("SELECT username FROM `users` WHERE `userid`='" . $_GET['resetPassword'] . "'");
				if ($sql->getCount() == 0) {
					die("Invalid forespørgsel");
				}
				$data = USER::get($data['username']);
				$ok = $sql->doQuery("UPDATE `users` SET `password`='" . md5($_POST['pass']) . "' WHERE `userid`='" . $data['userid'] . "'");

				$user->errormsg = "Dit password blev sat til det ønskede";
				unset($_GET['resetPassword']);
				echo "<meta http-equiv=\"Refresh\" content=\"4;URL=login.php\"/>";
			} else {

				$sql = new SQL("admin");
				$data = $sql->doQueryGetFirstRow("SELECT username FROM `users` WHERE `userid`='" . $_GET['resetPassword'] . "'");
				if ($sql->getCount() == 0) {
					die("Invalid forespørgsel");
				}
				$data = USER::get($data['username']);
				$possible = '23456789bcd#&._01fgAHGDBALEIhjklfdeabkmn!pqrstvwxyz';
				$code = '';
				$i = 0;
				while ($i < 8) {
					$code .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
					$i++;
				}
				$ok = $sql->doQuery("UPDATE `users` SET `password`='" . md5($code) . "' WHERE `userid`='" . $data['userid'] . "'");
			}
		}
		?>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<title>Login @ <?php echo $_SERVER['SERVER_NAME']; ?></title>
		<link rel="stylesheet" href="<?=$CONFIG['relurl'];?>css/oocms.css"/>
	</head>
	<body class="claro loginpage" style="height: 100%">
		<span style="display:hidden" id="iamloginpage"></span>
		<table class="loginpage"><tbody><tr>
					<td style="height:100%"><div class="login">
							<div class="line">
								<div style="<?php echo (isset($user) && !empty($user->errormsg) ? "display:block" : "display:none"); ?>" class="errormsg"><?php echo (isset($user) && !empty($user->errormsg) ? $user->errormsg : ""); ?></div>
								<div class="notify">

									<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
										<span class="headline-1">OoCmS Websitemanager</span>
										<span class="headline-2">OoCmS Websitemanager</span>
										<span class="headline-3">OoCmS Websitemanager</span>
									</div>
									<form method="post" action="<?= $CONFIG['relurl'] . "login.php" ?>">
										<div class="description">
											<input type="hidden" id="_returnUrl" name="returnUrl" value="<?php echo $_REQUEST['returnUrl']; ?>" />
											<table class="logintable"><tbody>
													<?php
													if (isset($_GET['requestPassword'])) {
														?>
														<tr><td style="font-size:normal;font-weight:500" colspan="2">Indtast dit brugernavn her, vi sender herefter en mail med yderligere oplysninger om hvordan du nulstiller password'et for din bruger.</td></tr>
														<tr><td>Brugernavn:</td></tr>
														<tr><td>
																<input data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" 
																		 type="text" name="user" value="<?php echo $_POST['user']; ?>"/>
															</td>
															<td><input type="submit" name="submit" value="Anmod om nulstilling"/></td></tr>

														<tr></tr>
														<?php
													} else if (isset($_GET['resetPassword'])) {
														?>
														<tr><td style="font-size:normal;font-weight:500" colspan="2">Dit password er nu nulstillet og sat til: <b><?php echo $code; ?></b><br />Du har nu mulighed for at sætte et nyt ved at indtaste i nedenstående felt og indsende password'et.</td></tr>
														<tr>
															<td>
																<input data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" 
																		 type="text" name="pass" value="<?php echo $code; ?>"/>
															</td>
															<td><input type="submit" name="submit" value="Sæt nyt password"/></td>
														</tr>

														<tr></tr>


													<?php } else { ?>
														<tr><td>Brugernavn:</td></tr>
														<tr><td>
																<input data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" 
																		 type="text" name="user" value="<?= !empty($_POST['user']) ? $_POST['user'] : ""; ?>"/>
															</td>

														</tr><tr><td>Password:</td></tr>
														<tr><td>
																<input data-dojo-type="dijit.form.ValidationTextBox" data-dojo-props="required:true,missingMessage:'Feltet skal udfyldes'" 
																		 type="password" name="pass"/>
															</td></tr>
														<tr><td><button data-dojo-type="dijit.form.Button" 
																	type="submit" name="submit" value="Log ind">Log ind</button>
															</td></tr>
													<?php } ?>
												</tbody></table>

											<div class="bottomheadline" style="position:relative; height:30px;margin-top:15px;">
												<span class="headline-1">Hvad kan vi bruge det til</span>
												<span class="headline-2">Hvad kan vi bruge det til</span>
												<span class="headline-3">Hvad kan vi bruge det til</span>
											</div>
											<div class="bottomtext">
												OoCmS systemet bringer dig muligheden for at uploade indhold til din hjemmeside. På en overskuelig måde guides administratoren hele vejen igennem af kontekstuel, dansk og letforståeligt hjælp. Der kan ved brug af html-editoren styles som det gøres i de velkendte tekstbehandlingsprogrammer. Sidernes elementer krydsrefereres på en intuitiv måde så opfølgende ændringer simplificeres.
											</div>
										</div>
									</form>
								</div>
								<div class="tail">
									<a class="notifyanchor" href="?requestPassword">Nulstilling af password</a> |
									<a class="notifyanchor" href="register.php">Registrér</a>
								</div>
								<div class="footer">
									Copyrights mSigsgaard web-udvikling 2009-2014 - All rights served
								</div>
							</div>
						</div></td>
				</tr></tbody></table>
	</body></html>

