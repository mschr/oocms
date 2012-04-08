<?php

include_once "include/cm.CommonFunctions.php";
?>

<?php
documentHead();
if(!empty($_GET['activate'])) {
	include_once "include/mysqlport.php";
	$sql = new SQL("admin");
	$ok = $data = $sql->doQueryGetFirstRow("SELECT * from `".$CONFIG["db_userstable"]."` WHERE `userid`='".$_GET['activate']."'");

	if($ok) {
		if($data['inactive'] == 0) {
				$output = "Brugeren (".$data['username'].") er allerede aktiv";
		} else {
			$sql->doQuery("UPDATE `".$CONFIG["db_userstable"]."` SET `inactive`='0' WHERE `userid`='".$_GET['activate']."'");
			$mail = $CONFIG['webmaster'];
			$subject = "Bruger aktiveret";
			$body = "<h3>Kære ".$data['firstname']." ".$data['surname']."</h3>".
				"Din bruger er godtaget af administratoren for ".$_SERVER['SERVER_NAME'].". ".
				"Du kan nu logge ind og anvende administration-delen for sitet!<br />".
				"Prøv at besøge dette link, bookmark det eventuelt for senere anvendelse:<br />".
				" &nbsp; <a href=\"http://".$_SERVER['SERVER_NAME'].$CONFIG['relurl']."admin/index.php\">OoCmS Content Management system</a><br />".
				"Har du henvendelser i form af spørgsmål eller rettelser til sitet, ".
				"er følgende mail indbox for webmaster for dit site: <a href=\"mailto:".$CONFIG['webmaster']."\">".$CONFIG['webmaster']."</a>";
			$output = "Brugeren (".$data['username'].") er nu aktiv";

			require_once $CONFIG['lib'] . "swiftmailer/swift_required.php";
			$message = Swift_Message::newInstance()
					  ->setSubject($subject)
					  ->setFrom(array("no-reply@" . $_SERVER['SERVER_NAME'] => 'OoCmS'))
					  ->setTo(array($mail))
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
		?>
	<table style="width:100%;height:100%"><tbody><tr>
			<td style="height:100%"><div class="login">
				<div class="line">
					<div class="notify">
						<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
							<span class="headline-1">Registration af bruger</span>
							<span class="headline-2">Registration af bruger</span>
							<span class="headline-3">Registration af bruger</span>
						</div>
						<div class="description">
							<?php echo $output;?> 
<?php
	} else { /*!ok*/ echo "<h2>Der opstod en fejl... ". mysql_error()."<h2>"; }
} else { /*!activate*/
	$outputForm = true;
	if(isset($_POST['content'])) {
		$error = "";
		if(empty($_POST['name'])) $error .= "Mangler at udfylde navn";
		if(empty($_POST['email'])) $error .= ($error != "" ? ", m":"M")."angler at udfylde email";
		if($error == "") {
			$outputForm = false;
			$mail = $CONFIG['webmaster'];
			$subject = "Mail fra ".$_SERVER['SERVER_NAME']." OoCmS admincontact";
			$body = "<h3>Hej admin</h3>".
				"<div style=\"background-color: lightBlue;padding: 50px;width:55%;margin-left: 20px;\">".
				preg_replace("/\n/", "<br />", $_POST['content']).
				"</div>".
				"<br /><br />mvh<br />".
				$_POST['name'].
				"<br /><br /><hr /><span style=\"font-size: x-small;\">Mailen blev indsendt af en bruger af sitet ".$_SERVER['SERVER_NAME'].".";

			require_once $CONFIG['lib'] . "swiftmailer/swift_required.php";
			$message = Swift_Message::newInstance()
					  ->setSubject($subject)
					  ->setFrom(array($_POST['email'] => $_POST['name']))
					  ->setTo(array($mail))
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
			$result = $mailer->send($message);		}
	}
?>
	<table style="width:100%;height:100%"><tbody><tr>
			<td style="height:100%"><div class="login">
				<div class="line">
					<div style="<?php echo ($error != "" ? "display:block" : "display:none");?>" class="errormsg"><?php echo $error;?></div>
					<div class="notify">
						<div class="topheadline" style="position:relative; height:55px; position-left:-15px;">
							<span class="headline-1">Kontakt webmaster</span>
							<span class="headline-2">Kontakt webmaster</span>
							<span class="headline-3">Kontakt webmaster</span>
						</div>
						<div class="description">
<?php 

	if($outputForm) {
		?>
							<form action="" method="post">

							<table cellspacing="1" cellpadding="1">
							<tbody><tr><td> Navn </td><td> <input name="name" value="<?php echo $_POST['name'];?>"/> (Skal indtastes) </td></tr>
							<tr><td> Email Adresse </td><td> <input name="email" value="<?php echo $_POST['email'];?>"/> (Skal indtastes)
							</td></tr>
							</tbody></table>

							<p> Send dette: </p>
							<p> <textarea cols="50" rows="5" name="content"><?php echo $_POST['content'];?></textarea> </p>
							<p>
								<input type="submit" value="Send"/>
								<input type="reset" value="Ryd form"/>
							</p>
							</form>
		<?php 
	} else {
		?>

							<h3>Mailen er sendt</h3>

		<?php
	}
}
documentTail();

function documentHead() {
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
	background-image:url(ico/glossy_3d_blue_orbs2_040.png);
	background-position:10px 60px;
	background-repeat:no-repeat;
	border:2px outset LightBlue;
	height:580px;
	margin:2px 17%;
	padding:0;
	position:relative;
	min-width: 625px;
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
<body>
<?php
}
function documentTail() 
{
?>
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
<?php }?>
