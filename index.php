<?php
if (isset($_GET['debug']) && isset($_GET['sessionunset']))
	session_unset();
session_start();
header("Content-type: text/html; charset=utf-8");
include "include/cm.CommonFunctions.php";
require_once $CONFIG['lib'] . "oolib/cm.Theme.php";
if (route_is_head_request()) {
	fb("hier");
	route_to_location("index.php?OpenDoc&id=" . route_get_frontpage_id());
}



switch (route_get_request_type()) {
	case "page":
		static $DOC;
		static $BODY;
		require_once $CONFIG['templates'] . "cm.DocumentTemplate.php";
		require_once $CONFIG['templates'] . "cm.ResourceTemplate.php";
		try {
			$BODY = new Document(route_get_body_id());
		} catch (Exception $e) {
			route_to_location("error.php?id=404&returnUrl=" . rawurlencode($_SERVER['REQUEST_URI']));
		}
		$DOC = ($BODY->type == "subpage" ? $BODY->getToplevelDocument() : $BODY);
		$_SESSION['activeCategory'] = $DOC->pageid;
		$theTitle = $BODY->title;
		if (!preg_match("/r[0-9][0-9]/", $BODY->alias))
			$theTitle .= " | {$BODY->alias}";
		if ($DOC !== $BODY)
			$theTitle .= " | {$DOC->title}";
		$theTitle .= " | {$CONFIG['sitename']}";
		$BODY->title = $theTitle;
		break;
	case "product" :
		require_once $CONFIG['templates'] . "cm.ProductTemplate.php";
		static $PROD;
		try {
			$PROD = new Product(route_get_product_id());
		} catch (Exception $e) {
			route_to_location("error.php?id=404&returnUrl=" . rawurlencode($_SERVER['REQUEST_URI']));
		}
		$theTitle = "{$PROD->title}";
		if (!empty($PROD->ft_indexed))
			$theTitle .= " | " . substr($PROD->ft_indexed, 0, 22) . "...";
		$theTitle .= " | {$CONFIG['sitename']} | {$_SERVER['SERVER_NAME']}";
		break;
	default:
//		route_to_location("index.php?OpenDoc&id=" . route_get_frontpage_id());
}


$theme = new Theme($DOC, $BODY);
if (route_is_async_request())
	$theme->renderPartial();
else
	$theme->render();
?>
<!DOCTYPE html>
<html>
	<?= implode("\n", $theme->getContentBuffer()); ?>
</html>