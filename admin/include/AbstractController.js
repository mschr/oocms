define(["dojo/_base/declare",
	"dojo/_base/Deferred",
	"dijit/registry",
	"dojo/dom",
	"dojo/dom-construct",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dojo/query"], function(declare, ddeferred, registry, ddom, ddomconstruct, ddomgeom, ddomstyle, $){
		// TODO create subscrive / publish chan for contentpane.onload
		
//		has.add("dom-qsa2.1")
//		has.add("dom-qsa3")      CSS ENGINE
		var AbstractController = declare("OoCmS.AbstractController", [], {
			observers: [],
			dijitrdyId: null,
			_rdypoll:null,
			constructor: function(args) {
				this.containerNode = dojo.byId('mainContentPane');
				// setup ready.then resolve
				this.ready = new ddeferred();
				this.ready.then(dojo.hitch(this, this.postCreate));				
				
			},
			startup : function startup() {
				console.log('startup deferred', this);
				var id = this.dijitrdyId;
				this._pageframe = {
					left : ddom.byId('leftcolumn'),
					center : ddom.byId('centercolumn'),
					right : ddom.byId('rightcolumn'),
					outer : ddom.byId('mainContentPane')
				};
				if(this.dijitrdyId == null || registry.byId(id)) this.ready.resolve(null);
				else {
					if(this._rdypoll != null) return; // avoid double polling interval
					var self = this;
					this._rdypoll = setInterval(function() {
						console.log('poll', id, registry.byId(id))
						if (eval(registry.byId(id))) { //does the object exist?
							clearInterval(self._rdypoll);
							self.ready.resolve(null);
						}
					}, 150);
				}
			},
			postCreate: function postCreate() {
				console.log('postcreate deferred', this);
				this.observers.push(dojo.connect(window, "onresize", this, this.layout));
				this.layout();
			},
			isDirty: function() {
				console.error("Not Implemented (isDirty)")
			},
			unload: function() {
				console.error("Not Implemented (unload), should disconnect this.observers and teardown instantiated widgets")	
			},
			bindDom: function(node) {
				console.error("Not Implemented (bindDom)")	
			},
			layout: function() {
				var couter = ddomgeom.getMarginBox(this._pageframe.outer),
				//					cleft = AbstractController.calcmarginbox(this._pageframe.left),
				ccenter = AbstractController.calcmarginbox(this._pageframe.center),
				//					cright = AbstractController.calcmarginbox(this._pageframe.right),
				headerheight = 30,
				//					extouter = AbstractController.calcextents(this._pageframe.outer) ,
				extleft = AbstractController.calcextents(this._pageframe.left),
				extcenter = AbstractController.calcextents(this._pageframe.center),
				extright = AbstractController.calcextents(this._pageframe.right);

				if(this._pageframe.left) {
					ddomstyle.set(this._pageframe.left, "height", (couter.h - headerheight - extleft.h) + "px");
				}
				if(this._pageframe.center) {
					ddomstyle.set(this._pageframe.center, "height", (couter.h - headerheight - extcenter.h) + "px");
				}
				if(this._pageframe.right) {
					ddomstyle.set(this._pageframe.right, "height", (couter.h - headerheight - extright.h) + "px");
				}
			}
		});
		AbstractController.calcmarginbox = function(framepos) {
			return framepos ? ddomgeom.getMarginBox(framepos) : {
				w:0,
				h:0
			};
		};
		AbstractController.calcextents = function(framepos) {
			var ext = framepos ? ddomgeom.getMarginExtents(framepos) : {
				w:0,
				h:0
			};
			if(!framepos) return ext;
			var bext = ddomgeom.getBorderExtents(framepos);
			for(var i in bext) if(bext.hasOwnProperty(i)) {
				ext[i] += bext[i];
			}
			bext = ddomgeom.getPadExtents(framepos);
			for(var i in bext) if(bext.hasOwnProperty(i)) {
				ext[i] += bext[i];
			}
			return ext;
		}
		return AbstractController;

	});
	