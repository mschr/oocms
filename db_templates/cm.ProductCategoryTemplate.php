<?php
class ProductCategoryCollection {
	var $documents = array();
	private $internal = 0;
	private $nDocuments = 0;
	private $realized = false;
	private $includepath = "";
	function __construct($switch, $initializeWithIds = true) {
		$this->getIncludeDir();
		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		if($initializeWithIds) {
			$sql = new SQL("reader", true);
			$sql->doQuery("SELECT id,title FROM `products` WHERE `type`='category'", "object");
			while( ($row = $sql->getNextRow() ) != null) {
				$this->addDocument($row);
			}
		}
	}

	/**
	 * Collect documents based upon SQL WHERE *clause* with optional pagination and ordering<br><br>
	 * <font size=3>
	 * If this example call is made, the result would be row 5 through 8 between all types of 'subpage', modified since 6. jan 1981 - and sorted by time created:<br>
	 * <p><b>$coll->dbSearch("type='subpage' AND UNIX_TIMESTAMP(lastmodified)>".<br>mktime(0,0,0,1,6,1981), 3, 5, "created")</b><br>
	 * if available, this results in 3 Document's (realized), with database given the following query<br>
	 * <i>SELECT id as id FROM `pages` WHERE `type`='subpage' AND UNIX_TIMESTAMP(lastmodified) > 1823...9123 ORDER BY `created` LIMIT 3 OFFSET 5</i>
	 * </font>
	 * @param <type> $whereclause customized WHERE statement for an SQL query, e.g.<br>
	 * <ul>
	 * <li>type='include'</li>
	 * <li>UNIX_TIMESTAMP(lastmodified) < NOW()</li>
	 * </ul>
	 * @param <int> $max Maximum number of 'hits'
	 * @param <int> $offset Collect from nth row of the query
	 * @param <string> $order_column specifiy which column to order by, id is default, as its autoinc and unique
	 * @return Number of rows proccessed, i.e nDocuments in the resulting DocumentCollection
	 */
	function dbSearch($whereclause, $max = null, $offset = null, $order_column = null) {
		$sql = new SQL("reader", true);
		$sql->doQuery("SELECT id as id FROM `products` WHERE " .
			"$whereclause ".
			($order_column != null ? " ORDER BY $order_column" : "").
			($max != null ? " LIMIT $max" : "").
			($offset != null && $max != null ? " OFFSET $offset" : ""), "object");
		$err = $sql->errMessage();
		if($err != "") {
			echo "<br>\n<p>Info:<b>".$sql->statusMessage()."</b></p>";
		}
		while( ($row = $sql->getNextRow("array") ) != null) {
			$this->addDocument($row);
		}
		$this->realize();
		return $this->nDocuments;
	}
	function getIncludeDir() {
		if(is_dir("../include")) {
			$this->includepath="../include/";
		} else if(is_dir("include")) {
			$this->includepath="include/";
		} else if(is_dir("../../include")) {
			$this->includepath="../../include/";
		}
	}
	function realize() {
		if($this->realized) return;
		for($i = 0; $i < count($this->documents); $i++) {
			$id = $this->documents[$i]->id;
			$this->documents[$i] = new Product($id);
		}
		$this->realized = true;
	}
	function save() {
		if(!$this->realized) return;
		foreach($this->documents as $docref) $docref->updateDb();
	}
	function getSize() { return $this->nDocuments; }
	function getFirstDocument() {
		$this->internal = 0;
		if(count($this->documents) == 0) return null;
		return $this->documents[$this->internal];
	}
	function getLastDocument() {
		$this->internal = $this->nDocuments - 1;
		if(count($this->documents) == 0) return null;
		return $this->documents[$this->internal];
	}
	function nextDocument() {
		if($this->internal + 1 == count($this->documents)) {
			return null;
		}
		return $this->documents[++$this->internal];
	}
	function deleteDocument($doc) {

		$newO = array();
		for($i = 0; $i < count($this->documents); $i++)
		{
			if($this->documents[$i] === $doc) {
				$this->nDocuments--;
			} else {
				array_push($newO, $this->documents[$i]);
			}
			$this->documents = $newO;
		}
	}
	function addDocument($doc) {
		array_push($this->documents, $doc);
		$this->nDocuments++;
	}
	function generateJSON() {
		$json = "{\n";
		$json .= ' "id" : "id",'."\n";
		$json .= ' label": "title",'."\n";
		$json .= "\t".'"items": ['."\n";
		$i = 0;
		foreach($this->$documents as $d) {
			$json .= $d->getDocumentObject("JSON");
			if($i++ < $this->nDocuments-1) $json .= ",\n";
		}
		$json .= "\t]\n";
		$json .= "}\n";
		return $json;
	}
}
class ProductCategory {
	public $id;
	public $attachId;
	public $type;
	public $title;
	public $alias;
	public $isdraft;
	public $body;
	public $creator;
	public $editors;
	public $lasteditedby;
	public $lastmodified;
	public $showtitle;
	private $includepath = "";
	private $columns = array();
	function __construct($id = null) {
		$this->getIncludeDir();
		if($id != null) {
			$this->load($id);
		}
	}
	function load($id) {

		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$this->id = $id;
		$sql = new SQL("reader", true);
		$data = $sql->doQueryGetFirstRow("SELECT * FROM `products` WHERE `id`='$id'", "object");
		foreach($data as $field => $value) {

			$this->$field = $value;
			$this->columns[] = $field;

		}
	}

