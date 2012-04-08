<?php
if(!class_exists("Menu")) require_once "cm.Menu.php";

class ButtonMenu extends Menu {


	var $cssfile = "css/actionbuttons.css";
	var $jsfile = "include/menubuttons.js";
	private $recurselvl = 0;

	public function load() {
		/**
		 * VBoxBegin
		 *   {0} : prefix to className ( class="??item-c" )
		 *   {1} : value of id attribute ( id="??" )
		 */
		$this->messages['VBoxBegin']='<table style="{0}" cellspacing="0" cellpadding="0" border="0" class="actionBarButton1"><tbody><tr><td class="left"><img width="4" height="4" border="0" alt="" src="{1}gfx/transparent.gif"/></td><td onmouseout="onBBBout(this);" onmouseover="onBBBover(this);" class="middle">';
		/**
		 * VBoxEnd
		*/
		$this->messages['VBoxEnd']='</td><td class="right"><img width="4" height="4" border="0" alt="" src="{0}gfx/transparent.gif"/></td></tr></tbody></table>';

		/*
		 * anchorBegin
		 *  {0} : value of lvl attribute ( lvl="??" )
		 *  {1} : postfix value of href attribute ( href="$urlprefix??" )
		 *  {2} : pretfix value of className ( class="??itemref" )
		 */
		$this->messages['anchorBegin']='<a {0} href="'.$this->urlprefix.'{1}" class="actionText1 {2}">';
		 /*
		  * anchorEnd
		  *   {0} : innerHTML in anchor ( ..>??Click??here??</a> )
		  */
		$this->messages['anchorEnd']='{0}</a>';

		$this->messages['css'] = '<link rel="stylesheet" media="screen" href="{0}" />';
		$this->messages['js'] = '<script type="text/javascript" src="{0}" ></script>';
		$this->messages['jseval'] = '<script type="text/javascript">{0}</script>';

	}

	public function data_r_generate($children, $parentid) {
		require_once "cm.CommonFunctions.php";
		global $CONFIG;
		$html = "";

		foreach($children as $child)
		{
			$anchorAttr = ($child->onMouseOver != "" ? " onmouseover=\"".$child->onMouseOver."\"" : "").
			($child->onMouseOut != "" ? " onmouseout=\"".$child->onMouseOut."\"" : "").
			($child->onClick != "" ? " onclick=\"".$child->onClick."\"" : "").
			($firstlvl->title != "" ? " title=\"".$firstlvl->title."\"" : "");
			$html .= $this->getMessage("VBoxBegin", "margin: 0px 5px;width:195px", $CONFIG['relurl']);
			$html .= $this->getMessage("anchorBegin", $anchorAttr, $child->href);
			$html .= $this->getMessage("anchorEnd", $child->inner);
			$html .= $this->getMessage("VBoxEnd", $CONFIG['relurl']);
		}
		return $html;

	}

	public function generateFromData($mData = null) {
		require_once "cm.CommonFunctions.php";
		global $CONFIG;

		if($mData == null) $mData = $this->top;
		if($mData == null) throw new Exception("No data to process...");
		$html = $this->getMessage("css", $this->cssfile);
		$html.= $this->getMessage("js", $this->jsfile);
		$this->setUrlPrefix("");
		foreach($mData->mData as $firstlvl) {
			$anchorAttr = ($child->onMouseOver != "" ? " onmouseover=\"".$child->onMouseOver."\"" : "").
			($child->onMouseOut != "" ? " onmouseout=\"".$child->onMouseOut."\"" : "").
			($child->onClick != "" ? " onclick=\"".$child->onClick."\"" : "").
			($firstlvl->title != "" ? " title=\"".$firstlvl->title."\"" : "");
			$html .= $this->getMessage("VBoxBegin", "width:204px", $CONFIG['relurl']);
			$html .= $this->getMessage("anchorBegin",  $anchorAttr, $firstlvl->href);
			$html .= $this->getMessage("anchorEnd", $firstlvl->inner);
			$html .= $this->getMessage("VBoxEnd", $CONFIG['relurl']);

			if(count($firstlvl->children) > 0) $html .= $this->data_r_generate($firstlvl->children, $firstlvl->id);
		}

		return $html;

	}
}
?>
