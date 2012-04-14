<?php
require_once dirname(__FILE__) ."/../include/mysqlport.php";

function trimurl($url) {
	$max = 40;
	$len = ($length != null ? $length : $max);
	$s = $url;
	if (strlen($s) > $len) {
		$s = substr($s, 0, 12) . ".." . substr($s, strlen($s) - ($max - 12));
	}
	return $s;
}

class ResourceCollection {

	private $attachedIds = array();
	public $resources = array();
	private $pageid;
	private $includepath = "";
	private $internal = -1;
	function __construct($pageid = null) {
		if (!$pageid)
			return;
		global $CONFIG;

		$this->pageid = $pageid;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT id,attach_id FROM `{$CONFIG['db_resourcestable']}` " .
				  "WHERE FIND_IN_SET({$pageid},attach_id) > 0");
		while (($row = $sql->getNextRow("object")) != null) {
			if (in_array($pageid, explode(",", $row->attach_id))) {
				array_push($this->attachedIds, $row->id);
				array_push($this->resources, null);
			}
		}
	}

	function dbSearch($whereclause, $max = null, $offset = null, $order_column = null) {

		global $CONFIG;
		$sql = new SQL("reader");
		$q = "SELECT id FROM `" . $CONFIG['db_resourcestable'] . "` WHERE " .
				  "$whereclause " .
				  ($order_column != null ? " ORDER BY $order_column" : "") .
				  ($max != null ? " LIMIT $max" : "") .
				  ($offset != null && $max != null ? " OFFSET $offset" : "");
		$sql->doQuery($q, "object");

		$err = $sql->errMessage();
		if ($err != "") {
			echo "<br>\n<p>Info:<b>" . $sql->statusMessage() . "</b><br />$q</p>";
		}
		while (($row = $sql->getNextRow("object")) != null) {
			array_push($this->attachedIds, $row->id);
			array_push($this->resources, null);
		}
		$this->realize();
		return count($this->resources);
	}

	function realize() {
		$nth = 0;
		foreach ($this->attachedIds as $id) {
			$this->resources[$nth] = new Resource();
			$this->resources[$nth]->load($this->attachedIds[$nth++]);
		}
	}

	function loadAll() {
		$this->realize();
	}

	function getIncludeDir() {
		if (is_dir("../include")) {
			$this->includepath = "../include/";
		} else if (is_dir("include")) {
			$this->includepath = "include/";
		} else if (is_dir("../../include")) {
			$this->includepath = "../../include/";
		}
	}

	function load($nth) {
		if ($this->resources[$nth] == null) {
			$this->resources[$nth] = new Resource($this->attachedIds[$nth]);
		}
		return $this->resources[$nth];
	}
	function getSize() {
		return $this->nDocuments;
	}

	function getFirstDocument() {
		$this->internal = 0;
		if (count($this->resources) == 0)
			return null;
		return $this->resources[$this->internal];
	}

	function getLastDocument() {
		$this->internal = count($this->resources) - 1;
		if (count($this->resources) == 0)
			return null;
		return $this->resources[$this->internal];
	}

	function nextDocument() {
		if ($this->internal + 1 == count($this->resources)) {
			return null;
		}
		return $this->resources[++$this->internal];
	}

	function generateHTML() {
		$head = "";
		$incl = "";
		foreach ($this->resources as $res) {
			if ($res->type == "media") {
				$head .= $res->generateHTML() . "\n\n";
			} else if ($res->type == "include") {
				$incl .= $res->generateHTML() . "\n\n";
			}
		}
		return array($incl, $head);
	}

	function generateEditInfoHTML() {
		$html = "";
		foreach ($this->resources as $res) {
			$html .= $res->generateEditInfoHTML() . "\n\n";
		}
		return $html;
	}

}

class Resource {

	public $id;
	public $attachId;
	public $type;
	public $mimetype;
	public $uri;
	public $body;
	public $media;
	public $comment;
	public $alias;
	public $created;
	public $creator;
	public $lastmodified;
	private $includepath = "";

	function __construct($id = null) {
		$this->getIncludeDir();
		if ($id != null) {
			$this->load($id);
		}
	}

	private function generateAlias() {
		$s = "r";
		for ($i = 1; $i <= 16; $i++) {
			$s[$i] = rand(0, 9);
		}
		return $s;
	}

	function load($id) {

		global $CONFIG;
		$sql = new SQL("reader");
		$sql->doQuery("SELECT * FROM `" . $CONFIG['db_resourcestable'] . "` WHERE `id`='$id'");
		$data = $sql->getNextRow("object");

		$this->id = $id;
		$this->attachId = $data->attach_id; // pages id  referring to this
		$this->attachId = trim(preg_replace("/^[,]?(.*)[,]?$/", "$1", $this->attachId));
		$this->type = $data->type; // media / script / css
		$this->mimetype = $data->mimetype; // media / script / css
		$this->uri = $data->uri; // [if ! html] href src
		$this->body = $data->body; //[if html] contents
		$this->comment = $data->comment; // alt
		$this->alias = $data->alias;
		$this->created = $data->created;
		$this->creator = $data->creator;
		$this->lastmodified = $data->lastmodified;
	}

