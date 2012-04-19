<?php 
header("Content-Type: application/javascript;charset=UTF-8");
require_once("../../include/config.inc.php");
$live = preg_match("/deploy/", $CONFIG['dojoroot']); 
	?>
	var menudata = [  {
		label: 'Sider',
		id: '2',
		children:  [ { 
				id: '2.1',
				label: 'Opsætning',
				tooltip:'Rediger/Slet/Tilføj sider',
				action:'page'
			}, {
				id: '2.2',
				label: 'Hieraki',
				icon: 'dijitIconFilter',
				tooltip:'Arranger siders relation ift hverandre',
				action: 'pagetree'
			} ]
	}, {
		label: 'Produkter',
		id: '3',
		children: [ {
				id: '3.1',
				label: 'Opsætning',
				icon: 'OoIcon-24 OoIconProduct',
				tooltip:'Rediger/Slet/Tilføj produkter',
				action:'product'
			}, { 
				id: '3.2',
				label: 'Gruppering',
				icon: 'OoIcon-24 OoIconProductCategory',
				tooltip:'Opret/Slet produktgruppering og mærk eksisterende produkter med disse labels',
				action:'productcategory'
			} ]
	} ,{
		label: 'Domæne',
		id: '1',
		children:  [ {
				id: '1.1',
				label: 'Opsætning',
				icon: 'dijitIconFunction',
				tooltip: 'Tilpas meta-tags, ejerskab og standard indstillinger for domænet',
				action: 'setup'
			}, {
				id: '1.2',
				label: 'Template',
				tooltip: 'Specificér CSS dokumenter, layout-template mv.',
				action: 'template'
			}, {
				id: '1.3',
				label: 'Filer',
				tooltip: 'Naviger under fileadmin.',
				action: 'assets'
			}]
	} ];
// <?php if(!$live) {  //////////// Dev Version ///////////////
// ?> 
	

var cconfig = {

	packages: [ {
			name: 'OoCmS', 
			location: document.location.protocol + "//" + document.location.host +
				gPage.baseURI + "admin/include"
		} ]

	
};
	
