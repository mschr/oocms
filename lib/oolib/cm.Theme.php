<?php

require_once $CONFIG['templates'] . "cm.DocumentTemplate.php";
require_once $CONFIG['templates'] . "cm.ResourceTemplate.php";
require_once dirname(__FILE__) . "/cm.Route.php";

define('HTML_GENERATOR_WS', "    ");
define('HTML_GENERATOR_PRETTYHTML', 1);

class NodeList {

	public $region = null;
	private $children = null;
	private $count = null;

	function __construct($region) {
		$this->region = $region;
		$this->children = array();
	}

	public function addChild(Node $n) {
		array_push($this->children, $n);
		$this->count++;
		return $this;
	}

	public function hasChildren() {
		return $this->count > 0;
	}

	public function getChildrenCount() {
		return $this->count;
	}

	public function getChildren() {
		return $this->children;
	}

	public function generateHtml($level = 0) {
		$indent = str_repeat(HTML_GENERATOR_WS, $level);
		$szHtml = "";
		foreach ($this->children as $child)
			$szHtml .= $child->generateHTML($level + 1);
		return $szHtml;
	}

}

class Node {

	protected $selfclosing = false;
	protected $tagName = null;
	protected $innerHTML = "";
	protected $children = null;
	protected $count = null;
	protected $parent = null;

	// static instance factory, solely for use with
	// $html = Node::create($args)        and then giving the opportunity to do chaining
	//    -> setAttribute("foo","bar")
	//    -> generateHtml();
	//    
	// instead of
	// 
	// $node = new Node($args);
	// $html = $node -> setAttribute("foo", "bar")
	//     -> generateHtml();
	public static function create($name, $attr = array(), $inner = "", $selfclosing = false) {
		// return new factored instance
		return new Node($name, $attr, $inner, $selfclosing);
	}

	/**
	 *
	 * @param type $name
	 * @param type $attr
	 * @param type $inner
	 * @param type $selfclosing 
	 */
	public function __construct($name, $attr = array(), $inner = "", $selfclosing = false) {
		$this->tagName = $name;
		$this->attributes = $attr;
		$this->innerHTML = $inner;
		$this->selfclosing = $selfclosing;
		$this->children = array();
		$this->count = 0;
	}

	public function addChild(Node $n) {
		if ($this->selfclosing == true)
			throw new Exception("Cannot add childNodes to this node ({$this->tagName})");
		$n->parent = $this;
		array_push($this->children, $n);
		$this->count++;
		return $this;
	}

	public function hasChildren() {
		return $this->count > 0;
	}

	public function firstChild() {
		if ($this->count > 0)
			return $this->children[0];
		return null;
	}

	public function lastChild() {
		if ($this->count > 0)
			return $this->children[$this->count - 1];
		return null;
	}

	public function getChildrenCount() {
		return $this->count;
	}

	public function getChildren() {
		return $this->children;
	}

	public function getParent() {
		return $this->parent;
	}

	private function isComment() {
		return ($this->tagName == "comment") ? "<!-- {$this->innerHTML} -->\n" : false;
	}

	public function generateHtml($level = 0) {
		if (!isset($this->tagName))
			throw new Exception("Invalid node context");
		$indent = str_repeat(HTML_GENERATOR_WS, $level);
		$isComment = $this->isComment();
		if ($isComment)
			return $indent . $isComment;
		$szHtml = "";
		$attr = "";
		$len = count($this->attributes);
		$i = 0;
		foreach ($this->attributes as $k => $v) {
			$attr .= "$k=\"$v\"" . ($i < $len - 1 ? " " : "");
			$i++;
		}
		$this->innerHTML = trim($this->innerHTML);
		if ($this->tagName == "script" && empty($this->attributes['src'])) {
			$this->innerHTML = "// <![CDATA[\n{$this->innerHTML}\n// ]]>";
		}
		$szHtml = "$indent<{$this->tagName}" . (!empty($attr) ? " $attr" : "");
		if (!$this->selfclosing) {
			$szHtml .= ">";
			if (!empty($this->innerHTML)) {
				if (HTML_GENERATOR_PRETTYHTML) {
					$lf = "\n$indent";
					$lf .= HTML_GENERATOR_WS;
					$szHtml .= $lf . implode($lf, explode("\n", $this->innerHTML)) . "\n$indent";
				} else
					$szHtml .= $this->innerHTML . "\n$indent";
			} else {
				$szHtml .= "\n";
				foreach ($this->children as $child)
					$szHtml .= $child->generateHTML($level + 1);
			}
			$szHtml .= "</{$this->tagName}>\n";
		} else {
			$szHtml .= "/>\n";
		}
		return $szHtml;
	}

