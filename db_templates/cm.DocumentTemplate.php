<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Database {

	function __construct() {
		global $CONFIG;
		if (!class_exists("SQL"))
			require_once $CONFIG['includes'] . "mysqlport.php";
	}

	public function dbSearch($whereclause, $max = null, $offset = null, $order_column = null, $sort_order = null) {
		$col = new DocumentCollection("", false);
		return $col->dbSearch($whereclause, $max, $offset, $order_column, $sort_order);
	}

	public function ftSearch($words) {
		global $CONFIG;
		$col = new DocumentCollection("", false);
		$sql->doQuery("SELECT `id` as pageid FROM `" . $CONFIG['db_pagestable'] . "` WHERE MATCH(ft_indexed) AGAINST(" . explode(",", $words) . ")");
		while (($row = $sql->getNextRow("object") ) != null) {
			$col->addDocument($row);
		}
		$col->realize();
		return $col;
	}

	public function updateIndex($forceAll=false) {
		global $CONFIG;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` FROM  `" . $CONFIG['db_pagestable'] . "` WHERE " . ($forceAll ? "1=1" : "`ft_lastmodified` < `lastmodified`"));
		while (($row = $sql->getNextRow("object") ) != null) {
			$doc = new Document();
			$doc->load($row->id);
			echo $doc->title . "\n";
			if (!$doc->updateIndex($forceAll)) {
				return false;
			}
		}
		return true;
	}

	public function updateKeywords($forceAll=false) {
		global $CONFIG;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` FROM  `" . $CONFIG['db_pagestable'] . "` WHERE " . ($forceAll ? "1=1" : "`ft_lastmodified` < `lastmodified`"));
		while (($row = $sql->getNextRow("object") ) != null) {
			$doc = new Document();
			$doc->load($row->id);
			echo $doc->title . "::" . $doc->updateKeywords($forceAll) . "\n";
		}
		return true;
	}

	private function updatePosRecursive($parentDocument) {
		global $CONFIG;
		$sqlRead = new SQL("reader");
		$sqlRead->doQuery("SELECT `id`,`title`,`tocpos` as pageid FROM `" . $CONFIG['db_pagestable'] .
				  "` WHERE `attach_id`='" . $parentDocument->pageid . "' ORDER BY `tocpos` ;");

		if ($sqlRead->getCount() > 0) {
			$sqlWrite = new SQL("admin");
			$i = 100;
			while ($child = $sqlRead->getNextRow("object")) {
				if ($child->tocpos != $i) {
					$sqlWrite->doQuery("UPDATE " . $CONFIG['db_pagestable'] . " SET `tocpos`='$i' WHERE `id`='" . $child->pageid . "'");
				}
				$i = $i + 100;
				$this->updatePosRecursive($child);
			}
		}
	}

	public function updateSorting() {
		global $CONFIG;
		$col = new DocumentCollection("page", true);
		$sql = new SQL("admin");
		$i = 100;
		foreach ($col->documents as $doc) {
			$sql->doQuery("UPDATE " . $CONFIG['db_pagestable'] . " SET `tocpos`='$i' WHERE `id`='" . $doc->pageid . "'");
			$i += 100;
			$this->updatePosRecursive($doc->pageid);
		}
		return true;
	}

}

class DocumentCollection {

	var $documents = array();
	private $internal = -1;
	private $nDocuments = 0;
	private $realized = false;
	private $includepath = "";