	function getIncludeDir() {
		if (is_dir("../include")) {
			$this->includepath = "../include/";
		} else if (is_dir("include")) {
			$this->includepath = "include/";
		} else if (is_dir("../../include")) {
			$this->includepath = "../../include/";
		}
	}

	function create($pageid, $mime, $uri = null) {

		global $CONFIG;
		global $user;
		$alias = $this->generateAlias();
		$sql = new SQL("admin");
		$sql->doQuery("INSERT INTO `" . $CONFIG['db_resourcestable'] . "` (" .
				  "`attach_id` ,`id` ,`creator` ,`created` ,`lasteditedby` ,`type` ,`mimetype` ,`uri` ,`alias`" .
				  ") VALUES (" .
				  "'$pageid', NULL , '{$user->userid}', NOW(), '{$user->username}', 'include', '$type', '$uri', '$alias');");

		$sql->doQuery("SELECT * FROM `" . $CONFIG['db_resourcestable'] . "` ORDER BY id DESC LIMIT 2");
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		$r = $sql->getNextRow("object");
		$this->load($r->id);
		$this->id = $r->id;
		return true;
	}

	function getUNID() {
		return $this->id;
	}

	function attachToPageID($pageid) {
		if (preg_match("/[,]?" . $pageid . "[,]?/", $this->attachId)) {
			return true;
		}
		$ids = split(",", $this->attachId);
		array_push($ids, $pageid);
		asort(&$ids);
		$this->attachId = join(",", $ids);
		$this->updateDb();
		return true;
	}

	function detachPageID($pageid) {
		if (!preg_match("/[,]?" . $pageid . "[,]?/", $this->attachId)) {
			return false;
		}
		$slice = explode(",", $this->attachId);
		array_splice($slice, array_search($this->id, $slice), 1);
		$this->attachId = implode(",", $slice);
		$this->updateDb();
		return true;
	}

	function updateDb() {

		global $CONFIG;
		global $user;
		$sql = new SQL("admin");
		$ok = $sql->doQuery("	UPDATE `" . $CONFIG['db_resourcestable'] . "` SET " .
				  "`attach_id`='" . $this->attachId . "'," .
				  "`media`='" . $this->media . "'," .
				  "`lasteditedby`='" . $user->username . "'," .
				  "`type`='" . $this->type . "'," .
				  "`mimetype`='" . $this->mimetype . "'," .
				  "`uri`='" . $this->uri . "'," .
				  "`alias`='" . $this->alias . "'," .
				  "`body`='" . htmlentities($this->body, ENT_QUOTES) . "'," .
				  "`comment`='" . $this->comment . "'" .
				  "  WHERE `" . $CONFIG['db_resourcestable'] . "`.`id` ='" . $this->id . "' LIMIT 1 ;");
		return $ok;
	}

	function generateHTML() {
		$html = "<!-- " . $this->type . "  | " . $this->comment . " | -->\n";
		if ($this->type == "media") {
			////////////////////////////////////
			$html .= '<div class="mediaResource" id="resource_' . $this->id . '">';
			$html .= $this->body;
			$html .= "</div>";
		} else if ($this->mimetype == "text/javascript") {
			////////////////////////////////////
			if ($this->body == "") {
				$html .= "\n<script id=\"resource_" . $this->id . "\" type=\"text/javascript\" href=\"" . $this->uri . "\"></script>\n";
			} else {
				$html .= "<script id=\"resource_" . $this->id . "\" type=\"text/javascript\">\n" . html_entity_decode($this->body, ENT_QUOTES, "utf-8") . "\n</script>\n";
			}
		} else if ($this->mimetype == "text/css") {
			////////////////////////////////////
			if ($this->body == "") {
				$html .= "\n<link rel=\"stylesheet\" id=\"resource_" . $this->id . "\" href=\"" . $this->uri . "\" />\n";
			} else {
				$html .= "<style  id=\"resource_" . $this->id . "\" type=\"text/css\" media=\"" . $this->media . "\">\n" . $this->body . "\n</style>\n";
			}
		}
		return $html;
	}

