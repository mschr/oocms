<?php

include "../../include/cm.CommonFunctions.php";
include "cm.Theme.php";
$BODY = $DOC = new Document(2 );
//$BODY =new Document(5);
$DOC->keywords = "DOCKEY";
//$BODY->keywords = "BODYKEY";
$theme = new Theme($DOC, $BODY);
$theme->render();
echo implode("\n", $theme->getContentBuffer());
?>

