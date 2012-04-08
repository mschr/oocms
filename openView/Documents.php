<?php
header("Pragma: public");
header("Expires: 0"); // set expiration time
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

$q_string = array();
foreach($_GET as $ident => $value) {
	$q_string[strtolower($ident)] = strtolower($value);
}
$maxdoc = (!isset($q_string['count']) ? null : $q_string['count']);
$offset = (!isset($q_string['start']) ? null : $q_string['start']);
$since =  (!isset($q_string['datetime']) ? null : $q_string['datetime']);
$since_column =  (!isset($q_string['datetime_col']) ? null : $q_string['datetime_col']);
$title =  (!isset($q_string['searchtitle']) ? null : $q_string['searchtitle']);
$id =  (!isset($q_string['searchid']) ? null : $q_string['searchid']);
$type =   (!isset($q_string['type']) ? "" : $q_string['type']);

$unidMap = "";

function getChildrenOfCategory($catid, $isdraft) {
	global $unidMap;
	$col = new DocumentCollection("page", false);
	$num_rows = $col->dbSearch("type='subpage' AND attach_id='$catid' AND isdraft='$isdraft'", null, 0, "tocpos");
//	if($isdraft) { // check if allready printed..
		$doc = $col->getFirstDocument();
		while(($doc=$col->nextDocument()) != null) 
			if(preg_match("/,".$doc->pageid.",/", $unidMap.","))
				$col->removeDocument($doc);
//	}
	$col->realize();
	return $col->documents;
}
function getChildrenOfPage($pageid, $subpages) {
	$titles = array();
	$ids = array();
	foreach($subpages as $sub) {
		if(preg_match("/[^0-9]+".$pageid."[^0-9]+/", ",".$sub->attach_id.",")) {
			array_push($titles, $sub->title);
			array_push($ids, $sub->pageid);
		}
	}
	return array($titles, $ids);
}
function r_generateJSON(Document $page) {
	global $unidMap;
	$i = 0;
	$subs = $page->getChildrenDocuments();
//	$subs_o = getChildrenOfCategory($page->pageid, $page->isdraft);

	if(preg_match("/,".$page->pageid.",/", $unidMap.",")) {
		return false;
	} else if($subs->getSize() > 0) {
		// retreive parent json
		$pageJSON = $page->getDocumentObject("JSON");
		$unidMap .= ",".$page->pageid;
		$pageJSON = substr($pageJSON, 0,strrpos($pageJSON, "\n}"));
		echo "$pageJSON,\n \"children\":\n\t[\n";
		// conform with children json
		while(($child = $subs->nextDocument()) != null) {
			r_generateJSON($child);
			echo ($i++ < $subs->getSize()-1) ? ",\n" : "\n";
		}
		echo "\t]\n}";

	} else {
		
		$unidMap .= ",".$page->pageid;
		echo $page->getDocumentObject("JSON");
	}
	return true;
}
function getDocumentBody($type, $id, $title) {
	if($id != null) {
		$doc = new Document($id);
		return $doc->body;
	}
	$col = new DocumentCollection($type, false);
	$col->dbSearch("type = '$type'  AND title LIKE '$title'", 1, null);
	$col->realize();
	$doc = $col->getFirstDocument();
	return $doc->body;
}
function outputSearchTerms($type, $maxdoc, $offset, $id, $since, $title) {

	$col = new DocumentCollection($type, false);
	$clause = "type = '$type'";
	if($since != null) {
		$clause .= " AND UNIX_TIMESTAMP(".
		($since_column == null ? "lastmodified":"$since_column").") > $since";
	}
	if($title != null) {
		$clause .= " AND title LIKE '%".$title."%'";
	}
	if($id != null) {
		$clause .= " AND id = $id";
	}
	$col->dbSearch($clause, $maxdoc, $offset, "tocpos");
	$col->realize();
	if(($len = $col->getSize()) == 0) exit();
	echo "{\n";
	echo ' "id" : "id",'."\n";
	echo ' "label": "title",'."\n";
	echo ' "identifier": "id",'."\n";
	echo ' "items": ['."\n";

	$i = 0;
	foreach($col->documents as $d) {
		echo $d->getDocumentObject("JSON", true);
		if($i++ < $len-1) echo ",";
		echo "\n";
	}
	echo "]\n";
	echo "}\n";
}
include "../include/cm.CommonFunctions.php";
require_once $CONFIG['templates']."cm.DocumentTemplate.php";


