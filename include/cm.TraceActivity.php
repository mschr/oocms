<?php

class HistoryManager {

	public $history;
	private $maxEntries = 12;
	private $currentPos;
	private $_internal;

	function __construct() {
		$this->history = array();
		$this->currentPos = -1;  // one less...
	}	
	function first() {
		$this->_internal = count($this->history) - 1;
		return $this->history[$this->_internal];
	}
	function next() {
		$this->_internal--;
		if($this->_internal < 0)
			return null;
		return $this->history[$this->_internal];
	}
	function addItem($title, $id, $cat) {
		$this->currentPos++;
		array_push($this->history, array(
				'title'=>$title,
				'id'=>$id,
				'cat'=>$cat
			)
		);
		if(count ($this->history) > $this->maxEntries) {
			array_shift(&$this->history);
		}
	}
	function getPosition() { return $this->currentPos; }
	function getCurItem() {	return $this->history[$this->currentPos]; }
	function getHistory() { return $this->history; }

	function clear() { $this->history = array(); }
	function goBack() {
		//echo "goback";
		$this->currentPos--;
		if($this->currentPos < 0) $this->currentPos = 0;
		return $this->history[$this->currentPos];
	}
	function goFwd() {
		//echo "gofwd";
		$this->currentPos--;
		if($this->currentPos > count($this->history)) $this->currentPos = count($this->history);
		return $this->history[$this->currentPos];
	}
}

class TraceActivity {

	var $currenCategory;
	var $currentPage= array('id'=>'', 'title'=>'');
	var $currentView;
	var $clientIP;

	private $historyMgr;

	function __construct() {
		$this->historyMgr = new HistoryManager();
		$this->clientIP = $_SERVER['REMOTE_ADDR'];
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
	}

	function getManager() { return $this->historyMgr; }


	function setCurPage($id, $title) {
		$this->currentPage['id'] = $id;
		$this->currentPage['title'] = $title;
		$pos = $this->getManager()->getPosition();
		//echo $this->historyMgr->history[($pos+1)]['title']."<br>";
		//echo $id. " <> " .$this->historyMgr->history[$pos]['id'];
//		if($id!=$this->historyMgr->history[$pos]['id']) { // if cur
//			if($id==$this->historyMgr->history[($pos+1)]['id']) {
//			//	echo "FREM";
//				$this->historyMgr->goFwd();
//			} else if ($id==$this->historyMgr->history[($pos-1)]['id']) {
//			//	echo "TILBAGE";
//				$this->historyMgr->goBack();
//			} else {
				//if ($id==$this->historyMgr->history[($pos+1)]['id']) $this->historyMgr->goFwd();
				//else
			//	echo "NY";
			if($id!=$this->historyMgr->history[$pos]['id'])  // if cur
				$this->historyMgr->addItem($title, $id, $this->currentCategory);
//			}
//		}
	}
	function setCurView($view) { 
		if($this->currentView == $view) return false;
		$this->currentView = $view; 
		return true;
	}
	function setCurCategory($cat) { 
		if($this->currentCategory == $cat) return false;
		$this->currentCategory = $cat; 
		return true;
	}
	function updateDb() {
		//...
	}
}
?>
