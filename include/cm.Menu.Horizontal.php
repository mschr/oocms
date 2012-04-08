<?php
if(!class_exists("Menu")) require_once "cm.Menu.php";

class HBoxMenu extends Menu {

	var $cssfile = "css/horizontal-toc.css";
	var $jsfile = "include/menulib.js";
	var $jseval = "initMenu()";
	private $recurselvl = 0;

	public function load() {
		global $CONFIG;
		/**
		 * VBoxBegin
		 *   {1} : value of id attribute ( id="??" )
		 */
		$this->messages['VBoxBegin']='<div id="{0}">';
		$this->messages['VBoxEnd']='</div>';
		/**
		/*
		 * HBoxBegin
		 *   {0} : prefix to className ( class="??item-c" )
		 */
		$this->messages['HBoxBegin']='<div class="{0}item-wrap" style="float:left;">';
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
		/**
		  *  {0} : value of parentid attribute
		 */
		$this->messages['xLinkBegin'] = '<div id="dojoXLink" class="dojoXLink"><div class="headLink" parentid="{0}"><div class="fisheyeTarget"></div><div class="inner">';
		/**
		 * xLinkAnchorBegin
		 *  {0} : value of href attribute
		 *  {1} : value of parentid attribute
		 *  {2} : additional entry to className ( class="headLink ??" )
		 */
		$this->messages['xLinkAnchorBegin'] = '<a href="'.$this->urlprefix.'{0}" lvl="0" parentid="{1}" class="xLink {2}">';
		 /**
		  * xLinkAnchorEnd
		  *   {0} : innerHTML in anchor ( ..>??Click??here??</a> )
		  */
		$this->messages['xLinkAnchorEnd'] = '{0}</a>';
		$this->messages['xLinkEnd'] = '</div></div></div>';

		$this->messages['css'] = '<link rel="stylesheet" media="screen" href="{0}" />';
		$this->messages['js'] = '<script type="text/javascript" src="{0}" ></script>';
		$this->messages['jseval'] = '<script type="text/javascript">{0}</script>';
		// toplvl
		if($this->sql != null) {
			$this->sql->doQuery("SELECT id,title,alias,tocpos ".
			"FROM `".$CONFIG["db_pagestable"]."` ".
			"WHERE `isdraft`=0 AND `type`='page' ORDER BY tocpos ASC");
			while(($row = $this->sql->getNextRow("array")) != null)
			{
				array_push($this->top, $row);
			}
			asort($this->top);
		}
	}

	private function r_generate($id) {

		$children = $this->getChildrenOfId($id);
		if(count($children) == 0) {
			return "";
		}
		$classprefix = "";
		$html = "<div style=\"position:relative; background-color:white;\">";
		$oLvl = $this->recurselvl++;

		for($i = 1; $i <= $this->recurselvl; $i++)
		$classprefix .= "sub";

		foreach($children as $child)
		{
			if($child['child_id'] == "") continue;
//			echo "<pre>parent: ".$child['parent_id']." => child, ".$child['child_title']."(".$child['child_id'].")</pre>";
			$anchorAttr = "lvl=\"$this->recurselvl\"";
			$html .= $this->getMessage("VBoxBegin", "\" style=\"position:absolute;");
			$html .= $this->getMessage("anchorBegin", $anchorAttr , $child['child_id'], $classprefix);
			$html .= $this->getMessage("anchorEnd", $child['child_title']);
			$html .= $this->r_generate($child['child_id']);
			$html .= $this->getMessage("VBoxEnd");
		}
		$this->recurselvl = $oLvl;
		return $html . "</div>";

	}
	public function data_r_generate($children, $parentid) {
		$classprefix = "";
		$html = "";
		$oLvl = $this->recurselvl++;
		for($i = 1; $i <= $this->recurselvl; $i++) $classprefix .= "sub";

		foreach($children as $child)
		{
			$child->setId($this->childCounterId++);
			$anchorAttr = "lvl=\"$this->recurselvl\"".
			($child->onMouseOver != "" ? " onmouseover=\"".$child->onMouseOver."\"" : "").
			($child->onMouseOut != "" ? " onmouseout=\"".$child->onMouseOut."\"" : "").
			($child->onClick != "" ? " onclick=\"".$child->onClick."\"" : "").
			($firstlvl->title != "" ? " title=\"".$firstlvl->title."\"" : "");

			$html .= $this->getMessage("VBoxBegin");
			$html .= $this->getMessage("anchorBegin", $anchorAttr , $child->href, $classprefix);
			$html .= $this->getMessage("anchorEnd", $child->inner);
			$html .= $this->data_r_generate($child->children, $child->id);
			$html .= $this->getMessage("VBoxEnd");

		}
		$this->recurselvl = $oLvl;
		return $html;

	}
	public function generate() { return $this->generateFromDb(); }
	public function generateFromDb() {
		$html = $this->getMessage("css", $this->cssfile);
		$html.= $this->getMessage("js", $this->jsfile);
		//		if(count($this->top) == 0)  $this->load();

		$i = 0;
		$html .= "<div id=\"menuHeader\">";
		foreach($this->top as $firstlvl) {

			$html .= $this->getMessage("xLinkBegin",  $firstlvl['id']);
			$html .=  $this->getMessage("xLinkAnchorBegin", $firstlvl['id'], $firstlvl['id']);
			$html .= $this->getMessage("xLinkAnchorEnd", $firstlvl['title']);
			$html .= $this->getMessage("xLinkEnd");
			$i++;
		}
		$html .= "</div><!--menuheader end -->";
		$html .= "<div id=\"submenuHeader\">";

		foreach($this->top as $firstlvl) {
			$html .= $this->getMessage("VBoxBegin", "attached_id_".$firstlvl['id']."\" class=\"attachMenu");
			$html .= $this->r_generate($firstlvl['id']);
			$html .= $this->getMessage("VBoxEnd");
		}
		$html .= "</div></div></div><!--submenuheader end -->";

		return $html . $this->getMessage("jseval", $this->jseval);
	}
	public function generateFromData($mData) {
		$html = $this->getMessage("css", $this->cssfile);
		$html.= $this->getMessage("js", $this->jsfile);
		$this->setUrlPrefix("");
		$genericId = 0;

		$html .= "<div id=\"menuHeader\">";
		foreach($mData->mData as $firstlvl) {
			$firstlvl->setId($genericId++);
			$html .= $this->getMessage("xLinkBegin");
			if($firstlvl->title != "") $html .=  $this->getMessage("xLinkAnchorBegin", $firstlvl->href, "\" title=\"".$firstlvl->title."\"");
			else $html .=  $this->getMessage("xLinkAnchorBegin", $firstlvl->href);

			$html .= $this->getMessage("xLinkAnchorEnd", $firstlvl->inner);
			$html .= $this->getMessage("xLinkEnd");
		}
		$html .= "</div>";
		foreach($mData->mData as $firstlvl) {
			if(count($firstlvl->children) < 1) continue;
			$html .= $this->getMessage("VBoxBegin", "attached_id_".$firstlvl->id."\" class=\"attachMenu");
			$html .= $this->data_r_generate($firstlvl->children, $firstlvl->id);
			$html .= $this->getMessage("VBoxEnd");
		}
		$html .= "</div></div><!--menuheader end -->";
		return $html . $this->getMessage("jseval", $this->jseval);

	}
}
?>
