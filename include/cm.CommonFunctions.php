<?php

// Included in every Open|EditDoc page as top include, preset session parameters
session_start();
ob_start();
ob_implicit_flush(0);
$deepdebug = true;
include "config.inc.php";
if (isset($_SERVER['HTTP_REFERER']) && preg_match("/(google|yahoo|jubii|eniro|bing)/", $_SERVER['HTTP_REFERER'])) {
	include_once "cm.SETracking.php";
	logSearchEngine();
}
require_once $CONFIG['lib']."oolib/cm.Route.php";
require_once $CONFIG['includes'] . "mysqlport.php";


try {
	require_once('FirePHPCore/fb.php');
	$options = array('maxObjectDepth' => 5,
		 'maxArrayDepth' => 5,
		 'maxDepth' => 10,
		 'useNativeJsonEncode' => true,
		 'includeLineNumbers' => true);
	FB::setOptions($options);
	if ($deepdebug) {
		// include transitional api for logging exceptions
		require_once('FirePHPCore/FirePHP.class.php');
		error_reporting(E_ALL);
		$firephp = FirePHP::getInstance(true);
		$firephp->registerErrorHandler(false); // dont pass to browser, use firephp
		$firephp->registerExceptionHandler();
		$firephp->registerAssertionHandler(
				  true, //$convertAssertionErrorsToExceptions - registering notice/warn
				  false //$throwAssertionExceptions - pass to browser output
		);
		unset($firephp);
	} else {
		error_reporting(E_ALL | E_NOTICE);
	}
} catch (Exception $NotInstalledException) {

	// create a fake logger for FirePHP
	function fb($void1=null, $void2=null, $void3=null, $void4=null) {
		
	}

}

if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']) {
	$_SESSION['stamp_load_in'] = microtime(true);
	$_SESSION['db_connections'] = 0;
	$_SESSION['db_sets'] = 0;
	$_SESSION['db_rows'] = 0;
}


$_SESSION['staticMenu'] = true;
if ($_SERVER['PHP_SELF'] == $CONFIG['relurl'] . "index.php") {
	$_SESSION['activeCategory'] = (isset($_GET['cat']) && $_GET['cat'] != "") ?
			  $_GET['cat'] : $_GET['id'];
}
//else if($_SERVER['PHP_SELF'] == $CONFIG['relurl']."openView/Documents.php" && $_GET['format'] == "contents") {
//	$_SESSION['activeSubPage'] = $_GET['id'];
//}

if (isset($_GET['logout']))
	$_SESSION['isLoggedIn'] = false;
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] == true) {
	if (isset($_GET['EnableEditMode']))
		$_SESSION['EnableEditMode'] = true;
	if (isset($_GET['DisableEditMode']))
		$_SESSION['EnableEditMode'] = false;
}

// Called on every OpenDoc page as footer is printed, setup session post-parameters
function breadcrumbs() {
	require_once $CONFIG['includes'] . "cm.TraceActivity.php";
	global $DOC, $BODY, $CONFIG;

	$track = unserialize($_SESSION['tO']);
	if ($track === false) {
		$track = new TraceActivity();
	}

	if ($_SERVER['PHP_SELF'] == $CONFIG['relurl'] . "openView/History.php")
	// serve history.reload (simple request, no documents loaded)
		return drawBreadCrumb($track->getManager());


	if ($track->setCurView($DOC->type) || $track->setCurCategory($DOC->pageid))
		$track->setCurPage($BODY->pageid, $BODY->title);


	$ret = drawBreadCrumb($track->getManager());
	$_SESSION['tO'] = serialize($track);
	return $ret;
}

function getRedirectPageId($uri) {

	echo $uri;
	exit;
}

function drawBreadCrumb($hist) {
	global $CONFIG;
	$cur = $hist->getPosition();
	$item = $hist->first();
	$html = '<table class="crumbScroll"><tbody><tr>' . "\n";
	while ($item != null) {
		$html .= '<td class="crumb' . ($cur == $i ? " selected" : "") . '">' . "\n" .
				  '  <a title="' . $item['view'] . '" href="' . $CONFIG['opendocprefix'] . '&amp;id=' . $item['id'] .
				  '&amp;cat=' . $item['cat'] . '">' . $item['title'] . '</a>' . "\n" .
				  '</td>' . "\n";
		$item = $hist->next();
	}
	$html .= "</tr></tbody></table>\n";
	return $html;
}

function initializeContentPane($body = null) {
	
}


