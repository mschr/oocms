function evtTarget(e)
{
	var targ;
	if (!e)
		var e=window.event;
	if (e.target)
		targ=e.target;
	else if (e.srcElement)
		targ=e.srcElement;
	if (targ.nodeType==3) // defeat Safari bug
		targ = targ.parentNode;
	return targ;
}
function addTransImg(el) {
	var img; 
	el.appendChild(img = document.createElement('img'))
	img.src = gPage.baseURI + "/gfx/transparent.gif"
	img.height="10";
	img.width = "10";
	
}
function dynamicBorderTable(idStr, clStr, bg) {
	var t = document.createElement('table');
	if(idStr) t.id = idStr
	var el, tr;
	var ret;
	t.appendChild(el = document.createElement('tbody'));
	if(clStr != null) t.className = clStr;
	el.appendChild(tr = document.createElement('tr'));
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderUL"
	el.appendChild(addTransImg())
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderFillU"
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderUR"
	tr.parentNode.appendChild(tr = document.createElement('tr'));
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderFillL"
	tr.appendChild(ret = document.createElement('td'))
	if(bg) ret.style.backgroundColor = bg;
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderFillR"
	tr.parentNode.appendChild(tr = document.createElement('tr'));
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderBL"
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderFillB"
	tr.appendChild(el = document.createElement('td'));
	el.className = "borderBR"
	el.appendChild(addTransImg())
	return ret;
}
var borderTopStartStr = '<table cellspacing="0" cellpadding="0"'
var borderTopMiddleStr = '><tbody><tr><td class="borderUL"><img src="/gfx/transparent.gif" height="10" width="10"/></td><td class="borderFillU"></td><td colspan="2" class="borderUR"></td></tr><tr><td class="borderFillL"></td>'
var borderBottomStr = '</td><td class="borderFillR"></td></tr><tr><td class="borderBL"></td><td class="borderFillB"></td><td class="borderBR"><img src="/gfx/transparent.gif" height="10" width="10"/></td></tr></tbody></table>';
function generateBorderTop(idStr, clStr, bg) {
	if (idStr != null) {
		idStr = " id=\"" + idStr + "\"";
	}
	if (idStr.indexOf("contentFrame") != -1) {
		idStr += " style=\"width:100%;\" ";
	}
	clStr = (clStr != null) ? " class=\"" + clStr + "\"" : "";
	bg = (bg != null && typeof bg != "undefined") ? "background-color: " + bg : "background-color: #ffffff";
	return borderTopStartStr + idStr + clStr + borderTopMiddleStr + "<td style=\"" + bg + ";\">";
}
function generateBorderBottom() {
	return borderBottomStr + "</div></div>";
}


///////////////////////////
var Page = function () {
	///////////////////////
	// privates
	var pageid;
	var initialized = false;
	var instance;

	function loadCSS(src) {
		var h = document.getElementsByTagName('head')[0];
		var loaded = false;
		dojo.query("link", h).forEach(function(n) {
			if(n.href == src) loaded = true;
		});
		if(!loaded) {
			loaded = document.createElement("link");
			loaded.href = src;
			loaded.rel = "stylesheet";
			loaded.media = "screen";
			h.appendChild(loaded);
		}
	}
	function loadContent(type, id) {
		this.pane.attr('href', gPage.baseURI+"openView/AJAXContents.php?id="+id);
		History.reload();
	}
	function beautifyCrumbs() {
		return;
		var step = 0, size = 16;
	
		var isMoz = /Firefox/.test(navigator.userAgent)
		var q = dojo.query("a", dojo.byId("crumbwrap")), div, img;
		for(i=0; i < q.length;i++) {
			if(q.length > 2) {
				if(i > step + 2) {
					step+=2;
					size = size - 1;
				}
				if(isMoz) {
					q[i].style.cssText = "border-bottom-width: 1px;border-bottom-style: dashed;border-bottom-color: rgb(40,40,180);";
					dojo.style(q[i],{

						fontSize: size,
						opacity: (i == 0) ? 1 : 1/(i+0.175)
					});
				} else {
					dojo.style(q[i], {
						fontSize: size
					});
				}
			}
			var fish ;
			try {
				fish = new dojox.widget.FisheyeLite({
					properties:{
						fontSize:1.2,
						letterSpacing:1.15
					},
					easeOut: dojox.fx.easing.backInOut,
					durationOut: 500
				},q[i]);
			} catch(e) {
				console.log(fish);
				console.log(q[i]);
			}
			if(isMoz) {
				
				dojo.connect(q[i], "mouseover", function(e) {
					evtTarget(e).setAttribute("op", evtTarget(e).style.opacity);
					dojo.style(evtTarget(e), {
						opacity : 1
					});
				});
				dojo.connect(q[i], "mouseout", function(e) {
					dojo.style(evtTarget(e), {
						opacity : evtTarget(e).getAttribute("op")
					});
				});
			}
		}

	}

	return {
		getInstance : function() {
			if(!initialized)
			{
				// interface internal references
				initialized = true
				Page.instance = this;
				Page.load = loadContent;
				Page.fishyCrumbs = beautifyCrumbs;
				Page.pane = null;
				dojo.addOnLoad(function() {
					Page.pane = dijit.byId('contentswrap');

				});
				
			//Page.login = login;
			}
			return Page.instance; // :)
		}
	}

}();


