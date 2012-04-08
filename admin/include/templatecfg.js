define(["dojo/_base/declare",
	"OoCmS/AbstractController",
	"dijit/layout/TabContainer",
	"dijit/layout/ContentPane",
	"dijit/form/Form",
	"dijit/form/FilteringSelect",
	"dijit/form/Button",
	"dijit/form/TextBox",
	"dijit/form/ValidationTextBox",
	"dijit/Tooltip"
	], function(declare,baseinterface){

		var Website = declare("OoCmS.config", [baseinterface], {

			_form : undefined,

			constructor: function constructor(/*Object*/ args){
				console.info(traceLog(this,arguments));
				var self = this;
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
				this._form = dijit.byId(this._tabs.selectedChildWidget.id.replace(/Tab$/, "Form"))
				return this._form;

			},
			getToolbar : function getToolbar() {
				console.info(traceLog(this,arguments));
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new dijit.Toolbar({});
				bt = new dijit.form.Button({
					label: "Vis config-fil",
					showLabel: true,
					onClick: dojo.hitch(this, "read"),
					iconClass: "dijitEditorIcon dijitEditorIconNewPage"
				});
				tb.addChild(bt);

				tb.addChild(bt);
				bt = new dijit.form.Button({
					label: "Gem",
					showLabel: true,
					title: 'Ved \'Gem\' ændres kun den viste form\'s udsnit af config filens indhold',
					onClick: dojo.hitch(this, "update"),
					iconClass: "dijitIconSave"
				});
				tb.addChild(bt);
				return this._toolbar;
			},
			read : function read() {
				console.info(traceLog(this,arguments));
				var dia = new dijit.Dialog({
					style:'height:500px;width 900px;overflow-y:auto;overflow-x:hidden',
					href:'views/setup.php?test',
					onCancel : function () {
						ctrl._wPurge(dia)
					},
					onExecute : function () {
						ctrl._wPurge(dia)
					}
				});
				dia.show();
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
	
			postCreate: function postCreate(self) {
				console.info(traceLog(this,arguments));
				self._pageframe = {
					wrapper : dojo.byId('websiteadminwrapper'),
					container : dojo.byId('setupTabContainer'),
					outer : dojo.byId('mainContentPane'),
					tbar : dojo.byId('websiteadmintoolbar')
				};
				self.observers.push(dojo.connect(self._tabs.tablist, "onSelectChild", self, self.tabChanged));
				self.observers.push(dojo.connect(window, "onresize", self, self.layout));
				self.layout();
				
			},
			layout: function layout() {
				console.info(traceLog(this,arguments));
				//				try {
				var c1 = dojo.coords(this._pageframe.outer),
				toolbarHeight = dojo.coords(this._pageframe.tbar).h,
				headerHeight = 27,
				margin = dojo.style(this._pageframe.outer, "margin-top");
				console.log(c1.h - headerHeight - toolbarHeight - 2 * margin, this._pageframe.container)
				dojo.style(this._pageframe.wrapper, { 
					height: (c1.h - headerHeight - toolbarHeight - 2 * margin) + "px"
				});
				dojo.style(this._pageframe.container, { 
					height: (c1.h - headerHeight - toolbarHeight - 7 * margin) + "px",
					width: (c1.w - 2*margin - 16) + "px"
				});
				this._tabs.resize()
			//				} catch(squelz) {
			//					console.error(squelz)
			//				}
			},
			startup: function startup() {
				console.info(traceLog(this,arguments));
				if(!this.getToolbar()) console.error("Error occured while instantiating pagetoolbar...");
				this._toolbar.placeAt(dojo.byId('websiteadmintoolbar'))
				this.ready(this.postCreate)
			},
			unload: function unload() {
				console.info(traceLog(this,arguments));
				dojo.forEach(this.observers, dojo.disconnect)
				this._toolbar.destroyRecursive();
				this._tabs.destroyRecursive();
			},
			ready: function ready(funcObject /*(self)*/) {
				console.info(traceLog(this,arguments));
				var self = this;
				if(self._tabs) {
					console.info(traceLog(this,arguments));
					if(typeof funcObject == "function") {

						funcObject(self);
					}
					return;
				}

				//	self._editor = dijit.byId('pageformEditor');
				var _interval = setInterval(function() {
					self._tabs = dijit.byId('setupTabContainer');
					if (eval(self._tabs)) { //does the object exist?
						clearInterval(_interval);
						console.info(traceLog(this,arguments));
						if(funcObject) {
							funcObject(self);
						}
					}
				}, 150);
			}
			
		});
		return Website;
	});
console.log('eval website.js');