	public function addAttribute($name, $val) {
		$val = (string) $val;
		if (!empty($val)) {
			$this->attributes[$name] = $val;
		}
		return $this;
	}

	public function setTagName($name) {
		$this->tagName = $name;
		return $this;
	}

	public function setInnerHTML($html) {
		if (!$this->selfclosing)
			$this->innerHTML = $html;
		return $this;
	}

}

class MetaEquiv extends Node {

	public static function create($httpequiv, $content) {
		// return new factored instance
		return new MetaEquiv($httpequiv, $content);
	}

	public function __construct($httpequiv, $content) {
		parent::__construct("meta", array('http-equiv' => $httpequiv, 'content' => $content), null, true);
	}

}
class MetaTag extends Node {

	public static function create($name, $content) {
		// return new factored instance
		return new MetaTag($name, $content);
	}

	public function __construct($name, $content) {
		parent::__construct("meta", array('name' => $name, 'content' => $content), null, true);
	}

}

class ScriptTag extends Node {

	public static function create($type, $src = null, $inner = "") {
		// return new factored instance
		return new ScriptTag($type, $src, $inner);
	}

	/**
	 *
	 * @param type $type
	 * @param type $src 
	 */
	public function __construct($type, $src = null, $inner = "") {
		parent::__construct("script", array('type' => $type), $inner);
		if ($src != null)
			$this->addAttribute('src', $src);
	}

}

class LinkTag extends Node {

	public static function create($rel, $href, $type = null) {
		// return new factored instance
		return new LinkTag($rel, $href, $type);
	}

	public function __construct($rel, $href, $type = null) {
		parent::__construct("link", array('rel' => $rel, 'href' => $href), null, true);
		if ($type != null)
			$this->addAttribute('type', $type);
	}

}

class TitleTag extends Node {

	public static function create($title) {
		// return new factored instance
		return new TitleTag($title);
	}

	public function __construct($title) {
		parent::__construct("title", array(), $title);
	}

}

class SmartyParser {

	private $tags = array(
		 "LOADSUB",
		 "LOADPROD",
		 "LOADCAT",
		 "PRIMARY_MENU",
		 "SECONDARY_MENU"
	);
	private $query = null;
	private $theme = null;

	public function __construct() {
		$this->query = Theme::parseRequestParameters($_REQUEST);
		$this->theme = Theme::getInstance();
	}
/////////////// TODO FIX PARSER TO INCLUDE MULTI-TAG IN SAME BODY (rebuild body pr foreach-tag)
	
	public function parse($document = null) {
		$doc = ($document == null ? Theme::$document : $document);
		if ($doc instanceof Document)
			$body = $doc->getHtml();
		else if ($doc instanceof Node)
			$body = $doc->generateHtml();
		else
			$body = $doc;
		$match = "/(<" . implode(">|<", $this->tags) . ">)/";
		/*
		  if ((!isset($this->query['category_doc_id'])
		  || $this->query['category_doc_id'] == $this->query['body_doc_id'])
		  && Theme::$document == Theme::$categoryDoc
		  && preg_match($match, $body)) {
		 */
		$newbody = "";
		foreach ($this->tags as $utag) {
			$szPos = 0;
			if (!preg_match_all("/<{$utag}>([a-zA-Z0-9]*)<\/{$utag}>/msi"
								 , $body, $matchesarray, PREG_OFFSET_CAPTURE))
				continue;
			$i = 0;
			$smarty_tag_offset = 0;
			$smarty_tag_length = 0;
			foreach ($matchesarray[0] as $match) {
				$smarty_tag_length = strlen($match[0]);
				$smarty_tag_offset = $match[1];
				$eval = $matchesarray[1][$i][0];
				switch ($utag) {

					case "LOADSUB":
						$szHtml = $this->smartDocument($eval);
						break;
					case "LOADPROD":
						$szHtml = $this->smartProduct($eval);
						break;
					case "LOADCAT":
						$szHtml = $this->smartProductCategory($eval);
						break;
					case "PRIMARY_MENU":
						fb($matchesarray, $smarty_tag_length);
						$szHtml = $this->smartMenu($eval);
						break;
					case "SECONDARY_MENU":
						$szHtml = $this->smartMenu($eval, false);
						break;
				}
				$newbody .= substr($body, $szPos, $smarty_tag_offset - $szPos) . $szHtml;
				$lastPos = $szPos = $smarty_tag_offset + $smarty_tag_length;
				$i++;
			}
		}
		return!empty($newbody) ? $newbody . substr($body, $lastPos) : $body;
	}

