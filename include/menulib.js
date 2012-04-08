if(typeof dojo == 'undefined')
{
	document.write("<scr"+"ipt type='text/javascript' "+
		"src='/dojo_tk/dojo/dojo.js' " +
		"djConfig='parseOnLoad:true'>"+
		"</sc"+"ript>"
		);
}
function LinkTo(url,topmenu)
{
	if (topmenu != null) ajax_loadContent('topBar', topmenu);
	ajax_loadContent('mainContainer', url);
}
function LinkExt(url)
{
	top.parent.location.href=url;
}
// lineHeight controlled by initial value in appresource tags
var lineHeight = '16', maxduration = 1200, animating = false;
var openQ;
var opened = [];
var openTimer = null;
var hoveredAnimObject = null;
var selectedAnchor = null;

var animTimer = null;

var speedfactor = 0.5;
var highestLvl = -1;

var downFontColor = "#FFFFFF";
var downBgColor = "#264B51";
var upBgColor = "#02BCED";
var upFontColor = "#000000";
var selectedBg = [ "#02BCED" , "#02BCED", "#02BCED" ];

//var blackToWhite = dojo.animateProperty({ node: "animDiv",duration: 1000,
//   properties:{ color: { end: downColor }, backgroundColor:   { end: upColor }}});
//var whiteToBlack = dojo.animateProperty( { node: "animDiv",duration: 1000,
//   properties:{ color: { end: upColor }, backgroundColor:   {end: downColor }}});
var UP = 0;
var DOWN = 0;


function findEventParent(e)
{
	var targ;
	if (!e || e == window) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	return targ;

}
function revertAnim()
{
	if(animTimer != null)
		window.clearTimeout(animTimer);
	animTimer = null;
}
function revertOpen()
{
	window.clearTimeout(openTimer);
	openTimer = null;
}

