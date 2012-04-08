<?php
if(!isset($CONFIG)) include_once "../include/cm.CommonFunctions.php";
require_once $CONFIG['templates']."cm.DocumentTemplate.php";
require_once $CONFIG['includes']."mysqlport.php";
//$doc = new Document;
//$doc->load(2);
//$subs = $doc->getChildrenDocuments();
//var_dump($doc);
//var_dump($subs);
//exit;
function r_childDocuments($cmDocument) {
	global $CONFIG, $lastModification;
	$subpages = $cmDocument->getChildrenDocuments();
	$result = array();
	while(($child = $subpages->nextDocument()) != null) {

		if($child->isdraft == "1") continue;
		$ts = Convert::timeToTime($child->lastmodified);
		if($ts > $lastModification) 
			$lastModification = $ts;
		$result[] = array(
			"unid"=>$child->pageid, 
			"title"=>$child->title,
			"alias"=>$child->alias, 
			"lastmod"=>date("Y-m-d", $ts),
			"link"=>$CONFIG['opendocprefix']."&amp;cat=".$cmDocument->pageid."&amp;id=".$child->pageid,
			"children"=>r_childDocuments($child)
		);

	}

	return count($result) == 0 ? null : $result;
}
function r_xml($subtree, $lvl = 0) {
	$i = 0;
	$output = "";
	$priority = ($lvl == 0 ? 0.9 : 1.0 - 0.2 * $lvl);

	foreach($subtree as $page) {
		$output .= "<url>\n";
		$output .= "  <loc>http://".$_SERVER['SERVER_NAME'].$page['link']."&amp;rel=".relation($page['title'])."</loc>\n";
		$output .= "<changefreq>weekly</changefreq>\n";
		$output .= "<priority>".($i++ == 0 && $lvl == 0 ? "1.0":$priority)."</priority>\n";
		$output .= "<lastmod>".$page['lastmod']."</lastmod>\n";
		$output .= "</url>\n";
		if($page['children'] != null) $output .= r_xml(&$page['children'], $lvl+1);
	}
	return $output;
}
function pingGoogleSitemaps() {
	
}
$lastModification = 0;
function getSitemap($format = null, $categoryId = null) {
	global $CONFIG, $lastModification;
	if($categoryId != null) {
		$pages = new DocumentCollection("", false);
		$pages->dbSearch("`id`='".intval($categoryId)."'", 1);
	} else {
		$pages = new DocumentCollection("page");
		$pages->realize();
	}
	$structure = array();
	$totalpages = 1;
	while(($cat = $pages->nextDocument()) != null) {
		if($cat->pageid == NULL || $cat->isdraft == "1") continue;
		$ts = Convert::timeToTime($cat->lastmodified);
		if($ts > $lastModification) 
			$lastModification = $ts;

		$structure[] = $pageInfo = array(
			"unid"=>$cat->pageid, 
			"title"=>$cat->title, 
			"alias"=>$cat->alias, 
			"lastmod"=>date("Y-m-d", $ts),
			"link"=>$CONFIG['opendocprefix']."&amp;id=".$cat->pageid,
			"children"=> r_childDocuments($cat)
		);
		$totalpages+=1+count($pageInfo['children']);
	}

	$output = "";
	$indentation = 0;
	$sitename = isset($CONFIG['sitename'])?$CONFIG['sitename']:"Home";
	switch(strtolower($format)) {
		case "html" :
			$output = '<div class="outputWrap" align="center">' .
				'<h1>Site Map</h1>'.
				'<h2><a href="http://'.$_SERVER['SERVER_NAME'].$CONFIG['relurl'].'index.php">'.
					$sitename.'</a></h2>'.
					'<p class="lastupdate">Sitets sidste opdatering : '.date("r",$lastModification).'</p>'.
					'<p class="totalpages">Totale antal sider : ' . $totalpages."</p>";

			$output .= '<table cellpadding="2" cellspacing="2" width="80%" class="outputTable">'."\n";

			foreach($structure as $page) {
				$output .= '<thead class="outputHead outputIndentation_'.$indentation.'"><tr>'."\n";
				$output .= '<th class="title"><a href="'.$page['link'].'">'.$page['title'].'</a></th>'."\n";
				$output .= '<th class="desc">'.$page['alias'].'</th>'."\n";
				$output .= '</tr></thead>'."\n";
				if(count($page['children']) > 0) {
					$output .= '<tbody class="outputBody">'."\n";
					++$indentation;
					foreach($page['children'] as $childA) {
						$output .= '<tr><td class="title outputIndentation_'.$indentation.'"><a href="'.$childA['link'].'">'.$childA['title'].'</a></td>'."\n";
						$output .= '<td class="desc outputIndentation_'.$indentation.'"">'.$childA['alias'].'</td></tr>'."\n";
						if(count($childA['children']) > 0) {
							$output .= '</tbody><tbody class="outputHead">'."\n";
							++$indentation;
							foreach($childA['children'] as $childB) {
								$output .= '<tr><td class="title outputIndentation_'.$indentation.'"><a href="'.$childB['link'].'">'.$childB['title'].'</a></td>'."\n";
								$output .= '<td class="desc outputIndentation_'.$indentation.'">'.$childB['alias'].'</td></tr>'."\n";
							}
							$output .= '</tbody>'."\n";
							$indentation--;
						}
					}
					$output .= '</tbody>'."\n";
					$indentation--;
				}
			}
			$output .= '</table>'."\n";
			break;
		case 'json' :
			$output = preg_replace("@({|}[^,]|,[^{])@", "$1\n", json_encode($structure));
			
			break;
		default :
			$output = @r_xml(&$structure);
			
	}
	return $output;
}

function relation($title) {
	return strtolower(implode("-", explode(" ", urlencode($title))));
}

?>