	private function smartDocument($eval) {
		if (strtolower($eval) == "closest") {
			$r = Theme::$categoryDoc->getChildrenDocuments(1);
			if ($r)
				$subdoc = $r->getFirstDocument();
			unset($r);
		} else {
			$subdoc = new Document(intval($eval));
		}
		// recurse into subdoc
		if ($this->theme->renderDocumentsOnTheme) {
			return $this->theme->parser_renderDocumentBody($subdoc);
		} else {
			return $this->parse($subdoc);
		}
	}

	private function smartMenu($eval, $primary = true) {
		$horizontal = true;
		$id = -1;
		foreach (explode(",", $eval) as $param) {
			if (strstr("vertical", $param))
				$horizontal = false;
			if (strstr("id=", $param)) {
				$id = explode("=", $param);
				$id = intval($param[1]);
			}
		}
		if (!$primary) {
			if ($id == -1)
				$subdoc = Theme::$document;
			else
				$subdoc = new Document($id);
			$menu_doccollection = $subdoc->getChildrenDocuments();
		} else {
			$menu_doccollection = new DocumentCollection("page", false);
			$menu_doccollection->dbSearch("`attach_id`='0' AND `isdraft`!='1'");
		}
		return $this->theme->renderDocumentMenu($menu_doccollection, $horizontal);
	}

	private function smartProduct($eval) {
		if (!class_exists("Product"))
			require_once $CONFIG['templates'] . "cm.ProductTemplate.php";
		$subdoc = new Product(intval($eval));
		return $this->theme->renderProduct(&$subdoc);
	}

	private function smartProductCategory($eval) {
		if (!class_exists("Product"))
			require_once $CONFIG['templates'] . "cm.ProductTemplate.php";
		$collection = new ProductCollection("category");
		$collection->dbSearch("`category`='" . mysql_real_escape_string($eval) . "'");
		return $this->theme->renderProductCollection($eval, &$collection);
	}

}

class Theme {

	private static $base_regions = array('head', 'top', 'section', 'footer', 'bottom');
	// placeholder of page contents
	// may be divided into areas, and customized by theme
	// there is a base layout if not overridden, consiting of following regions:
	//<head>
	// <          htmlhead           >
	//</head>
	//<body>
	// <-          header           ->
	// < left > <  middle  > < right >
	// <-          footer           ->
	//</body>
	private static $content = array();

	/**
	 * references the parent category, used for retreiving includes
	 * @var DocumentTemplate the document instance of the highest hierachial 'page'
	 */
	public static $categoryDoc = null;

	/**
	 * referemces the currently active page, be it 'page' or 'subpage'
	 * @var DocumentTemplate 
	 */
	public static $document = null;
	public static $onloadregistry = null;

	/**
	 * Theme class is supposed to be used as a static facility with transitional
	 * syntax, to be used where contextual nescessary
	 * @var Theme static instance returned by calling getInstance()
	 */
	private static $instance = null;

	/**
	 *
	 * @var int rendered menus for incrementing id's
	 */
	private static $renderedMenuCount = 0;

	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * Add content to a specified region.
	 *
	 * @param $region
	 *   Page region the content is added to.
	 * @param $data
	 *   Content to be added.
	 */
	function addRegion($region = NULL, $data = NULL) {
		if (isset($region) && isset($data)) {
			self::$content[$region][] = $data;
		}
		return self::$content;
	}

	/**
	 * Get assigned content for a given region.
	 *
	 * @param $region
	 *   A specified region to fetch content for. If NULL, all regions will be
	 *   returned.
	 * @param $delimiter
	 *   Content to be inserted between imploded array elements.
	 */
	function getRegion($region = NULL, $delimiter = ' ') {
		$content = $this->addRegion();
		if (isset($region)) {
			if (isset($content[$region]) && is_array($content[$region])) {
				return implode($delimiter, $content[$region]);
			}
		} else {
			foreach (array_keys($content) as $region) {
				if (is_array($content[$region])) {
					$content[$region] = implode($delimiter, $content[$region]);
				}
			}
			return $content;
		}
	}

