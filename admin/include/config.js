define(["dojo/_base/declare",
	"dojo/_base/lang",
	"dojo/_base/array",
	"dojo/dom",
	"dojo/dom-style",
	"dojo/dom-geometry",
	"dojo/_base/connect",
	"OoCmS/AbstractController",
	"dijit/registry",
	"dijit/layout/TabContainer", // markup require
	"dijit/layout/ContentPane", // markup require
	"dijit/form/Form", // markup require
	"dijit/form/FilteringSelect", // markup require
	"dijit/form/ValidationTextBox", // markup require
	"dijit/form/Button",
	"dijit/form/TextBox",
	"dijit/Dialog",
	"dijit/Toolbar",
	//	"dijit/Tooltip"
	], function(declare, lang, array, dom, dstyle, ddomgeometry, connect, baseinterface, registry, tabcontainer, contentpane, form, filteringselect, validationtextbox, button, textbox, dialog, toolbar/*, tooltip*/){

		var Website = declare("OoCmS.config", [baseinterface], {

			_form : undefined,

			constructor: function constructor(/*Object*/ args){
				console.info(traceLog(this,arguments));
				var self = this;
				this.dijitrdyId = 'setupTabContainer'
				dojo.mixin(this, args);
			//if(!args.attachTo) console.error("Nothing to attach pageeditor widget to...");
			},
			bindDom : function bindDom(node) {
			// _nop
			},
			
			getValues : function getValues() {
				console.info(traceLog(this,arguments));
				return this.getForm().getValues();
			},
			isDirty : function isDirty() {
				console.info(traceLog(this,arguments));
				return false;
			},
			
			getForm: function getForm() {
				console.info(traceLog(this,arguments));
				this._form = registry.byId(this._tabs.selectedChildWidget.id.replace(/Tab$/, "Form"))
				return this._form;

			},
			getToolbar : function getToolbar() {
				console.info(traceLog(this,arguments));
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new toolbar({});
				bt = new button({
					label: "Vis config-fil",
					showLabel: true,
					onClick: lang.hitch(this, "read"),
					iconClass: "dijitEditorIcon dijitEditorIconNewPage"
				});
				tb.addChild(bt);

				tb.addChild(bt);
				bt = new button({
					label: "Gem",
					showLabel: true,
					title: 'Ved \'Gem\' ændres kun den viste form\'s udsnit af config filens indhold',
					onClick: lang.hitch(this, "update"),
					iconClass: "dijitIconSave"
				});
				tb.addChild(bt);
				return this._toolbar;
			},
			read : function read() {
				console.info(traceLog(this,arguments));
				var h = dojo.coords(window._pageframe.outer).h * 3 / 4,
				dia = new dialog({
					title: 'config.inc.php',
					style:'width:900px;height: '+h+'px',
					href:'views/setup.php?configPreview=1',
					onCancel : function () {
						ctrl._wPurge(dia)
					},
					onExecute : function () {
						ctrl._wPurge(dia)
					}
				});
				dia.startup();
				dia.show();
				h = dojo.coords(dia.domNode).h - dojo.getMarginSize(dia.titleBar).h;
				var pads = dojo.getPadExtents(dia.containerNode);
				dojo.style(dia.containerNode, {
					'overflow-y':'auto',
					'overflow-x':'hidden',
					height: (h-pads.t-pads.b-3)+'px'
				});
			},
			update : function update() {
				console.info(traceLog(this,arguments));
				dojo.xhrPost({
					url: 'save.php?EditResource&type=configure',
					content: this.getValues(),
					load: function(res) {
						if(res.indexOf("SAVED") == -1) {
							ctrl.notify("Fejl!", this.notifyTemplates.saveErr.replace("{RESPONSE}", res), [ {
								id:'saveoption',
								cb:function() {
									self.update();
								},
								classes:'dijitEditorIcon dijitEditorIconSave'
							}, {
								id:'canceloption',
								classes:'dijitEditorIcon dijitEditorIconUndo'
							}
							]);
							return;
						}
						ctrl.notify("Opdaterede konfiguration", self.notifyTemplates.saveSuccess, [ {
							id:'canceloption',
							classes:'dijitEditorIcon dijitEditorIconUndo'
						} ]);
					}
				})
				
			},
	
			postCreate: function postCreate() {
				console.info(traceLog(this,arguments));
				this._tabs = registry.byId(this.dijitrdyId)
				this.observers.push(connect.connect(this._tabs.tablist, "onSelectChild", this, this.tabChanged));
				this.inherited(arguments);
			},

			startup: function startup() {
				console.info(traceLog(this,arguments));
				if(!this.getToolbar()) console.error("Error occured while instantiating pagetoolbar...");
				this._toolbar.placeAt(dom.byId('websiteadmintoolbar'))
				this.inherited(arguments);
			},
			unload: function unload() {
				console.info(traceLog(this,arguments));
				array.forEach(this.observers, connect.disconnect)
				this._toolbar.destroyRecursive();
				this._tabs.destroyRecursive();
			},
			layout: function layout() {
				console.info(traceLog(this,arguments));
				if(!this._pageframe.formwrapper) this._pageframe.formwrapper = dom.byId('formWrapper');
				dstyle.set(this._pageframe.formwrapper, {
					height: (baseinterface.calcmarginbox(this._pageframe.center).h
						- ddomgeometry.getMarginBox(this._pageframe.formwrapper).t 
						-baseinterface.calcextents(this._pageframe.center).h
						-baseinterface.calcextents(this._pageframe.formwrapper).h) + "px"
				});
				this._tabs.resize({
					h: baseinterface.calcmarginbox(this._pageframe.formwrapper).h // outer height
					- ddomgeometry.getMarginBox(this._tabs.domNode).t // minus offset editor node
					-  baseinterface.calcextents(this._tabs.domNode).h
					-  baseinterface.calcextents(this._pageframe.formwrapper).h 
				})
				return;
				var outer = ddomgeometry.getMarginBox(this._pageframe.outer),
				toolbar = ddomgeometry.getMarginBox(this._pageframe.tbar),
				header = ddomgeometry.getMarginBox(dojo.query(".paneHeader")[0]);
				dstyle.set(this._pageframe.wrapper, { 
					height: (outer.h - header.h - toolbar.h
						- ddomgeometry.getMarginExtents(this._pageframe.outer).h
						- ddomgeometry.getMarginExtents(this._pageframe.tbar).h) + "px",
					width: (outer 
						- ddomgeometry.getMarginExtents(this._pageframe.outer).w)  + "px"
				});
				var wrapper = ddomgeometry.getMarginBox(this._pageframe.wrapper);
				dstyle.set(this._pageframe.container, { 
					height: (outer.h - header.h - toolbar.h - 100) + "px"
				});
				this._tabs.resize()
			}
		});
		return Website;
	});
console.log('eval website.js');