require([
	'dojo',
	'dojo/_base/kernel',
	'dojo/domReady!' ], function(dojo, kernel) {
	console.warn('================ kernel READY ==============')
	dojo.body().style.display="none";
	require(cconfig, [
		'OoCmS/_messagebus'

	], function(mbusloading) {
		
		// level 1
		// show body but not borderlayout (for progressmeter)
		dojo.body().style.display="";
		dojo.byId('border').style.display = "none"
		mbusloading.loading({
			progress: 1,
			maximum: 5
		});

	
		require(cconfig , [ 
			"dojo/parser",
			"OoCmS/application"
		], function(dparser, application){
			console.warn('================ App READY ==============')
			// level 3
			mbusloading.loading({
				progress: 3,
				maximum: 5
			});
			window.ctrl = gPage.app = new application({
				menudata: menudata
					
			}); // level 5 (new application)
			mbusloading.loading({
				progress: 4,
				maximum: 5
			});
		}); // level 3
	}); // level 1
}); // domready
function traceLog(obj, args) {
	try {
		var i, len = obj.constructor._meta.bases ? obj.constructor._meta.bases.length : 0,
		max = Math.min(len, 4),
		objName = "",
		calleeName = "\t."+(args.callee?(args.callee.nom?args.callee.nom:args.callee.name):"unkown"),
		paranthesis = "(",
		res = null;
		if(len > 1) {
			objName += obj.constructor._meta.bases[0].prototype.declaredClass + "["
			for(i = 1; i < max; i++) {
				objName += obj.constructor._meta.bases[i].prototype.declaredClass
				if(i < max-1) objName +="/";
			}
		} else {
			if(obj.declaredClass) objName = obj.declaredClass + "[";
		}
		objName = (objName.length > 55 ? 
			objName.substring(0,49)+"...]            " : 
			objName + "]                           " ).slice(0,55) + "\t.";
		for(i = 0; i < args.length; i++) {
			// if its a dojo declared class, print its classname
			if(args[i] && typeof args[i].declaredClass != "undefined") {
				paranthesis += args[i].declaredClass;
			} else if(dojo.isString(args[i])) {
				paranthesis += "String";
			} else if(dojo.isArray(args[i])) {
				paranthesis += "Array"; 
			} else if(args[i] instanceof Number) {
				paranthesis += "Number";
			}else if(args[i] instanceof Date) {
				paranthesis += "Date"; 
			}else if(args[i] instanceof MouseEvent) {
				paranthesis += "MouseEvent"; 
			}else if(args[i] instanceof Event) {
				paranthesis += "Event"; 
			}else if(args[i] instanceof RegExp) {
				paranthesis += "RegExp"; 
				//			} else if((typeof Node === "object" && args[i] instanceof Node) 
				//				|| (typeof args[i].nodeType === "number" && typeof args[i].nodeName==="string")) {
				//				paranthesis += "Node";
				//			} else if((typeof HTMLElement === "object" && args[i] instanceof HTMLElement)
				//				|| (args[i].nodeType === 1 && typeof args[i].nodeName==="string")) {
				//				paranthesis += "HTMLElement";
				//			} else if((typeof Node === "object" && args[i] instanceof Node)
				//				|| (typeof args[i].nodeType != "undefined" && typeof args[i].nodeName==="string")) {
				//				paranthesis += "Node";
			} else if(typeof args[i] == "object") {
				paranthesis += "hash";
			}else {
				paranthesis += typeof(args[i]);
			}
			if(i<args.length-1)
				paranthesis += ", ";
		}
		paranthesis += ")";
	
		if(args.length != 0) {		
			res = [ objName+calleeName+paranthesis, {
					a: args
				}];
		} else {
			res=[objName+calleeName+paranthesis];
		}
	}catch(fallback){
		console.log(fallback.message)
		var callstack = [], lines, entry, funcExp = new RegExp(/[A-Za-z0-9_\$]+[\ ]?\(/)
		try{
			if(fallback.stack) {// firefox
				lines = fallback.stack.split('\n');
				for (i=0, len=Math.min(3,lines.length);i < len; i++)
					if (funcExp.test(lines[i]))
						callstack.push(lines[i].trim().replace(/([A-Za-z0-9_\$]+)[\ ]?\(.*/, "$1"));
			}
			else if(dojo.isOpera && fallback.message) { // opera

				lines = fallback.message.split('\n');
				for (i=0, len=lines.length; i<Math.min(6,len); i++) {
					if (funcExp.test(lines[i])) {
						entry = lines[i].trim().replace(/([A-Za-z0-9_\$]+)[\ ]?\(.*/, "$1");
						//Append next line also since it has the file info
						if (lines[i+1]) {
							entry += ' at ' + lines[i+1];
							i++;
						}
						callstack.push(entry);
					}
				}
			} 
			else {
				var fn = arguments.callee.caller.toString();
				callstack.push("traceLog")
				callstack.push(fn.substring(fn.indexOf("function") + 8, fn.indexOf('')) || 'anonymous');
			}
			//Remove call to printStackTrace()
			console.info(callstack[1], args);	
		} catch(failed) {
			console.info("anonymous", obj, args);	
		} finally {
			
		}
	}
	console.info(objName+calleeName+paranthesis);
	if(args.length > 0) console.info(args)
}
// <? } else { //////////// Live Version ///////////////
//  ?>  
		
require([
	'dojo',
	'OoCmS/init1',
	'OoCmS/init3',
	'OoCmS/init5',
	'dojo/domReady!' ], function(dojo) {
	console.warn('================ kernel READY ==============')
	dojo.body().style.display="none";
	require([
		'OoCmS/_messagebus',
		'OoCmS/application'
	], function(mbusloading, application) {
		console.warn('================ App READY ==============')
		// show body but not borderlayout (for progressmeter)
		dojo.body().style.display="";
		dojo.byId('border').style.display = "none"
		mbusloading.loading({
			indeterminate: true
		});
		window.ctrl = gPage.app = new application({
			menudata: menudata
		}); // level 5 (new application)
	});
}); // domready
	
function traceLog(obj, args) { }
	
// <? } ?> 
	
if(typeof window.console != "object") {
	window.console = {
		log : function(s) { },
		info : function(s) { },
		error : function(s) { }
	};
}
Date.prototype.sqlToDate = function(str) {
	var spl = str.split(" ");
	if(spl.length < 2) return "";
	var date = spl[0].split("-");
	var time = spl[1].split(":");
	this.setDate(date[2]);
	this.setMonth(date[1]-1)
	this.setYear(date[0])
	this.setHours(time[0])
	this.setMinutes(time[1])
	this.setSeconds(time[2])
	return this.asString();
}
Date.prototype.asString = function() {
	return this.toString().replace(/\ [GU].*/, "");
}
if(typeof String.prototype.trim !== 'function') {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, ''); 
	}
}

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
function evtElement(e, tagOfParent, classOfParent) {
	var t;
	if(!e) e = window.event;
	t = e.target || e. srcElement;
	if(t.nodeType==3)t=t.parentNode;
	if(tagOfParent){
		var iter = 0;
		do {
			if(t.nodeType==1 && t.tagName.toLowerCase() == tagOfParent.toLowerCase()){
				if(!classOfParent){
					return t;
				}else if(t.className!=null&&t.className!=""&&RegExp("([\ ]|^)"+classOfParent+"([\ ]|$)").test(t.className)){
					return t;
				}
			}
		} while((t = t.parentNode) !=null && iter++ < 1000)
		}
		return t;
	}
	function capMouse(e) {
		if(document.layers){
			return [e.pageX,e.pageY]
		}else if(document.all){
			return [window.event.x+document.body.scrollLeft, window.event.y+document.body.scrollLeft];
		}else{
			return [e.pageX,e.pageY]
		}
	}
	function loadCSS(url) {
		var h = document.getElementsByTagName('head')[0];
		var links = dojo.query('link[href="'+url+'"]');
		if(links.length > 0) return;
		var link = dojo.create("link", { rel: 'stylesheet', href:url}, h, 'last');
	}