	function renderNotFoundPage() {
		// TODO implement
	}

	function renderAccessDeniedPage() {
		// TODO implement
	}

	public static function parseRequestParameters($request) {
		// FIXME move to relevant class (router)
		$query = array();
		foreach ($request as $k => $v) {
			if ($k == 'id')
				$query['body_doc_id'] = $v;
			else if ($k == 'cat')
				$query['category_doc_id'] = $v;
			else if ($k == 'returnUrl')
				$query['return_url'] = $v;
		}
		return $query;
	}

	public static function renderAdminHTMLHead() {
		global $CONFIG;
		$root = new NodeList("head");
		$n = null;
		// render charset first to avoid possible xss injection on older IE
		$root->addChild(MetaEquiv::create("Content-Type", "text/html;charset=".($CONFIG['db_charset'] == "utf8" ? "UTF-8" : $CONFIG['db_charset'])))
				  ->addChild(TitleTag::create("OoCmS Websitemanager - Administration Interface"))
				  // contents descriptions
				  ->addChild(MetaTag::create("keywords", "OoCmS Websitemanager, content management, cms, editor, administation, backend"))
				  ->addChild(MetaTag::create("description", "OoCmS Websitemanager"));
		// site wide scripts (convenience functions and dojo tooklit)
		$root->addChild(Node::create("comment", null, "Script Toolkit"))
				  ->addChild(Node::create("script", array(
								  "type" => "text/javascript",
								  "data-dojo-config" => "parseOnLoad:false,async:true,locale: 'da-dk'",
								  "src" => "{$CONFIG['dojoroot']}" . "dojo/dojo.js"), "", false))
				  ->addChild(ScriptTag::create("text/javascript", "{$CONFIG['relurl']}" .
										"include/prototyping.js"))
				  ->addChild(ScriptTag::create("text/javascript", null, "")
							 ->setInnerHTML("var gPage = {\n\tbaseURI:'{$CONFIG['relurl']}'\n};\n"))
				  ->addChild(Node::create("comment", null, "Application bootstrap"))
				  ->addChild(ScriptTag::create("text/javascript", "{$CONFIG['relurl']}" .
										"admin/include/bootstrap.js"));
		// add reset global stylesheet
		$root->addChild(Node::create("comment", null, "Styles"))
				  ->addChild(LinkTag::create("stylesheet", "{$CONFIG['css']}oocms.css", "text/css")
							 ->addAttribute("media", "all"))
				  ->addChild(LinkTag::create("stylesheet", "{$CONFIG['relurl']}" .
										"admin/resources/admin.css", "text/css"))
				  ->addChild(LinkTag::create("stylesheet", "{$CONFIG['dojoroot']}" .
										"dijit/themes/{$CONFIG['dojotheme']}/{$CONFIG['dojotheme']}.css", "text/css"))
				  ->addChild(LinkTag::create("stylesheet", "{$CONFIG['dojoroot']}" .
										"dojo/resources/dnd.css", "text/css"));
		return $root->generateHtml();
	}

