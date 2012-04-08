require(["dojo/_base/declare",
	"dijit/Editor",
	"dijit/_editor/plugins/EnterKeyHandling",
	"dijit/_editor/plugins/ViewSource",
	"dojox/editor/plugins/Save",
	"dojox/editor/plugins/PrettyPrint",
	"dojox/editor/plugins/ToolbarLineBreak",
	"dojox/editor/plugins/Preview"], 
	function(declare, editor, plugEnter, plugColor, plugLink, plugFont, plugView, xplugSave) {
		var mySavePlugin = declare("mySavePlugin", [xplugSave],{
			save: function(content){
				dojo.publish("editor/save")
			}
		});

		dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o){
			if(o.plugin){
				return;
			}
			var name = o.args.name.toLowerCase();
			if(name ===  "customsave"){
				o.plugin = new mySavePlugin({
					url: ("url" in o.args)?o.args.url:""/*,
					logResults: ("logResults" in o.args)?o.args.logResults:true*/
				});
			}
		});
	});
define(["dojo/_base/declare",
	"OoCmS/AbstractController",
	"dijit/registry",
	"dojo/dom",
	"dojo/dom-construct",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dojo/query",
	"dojo/_base/xhr",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/_base/lang",
	"dojo/date/locale",
	"dojo/topic",
	"dijit/Tree",
	"dijit/form/Textarea",
	"dijit/form/TextBox",
	"dijit/tree/ForestStoreModel",
	"dijit/Editor"
	], function(declare, baseinterface, registry, ddom, ddomctor, ddomgeom, ddomstyle, dquery, xhr, darr, dconnect, lang, locale, dtopic, djtree, textarea, forestmodel, editor){
		var Product = declare("OoCmS.product", [baseinterface], {
			variable : 1,
			constructor: function(/*Object*/ args){
				if(args) lang.mixin(this, args);
			},
			bindDom : function bindDom(node) {
								console.info(traceLog(this,arguments));

				this.attachTo = node;
			},
			startup: function startup() {
				console.info(traceLog(this,arguments));
				if(!this.getForm()) console.error("No form with submit instructions underneath given element or error occured while instantiating...");
				if(!this.getToolbar()) console.error("Error occured while instantiating pagetoolbar...");
				ddom.byId('producttoolbarWrapper').appendChild(this._toolbar.domNode);

				this.ready(this.postCreate)
			},
			postCreate: function postCreate(self) {
				console.ingo(traceLog(this, arguments));
				self = self || this;
				var save_action = lang.hitch(this, this.editor_saveaction)
				self._pageframe = {
					left : ddom.byId('productleftcolumn'),
					right : ddom.byId('productcentercolumn'),
					outer : ddom.byId('productformwrapper'),
					selector: ddom.byId('productselector')
				};
				dtopic.subscribe("editor/save", save_action);
				self.layout();
			},
			editor_saveaction: function() {
				this.id == -1 && this.create() || this.update();
			},
			getForm: function getForm() {
				console.info(traceLog(this,arguments));
				if(this._form) return this._form;
				this._form = this.attachTo.getElementsByTagName('form')[0];
				this.editurl = this.editurl || this._form.action;
				return this._form;

			},
			/*
			getValues : function getValues() {
				var attach = this.getAttachBox().get("value", "");
				if(attach == "Øverst") attach = "";
				else if(attach != "") attach = parseInt(attach.split(" - ")[0]);
				return {
					body : this._editor.getValue(),
					doctitle: dijit.getEnclosingWidget(dquery("[name=\"doctitle\"]", this._form)[0]).get("value"),
					alias: dijit.getEnclosingWidget(dquery("[name=\"alias\"]", this._form)[0]).get("value"),
					form: attach == "" || this.isdraft == 1 ? 'page' : 'subpage',
					isdraft: this.isdraft,
					attachId: attach
				};
			},
			isDirty : function isDirty() {
				console.info(traceLog(this,arguments));
				// not running body through editor prettyprint to validate alikeness
				var titlechange =dijit.getEnclosingWidget(this._form.elements['doctitle']).get("value") != this.title,
				aliaschange = dijit.getEnclosingWidget(this._form.elements[ 'alias'  ] ).get("value") != this.alias,
				bodyblank = this._editor.getValue().trim() == "<br />" && this.body == "",
				bodychange = this._editor.getValue() != this.body;
				return (!bodyblank && bodychange) || titlechange || aliaschange;
			},
			*/
			unload: function unload() {
				this._editor.destroyRecursive();
				dojo.destroy(this._form);
				try {
					dtopic.unsubscribe("editor/save");
				} catch(e) {}
			},
			ready: function ready(funcObject /*(self)*/) {
				var self = this;
				if(self._editor) {
					console.info(traceLog(this,arguments));
					if(typeof funcObject == "function") {
						funcObject(self);
					}
					return;
				}

				//	self._editor = registry.byId('pageformEditor');
				var _interval = setInterval(function() {
					self._editor = registry.byId('productformEditor');
					if (eval(self._editor)) { //does the object exist?
						clearInterval(_interval);
						console.info(traceLog(self,arguments));
						if(funcObject) {
							funcObject(self);
						}
					}
				}, 150);
			},
			layout: function layout() {
				try {
				
					var c1 = ddomgeom.getMarginBox(this._pageframe.outer),
					c2 = ddomgeom.getMarginBox(this._pageframe.left),
					headerheight = 30;
					ddomstyle.set(this._pageframe.right, "height", (c1.h -headerheight) + "px");
					ddomstyle.set(this._pageframe.left, "height", (c1.h -headerheight) + "px");
					if(this._editor)
						this._editor.resize({
							h:c1.h - ddomgeom.position(this._editor.domNode).y
						})
			
				}catch(squelz) { }
			}
		});
		return Product;
	});
