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
$category =  (!isset($q_string['searchcategory']) ? null : $q_string['searchcategory']);
$id =  (!isset($q_string['searchid']) ? null : $q_string['searchid']);
$type =   (!isset($q_string['type']) ? "" : $q_string['type']);

$unidMap = "";

function getChildrenOfCategory($catid) {
	$col = new ProductCollection("products", false);
	$num_rows = $col->dbSearch("type='product' AND category='$catid'");
	$col->realize();
	return $col->documents;
}
//
//function r_generateJSON($page) {
//	global $unidMap;
//	$i = 0;
//	$subs = getChildrenOfCategory($page->pageid, $page->isdraft);
//	if(preg_match("/,".$page->pageid.",/", $unidMap.",")) {
//		return false;
//	} else if(($subCount = count($subs)) > 0) {
//		$pageJSON = $page->getDocumentObject("JSON");
//		$unidMap .= ",".$page->pageid;
//		$pageJSON = substr($pageJSON, 0,strrpos($pageJSON, "\n}"));
//		echo "$pageJSON,\n \"children\":\n\t[\n";
//		foreach($subs as $child) {
//			r_generateJSON($child);
//			echo ($i++ < $subCount-1) ? ",\n" : "\n";
//		}
//		echo "\t]\n}";
//
//	} else {
//		$unidMap .= ",".$page->pageid;
//		echo $page->getDocumentObject("JSON");
//	}
//	return true;
//}
function outputSearchTerms($type, $maxdoc, $offset, $id, $since, $title, $category) {

	$col = new ProductCollection($type, false);
	$clause = "type = '$type'";
	if($since != null) {
		$clause .= " AND UNIX_TIMESTAMP(".
		($since_column == null ? "lastmodified":"$since_column").") > $since";
	}
	if($title != null) {
		$clause .= " AND title LIKE '%".$title."%'";
	}
	if($category != null) {
		$clause .= " AND category LIKE '%".$category."%'";
	}
	if($id != null) {
		$clause .= " AND id = $id";
	}
	$col->dbSearch($clause, $maxdoc, $offset);
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
require_once $CONFIG['templates']."cm.ProductTemplate.php";


if($q_string['format'] == "json") {

//ob_start();
	header("Content-Type: application/x-javascript; charset=UTF-8");
	if($type != "")  {
		outputSearchTerms($type, $maxdoc, $offset, $id, $since, $title, $category);
	} else {


		echo "{ \"identifier\" : \"id\",".
			"\"label\" : \"title\",".
			"\"items\" :\n[\n";

		$col = new ProductCollection("category", false);

		$pageCount = $col->dbSearch("type='category'");
		$col->realize();
		// begin public
		$i = 0;
		foreach($col->documents as $cat) {

			$pageJSON = $cat->getDocumentObject("JSON");
			$products = getChildrenOfCategory($cat->title);
			if(($subCount = count($products)) > 0) {
				$j = 0;
				$pageJSON = substr($pageJSON, 0,strrpos($pageJSON, "}"));
				echo $pageJSON. ", children:\n\t[\n";
				foreach($products as $prod) {
					echo $prod->getDocumentObject("JSON");
					echo ($j++ < $subCount-1) ? ",\n" : "\n";
				}
				echo "\t]\n}";
			} else echo $pageJSON;
			echo ($i++ < $pageCount - 1) ? ",\n": "\n";

		}
		// end items
		echo "]\n}";

	}
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
	There are two approaches on the openView for Documents (product types).<br>
	The purpose most be specified in either one of these ways, to get
	<ul>
		<li><b>Single Product Search (all parameters as JSON including thumbnail in base64 encoding)</b><br>&nbsp; - An explicite Document by id or no-wildcard-title search</li>
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
		<li><pre>type=[category|product|&lt;blank&gt;]</pre>
			&nbsp; <i>category</i>: Specifies, toplevel categories of products<br>
			&nbsp; <i>product</i>: Specifies, products<br>
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
		<h5>The JSON SubSearch</h5>
		Say, you would want the <i>gDocument</i> updated from a document in database. <br><br>
		Lets get the ID 1 and ensure that it's a PAGE, do like this:<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Products.php?format=jsON&searchid=1&type=page">
			<?php echo htmlentities("openView/Products.php?format=jsON&searchid=1&type=product"); ?>
		</a><br>
		This also works for title subsearch, there might be PAGEs with TITLE containing letters 'AV', lets look them up<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Products.php?format=jsON&searchtitle=hest&type=product">
			<?php echo htmlentities("Products.php?format=jsON&searchtitle=av&type=product"); ?>
		</a><br>
		As well as TITLE, ID can be used for a search, though ID must be explicit<br><br>
		Lets try searching like: <br>&nbsp; &nbsp;All documents created later than 1st of May 2009: ( mktime(0,0,0,5,1,2009) )<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Products.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created">
			<?php echo htmlentities("Products.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created"); ?>
		</a><br>
		<br>
		Now, thats a lot of documents - we sure want to get those in pagefiles, for COUNT and OFFSET we set <i>count</i> and <i>start</i>, like:<br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Products.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created&count=4&start=1">
			<?php echo htmlentities("Products.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created&count=4&start=1"); ?>
		</a><br>
		&nbsp; &nbsp;<a href="javascript://" onclick="loadA(this, 'loadJSON');" srcRef="Products.php?format=json&type=page&datetime=<?php echo mktime(0,0,0,5,1,2009);?>&datetime_col=created&count=4&start=5">
			<?php echo htmlentities("Products.php?format=json&type=page&datetime=".mktime(0,0,0,5,1,2009)."&datetime_col=created&count=4&start=5"); ?>
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