	function renderGeneralHTMLHead() {
		// TODO add 'css' to config generator
		global $CONFIG;
		$doc = self::$categoryDoc;
		$body = self::$document;


		$pageid = $body->pageid;
		$title = $body->title;
		$keywords = array();
		foreach (explode(",", $CONFIG['keywords'] . ", " . $doc->keywords . ($doc !== $body ? ", {$body->keywords}" : "")) as $kword) {
			$kword = trim($kword);
			if (!empty($kword) && !in_array($kword, $keywords))
				array_push($keywords, $kword);
		}
		$root = new NodeList("head");
		$n = null;
		// render charset first to avoid possible xss injection on older IE
		$root->addChild(MetaEquiv::create("Content-Type", "text/html;charset=".($CONFIG['db_charset'] == "utf8" ? "UTF-8" : $CONFIG['db_charset'])))
				  ->addChild(TitleTag::create($title))
				  // add reset global stylesheet
				  ->addChild(LinkTag::create("stylesheet", "{$CONFIG['css']}oocms.css", "text/css")
							 ->addAttribute("media", "all"));
		// contents descriptions
		$root->addChild(Node::create("comment", null, "Robots contents description"))
				  ->addChild(LinkTag::create("DC.Identifier", "http://purl.org/dc/elements/1.1/"))
				  ->addChild(MetaTag::create("keywords", implode(",", $keywords)))
				  ->addChild(MetaTag::create("description", $CONFIG['description']))
				  ->addChild(MetaTag::create("DC.Title", $title))
				  ->addChild(MetaTag::create("DC.Date", date('Y-m-d', time() - 259200)))
				  ->addChild(MetaTag::create("DC.Type", "Service"))
				  ->addChild(MetaTag::create("DC.Creator", "mSigsgaard.dk"))
				  ->addChild(MetaTag::create("DC.Contributor", $CONFIG['siteowner']))
				  ->addChild(MetaTag::create("DC.Publisher", $CONFIG['sitename']))
				  ->addChild(MetaTag::create("DC.Identifier", "http://{$_SERVER['SERVER_NAME']}"))
				  ->addChild(MetaTag::create("DC.Rights", "copyright " . date('Y') . "- {$CONFIG['sitename']}. All rights reserved."));
		// spider relationals
		$root->addChild(Node::create("comment", null, "Sitemapping"))
				  ->addChild(LinkTag::create("contents", "sitemap.php?cat=$pageid"))
				  ->addChild(LinkTag::create("start", "{$CONFIG['opendocprefix']}&amp;id=" . route_get_frontpage_id()))
				  ->addChild(LinkTag::create("index", "{$CONFIG['opendocprefix']}&amp;id={$doc->pageid}"))
				  ->addChild(LinkTag::create("section", "{$CONFIG['opendocprefix']}&amp;id=$pageid"));
		// site wide scripts (convenience functions and dojo tooklit)
		$root->addChild(Node::create("comment", null, "Script Toolkit"))
				  ->addChild(Node::create("script", array(
								  "type" => "text/javascript",
								  "data-dojo-config" => "parseOnLoad:false,async:true,locale: 'da-dk',packages:" .
								  "[{name:'OoCmS',location:'{$CONFIG['relurl']}lib/dojoextensions'}]",
								  "src" => "{$CONFIG['dojoroot']}" . "dojo/dojo.js"), "", false))
//				  ->addChild(ScriptTag::create("text/javascript", "{$CONFIG['relurl']}include/prototyping.js"))
				  ->addChild(ScriptTag::create("text/javascript", null, "")
							 ->setInnerHTML("var gPage = { baseURI:'{$CONFIG['relurl']}'};\n" .
										"var gDocument = " . $doc->getDocumentObject("JSON") . ";\n"));
		if ($doc->type == "page") {
			$this->renderDocumentResources($root);
		}
		return $root;
	}

	function renderDocumentResources(&$root) {
		// as each category page may attach resources such as css or javascript
		$resourceCol = new ResourceCollection(self::$categoryDoc->pageid);
		$resourceCol->realize();
		$root->addChild(new Node("comment", null, "Pagetree Ressources"));
		$resource = $resourceCol->getFirstDocument();
		if ($resource != null) {

			do {
				fb($resource);
				if ($resource->type == "include") {
					$root->addChild(new Node("comment", null, $resource->comment));

					if ($resource->mimetype == "text/javascript") {
						if (empty($resource->url))
							$resource->url = null;
						$root->addChild(new ScriptTag($resource->mimetype, $resource->uri, $resource->body));
					} else if ($resource->mimetype == "text/css") {
						if (empty($resource->url))
							$root->addChild(new Node("style", array('type' => $resource->mimetype), $resource->body));
						else
							$root->addChild(new LinkTag("stylesheet", $resource->url, $resource->mimetype));
					}
				}
			} while (($resource = $resourceCol->nextDocument()) != null);
		}
	}

	function parser_renderDocumentBody($doc = null) {
		$doc = ($doc ? $doc : self::$document);
		return $this->renderDocumentBody($doc)->generateHtml();
	}

	function renderDocumentBody($doc = null) {
		$doc = ($doc ? $doc : self::$document);
		global $CONFIG;
		$themedir = "{$CONFIG['document_root']}site/{$CONFIG['site_theme']}";
		$parser = new SmartyParser();
		$szBuf = $this->theme_renderSection($themedir);
		$top = "";
		$mid = "";
		$bottom = "";
		// TODO: two extra entries in db for caching here.... no-cache (!), context-depending modules will fail FIXME :)
		//[0] => '<div class="section_top">some header</div><div class="leftside usermodule"><MYMODULE1/></div><div class="section">'
		//[1] => '</div><!-- $curdoc->body end --><div class="section_footer">some footer</div><div class="bottom usermodule"><MYMODULE2/></div>'
		if ($szBuf) {
			if (count($szBuf) > 0 && !empty($szBuf[0]))
				$top = $szBuf[0];
			if (count($szBuf) > 1)
				$bottom = $szBuf[1];
		}
		$mid = $parser->parse($doc);
		$wrapperNode = Node::create("div", array("class" => "oocms_" . self::$document->type), $top . $mid . $bottom);
		return $wrapperNode;
	}

