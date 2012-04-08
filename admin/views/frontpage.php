<?php
if (!isset($CONFIG))
	include dirname(__FILE__)."/../../include/cm.CommonFunctions.php";

if (!isset($user))
	include $CONFIG['includes']."cm.RequireLogin.php";

?>
<h2>OoCmS Administration</h2>