if($q_string['format'] == "json") {


	header("Content-Type: text/json; charset=UTF-8");
	if($type != "")  {
		outputSearchTerms($type, $maxdoc, $offset, $id, $since, $title);
	} else {
//ob_start();

		echo "{ \"identifier\" : \"id\",".
			"\"label\" : \"title\",".
			"\"items\" :\n[\n";

		$col = new DocumentCollection("page", false);
		$pageCount = $col->dbSearch("type='page' AND isdraft=0", null, null, "tocpos");
		// begin public
		$i = 0;
		foreach($col->documents as $page) {

			$pageJSON = $page->getDocumentObject("JSON");
			$subs = $page->getChildrenDocuments()->documents; // getChildrenOfCategory($page->pageid, $page->isdraft);
			if(($subCount = count($subs)) > 0) {
				$j = 0;
				$pageJSON = substr($pageJSON, 0,strrpos($pageJSON, "\n}"));
				echo $pageJSON. ", children:\n\t[\n";
//echo "TOP ". $page->pageid . " has $subCount chilrdren";
				foreach($subs as $topsub) {
					r_generateJSON($topsub);
					echo ($j++ < $subCount-1) ? ",\n" : "\n";
				}
				echo "\t]\n}";
			} else echo $pageJSON;
			echo ($i++ < $pageCount - 1) ? ",\n": "\n";
			
		}
		// end public
		if($pageCount > 0) echo ",";
		unset($col);
		unset($subs);
		$col = new DocumentCollection("subpage", false);
		$pageCount = $col->dbSearch("type='subpage' AND attach_id=0");
		$i = 0;
		echo "{\"title\":\"-- -- -- -- -- -- -- -- -- -- -- --\",\"comment\":\"Non-public\", \"id\":\"9995\", \"attachId\" : \"0\", \"type\":\"page\"},\n";
/*
		echo "{\"title\":\"Undersider uden reference\",\"id\":\"9996\", \"comment\":\"Subpages, non-public\", \"type\":\"page\", children:[\n";
		foreach($col->documents as $page) {
			echo $page->getDocumentObject("JSON");
			echo ($i++ < $pageCount - 1) ? ",\n": "\n";
		}
		echo "]\n},\n";
		unset($col);
*/
		// start draft
		echo "{\"title\":\"Kladder\",\"id\":\"9997\", \"attachId\" : \"0\", \"isdraft\":\"1\", \"type\":\"page\", children:[\n";
	//	echo "{\"title\":\"Kategorisider\", \"id\":\"9998\", \"comment\":\"Categories, drafts, non-public\", \"type\":\"page\",\"children\":[\n";
		$col = new DocumentCollection("page", false);
		$pageCount = $col->dbSearch("type='page' AND isdraft=1");
		$i = 0;
		foreach($col->documents as $page) {
			echo $page->getDocumentObject("JSON");
			echo ($i++ < $pageCount - 1) ? ",\n": "\n";
		}
		// end page draft
		echo "]\n}\n";
		unset($col);
		/*
		$col = new DocumentCollection("page", false);
		$pageCount = $col->dbSearch("type='subpage' AND isdraft=1");
		//$category->realize();
		$i = 0;
		$printout = false;
		echo "{\"title\":\"Undersider\", \"id\":\"9999\", \"comment\":\"Subpages, drafts, non-public\", \"type\":\"page\",\"children\":[\n";
		foreach($col->documents as $page) {
			r_generateJSON($page);
			echo ($i++ < $pageCount - 1) ? ",\n": "\n";
		}
		// end subpage draft
		echo "]\n}\n";
		unset($col);
		// end draft
		echo "]\n}\n";
		 * 
		 */
		// end items
		echo "]\n}";

//$out = ob_get_contents();
//echo preg_replace("/\n/", "",$out);
		exit();


		$pages = new DocumentCollection("page");
		$subpages = new DocumentCollection("subpage");
		$nonattachedSubs = array();
		$attachedSubs = array();
		foreach($subpages->documents as $sub) {
			if($sub->attach_id =="0" || $sub->attach_id == null) {
				array_push($nonattachedSubs, $sub);
			} else {
				array_push($attachedSubs, $sub);
			}
		}
		// load collection, no realization and then output for treeview hierachy of pages/subpages
		header("Content-Type: text/json; charset=utf-8");
		echo "{ identifier: 'id',".
			"label: 'title',".
			"items:\n[\n";
		// foreach($category as $cat) {
		$i = 0;
		$naCount = count($nonattachedSubs);
		//	$naCount = 0;
		$i = 0;
		if($naCount > 0) {
		echo "\t{title:'Ikke kategoriseret', id:'0', type:'page',disabled:'true'";
			echo ", children:\n\t [\n";
			foreach($nonattachedSubs as $page) {
				echo $page->getDocumentObject();
				echo ($i++ < $naCount - 1) ? ",\n": "\n";
				//echo "\t\t{title:'".$page->title."', id:'".$page->pageid."', type:'subpage'";
				//echo "}".(($i++ < $naCount-1)? ",\n" : "\n");
			}
			echo "\t ]\n\t";
			echo "},\n";
		}
		$i = 0;
		$pCount = count($pages->documents);
		foreach($pages->documents as $page) {
			echo "\t{title:'$page->title', id:'$page->pageid', type:'page'";
			list($titles,$ids) = getChildrenOfPage($page->pageid, $attachedSubs);

			if(($cCount = count($titles)) > 0) {
				echo  ",children:\n\t [\n";
				for($j = 0; $j < $cCount; $j++) {
					echo "\t\t{title:'".$titles[$j]."', id:'".$ids[$j]."', type:'subpage'";
					echo "}".( ($j < $cCount-1) ? ",\n" : "\n");
				}
				echo "\t ]\n\t";
			}

			echo "}".(($i++ < $pCount-1 )? ",\n" : "\n");
		}


		//
		echo "]\n}";
		// }
	}

} else if($q_string['format'] == "contents") {
	header("Content-Type: text/html; charset=UTF-8");
	echo getDocumentBody($type, $id, $title);
} else {
	?>
<script type="text/javascript" src="/dojo_tk/dojo/dojo.js"></script>
<script type="text/javascript">
	function loadA(a, id) {
		dojo.xhrGet({
			url:dojo.attr(a, "srcRef"),load:function(res) {
				dojo.byId(id).innerHTML = res;
			}
		});
		return true;
	}
</script>
<h1>openView API documentation</h1>
<p>
	There are three approaches on the openView for Documents (page/subpage types).<br>
	The purpose most be specified in either one of these 3 ways, to get
	<ul>
		<li><b>Document Search (body output as HTML)</b><br>&nbsp; - An explicite Document by id or no-wildcard-title search</li>
		<li><b>JSON SubSearch (JSON output with all parameters but body)</b><br>&nbsp; -  Collects documents based upon specified search-parameters. Optional pagination and ordering available</li>
		<li><b>Dojo TreeViews</b><br>&nbsp; - will produce a set of JSON Objects which can be interpreted by the dojo.tree API. A bit extensive on larger databases</li>
	</ul>
</p>
<div style="padding-left: 20px;">
	<h2>Parameterlist:</h2>
	<ul>
		<li><pre>format=[json|contents]</pre>
			&nbsp; <i>json</i>: for subsearch or tree. With json as format and leaving out 'type' produces treeview json,<br>
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;but provide 'type' parameter, and openView will result in a search, based on your terms.<br>

			&nbsp; <i>contents</i>: for specific documents (with valid id or title provided), outputs the HTML body.
		</li>
		<li><pre>type=[page|subpage|&lt;blank&gt;]</pre>
			&nbsp; <i>page</i>: Specifies, toplevel Documents<br>
			&nbsp; <i>subpage</i>: Specifies, sublevel Documents<br>
			&nbsp; <i>&lt;blank&gt;</i>: Regardless of any other parameter, leaving out type results in a treeview<br>
		</li>
		<li><pre>searchtitle=[searchstring]</pre>
			&nbsp; <i>searchstring</i>: And SQL 'LIKE' string, % _ and [<i>(!|^)list</i>] allowed, see <a href="http://www.w3schools.com/SQL/sql_wildcards.asp">this</a>
		</li>
		<li><pre>searchid=[numeric]</pre>
			&nbsp; <i>numeric</i>: A specific document-id, <i>type</i>-parameter required
		</li>
		<li><pre>datetime=[timestamp]</pre>
			&nbsp; <i>timestamp</i>: A UNIX timestamp (seconds since 1. jan 1970), will leave out documents predated this stamp from search.
		</li>
		<li><pre>datetime_col=[lastmofied|created]</pre>
			&nbsp; <i>lastmodified</i>: specify this to filter out documents, which lastmodified entry predates the specified <i>datetime</i> (default)<br>
			&nbsp; <i>created</i>: specify this to filter out documents, which created entry predates the specified <i>datetime</i>
		</li>
		<li><pre>count=[numeric]</pre>
			&nbsp; <i>numeric</i>: Maximum number of documents in output (defaults to no-limit), e.g.
			<pre>   count=4&stype=subpage</pre>
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; outputs first 4 subpages (LIMIT search)
		</li>
		<li><pre>start=[numeric]</pre>
			&nbsp; <i>numeric</i>: Offset in the optional pagination (default is 1), e.g.
			<pre>   count=4&start=21</pre>
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; will output 4 documents from 21st row (OFFSET search)
		</li>
	</ul>
</div>
<div style="padding-left: 20px;">
	<h2>How do i access API, examples</h2>
	<p>
		<h5>The Document Search</h5>
		If for example a pull for HTML is needed, say you want to ajax-load a contentpane, do like this:<br>
		<a href="javascript://" onclick="loadA(this, 'loadDocument');" srcRef="Documents.php?format=contents&type=page&searchtitle=Velkommen">
			<?php echo htmlentities("openView/Documents.php?format=contents&type=page&searchtitle=Velkommen"); ?>
		</a><br>
		<p id="loadDocument" style="background-color: whiteSmoke;">Link loads 'Velkommen' here</p>


	</p>
	<p>
		<h5>The JSON SubSearch</h5>
		Say, you would want the <i>gDocument</i> updated from a document in database. <br><br>
		Lets get the ID 1 and ensure that it's a PAGE, do like this:<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Documents.php?format=jsON&searchid=1&type=page">
			<?php echo htmlentities("openView/Documents.php?format=jsON&searchid=1&type=page"); ?>
		</a><br>
		This also works for title subsearch, there might be PAGEs with TITLE containing letters 'AV', lets look them up<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Documents.php?format=jsON&searchtitle=av&type=page">
			<?php echo htmlentities("Documents.php?format=jsON&searchtitle=av&type=page"); ?>
		</a><br>
		As well as TITLE, ID can be used for a search, though ID must be explicit<br><br>
		Lets try searching like: <br>&nbsp; &nbsp;All documents created later than 1st of May 2009: ( mktime(0,0,0,5,1,2009) )<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Documents.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created">
			<?php echo htmlentities("Documents.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created"); ?>
		</a><br>
		<br>
		Now, thats a lot of documents - we sure want to get those in pagefiles, for COUNT and OFFSET we set <i>count</i> and <i>start</i>, like:<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Documents.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created&count=4&start=1">
			<?php echo htmlentities("Documents.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created&count=4&start=1"); ?>
		</a><br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Documents.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created&count=4&start=5">
			<?php echo htmlentities("Documents.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created&count=4&start=5"); ?>
		</a><br>

		<p style="background-color: whiteSmoke;"><pre id="loadJSON">JSON samples loads here</pre></p>
	</p>
	<p>
		<h5>The Treeview</h5>
		Simply put, leave out the <i>type</i> with JSON as <i>format</i>, any other parameters are then ignored, for example:<br>

	</p>
</div>
<?php
}



//if($q_string['format'] == "xml") {
//	echo "<?xml version=\"1.0\" encoding=\"utf8\"? >\n";
//	echo "<store>";
//	echo "<toplevel>";
//	//for...
//	echo "<entry type=\"document\" unid=\"$d->pageid\">";
//	echo "<title><[!CDATA[$d->title]]></title>";
//	echo "<owner><[!CDATA[$d->creator]]></owner>";
//	echo "<created>$d->created</created>";
//	echo "<lastmodified>$d->lastmodified</lastmodified>";
//	echo "<link>".$_SERVER['HOST_NAME']."/".$CONFIG['relurl']."kortlink-".$d->alias."</link>";
//	echo "</entry>";
//	//...
//	echo "</toplevel>";
//	echo "<entries>$i</entries>";
//	echo "</store>";
//} else

?>