	function renderDocumentMenu(DocumentCollection &$collection, $horizontal = true) {
		global $CONFIG;
		$id = self::$renderedMenuCount++;
		$root = Node::create("div", array(
						"id" => "OoCmS_Menu_$id",
						"class" => "oocms_menu"))
				  ->addChild(Node::create("ul", array(
						"class" => "oocms_menu-" . ($horizontal ? "horizontal" : "vertical"))))
				  ->addChild(Node::create("div", array("class" => "clear")));
		$list = $root->firstChild();
		while (($doc = $collection->nextDocument()) != null) {
			$list->addChild(Node::create("li", array(
									  "data-oocms-pageid" => $doc->pageid,
									  "data-oocms-doctype" => $doc->type,
									  "class" => "oocms_menu_item"
								 ))
								 ->addChild(Node::create("a", array(
												 "href" => "{$CONFIG['opendocprefix']}&amp;id={$doc->pageid}"
													  ), $doc->title)));
		}
		$this->addDojoOnload(array("OoCmS/Menu"), "new OoCmSMenu({\n" .
				  "\t horizontal:" . var_export($horizontal, true) . ",\n" .
				  "\t async:true,\n" .
				  "\t node: 'OoCmS_Menu_$id'\n" .
				  "});");
		
		return $root->generateHtml();
	}

	function renderProductCollection($title, &$collection) {
		$root = Node::create("div", array("class" => "oocms_product_collection"));
	}

	function renderProduct(&$product, $tiled = false) {
		// theme should supply two versions of the product template
		// one, a product.tiled.inc and two, product.detailed.inc
		// The Detailed version smartytag support 
		// 
		// ** the product title name **
		// <TITLE></TITLE>
		// 
		// ** a popup-gallery rendered by inbuilt js-module **
		// <IMAGEGALLERY></IMAGEGALLERY>
		// ** or a single image, first in list of attached images **
		// <IMAGE></IMAGE> 
		// 
		// ** loopable list of specifications, each  **
		// **   of which are rendered as itemvalue   **
		// <SPECIFICATION_LIST> 
		//   <SPECIFICATION_ITEMVALUE>
		// </SPECIFICATIONS_LIST>
		// 
		// ** loopable list of features, each    **
		// ** feature has markup as key -> value **
		// <FEATURES_LIST>                 << loopable list
		//   <FEATURES_KEY>  <FEATURES_VALUE> << rendered as key->value pr feature
		// </FEATURES_LIST>
		// 
		// ** full-text description (stored as html beforehand **
		// <DESCRIPTION></DESCRIPTON>
		// 
		// ** price, includes DISCOUNTPRICE **
		// <PRICE>price_postfix</PRICE>
		// 
		// The Tiled version smartytag support 
		// ** the product title name **
		// <TITLE></TITLE>
		// ** a single image, first in list of attached images **
		// <IMAGE></IMAGE> 
		// 
		// ** full-text description cut off pretty at the teaser_length **
		// <TEASER>teaser_length</TEASER>
		// 
		// ** price, includes DISCOUNTPRICE **
		// <PRICE>price_postfix</PRICE>
		// 
	}

	function theme_renderPageContainer($themedir) {
		if (file_exists("$themedir/page.inc.php"))
			$page = file_get_contents("$themedir/page.inc.php");
		else
			return null;
		return explode("<PAGE/>", $page);
	}

	function theme_renderSection($themedir) {
		if (file_exists("$themedir/section.inc.php"))
			$section = file_get_contents("$themedir/section.inc.php");
		else
			return null;
		return explode("<SECTION/>", $section);
	}

	function theme_renderHead($themedir) {
		return (file_exists("$themedir/head.inc.php") ? file_get_contents("$themedir/head.inc.php") : null);
	}

	function theme_renderFooter($themedir) {
		return (file_exists("$themedir/footer.inc.php") ? file_get_contents("$themedir/footer.inc.php") : null);
	}