	function getIncludeDir() {

		if(is_dir("../include")) {
			$this->includepath="../include/";
		} else if(is_dir("include")) {
			$this->includepath="include/";
		} else if(is_dir("../../include")) {
			$this->includepath="../../include/";
		}
	}

	private function generateAlias() {
		$s = "p";
		for($i = 1; $i <= 16; $i++) {

			$s[$i] = rand(0,9);

		}
		return $s;
	}

	function base64_generateThumb() {
		if(!isset($this->thumb_file) || empty($this->thumb_file)) {

			return null;

		}

		include_once "../include/cm.CommonFunctions.php";
		global $CONFIG;
		if(!file_exists($CONFIG['document_root'].$this->image_file))  {

			return null;

		}
		$max_width = "160";
		$this->thumb_file = "fileadmin/products/thumbs/".substr($this->image_file, strrpos($this->image_file,"/")+1);
		$this->thumb_file = substr($this->thumb_file, 0, strrpos($this->image_file,".")) . "gif";
		$tf = $CONFIG['document_root'].$this->thumb_file;
		$if= $CONFIG['document_root'].$this->image_file;
		$image_details = getimagesize($if);
		$imagesize_x = $image_details[0];
		if($imagesize_x <= 160) {

			return base64_encode(file_get_contents($tf));

		}

		$imagesize_y = $image_details[1];
		$thumb_width = $max_width;
		$thumb_height = (int)(($max_width*$imagesize_y)/$imagesize_x);
		if(strstr($if, ".jpg") || strstr($if, "jpeg")) {
			$source = imagecreatefromjpeg($if);

		} else if(strstr($if, ".png")) {
			$source = imagecreatefrompng($if);

		} else if(strstr($if, ".gif")) {
			$source = imagecreatefromgif($if);

		}

		$dest = imagecreatetruecolor($thumb_width, $thumb_height);
		$final = imagecopyresampled ($dest, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $imagesize_x, $imagesize_y);

		if($final) imagegif($dest, $tf);

		imagedestroy($dest);
		imagedestroy($source);
		if($final) return null;
		return base64_encode(file_get_contents($tf));
	}

	function setRating($n) {

		$sql = new SQL("admin");
		$sql->doQuery("UPDATE `products` SET `rating` = '$n',`lastmodified` = NOW( )".
			" WHERE `products`.`id` =11 LIMIT 1;");
		if($sql->errMessage() != "") {
			return false;
		}
		return true;
	}

	function getChildrenDocumentIds() {

		if($this->type != "category") return;
		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` FROM `products` ORDER BY `rating` `type`='product' AND `category`=".$this->title."';");
		$ids = array();
		while(($row = $sql->getNextRow("object")) != null) $ids[]=$row->id;
		return $ids;
	}

	function getChildrenDocuments() {

		if($this->type != "category") return;
		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` FROM `products` ORDER BY `rating` WHERE `type`='product' AND `category`=".$this->title."';");
		$rows = array();
		while(($row = $sql->getNextRow("object")) != null) $rows[]=$row;
		return $rows;
	}

	function create($USER, $type, $title) {

		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$un = $_SERVER['REMOTE_USER'];
		$sql = new SQL("admin");

		$alias = $this->generateAlias();
		$msg = $sql->doQuery("INSERT INTO `products` (".
			"`id` ,`alias`, `type` , `title`, `creator` ,`created`, `lastmodified`".
			") VALUES (".
			"NULL, '$alias', '$type', '$title', '$un',   NOW( ),    NOW( ) );");


		if($sql->errMessage() != "") {

			$msg = $sql->errMessage();
			if(strstr($msg, "Duplicate") !== false) {

				$_SESSION['error'] = "Titel eksisterer";
				return false;

			}

		}

		$sql->doQuery("SELECT id FROM `products` ORDER BY lastmodified DESC LIMIT 3");
		$r = $sql->getNextRow("object");
		$this->load($r->id);
		return true;
	}

