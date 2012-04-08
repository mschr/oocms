<?php
if(!class_exists("Menu")) require_once "cm.Menu.php";

class VBoxMenu extends Menu {


	var $cssfile = "css/vertical-toc.css";
	var $jsfile = "include/menulib.js";
	var $jseval = "initMenuElements()";
	private $recurselvl = 0;

	public function load($initializeDb = true) {
		global $CONFIG;
		/**
		 * VBoxBegin
		 *   {0} : prefix to className ( class="??item-c" )
		 *   {1} : value of id attribute ( id="??" )
		 */
		$this->messages['VBoxBegin']='<div class="vbox-wrap"><div class="{0}item-c" id="{1}">';
		$this->messages['VBoxEnd']='</div></div>';
		/**
		/*
		 * HBoxBegin
		 *   {0} : prefix to className ( class="??item-c" )
		 */
		$this->messages['HBoxBegin']='<div class="{0}item-wrap" style="text-align:left;">';
		$this->messages['HBoxEnd']='</div>';
		/*
		 * anchorBegin
		 *  {0} : value of lvl attribute ( lvl="??" )
		 *  {1} : postfix value of href attribute ( href="$urlprefix??" )
		 *  {2} : pretfix value of className ( class="??itemref" )
		 */
		$this->messages['anchorBegin']='<a {0} href="'.$this->urlprefix.'{1}" class="{2}itemref">';
		 /*
		  * anchorEnd
		  *   {0} : innerHTML in anchor ( ..>??Click??here??</a> )
		  */
		$this->messages['anchorEnd']='{0}</a>';

		$this->messages['css'] = '<link rel="stylesheet" media="screen" href="{0}" />';
		$this->messages['js'] = '<script type="text/javascript" src="{0}" ></script>';
		$this->messages['jseval'] = '<script type="text/javascript">{0}</script>';
		// toplvl
		if($this->sql && $initializeDb) {
			$this->sql->doQuery("SELECT id,title,alias,tocpos ".
			"FROM `".$CONFIG["db_pagestable"]."` ".
			"WHERE `isdraft`='0' AND `type`='page' ORDER BY tocpos ASC");
			while(($row = $this->sql->getNextRow("array")) != null)
			{
				array_push($this->top, $row);
			}
		}
	}

	public function r_generate($id) {
		$classprefix = "";
		$html = "<div class=\"subitems-bgwrap\">";
		$oLvl = $this->recurselvl++;

		$children = $this->getChildrenOfId($id);

		for($i = 1; $i <= $this->recurselvl; $i++)
		$classprefix .= "sub";

		foreach($children as $child)
		{
			if($child['child_id'] == "") continue;
			//echo "<pre>parent: ".$child['parent_id']." => child, ".$child['child_title']."(".$child['child_id'].")</pre>";
			$anchorAttr = "lvl=\"$this->recurselvl\" parentid=\"".$child['parent_id']."\"".
			($child['child_alias'] != "" ? " title=\"".$child['child_alias']."\"" : "");
			$html .= $this->getMessage("HBoxBegin");
			$html .= $this->getMessage("anchorBegin",  $anchorAttr, $child['child_id']."&amp;cat=$id");
			$html .= $this->getMessage("anchorEnd", $child['child_title']);
			$html .= $this->getMessage("HBoxEnd");
		}
		$this->recurselvl = $oLvl;
		return $html."</div>";

	}
	public function data_r_generate($children, $parentid) {
		$classprefix = "";
		$html = "";
		$oLvl = $this->recurselvl++;
		for($i = 1; $i <= $this->recurselvl; $i++) $classprefix .= "sub";

		foreach($children as $child)
		{
			//if($child->id == "") continue;
			//echo "<pre>parent: ".$child['parent_id']." => child, ".$child['child_title']."(".$child['child_id'].")</pre>";
			$anchorAttr = "lvl=\"$this->recurselvl\" parentid=\"$parent_id\"".
			($child->onMouseOver != "" ? " onmouseover=\"".$child->onMouseOver."\"" : "").
			($child->onMouseOut != "" ? " onmouseout=\"".$child->onMouseOut."\"" : "").
			($child->title != "" ? " title=\"".$child->title."\"" : "");
			$html .= $this->getMessage("HBoxBegin");
			$html .= $this->getMessage("anchorBegin",  $anchorAttr, $child->href);
			$html .= $this->getMessage("anchorEnd", $child->inner);
			$html .= $this->getMessage("HBoxEnd");
		}
		$this->recurselvl = $oLvl;
		return $html;

	}
	public function generate($tocpos = false) { 
		return $this->generateFromDb($tocpos); 
	}
	public function getTopCount() {
		return count($this->top);
	}
	public function generateFromDb($tocpos = false) {
		$html = ($this->cssfile != "" && $this->cssfile != false ? $this->getMessage("css", $this->cssfile) : "");
		if(!$_SESSION['staticMenu'] && $this->jseval) $html.= $this->getMessage("js", $this->jsfile);
		if($tocpos !== false) {
			$html .= $this->getMessage("VBoxBegin");
			$anchorAttr = 'lvl="'.$this->recurselvl.'"';
			$html .= $this->getMessage("anchorBegin", $anchorAttr, $this->top[$tocpos]['id']);
			$html .= $this->getMessage("anchorEnd", $this->top[$tocpos]['title']);
			$html .= $this->r_generate($this->top[$tocpos]['id']);
			$html .= $this->getMessage("VBoxEnd");
		}else
			foreach($this->top as $firstlvl) {
			
				$html .= $this->getMessage("VBoxBegin");
				$anchorAttr = "lvl=\"$this->recurselvl\"".
				($firstlvl->alias != "" ? " title=\"".$firstlvl->alias."\"" : "");
				$html .= $this->getMessage("anchorBegin", $anchorAttr, $firstlvl['id']);
				$html .= $this->getMessage("anchorEnd", $firstlvl['title']);
				$html .= $this->r_generate($firstlvl['id']);
				$html .= $this->getMessage("VBoxEnd");
			}

		return $html . (!$_SESSION['staticMenu']&& $this->jseval ? $this->getMessage("jseval", $this->jseval) : "");
	}

	public function generateFromData($mData = null) {
		if($mData == null) $mData = $this->top;
		$html =  ($this->cssfile != "" && $this->cssfile != false ? $this->getMessage("css", $this->cssfile) : "");
		if(!$_SESSION['staticMenu']) $html.= $this->getMessage("js", $this->jsfile);
		$this->setUrlPrefix("");
		$genericId = 0;
		foreach($mData->mData as $firstlvl) {

			$html .= $this->getMessage("VBoxBegin");
			$firstlvl->setId($genericId++);
			$anchorAttr = "lvl=\"$this->recurselvl\"".
			($firstlvl->onMouseOver != "" ? " onmouseover=\"".$firstlvl->onMouseOver."\"" : "").
			($firstlvl->onMouseOut != "" ? " onmouseout=\"".$firstlvl->onMouseOut."\"" : "").
			($child->onClick != "" ? " onclick=\"".$child->onClick."\"" : "").
			($firstlvl->title != "" ? " title=\"".$firstlvl->title."\"" : "");

			$html .= $this->getMessage("anchorBegin", $anchorAttr, $firstlvl->href);
			$html .= $this->getMessage("anchorEnd", $firstlvl->inner);
			if(count($firstlvl->children) > 0) $html .= $this->data_r_generate($firstlvl->children, $firstlvl->id);
			$html .= $this->getMessage("VBoxEnd");
		}

		return $html . (!$_SESSION['staticMenu'] ? $this->getMessage("jseval", $this->jseval) : "");

	}
}
?>