	/**
	 *
	 * @param array $require list of dojo classes
	 * @param type $funcBody 
	 * example:
	 *   addDojoOnLoad(
	 *     array("dojo/_base/array", "dijit/Editor"), 
	 *     "new dEditor({}, 'node');\n" .
	 *     "d_array.forEach(["hey","you"], function(msg) { console.log(msg);});");
	 * 
	 * will result in the following, being output in <head> as inline javascript
	 * 
	 *  <script type="text/javascript>
	 *  require(["dojo/_base/array", "dijit/Editor", "dojo/domReady!"], function(d_array, djEditor) {
	 * 		// called on DOMReady and when above, required  modules has loaded.
	 * 		// function parameters are named according to classname like 
	 * 		// dojo to d, dijit to dj, dojox to dx and any other custom as full string
	 * 		// subnamespaces will all concatinate as the first char and last as full string
	 * 		//   e.g d|dj|dx/char/char/string
	 * 		new djEditor({}, 'node');
	 * 		d_array.forEach(["hey","you"], function(msg) { 
	 * 			console.log(msg);
	 * 		});
	 *  });
	 */
	function addDojoOnload($require, $funcBody) {
		$key = implode(",", $require);
		if (self::$onloadregistry == null)
			self::$onloadregistry = array();
		if (!isset(self::$onloadregistry[$key]))
			self::$onloadregistry[$key] = array();
		self::$onloadregistry[$key] =
				  array_merge(self::$onloadregistry[$key], explode("\n", $funcBody));
	}

	function renderDojoOnLoad() {
		$require = array();
		foreach (self::$onloadregistry as $key => $lines) {
			$require = array_merge($require, explode(",", $key));
		}
		array_push($require, "dojo/domReady!");
		// make sure only required once
		array_unique($require);
		// construct function prototype
		$prototype = "function(";
		foreach ($require as $mod) {
			$parts = explode("/", $mod);
			$argument = ($parts[0] == "dojo" ? "d" : ($parts[0] == "dijit" ? "dj" : ($parts[0] == "dojox" ? "dx" : $parts[0])));
			for ($i = 1; $i < count($parts) - 1; $i++) {
				$argument .= strtolower($parts[$i][0]);
			}
			$argument .= preg_replace("/!$/", "", $parts[count($parts) - 1]);
			$prototype .= $argument . ",";
		}
		// strip last comma
		$prototype = preg_replace("/,$/", "", $prototype) . ") {\n\t";
		// merge all into one big script
		$alllines = array();
		foreach (self::$onloadregistry as $key => $lines) {
			$alllines = array_merge($alllines, $lines);
		}
		// return, wrapped in an inline script tag
		return ScriptTag::create("text/javascript", null, "require([\"" .
							 implode("\",\"", $require) . "\"],"
							 . $prototype .
							 implode("\n\t", $alllines) . "\n});\n")->generateHtml(1);
	}

	/**
	 * expects construct to have set doc + body
	 * may be same, may be either of type product, page or subpage
	 * @global type $CONFIG must be require beforehand
	 */
	function render() {
		global $CONFIG;
		$this->renderDocumentsOnTheme = true;
		$parser = new SmartyParser();
		$themedir = "{$CONFIG['document_root']}site/{$CONFIG['site_theme']}";
		$this->addRegion("head", $this->renderGeneralHTMLHead()->generateHtml());

		$szBuf = $this->theme_renderHead($themedir);
		if ($szBuf)
			$this->addRegion("head", $szBuf);
		$szBuf = $this->theme_renderPageContainer($themedir);
		if ($szBuf) {
			// look for <SITENAME></SITENAME> and <PRIMARY_MENU> / <SECONDARY_MENU>
			// or any customs, parser will substitute accordingly if any are found;
			// as szBuf is set, a minimum 'top-page' was found (file not empty)
			if (count($szBuf) > 0 && !empty($szBuf[0]))
				$this->addRegion("top", $parser->parse($szBuf[0]));
			// and there may be a maximum of 2 entries (top <PAGE/> bottom)
			if (count($szBuf) > 1)
				$this->addRegion("bottom", $parser->parse($szBuf[1]));
		}
		// custom tags may for instance be <LEFTCOL>relpath/phpmodulefile</LEFTCOL>
		// atm modulefiles are not parsed unless administrative user invokes it
		switch (self::$document->type) {
			case 'page':
			case 'subpage':
				// create no cache here, this will be output at position of <OOCMS_THEME_GAP/>
				// each smarty-LOADSUB makes use of the theme file section.inc.php
				// and this is therefor embedded in the renderDocumentBody <-> parser
				// this make it possible for eventual ajax requests to also uphold
				// the user theming in section.inc.php
				$this->addRegion("section", $this->renderDocumentBody()->generateHtml());
				break;
			case 'product':
				break;
		}
		if (($szBuf = $this->theme_renderFooter($themedir)) != null) {
			fb($parser->parse($szBuf));
			$this->addRegion("footer", $parser->parse($szBuf));
		}
		$this->addDojoOnload(array("dojo/_base/kernel"), "togglemenu('boxed1');");
		if (self::$onloadregistry != null && count(self::$onloadregistry) > 0)
			$this->addRegion("head", $this->renderDojoOnLoad());
	}