function mixin(args) {

	if(args.highlight) {
		upBgColor = args.highlight.bg ? args.highlight.bg : upBgColor;
		upFontColor = args.highlight.fg ? args.highlight.fg : upFontColor;
	}
	if(args.normlight) {
		downBgColor = args.normlight.bg ? args.normlight.bg : downBgColor;
		downFontColor = args.normlight.fg ? args.normlight.fg : downFontColor;
	}
	speedfactor = args.speed ? args.speed : speedfactor;
	if(args.selectedBg) {
		selectedBg[0] = args.selectedBg['0'] ? args.selectedBg['0'] : selectedBg[0];
		selectedBg[1] = args.selectedBg['1'] ? args.selectedBg['1'] : selectedBg[0];
		selectedBg[2] = args.selectedBg['2'] ? args.selectedBg['2'] : selectedBg[0];
	}
	highestLvl = args.highestLvl ? args.highestLvl : -1;
}
/* create an extension to dojo.animation, for each menu-item */
function initMenuElements(topid, coloring)
{
	if(coloring) {
		mixin(coloring);
	}
	var divElements = dojo.query("div a[class*=\"ref\"]", topid);
	var div, a;
	//	var tt, toolSrcNode = document.createElement('span');
	//	toolSrcNode.innerHTML += '<a href="#" onclick="console.log(this.parentNode);">click</a>';
	for(var i = 0; i < divElements.length; i++)
	{
		div = divElements[i].parentNode;
		a = divElements[i];
		highestLvl = 0;

		/* preset the colors */
		if(!/^item-c/.test(div.className)) div.style['backgroundColor'] = downBgColor;
		div.style['color'] = downFontColor;
		div.style['height'] = lineHeight+"px";
		/* start the hover effect */
		div.highlight = function () {
			if(/^item-c/.test(this.className)) return;
			if(this.curAnim._active) {
				this.curAnim.stop();
				this.curAnim.gotoPercent(0);
			}
			this.curAnim = this._animationSel;
			this._animationSel.play();
		//this._animationSel.play(this.curAnim.duration);
		};
		/* anull the hover */
		div.normlight = function () {
			if(/^item-c/.test(this.className)) return;
			if(this.curAnim._active) {
				this.curAnim.stop();
				this.curAnim.gotoPercent(0);
			}
			if(this._animationSel._active) {
				this._animationSel.stop();
				this._animationSel.gotoPercent(0);
			}
			this.curAnim = this._animationDown;
			this._animationDown.play();
		};
		/* selected stage */

		div.curAnim = div._animationUp =
		dojo.animateProperty({
			node: div,
			duration: Math.ceil(150*speedfactor),
			properties:{
				color: {
					end: upFontColor
				},
				backgroundColor:{
					end: upBgColor
				}
			}
		});
		div._animationDown =
		dojo.animateProperty( {
			node: div,
			duration: Math.ceil(900*speedfactor),
			properties:{
				color: {
					end: downFontColor
				},
				backgroundColor: {
					end: downBgColor
				}
			}
		});
		div._animationSel =
		dojo.animateProperty({
			node: div,
			duration:Math.ceil(900*speedfactor),
			properties:{
				color: {
					end: upFontColor
				},
				backgroundColor:{
					end: selectedBg[a.getAttribute('lvl')]
				}
			}
		});
		//		div._animationSel.beforeStart = function () {
		//			this.style['color'] = upColor; this.style['backgroundColor'] = downColor;
		//		};

		if(highestLvl == -1 || (a.getAttribute('lvl') && a.getAttribute('lvl') <= highestLvl) ) {

			a.onmouseout = function() {
				revertOpen();
			}
			a.onmouseover = function() {
				openMenuProxy(this);
			};
		}
		//tt = new dijit.Tooltip({label:"DomainSelector", connectId:["menuTipId"+i]});
		a.onclick = function () {
			if(this.lvl == 0) {
				openMenu(this);
				return;
			}
			this.className = this.className.replace("itemref", "itemref-selected");
			if(selectedAnchor)
				selectedAnchor.className = selectedAnchor.className.replace("itemref-selected", "itemref");
			selectedAnchor = this;
		};
		if(a.lvl != 0)
			div.onclick = function(e) {
			e=e||window.event;
			if(e.cancelable && typeof e.cancelBubble != "undefined") e.cancelBubble = true;
				location = dojo.query("a", this)[0].href
			}
		div.onmouseout = function() {
			this.normlight();
		};
		div.onmouseover = function() {
			revertCloseArea = dojo.coords(this);
			this.highlight();
			revertCloseAll(null);
			closeTimer = setTimeout(_closeAll, 3200);
		};
	} // for elements
	// Shiny mozilla
	//divElements = dojo.query("div[class='item-c']");
	//divElements[0].style.cssText += '-moz-border-radius-topleft: 4px;-moz-border-radius-topright: 4px;';
	//divElements[(divElements.length-1)].style.cssText += '-moz-border-radius-bottomleft: 4px;-moz-border-radius-bottomright: 4px;';
}
var revertCloseArea = { x:0,y:0 };
var mousepos = { }
var closeTimer=null;
function _closeAll(e) {
	if(mousepos.x > revertCloseArea.x && mousepos.x < revertCloseArea.w+revertCloseArea.x
	&& mousepos.y > revertCloseArea.y && mousepos.y < revertCloseArea.h+revertCloseArea.y)
	{
		closeTimer = setTimeout(_closeAll, 2000);
		return;
	}
	closeTimer = null
	var node;
	// close subtree's beneath out current lvl
	while((node = opened.pop()))
	{
		animateToHeight(node, lineHeight, 1200);
	}
}
function revertCloseAll() {
	if(closeTimer == null)
		return;
	clearTimeout(closeTimer);
	closeTimer = null;
}
function openMenuProxy(el)
{

	if(dojo.query('div[class*="item-wrap"]', el.parentNode).length == 0 &&
		dojo.query('div[class*="subitem"]', el.parentNode).length == 0)
		return;

	if(animating) { // div.highligt()
		if(openTimer != null)
			window.clearTimeout(openTimer);
		openTimer = setTimeout(function() {
			openMenuProxy(el);
		} , (openQ[openQ.length-1]._endTime - new Date().getTime() ) );
	}else {
		if(openTimer != null)
			window.clearTimeout(openTimer);
		openTimer = setTimeout( function() {
			openMenu(el);
		} , 120);
	}

}
function calculateHeight(lvl, tree)
{

	lvl++;
	var q = dojo.query("div a[lvl='"+lvl+"']", tree);
	// height of self (parent) and its subitems
	var factor;
	if (lvl <3 ) factor = 1;
	else factor = 1;
	return q.length != 0 ? ((q.length * (parseInt(lineHeight)+4)) + parseInt(factor*lineHeight)) : parseInt(lineHeight);
}
function animateToHeight(el, h, dur)
{
	if(dur > maxduration || !dur)
		dur=Math.ceil(maxduration*speedfactor);
	dojo.animateProperty({
		node : el,
		properties : {
			height: h,
			unit: 'px'
		},
		duration : dur
	}).play();
}
function openMenu(el)
{
	var lvl = el.getAttribute("lvl");
	var node;
	if(opened[lvl] === el.parentNode)
		return;
	var oLvl = opened.length;
	// close subtree's beneath out current lvl
	while(oLvl-- > lvl)
	{
		node = opened.pop();
		animateToHeight(node, lineHeight, 1200);
	}
	opened[lvl] = el.parentNode;
	var newH = [];
	// recalculate parent tree height, if any
	for(var i = 0; i < opened.length; i++) {
		newH[i] = calculateHeight(i, opened[i]);
	}
	oLvl = lvl-1;
	for(i = 0; i < newH.length; i++)
	{
		for(j = i+1; j < newH.length; j++)
			if(newH[j] != lineHeight)
				newH[i] += newH[j];
		animateToHeight(opened[i], newH[i], 600);
	}
	revertCloseAll(null);
	closeTimer = setTimeout(_closeAll, 2200);
}
function MenuShowBox(forid) {
	var showing=dojo.byId('attached_id_'+forid);
	dojo.query(".item-wrap", showing).forEach(function(el){
		dojo.removeClass(el, "closed");
		dojo.addClass(el, "opened");
	});

}
function initMenu()
{

	var visibleBar = null;
	var curpos = 0;
	var w = 5;

	var positioners = dojo.query("#menuHeader div.dojoXLink").forEach(function(n) {
		curpos += w;
		n.style.left = (curpos)+"px";
		w = dojo.query(".inner a.xLink", n)[0];
		w = dojo.coords(w).w + 25;
		n.style.width = w + "px";
		var fishEye = dojo.query(".fisheyeTarget", n)[0];
		fishEye.innerHTML = '<div class="left"></div>'+
		'<div class="center" style="width:'+ (w - 25) + 'px;"></div>'+
		'<div class="right"></div>'+
		'<div style="clear:both;"></div>';

		fishEye.style.width = w + "px";

	});

	dojo.require("dojox.widget.FisheyeLite");
	dojo.require("dojox.fx");
	dojo.require("dojox.fx.easing");
	var effects = dojo.query(".headLink").forEach(function(n){

		var widget = new dojox.widget.FisheyeLite({
			properties: {
				height:42
			},
			//node : dojo.query(".fisheyeTarget", n)[0],
			easeOut:dojox.fx.easing.bounceOut,
			easeIn:dojox.fx.easing.linear,
			durationOut:700,
			durationIn: 100
		},n);
		
		var subpanel = "attached_id_"+dojo.attr(n,"parentid");
		subpanel = dojo.byId(subpanel);
		if(subpanel.firstChild){
			subpanel.show = function () {
				if(this.curAnim._active && this.curAnim.status) {
					this.curAnim.stop();
					this.curAnim.gotoPercent(0);
				}
				this.curAnim = this._show;
				this.curAnim.play();
				visibleBar = this;
			//this._animationSel.play(this.curAnim.duration);
			};
			subpanel._show = dojo.fadeIn({
				node: subpanel.firstChild,
				duration:350
			});
			/* anull the hover */
			subpanel.hide = function () {
				if(this.curAnim._active && this.curAnim.status) {
					this.curAnim.stop();
					this.curAnim.gotoPercent(0);
				}
				this.curAnim = this._hide;
				this.curAnim.play();
			};
			subpanel._hide = dojo.fadeOut({
				node:subpanel.firstChild,
				duration:250
			});
			subpanel.curAnim = subpanel._hide;
			dojo.style(subpanel.firstChild,{
				"opacity":0.0,
				"visibility":"visible"
			});
			var _anim = null;
			dojo.connect(widget,"show",function(e){
				visibleBar != null && visibleBar.hide();
				subpanel.show();
			});
		/*dojo.connect(widget,"hide",function(e){
				_anim && _anim.status && _anim.stop();
				_anim = dojo.fadeOut({ 
					node: subpanel,
					duration:250
				});
				_anim.play();
			});*/
		} else {
			if(subpanel) subpanel.parentNode.removeChild(subpanel);
		}

	//		dojo.connect(n,"onclick",function(e){
	//			// anchor behavior... dont
	//			});


	});


}
function capMouse(e) {

	mousepos = {
		x: (document.layers ? e.pageX : (document.all ? window.event.x+document.body.scrollLeft : e.pageX)),
		y: (document.layers ? e.pageY : (document.all ? window.event.y+document.body.scrollLeft : e.pageY))
	}

}
if (document.layers) { // Netscape
    document.captureEvents(Event.MOUSEMOVE);
    document.onmousemove = capMouse;
} else if (document.all) { // Internet Explorer
    document.onmousemove = capMouse;
} else if (document.getElementById) { // Netcsape 6
    document.onmousemove = capMouse;
}