	function __construct($switch = null, $initializeWithIds = true) {
		global $CONFIG;
		if (!class_exists("SQL"))
			require_once $CONFIG['includes'] . "mysqlport.php";
		if ($switch != null && $initializeWithIds) {
			$sql = new SQL("reader", true);
			$sql->doQuery("SELECT id as pageid, title" .
					  ($switch == "subpage" ? ", attach_id" : "") .
					  " FROM `" . $CONFIG['db_pagestable'] . "` WHERE `type`='$switch' ORDER BY `tocpos`", "object");
			while (($row = $sql->getNextRow("object") ) != null) {
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
	 * <i>SELECT id as pageid FROM `".$CONFIG['db_pagestable']."` WHERE `type`='subpage' AND UNIX_TIMESTAMP(lastmodified) > 1823...9123 ORDER BY `created` LIMIT 3 OFFSET 5</i>
	 * </font>
	 * @param <type> $whereclause customized WHERE statement for an SQL query, e.g.<br>
	 * <ul>
	 * <li>type='include'</li>
	 * <li>UNIX_TIMESTAMP(lastmodified) < NOW()</li>
	 * </ul>
	 * @param <int> $max Maximum number of 'hits'
	 * @param <int> $offset Collect from nth row of the query
	 * @param <string> $order_column specifiy which column to order by, id is default, as its autoinc and unique
	 * @param <string> $sort_order sets ascending or descending (default asc)
	 * @return Number of rows proccessed, i.e nDocuments in the resulting DocumentCollection
	 */
	function dbSearch($whereclause, $max = null, $offset = null, $order_column = null, $sort_order = null) {
		global $CONFIG;
		$sql = new SQL("reader", true);

		$order = ($sort_order != null ? (strtolower($sort_order) == "asc" ? "ASC" : "DESC") : "ASC");
		$sql->doQuery("SELECT id as pageid FROM `" . $CONFIG['db_pagestable'] . "` WHERE " .
				  "$whereclause " .
				  ($order_column != null ? " ORDER BY $order_column $order" : "") .
				  ($max != null ? " LIMIT $max" : "") .
				  ($offset != null && $max != null ? " OFFSET $offset" : ""), "object");

		$err = $sql->errMessage();
		if ($err != "") {
			echo "<br>\n<p>Info:<b>" . $sql->statusMessage() . "</b></p>";
		}
		while (($row = $sql->getNextRow("object") ) != null) {
			$this->addDocument($row);
		}
		$this->realize();
		$this->internal = -1;
		return $this->nDocuments;
	}

	function ftSearch($words) {
		global $CONFIG;
		$this->realize();
		$sql = new SQL("reader", true);
		$docIds = array();
		$doc = $this->getFirstDocument();
		while ($doc != null) {
			if (!$doc->isOpen())
				continue;
			$sql->doQuery("SELECT `id` FROM `" . $CONFIG['db_pagestable'] . "` WHERE MATCH(ft_indexed) AGAINST(" . explode(",", $words) . ")");
			while (($row = $sql->getNextRow("object")) != null) {
				$docIds[] = $row->id;
			}
			$this->internal = -1;
		}
	}

	function getIncludeDir() {
		if (is_dir("../include")) {
			$CONFIG['includes'] = "../include/";
		} else if (is_dir("include")) {
			$CONFIG['includes'] = "include/";
		} else if (is_dir("../../include")) {
			$CONFIG['includes'] = "../../include/";
		}
	}

	function realize() {
		for ($i = 0; $i < count($this->documents); $i++) {
			$this->documents[$i] = new Document($this->documents[$i]->pageid);
		}
		$this->realized = true;
	}

	function save() {
		if (!$this->realized)
			return;
		foreach ($this->documents as $docref)
			$docref->updateDb();
	}

	function getSize() {
		return $this->nDocuments;
	}

	function getFirstDocument() {
		$this->internal = 0;
		if (count($this->documents) == 0)
			return null;
		return $this->documents[$this->internal];
	}

	function getLastDocument() {
		$this->internal = $this->nDocuments - 1;
		if (count($this->documents) == 0)
			return null;
		return $this->documents[$this->internal];
	}

	function nextDocument() {
		if ($this->internal + 1 == count($this->documents)) {
			return null;
		}
		fb(count($this->documents), $this->internal);
		return $this->documents[++$this->internal];
	}

	function filterOut($value) {
		if (is_array($this->_mixedMatch)) {
			foreach ($this->_mixedMatch as $m)
				if ($m === $value)
					return false;
		}
		return ($this->_mixedMatch === $value ? false : true);
	}

	function grep($value, $parameter=null) {
		if (is_array($this->_mixedMatch)) {
			foreach ($this->_mixedMatch as $m) {
				if ($parameter != null)
					if ($m->$parameter == $value->$parameter)
						return true;
					else if ($m === $value)
						return true;
			}
		}
		if ($parameter != null && $m->$parameter == $value->$parameter)
			return true;
		else if ($m === $value)
			return true;
		else
			return false;
	}

	function deleteDocument($doc) {
		$this->_mixedMatch = &$doc;
		$this->documents = array_filter($this->documents, array($this, "filterOut"));
		$this->nDocuments = count($this->documents);
		return;
		$newO = array();
		for ($i = 0; $i < count($this->documents); $i++) {
			if ($this->documents[$i] === $doc) {
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
		$json .= ' "id" : "id",' . "\n";
		$json .= ' label": "title",' . "\n";
		$json .= "\t" . '"items": [' . "\n";
		$i = 0;
		foreach ($this->documents as $d) {
			$json .= $d->getDocumentObject("JSON");
			if ($i++ < $this->nDocuments - 1)
				$json .= ",\n";
		}
		$json .= "\t]\n";
		$json .= "}\n";
		return $json;
	}

}

class Document {

	// all public member variables, initialized as an empty string are 
	// get/set-able via get_membervar() and set_membervar($val)
	public $pageid = "";
	public $attachId = "";
	public $type = "";
	public $title = "";
	public $alias = "";
	public $isdraft = "";
	public $tocpos = "";
	public $body = "";
	public $creator = "";
	public $created;
	public $editors = "";
	public $lasteditedby = "";
	public $lastmodified;
	public $ft_lastmodified;
	public $keywords = "";
	public $showtitle;
	private $updateAddQuery = "";
	private $includepath = "";
	private $_open = false;
	private $_attachedTo = 0;
	private $attributes = array("pageid", "attachId", "type", "title", "alias",
		 "isdraft", "tocpos", "body", "creator", "created", "editors",
		 "lasteditedby", "lastmodified", "keywords", "showtitle", "ft_lastmodified");

	function __construct($id = null) {
		global $CONFIG;
		if (!class_exists("SQL"))
			require_once $CONFIG['includes'] . "mysqlport.php";
		if ($id != null) {
			if (!$this->load($id)) {
				throw new Exception("DocumentLoadException: " . $_SESSION['error']);
			}
		}
	}

	// this magic method gets called
	// when a non-existing method is called
	public function __call($name, $arguments) {
		// get the property name we want to set
		$property = substr($name, 4);
		switch (substr($name, 0, 4)) {
			case 'get_':
				return $this->$property;
				break;
			case 'set_':
				if (isset($this->$property))
					$this->$property = $arguments[0];
				break;
			case 'add_':
				if (isset($this->$property)) {
					if (is_array($this->$property))
						array_push($this->$property, $arguments[0]);
					else if (is_int($this->$property))
						$this->$property += $arguments[0];
					else // is_string, add optional delimiter
						$this->$property .= (isset($arguments[1]) ? $arguments[1] : "") . $arguments[0];
				}
				break;
			default:
		}
		// for method chaining
		return $this;
	}

	function isOpen() {
		return $this->_open;
	}

	function load($id) {
		global $CONFIG;
		$sql = new SQL("reader", true);
		$data = $sql->doQueryGetFirstRow("SELECT id, type, title, alias, isdraft, tocpos, body,keywords, creator, created, editors, showtitle, lastmodified, ft_lastmodified, lasteditedby, attach_id FROM `" . $CONFIG['db_pagestable'] . "` WHERE `id`='$id'", "object");

		if ($data == null) {
			$_SESSION['error'] = $sql->errMessage();
			$this->_open = false;
			return false;
			;
		}
		else
			$this->_open = true;

		$this->pageid = $data->id;
		$this->type = $data->type;
		$this->title = $data->title;
		$this->alias = $data->alias;
		$this->isdraft = $data->isdraft;
		$this->tocpos = $data->tocpos;
		$this->body = $data->body;
		$this->body = $this->html_entity_decode("UTF-8");
		$this->creator = $data->creator;
		$this->created = $data->created;
		$this->editors = $data->editors;
		$this->showtitle = $data->showtitle;
		$this->lastmodified = $data->lastmodified;
		$this->ft_lastmodified = $data->ft_lastmodified;
		$this->keywords = $data->keywords;
		$this->lasteditedby = $data->lasteditedby;
		$this->_attachedTo = $this->attachId = $data->attach_id;
		return true;
	}

	function getIncludeDir() {

		if (is_dir("../include")) {
			$CONFIG['includes'] = "../include/";
		} else if (is_dir("include")) {
			$CONFIG['includes'] = "include/";
		} else if (is_dir("../../include")) {
			$CONFIG['includes'] = "../../include/";
		}
	}

	private function generateAlias() {
		$s = "r";
		for ($i = 1; $i <= 16; $i++) {
			$s[$i] = rand(0, 9);
		}

		return $s;
	}

	function fixup() {//TODO: make sql->doQuery efficiency update
		if ($this->type == "page") {
			$pages = new DocumentCollection("page");
		} else if ($this->type == "subpage") {
			$parent = $this->getParentDocument();
			$pages = $parent->getChildrenDocuments();
		}
		$pages->realize();
		$i = 0;
		while (($page = $pages->nextDocument()) != null) {
			if ($page->isdraft == "1")
				continue;
			$page->tocpos = ($i++) * 100 + 100;
			$page->updateDb();
		}
	}

	function recalculatePosition($direction) {
		global $CONFIG;
		// straighten out 1,2,3 if a messy 1, 4, 5, 8 or like exists
		$this->fixup();
		$lessorgreater = $direction > 0 ? ">" : "<";
		switch ($this->type) {

			case "page" :
				$pages = new DocumentCollection();
				$count = $pages->dbSearch("type='page' AND tocpos $lessorgreater {$this->tocpos}", 1, null, "tocpos", "ASC");
				break;

			case "subpage":
				$pages = new DocumentCollection();
				$count = $pages->dbSearch("type='page' AND `attach_id`='{$this->attachId}' AND tocpos $lessorgreater {$this->tocpos}", 1, null, "tocpos", "ASC");
				break;
			default:
				echo "Wrong type...";
				return false;
		}
		if ($count == 0) {
			echo "Nothing to swap with";
			return -1;
		}
		$adjectantDoc = $pages->getFirstDocument();
		$currentPos = $this->tocpos;
		$this->tocpos = $adjectantDoc->tocpos;
		$adjectantDoc->tocpos = $currentPos;
		$this->updateDb();
		$adjectantDoc->updateDb();
		return true;
	}

	function _lastPosition() {
		global $CONFIG;
		$sql = new SQL("reader");
		if ($this->type == "page") {
			$row = $sql->doQueryGetFirstRow("SELECT `tocpos` FROM `" . $CONFIG['db_pagestable'] . "` WHERE `type`='page' ORDER BY `tocpos` DESC  LIMIT 1");
			if ($row)
				return intval($row['tocpos']) + 100;
			else
				return 100;
		} else {
			$row = $sql->doQueryGetFirstRow("SELECT `tocpos` FROM `" . $CONFIG['db_pagestable'] . "` WHERE `type`='subpage' AND `attach_id`='" . $this->attachId . "' ORDER BY `tocpos` DESC LIMIT 1");
			if ($row)
				return intval($row['tocpos']) + 100;
			else
				return 100;
		}
	}

	function getChildrenDocumentIds($limit = false) {
		global $CONFIG;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` FROM `" . $CONFIG['db_pagestable'] . "` WHERE `attach_id`='" . $this->pageid . "' ORDER BY `tocpos`" .
				  ($limit ? " LIMIT $limit" : ""));
		$ids = array();
		while (($row = $sql->getNextRow("object")) != null) {
			array_push($ids, $row->id);
		}
		return $ids;
	}

	function getParentDocument() {
		if (empty($this->attachId)) {
			return null;
		}
		$parent = new Document();
		$parent->load($this->attachId);
		return $parent;
	}

	function getToplevelDocument() {
		if (empty($this->attachId)) {
			return null;
		}
		$parent = $this->getParentDocument();
		while (($parent != null && $parent->type != "page")) {
			$parent = $parent->getParentDocument();
		}
		return $parent;
	}

	function getChildrenDocuments($limit = false) {
		global $CONFIG;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT `id` as pageid FROM `" . $CONFIG['db_pagestable'] . "` WHERE `attach_id`='" . $this->pageid . "' ORDER BY `tocpos`" .
				  ($limit ? " LIMIT $limit" : ""));
		$childDocuments = new DocumentCollection("", false);
		while (($row = $sql->getNextRow("object")) != null) {
			$childDocuments->addDocument($row);
		}
		$childDocuments->realize();
		return $childDocuments;
	}

	function getAttachedResourceIds() {
		global $CONFIG;
		$sql = new SQL("reader");
		$ok = $sql->doQuery("SELECT * FROM {$CONFIG['db_resourcestable']} WHERE FIND_IN_SET({$this->id},attach_id) > 0");
		$resources = array();
		if ($ok !== false && $sql->getCount() > 0) {
			while (($row = $sql->getNextRow("object")) != null)
				array_push($resources, $row->id);
		}
		return $resources;
	}

	function create($user, $type, $title) {
		global $CONFIG, $user;
		$_SESSION['error'] = "";
		$un = $user->username;
		$sql = new SQL("admin");
		$alias = $this->generateAlias();
		$msg = $sql->doQuery("INSERT INTO `" . $CONFIG['db_pagestable'] . "` (" .
				  "`id` ,`alias`, `type` , `title`, `creator` ,`created`, `lastmodified` ,`editors`, `tocpos`" .
				  ") VALUES (" .
				  "NULL, '$alias', '$type', '$title', '$un',   NOW( ),    NOW( ) ,   '$un', " . $this->_lastPosition() . ");");
		if ($sql->errMessage() != "") {
			$msg = $sql->errMessage();
			if (strstr($msg, "Duplicate") !== false)
				$_SESSION['error'] = "Titel eksisterer";
			else
				$_SESSION['error'] = $msg;
			return false;
		}
		$r = $sql->doQueryGetFirstRow("SELECT id FROM `" . $CONFIG['db_pagestable'] . "` ORDER BY lastmodified DESC LIMIT 1", "object");
		$this->load($r->id);
		return true;
	}

	function isFulltextIndexed() {
		return (Convert::timeToTime($this->ft_lastmodified) == Convert::timeToTime($this->lastmodified));
	}

	function updateIndex($force = false) {
		global $CONFIG;
		if (!$this->isOpen())
			return false;
		if (!$force && $this->isFulltextIndexed()) {
			return true;
		}
		$doc = new DOMDocument;
		ob_start();
		$doc->loadHTML("<html><head><meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\"/></head><body><pre>" . $this->html_entity_decode("UTF-8") . "</pre></body>");
		ob_end_clean();
		$includes = $doc->getElementsByTagName('p');
		$ft_text = "";
		foreach ($includes as $p) {
			$ft_text .= " " . $p->textContent;
		}
		$includes = $doc->getElementsByTagName('h1');
		foreach ($includes as $h) {
			$ft_text .= " " . $h->textContent;
		}
		$includes = $doc->getElementsByTagName('h2');
		foreach ($includes as $h) {
			$ft_text .= " " . $h->textContent;
		}
		$ft_text = preg_replace("/'/si", "\\'", $ft_text);
		$ft_text = preg_replace('/"/si', '\\"', $ft_text);
		$sql = new SQL("admin");
		$sql->doQuery("UPDATE `" . $CONFIG['db_pagestable'] . "` SET `ft_indexed`='" . $ft_text . "', `ft_lastmodified`='" . $this->lastmodified . "' WHERE `id`='" . $this->pageid . "'");
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		return true;
	}

	function uniqueWord($word, $dupeof) {
		$i = 0;
		$eq = true;
		$wlen = strlen($word);
		$word = strtolower($word);
		foreach ($dupeof as $current) {
			$current = strtolower($current);
			$clen = strlen($current);
			if ($wlen > $clen) {

				for ($i = 0; $i < $clen; $i++)
					if ($current{$i} != $word{$i})
						break;
				if ($clen == $i && $i > 3)
					return true;
			} else {

				for ($i = 0; $i < $wlen; $i++)
					if ($current{$i} != $word{$i})
						break;
				if ($wlen == $i && $i > 3)
					return true;
			}
		}
		return false;
	}

	private function testStopwords($check, $noisewords) {
		if (strlen($check) < 5)
			return false;
		foreach ($noisewords as $stopword) {
			if ($stopword == strtolower($check)
					  || (strpos($word, $stopword) == 0 && strlen($check) < 7)) {
				return false;
			}
		}
		return true;
	}

	function updateKeywords() {
		global $CONFIG;
		if (!$this->isFulltextIndexed())
			$this->updateIndex();
		$globalKeys = explode(",", $CONFIG['keywords']);
		$noisewords = explode(",", "af,alle,andet,andre,at,begge,da,de,den,denne,der,deres,det,dette,dig,din,dog,du,ej,eller,en,end,ene," .
				  "eneste,enhver,et,fem,fire,flere,fleste,for,fordi,forrige,fra,få,før,god,han,hans,har,hendes,her,hun,hvad,hvem,hver,men,mens,mere,mig,ned," .
				  "hvilken,hvis,hvor,hvordan,hvorfor,hvornår,i,ikke,ind,ingen,intet,jeg,jeres,kan,kom,kommer,lav,lidt,lille,man,mand,mange,med,meget,ni," .
				  "nogen,noget,ny,nyt,nær,næste,næsten,og,op,otte,over,på,se,seks,ses,som,stor,store,syv,ti,til,to," .
				  "tre,ud,var,om,dit");
		$sql = new SQL("reader");
		$r = $sql->doQueryGetFirstRow("SELECT `ft_indexed` as str FROM `" . $CONFIG['db_pagestable'] . "` WHERE `id`='" . $this->pageid . "' LIMIT 1");
		$count = array();
		$words = preg_split("/[\ -]/", $r['str']);
		foreach ($words as $word) {
			if ($this->testStopwords($word, &$noisewords)) {
				if (!$this->uniqueWord($word, array_merge($globalKeys, explode(" ", trim($this->keywords))))) {
					if (empty($count[$word]))
						$count[$word] = 0;
					$count[$word]++;
				}
			}
		}
		arsort(&$count);
		//$ret = array();
		$totalwords = count($words);
		$this->keywords = "";
		foreach ($count as $w => $c) {
			if (($totalwords > 35 && $c < 3) || $c < 2)
				break;
			$this->keywords .= trim(preg_replace("/,/", "", $w)) . " ";
			//$ret[] =array($w=>$c);
		}
		/*
		  foreach(explode(" ",$this->alias.",") as $word) {
		  if($this->testStopwords($word,&$noisewords)) {
		  $this->keywords .= trim(preg_replace("/[,&]/s", "", $word))." ";
		  }
		  }
		 */
		$this->keywords = implode(",", explode(" ", trim($this->keywords)));
		$this->save();
		return $this->keywords;
	}

	function getHtml() {
		return $this->html_entity_decode("UTF-8");
	}

	function htmlentities($toEncoding) {
		$encoding = mb_detect_encoding($this->body, "ASCII,JIS,UTF-8,ISO-8859-1,ISO-8859-15,EUC-JP,SJIS");
		$bodybuf = $this->body;
		$this->body = mb_convert_encoding($this->body, $toEncoding, ($encoding != "" ? $encoding : "auto"));
		$body = "";
		for ($i = 0; $i < strlen($this->body); $i++) {
			if (ord($this->body[$i]) != 194)
				$body.=$this->body[$i];  // nbsp;
			else
				$body.=" ";
		}
		if (strlen($bodybuf) > 255 && strlen($body) < strlen($bodybuf) / 5)
			return $bodybuf;
		return htmlentities($body, ENT_QUOTES);
	}

	function html_entity_decode($toEncoding) {
		$encoding = mb_detect_encoding($this->body, "ASCII,JIS,UTF-8,ISO-8859-1,ISO-8859-15,EUC-JP,SJIS");
		$body = mb_convert_encoding($this->body, $toEncoding, ($encoding != "" ? $encoding : "auto"));
		return html_entity_decode($body, ENT_QUOTES, $toEncoding);
	}

	function save() {
		return $this->updateDb();
	}

	function updateDb() {
		global $CONFIG;
		$sql = new SQL("admin");
		$sql->doQuery("UPDATE `" . $CONFIG['db_pagestable'] . "`" .
				  " SET `type` = '" . $this->type . "'," .
				  //(!isset($this->created) || $this->created == "" ? "`created`=NOW ( )", : "").
				  "`alias` = '" . mysql_real_escape_string($this->alias) . "'," .
				  "`title` = '" . mysql_real_escape_string($this->title) . "'," .
				  "`isdraft` = '" . intval($this->isdraft) . "'," .
				  "`tocpos` = '" . intval($this->tocpos) . "'," .
				  "`editors` = '" . mysql_real_escape_string($this->editors) . "'," .
				  "`attach_id` = '" . intval($this->attachId) . "'," .
				  "`lasteditedby` = '" . mysql_real_escape_string($this->lasteditedby) . "'," .
				  "`showtitle` = '" . intval($this->showtitle) . "'," .
				  "`keywords` = '" . mysql_real_escape_string($this->keywords) . "'," .
				  "`body` = '" . $this->htmlentities("UTF-8") . "'," .
				  "`ft_lastmodified` = '" . (!isset($this->ft_lastmodified) || $this->ft_lastmodified == "" ? "0000-00-00 00:00:00" : $this->ft_lastmodified) . "'," .
				  "`lastmodified` = NOW( ) WHERE `" . $CONFIG['db_pagestable'] . "`.`id` = '" . intval($this->pageid) . "' LIMIT 1 ;");
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		if ($this->updateAddQuery != "") {
			$sql->doQuery($this->updateAddQuery);
		}
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		return true;
	}

	function delete($forceid = null) {
		global $CONFIG;
		$sql = new SQL("admin");
		if (!$forceid)
			$forceid = $this->pageid;
		require_once $CONFIG['templates'] . "cm.ResourceTemplate.php";
		$attached = new ResourceCollection($forceid);
		$attached->realize();
		foreach ($attached->resources as $resource) {
			echo "Updating ID " . $resource->id . " has : " . $resource->attachId . "\n";
			if (!$resource->detachPageID($forceid))
				return false;
		}
		$sql->doQuery("DELETE FROM `" . $CONFIG['db_pagestable'] . "` WHERE `" . $CONFIG['db_pagestable'] . "`.`id` = $forceid");
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		return true;
	}

	function getDocumentObject($format) {
		$conv = new Convert();
		if ($format == "JS" || $format == "JSON") {
			$szHtml = "{\n" .
					  "\"title\":\"" . $this->title . "\",\n" .
					  "\"isdraft\":\"" . $this->isdraft . "\",\n" .
					  "\"alias\":\"" . $this->alias . "\",\n" .
					  "\"type\":\"" . $this->type . "\",\n" .
					  "\"id\":\"" . $this->pageid . "\",\n" .
					  "\"keywords\":\"" . $this->keywords . "\",\n" .
					  "\"attachId\":\"" . $this->attachId . "\",\n" .
					  "\"position\":\"" . $this->tocpos . "\",\n" .
					  "\"created\":\"" . ($this->created != "" ? $conv->timeToDate($this->created, "r") : "") . "\",\n" .
					  "\"lastmodified\":\"" . ($this->lastmodified != "" ? $conv->timeToDate($this->lastmodified, "r") : "") . "\",\n" .
					  "\"editors\":\"" . $this->editors . "\",\n" .
					  "\"creator\":\"" . $this->creator . "\",\n" .
					  "\"lasteditedby\":\"" . $this->lasteditedby . "\",\n" .
					  "\"showtitle\":\"" . $this->showtitle . "\"\n" .
					  "}";
		} else if ($format == "XML") {
			
		}
		return $szHtml;
	}

	function relevance($keywords, $delim = ",") {
		global $CONFIG;
		include_once $CONFIG['includes'] . "cm.URL.php";
		$curl = new URL("our-site.dk");
		$count = array();
		$total = 0;
		foreach (explode($delim, $keywords) as $word) {
			list($h, $b) = $curl->execute("POST", "http://our-site.dk/service/ressourcer/danske_soegeord.php", "inpord1=$word");
			if ($h['status'] != 200) {
				$_SESSION['error'] = "our-site.dk who services keyword hitcount gave wrong response";
				return false;
			}
			$begin = strpos($b, '<div class="sline">');
			$end = strpos($b, "</div>", $begin);
			$b = substr($b, $begin, $end - $begin);
			$begin = strrpos($b, "<i>");
			$end = strpos($b, "</i>", $begin);
			$b = substr($b, $begin, $end - $begin);
			$count[$word] = intval($b);
			$total += intval($b);
		}
		$html = "<table><tbody><tr><th title=\"google, msn, eniro, live, tdconline\">" .
				  "Søgninger per. måned på populære danske søgetjenester</th></tr>";
		foreach ($count as $w => $c) {
			$html .= "<tr><td>$w:</td><td>$c</td></tr>";
		}
		$html .= "<tr><td>Total der kunne ramme '" . $this->title . "':</td>" .
				  "<td>$total</td></tr></tbody></table>";
		return $html;
	}

	function updateViewPages() {
		/*
		  CREATE  OR REPLACE
		  ALGORITHM = TEMPTABLE
		  VIEW ViewPages(
		  unid,
		  modified,
		  uri,
		  owner
		  ) AS
		  SELECT id, lastmodified, alias, creator
		  FROM `pages`
		  WHERE `type` = 'page'
		  ORDER BY `pages`.`lastmodified` DESC
		 *
		 */
	}

}

class Blog extends DocumentCollection {

	var $html = "<link rel=\"stylesheet\" href=\"css/blog.css\" /><img src=\"gfx/eye.png\" class=\"blog-logo\"/><h3 class=\"blogCategory\">";
	var $editor = false;
	var $update = 0;
	var $adminHtml = "";
	var $blogid;
	var $blogtitle;
	var $conv;

	function append($html) {
		$this->html .= $html;
	}

	function __construct($blogDoc) {
		fb('ctor');
		$this->blogid = $blogDoc->pageid;
		$this->blogtitle = $blogDoc->title;
		$this->conv = new Convert();
		global $CONFIG;
		fb('hier', $CONFIG['includes']);
		if ($_SESSION['isLoggedIn']) {
			require_once $CONFIG['includes'] . "userProtocol.php";
			$this->editor = new USER();
			$this->adminHtml = '<span style="font-size:xx-small;"><a href="' . $CONFIG['editdocprefix'] . 'type=subpage&amp;id={ID}">edit</a>|<a href="javascript:openBlogEdit({ID});">quickedit</a></span>';
		}
		$this->update = 0;
		$this->dbSearch("`attach_id`=" . $this->blogid, 15, isset($_GET['offset']) ? $_GET['offset'] : null, 'created'/* ,"ASC" */);
		//$sql->doQuery("SELECT id from ". $CONFIG['db_pagestable'] ." WHERE `attach_id`='".$eval."' ORDER BY `created`");

		$this->append($this->blogtitle . "</h3><div class=\"blogWrapper\">");
		while (createItem($this->getNextDocument()));
		$this->append("<p class=\"blogDocCaption blogInfo\">" .
				  "<span class=\"footer\">&copy; " . $CONFIG['siteowner'] . " &copy; <br />");
		$this->append(" Sidste indlæg var: " . $this->conv->timeToDate(
							 $this->update, "l \d. j. M \k\l H:i", "da", false) . "</span></p></div>");
	}

	function createItem($item) {
		if ($item == null)
			return false;
		if ($item->lastmodified > $this->update)
			$this->update = $item->lastmodified;
		$alias = ucfirst(preg_match("/[rp][0-9][0-9][0-9][0-9]/", $item->alias) ? false : $item->alias);
		$this->append("<div class=\"blogDocWrapper\"><div class=\"blogDocWrapper-inner\">");
		$this->append("<p class=\"blogDocCaption\">");
		$this->append("<a href=\"" . $CONFIG['opendocprefix'] . "&amp;id=" . $item->pageid . "\">");
		$this->append("<span class=\"title\">" . $item->title . "</span>");
		$this->append("<span class=\"alias\">" . ($alias == false ? "" : " - " . $alias) . "</span></a>");

		if ($this->editor && ( $this->editor > username == $item->creator
				  || preg_match("/^(" . implode("|", explode(",", $item->editors)) . ")$/", $this->editor->username) )) {
			$this->append("<span style=\"float:right\">");
			$this->append(preg_replace("/{ID}/g", $item->pageid, $this->adminHtml));
			$this->append("</span><span style=\"clear:both;\"></span>");
		}

		$this->append("<br /><span class=\"editor\">Af: " . $item->creator . "</span>");
		$this->append("<abbr class=\"date\">" . $this->conv->timeToDate($item->created, "r") . "</abbr>");
		$this->append("<span style=\"clear:both;\">&thinsp;</span>");
		$this->append("</p><div class=\"blogDocBody\">" . $item->getHtml() . "</div>");
		$this->append("</div><!-- blogDoc item #" . $item->id . " --></div>");
		return true;
	}

	function getHtml() {
		return $this->html;
	}

}

//$db = new Database();
//$db->updateIndex(true);
/*
  $col->dbSearch("type='page'");
  $col->realize();
  $a = array();
  $a[] = $col->getFirstDocument();
  $a[] = $col->nextDocument();
  foreach($col->documents as $d) echo $d->title."\n";
  $col->deleteDocument($a);
  echo "c:".$col->getSize()."\n";
  foreach($col->documents as $d) echo $d->title."\n";
 */
?>