	public function getContentBuffer() {
		global $base_regions;
		// inbuilt regions are 'head', 'top', 'section', 'footer' and 'bottom'
		// rendered in the said sequence, any custom regions will be output as sections
		$buffer = array();

		if (self::$content['head']) {
			array_push($buffer, "<head>\n");
			for ($i = 0; $i < count(self::$content['head']); $i++)
				array_push($buffer, & self::$content['head'][$i]);
			array_push($buffer, "</head>\n");
		}
		array_push($buffer, "<body>\n");
		if (self::$content['top']) {
			array_push($buffer, "<div class=\"oocms-contents-top\">\n");
			for ($i = 0; $i < count(self::$content['top']); $i++) {
				array_push($buffer, "<div class=\"oocms-contents-top oocms-contents-iter$i\">\n");
				array_push($buffer, & self::$content['top'][$i]);
			}

			if (!isset(self::$content['bottom']))
				foreach (self::$content['top'] as $i)
					array_push($buffer, "</div>\n");
		}
		array_push($buffer, "<div id=\"oocms-contents-pane\" class=\"oocms-contents-wrapper\">\n");
		if (self::$content['section']) {
			for ($i = 0; $i < count(self::$content['section']); $i++) {
				array_push($buffer, "<div class=\"oocms-contents-section oocms-contents-iter$i\">");
				array_push($buffer, & self::$content['section'][$i]);
				array_push($buffer, "</div>\n");
			}
		}
		// add user regions
		foreach (self::$content as $region => $valuearray) {
			if (in_array($region, self::$base_regions))
				continue;
			for ($i = 0; $i < count(self::$content[$region]); $i++) {
				array_push($buffer, "<div class=\"oocms-userregion-section oocms-contents-iter$i\">");
				array_push($buffer, & self::$content[$region][$i]);
				array_push($buffer, "</div>\n");
			}
		}
		array_push($buffer, "</div>");
		if (self::$content['footer']) {
			for ($i = 0; $i < count(self::$content['footer']); $i++) {
				array_push($buffer, "<div class=\"oocms-contents-footer oocms-contents-iter$i\">");
				array_push($buffer, & self::$content['footer'][$i]);
				array_push($buffer, "</div>\n");
			}
		}
		if (self::$content['bottom']) {
			for ($i = 0; $i < count(self::$content['bottom']); $i++) {
				array_push($buffer, "<div class=\"oocms-contents-bottom oocms-contents-iter$i\">");
				array_push($buffer, & self::$content['bottom'][$i]);
				array_push($buffer, "</div>\n");
			}
			foreach (self::$content['top'] as $i)
				array_push($buffer, "</div>\n");
		}
		array_push($buffer, "</body>\n");
		return $buffer;
		foreach (self::$content as $position => $list) {
			// defaults are head, top, middle, bottom 
			// head is special as it has its own special tag, not included in 
			// any .inc files as opposed to <body> which should be in page.inc.php
			if ($position == "head") {
				
			} else {
				$nodelist = new NodeList($position);
			}
			for ($i = 0; $i < count($list); $i++) {
				if ($position == "head")
					echo $list[$i];
				else
					$nodelist->addChild(Node::create("div", array("class" => "oocms-contents-$position"), &$list[$i]));
			}
			if ($position == "head")
				echo "</head>\n";
			else
				echo $nodelist->generateHtml();
		}
	}

	function __construct(&$doc = null, &$body = null) {
		if ($doc != null)
			self::$categoryDoc = $doc;
		if ($body != null)
			self::$document = $body;
		if (Theme::$instance == null) {
			Theme::$instance = $this;
		}
	}

}

?>
