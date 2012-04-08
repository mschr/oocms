<?php
/**
 * The Menu class looks up in database for child-parent relations in a table-of-contents<br>
 * Its interface is much alike the java-bean ActionMessage and the 'message' structure is
 * initialised in the <i>load</i> method.<br><ul><li>
 * Previous to calling <i>load</i> set nescessary urlprefix variable.</li>
 *<li> Previous to calling <i>generate</i> set script, css and if needed, a final js evaluation command or functioncall.</li>
 * </ul>
 */
class Menu {
	protected $sql = null;
	protected $top = array();
	protected $messages;

	public $urlprefix = "";

	public function __construct($data = false) {
		global $CONFIG;
		$this->cssfile = $CONFIG['relurl'].$this->cssfile;
		$this->jsfile = $CONFIG['relurl'].$this->jsfile;
		if($data == false) {
			if(!class_exists("SQL")) require_once "mysqlport.php";
			$this->sql = new SQL("admin");
		} else {
			$this->top = $data;
		}
	}

	private function getValue($id) {
		$assoc = split("\.", $id);
		$msg = "";
		$tmp = null;
		foreach($assoc as $ass)
		{
			if($tmp == null)
			$tmp = $this->messages[$ass];
			else
			$tmp = $tmp[$ass];
		}
		return $tmp;
	}
	protected function getMessage($msgid, $param1=null, $param2=null, $param3=null) {
		$msg = $this->getValue($msgid);
		if($param1 != null)
		$msg = preg_replace("/".preg_quote("{0}")."/", $param1, $msg);
		if($param2 != null)
		$msg = preg_replace("/".preg_quote("{1}")."/", $param2, $msg);
		if($param3 != null)
		$msg = preg_replace("/".preg_quote("{2}")."/", $param3, $msg);
		// trim unused parameters
		$msg = preg_replace("/\{[0-9]+\}/", "", $msg);
		return $msg;
	}


	protected function getChildrenOfId($id) {
		global $CONFIG;
		$subs = array();
/*		$this->sql->doQuery("SELECT a.id AS parent_id,a.title as parent_title,b.id as child_id,b.title as child_title,b.alias as child_alias, b.tocpos as child_pos ".
			"FROM `pages` as a ".
			"LEFT JOIN `pages` b ".
			"ON (b.attach_id=a.id) ".
			"WHERE a.id='$id' AND b.isdraft=0 ORDER BY b.tocpos ASC");
*/
		$this->sql->doQuery("SELECT id as child_id,title as child_title,alias as child_alias ".
			"FROM `".$CONFIG['db_pagestable']."` ".
			"WHERE `attach_id`='$id' AND `isdraft`=0 ORDER BY tocpos ASC");
		while(($row = $this->sql->getNextRow("array")) != null)
		{
			array_push($subs, $row);
		}
		return $subs;
	}

	public function getToplevelEntries() { return $this->top; }
	public function setUrlPrefix($pre) { $this->urlprefix = $pre; }
	public function setCSS($css) { $this->cssfile = $css; }
	public function setScript($js) { $this->jsfile = $js; }
	public function setScriptInitMethod($eval) { $this->jseval = $eval; }

}
function GenerateMenuHTML($horix = false) {
	$theMenu = new Menu($horix);
	$theMenu->load();
	return $theMenu->generate();
}

	/**
	 * @param inner innerHTML of the anchor
	 * @param href anchor links-to this
	 * @param title mouseover title
	 * @param onClick DOMEvent click on anchor
	 * @param onMouseOver DOMEvent mouseover on anchor
	 * @param onMouseOut DOMEvent mouseout on anchor
	 * @param id Integer value, unique ID
	 * @param children Array of DB-rows og MenuItems
	 */
class MenuItem {
	public $inner = "";
	public $href = "";
	public $title = "";
	public $onClick = "";
	public $onMouseOver = "";
	public $onMouseOut = "";
	public $id = "";
	public $children = array();


	function __construct($html, $eval, $title = "", $click = "") {
		$this->inner = $html;
		$this->href = $eval;
		$this->title = $title;
		$this->onClick = $click;
	}
	function setId($id) { $this->id = $id; }
	function addTitle($t) { $this->$title = $t; }
	function addOnMouseOver($sc) { $this->$onMouseOver = (strlen($this->onMouseOver) > 0 && $this->onMouseOver[strlen($this->onMouseOver)]!=';' ? ";":"") . $sc . ";"; }
	function addOnMouseOut($sc)  { $this->$onMouseOut  = (strlen($this->onMouseOut)  > 0 && $this->onMouseOut[strlen($this->onMouseOut)]!=';' ? ";":"") . $sc . ";"; }
	function addChild($ch) { array_push($this->children, $ch); }
}
class MenuData {
	public $mData = array();

	public function evaluateHeight($prline) {
		$h = 0;
		$childcount = array();

		foreach($this->mData as $top) {
			array_push($childcount, count($top->children));
			$h += $prline;
		}
		asort($childcount);
		if($_SESSION['staticMenu']) {
			foreach($childcount as $c) $h += $c*$prline;
		} else {
			if(count($childcount) > 0) {
				$h += $childcount[0]*$prline + $prline;
			}
			if(count($childcount) > 1) {
				$h += $childcount[1]*$prline + $prline;
			}
		}
		return $h;
	}
	private function addItem($item) { array_push($this->mData, $item); }
	public function addTopLevel($item) { $this->addItem($item); }
	public function addChild($toObj, $item) {

		if(get_class($toObj) == "MenuItem") {

			foreach($this->mData as $tLvl) {
				if($tLvl === $toObj) {
					$tLvl->addChild($item);
					return true;
				}
			}
			return false;


		} else if(is_numeric($toObj)) {

			if(count($this->mData) < $toObj) return false;
			$this->mData[$toObj]->addChild($item);
			return true;

		}
		return false;
	}
}
?>
