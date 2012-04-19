/* example:
 * new Date().sqlToDate('2009-10-03 11:19:52')
*/
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