	function updateDb() {

		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$sql = new SQL("admin");
		$sql->doQuery("SELECT thumb_file FROM `products` WHERE id='".$this->id."'");
		$r = $sql->getNextRow("array");
		if($this->type != "category" && $this->image_file != $r['image_file']) {

			$this->thumbnail = $this->base64_generateThumb();

		}
		$cols = count($this->columns);
		$exclude = "|id|lastmodified|";
		for($i=0; $i < $cols; $i++) {

			$colName = $this->columns[$i];
			if(strstr($exclude, "|".$colName."|")) continue;
			$q.="`".$colName."`='".$this->$colName."'" . ($i < $cols - 1 ? ",":"");

		}
		$sql->doQuery("UPDATE `products` SET ".
			"$q WHERE `products`.`id` = '".$this->id."'LIMIT 1 ;");
		if($sql->errMessage() != "") {

			$_SESSION['error'] = $sql->errMessage();
			return false;

		}
		return true;
	}

	function delete($forceid = null, $removeSrc = false) {

		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$sql = new SQL("admin");
		if(!$forceid) $forceid = $this->id;
		if($removeSrc) {
			include_once $this->includepath."cm.CommonFunctions.php";
			$srcFile = $CONFIG['document_root'] . "fileadmin/products/" . $this->thumb_file;
			if(file_exists($srcFile))  {
				unlink($srcFile);
			}
		}
		$sql->doQuery("DELETE FROM `products` WHERE `products`.`id` = $forceid");
		if($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		return true;
	}
	function getDocumentObject($format, $withFull = false) {
		if(!class_exists("SQL")) require_once $this->includepath."mysqlport.php";
		$exclude = array("thumbnail");
		$conv = new Convert();
		include_once $this->includepath."cm.CommonFunctions.php";

		if($format=="JS"||$format=="JSON") {
			$szHtml = "{\n";
			$cols = count($this->columns);
			$exclude = "|lastmodified|" . (!$withFull ? "description|thumbnail|" : "");
			for($i=0; $i < $cols; $i++) {
				$colName = $this->columns[$i];
				if(strstr($exclude, "|".$colName."|")) continue;
				$q.="\"$colName\":\"".$this->$colName."\",\n";
			}
			$q.= "\"lastmodified\":\"".($this->lastmodified!=""?$conv->timeToDate($this->lastmodified, "r"):"")."\"\n}";
			$szHtml .= $q;
			//				"\"title\":\"".$this->title."\",\n" .
			//				"\"id\":\"".$this->id."\",\n" .
			//				"\"alias\":\"".$this->alias."\",\n" .
			//				"\"type\":\"".$this->type."\",\n" .
			//				"\"category\":\"".$this->category."\",\n" .
			//				"\"rating\":\"".$this->rating."\",\n" .
			//				"\"creator\":\"".$this->creator."\",\n" .
			//				"\"created\":\"".($this->created!=""?$conv->timeToDate($this->created, "r"):"")."\",\n" .
			//				"\"lastmodified\":\"".($this->lastmodified!=""?$conv->timeToDate($this->lastmodified, "r"):"")."\cho ",\n" .
			//				"\"creator\":\"".$this->creator."\",\n" .
			//				"\"thumb_file\":\"".$CONFIG['relurl'].$this->thumb_file."\",\n" .
			//				"\"thumbnail\":\"".$this->thumbnail."\",\n" .
			//				"\"description\":\"".$this->description."\",\n" .
			//				"\"price\":\"".$this->price."\"\n" .
			//				"}";
		}else if($format=="XML") {
		} else if($format=="WIDGET") {
			$szHtml = '<table '.(!empty($this->price)?'title="Pris: '.$this->price.'" ':'').
				'class="productTable" cellspacing="0" cellpadding="0"><tbody><tr>';
			$szHtml .= '<td><img alt="No thumbnail" src="data:;base64,'.$this->thumbnail.'"';
			if(!empty($this->thumb_linksto)) {
				$szHtml .= ' onclick="javascript:location=\''.$this->thumb_linksto.'\'"';
			}
			$szHtml .= '/></td></tr>';
			$szHtml .= '<tr><td><span class="productTitle">'. $this->title .'</span></td></tr>';
			$szHtml .= '<tr><td><span class="productDescription">'.$this->description.'</span></td></tr>';
			$szHtml .= '</tbody></table>';
		}
		return $szHtml;
	}

}
?>