	function generateEditInfoHTML() {
		$html = "<!-- Resource info [" . $this->id . "] -->\n";
		//$html .= '<script type="text/javascript">function hidePreview(el) { var b = dojo.body(),node=el; while(node&&node != b) {console.log(node);if(node&&/preview/.test(node.className)) {return;};node=node.parentNode;}; el.style.display="none";};</script>';
		$html .= "<tbody class=\"mceLookFeel\" id=\"resource_" . $this->id . "\"><tr title=\"" . $this->comment . "\"><td>" .
				  '<div class="first last mceButtonStyle" style="font-size:0.92em; width: 84%;height:auto !important">' .
				  ( ($this->body == "") ?
							 '<label>Kilde:</label><br/>' .
							 '<span style="font-size:0.92em;white-space:nowrap;" class="urlformat">' . trimurl($this->uri) . '</span>' :
							 '<label>Type:</label><br/>' .
							 '<div>' . $this->mimetype . '<br />' .
							 "<a style=\"text-decoration:none;font-style:italic;float:right\" onclick=\"dojo.query('.preview', this.parentNode).style({display:'block'});\">(preview)</a>" .
							 '<pre class="preview" id="previewresource_' . $this->id . '">' .
							 ($this->mimetype == "text/html" ?
										html_entity_decode($this->body, ENT_QUOTES) :
										html_entity_decode(substr($this->body, 0, 220), ENT_QUOTES) . "\n    ...\n") .
							 "</pre>"
				  ) .
				  '</div><div style="clear:both;"></div></div>' .
				  "</td></tr>";
		$html .= "<tr><td align=\"center\">" .
				  "<span id=\"editResourceId_$this->id\">Redigér</span>" .
				  "<span id=\"deleteResourceId_$this->id\">Frigør</span>\n\n" .
				  "<script type=\"text/javascript\">\n" .
				  "var node = dojo.byId('editResourceId_$this->id');\n" .
				  "new dijit.form.Button({" .
				  "iconClass:'dijitEditorIcon dijitEditorIconSelectAll'," . //
				  "onClick:function() {editResource('$this->type', '$this->position', $this->id)}" .
				  "}, node).startup();\n" .
				  "    node = dojo.byId('deleteResourceId_$this->id');\n" .
				  "new dijit.form.Button({" .
				  "iconClass:'dijitEditorIcon dijitEditorIconDelete'," . // dijitEditorIconRedo
				  "onClick:function() {" .
				  "detachResource(" . $this->id . ",'" . $this->type . "','" . $this->attachId . "')}" .
				  "}, node).startup();\n" .
				  "dojo.connect(dojo.byId('previewresource_" . $this->id . "'), 'mouseout', function(e) {" .
				  "var c = { x:e.clientX||e.pageX, y:e.clientY||e.pageY};" .
				  "var cpre = dojo.coords(dojo.byId('previewresource_" . $this->id . "'));" .
				  "if(c.x-5 < cpre.x || c.x+5 > cpre.x+cpre.w || c.y-5 < cpre.y || c.y+5 > cpre.y+cpre.h)" .
				  "  dojo.byId('previewresource_" . $this->id . "').style.display='none';" .
				  "});" .
				  "</script>" .
				  "</td></tr></tbody>\n";
		return $html;
	}

	function getDocumentObject() {
		if (!class_exists("SQL"))
			require_once $this->includepath . "mysqlport.php";
		$conv = new Convert();
		$szHtml = "{\n" .
				  "\"alias\":\"" . $this->alias . "\",\n" .
				  "\"relation\":\"" . $this->mimetype . "\",\n" .
				  "\"id\":\"" . $this->id . "\",\n" .
				  "\"title\":\"" . ($this->mimetype == "text/javascript" ? "Script" : $this->mimetype == "text/css" ? "Stylesheet" : "Medie") . "\",\n" .
				  "\"attachId\":\"" . $this->attachId . "\",\n" .
				  "\"uri\":\"" . $this->uri . "\",\n" .
				  "\"media\":\"" . $this->media . "\",\n" .
				  "\"creator\":\"" . $this->creator . "\",\n" .
				  "\"created\":\"" . ($this->created != "" ? $conv->timeToDate($this->created, "r") : "") . "\",\n" .
				  "\"lastmodified\":\"" . ($this->lastmodified != "" ? $conv->timeToDate($this->lastmodified, "r") : "") . "\",\n" .
				  "\"lasteditedby\":\"" . $this->lasteditedby . "\",\n" .
				  "\"type\":\"" . $this->type . "\",\n" .
				  "\"comment\":\"" . $this->comment . "\"\n" .
				  "}";

		return $szHtml;
	}

	function delete($forceid = null) {

		global $CONFIG;
		$sql = new SQL("admin");
		$forceid = ($forceid != null) ? $forceid : $this->id;
		$sql->doQuery("DELETE FROM `" . $CONFIG['db_resourcestable'] . "` WHERE `" . $CONFIG['db_resourcestable'] . "`.`id` = $forceid");
		if ($sql->errMessage() != "") {
			$_SESSION['error'] = $sql->errMessage();
			return false;
		}
		return true;
	}

}

?>
