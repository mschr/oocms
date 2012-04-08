define([
	"dojo/_base/declare", 
	"dojo/_base/Deferred",
	"dojo/query",
	"dojo/dom",
	"dojo/_base/lang", 
	"dojo/fx"], function(declare, ddeferred, $, ddom, dlang, dfx) {
		return declare("OoCmS.Menu", [],  {
			observers: [],
			constructor: function(args) {
				var self = this;
				if(!args.node) throw 'No Node Available for Menu... Mooo'
				this.domNode = ddom.byId(args.node);
				this.async = args.async;
				this.horizontal = (args.vertical ? false : (args.horizontal ? true : false));
				this.async = args.async;
				// hook onClick event
				$("li", args.node).on("click", dlang.hitch(this, function(evt){
					this.onItemClick(evt.target);
				}));
				// disable html eventengine href behavior
				$("li a", args.node).forEach(function(a) {
					a.onclick = function() {
						return false;
					}
				});
			
		},
		onItemClick: function(node) {
			var a = (node.tagName == "A" ? node : $("a", node)[0]),
			li = a.parentNode,
			type = li.getAttribute("data-oocms-doctype");
			var url = a.getAttribute("href");
			if(this.async && type != "page") {
				if(!this.getPane()) {
					this.setupPane().then(function(pane) {
						pane.set("href", url + "&amp;subpagefetch=1");
					})
				} else 
					this.getPane().set("href", url);
			} else {
				document.location.href = url;
			}
		},
		getPane: function() {
			return this._contentpane;
		},
		setupPane: function() {
			var dfd = new ddeferred, self = this;
			if(this._contentpane) return this._contentpane;
			require(["dijit/layout/ContentPane"], function(pane) {
				self._contentpane = new pane({}, "oocms-contents-pane");
				dfd.resolve(self._contentpane);
			});
			return dfd;
		}
		});
})