function getPage($doc) {
	return getCategory($doc);
}

function getCategory($doc) {
	global $CONFIG;
	return ""; // deprecated over cm.Theme
	if (!class_exists("ResourceCollection"))
		require_once $CONFIG['templates'] . "cm.ResourceTemplate.php";
	if ($doc->type == "page") {
		$ResCol = new ResourceCollection($doc->pageid);
		$ResCol->loadAll();
		list($resources, $head) = $ResCol->generateHTML();
	} else {
		$head = "";
	}
	$smarty = array("LOADSUB", "LOADPROD", "LOADCAT", "VBOXMENU", "HBOXMENU");
	$body = $doc->getHtml();
	$b = 0;
	if ((!isset($_GET['cat']) || $_GET['cat'] == $_GET['id']) && preg_match("/(<" . implode(">|<", $smarty) . ">)/", $body)) {
		$nbody = "";
		foreach ($smarty as $utag) {
			while ($b = strpos($body, "<" . $utag, $b)) {
				$e = strpos($body, "</" . $utag . ">", $b);
				if ($e < 0) {
					echo "<p>Warning smart-tag misconfigured</p>";
				}
				$eval = substr($body, $b, $e - $b + strlen($utag) + 3);
				$eval = preg_replace("/<[^>]*>([^<]*)<.*/", "$1", $eval);
				switch ($utag) {
					case "LOADSUB":
						global $BODY;
						if (strtolower($eval) == "closest") {
							$sql = new SQL("reader");
							$sql->doQuery("SELECT id from " . $CONFIG['db_pagestable'] .
									  " WHERE `attach_id`='" . $doc->pageid . "'".
									  " ORDER BY `tocpos` LIMIT 1");
							$r = $sql->getNextRow("object");
							$BODY = new Document($r->id);
						} else {
							$BODY = new Document($eval);
						}
// FIXME fortsæt loop, returner i bunden
						return array($head, $BODY->getHtml(), $resources);
						break;
					case "LOADPROD":
						if (!class_exists("Product"))
							require_once $CONFIG['templates'] . "cm.ProductTemplate.php";
						$doc = new Product(intval($eval));
						$prebuffer = ob_get_clean();
						ob_start();
						product_contents($doc);
						$nbody .= ob_get_clean();
						ob_start();
						ob_implicit_flush(0);
						echo $prebuffer;
// FIXME fortsæt loop, returner i bunden
						//				return array($head, $BODY->getHtml(), $resources);
						break;
					case "LOADCAT":
						if (!class_exists("Product"))
							require_once $CONFIG['templates'] . "cm.ProductTemplate.php";
						$prebuffer = ob_get_clean();
						ob_start();
						product_category_contents($eval);
						$nbody .= ob_get_clean();
						ob_start();
						ob_implicit_flush(0);
						echo $prebuffer;
						break;
					case "VBOXMENU":
						break;
					case "HBOXMENU":
						break;
				}
				$body = substr($body, 0, $b) . substr($body, $e + strlen($utag) + 3);
			}
		}
	}
	return array($head, ($nbody != "" ? $nbody : $body), $resources);
}

function timeToTime($sqlstamp) {
	list($date, $time) = explode(" ", $sqlstamp);
	list($year, $month, $day) = explode("-", $date);
	if ($time != null) {
		list($hour, $minute, $second) = explode(":", $time);
		$ts = mktime($hour, $minute, $second, $month, $day, $year);
	} else {
		$ts = mktime(0, 0, 0, $month, $day, $year);
	}
	return $ts;
}

function pushContents($type="text/html") {
	global $DOC;
	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
		$encoding = 'x-gzip';
	} elseif (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
		$encoding = 'gzip';
	} else {
		$encoding = false;
	}

	if ($encoding) {
		$contents = ob_get_contents();
		ob_end_clean();
		$contents = gzencode($contents, 9);

		header('Content-Encoding: ' . $encoding);
		if (!empty($DOC->lastmodified))
			header("Last-Modified: " . date("r", timeToTime($DOC->lastmodified)));
		header("Expires: " . date("r", time() - 5));
		//header("Content-Type: $type");
		header("Vary: Accept-Encoding");
		header("Accept-Ranges: bytes");
		header("Content-Length: " . strlen($contents));
		//header("Keep-alive: timeout=15, max=100");
		//header("Connection: Keep-alive");
		print($contents);
	}
	else {
		ob_end_flush();
	}
}

?>
