define(["dojo/_base/declare",
	"dijit/layout/BorderContainer",
	//	"OoCmS/AbstractController",
	"dijit/registry",
	"dojo/dom",
	"dojo/dom-construct",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dojo/dom-form",
	"dojo/query",
	"dojo/_base/xhr",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/_base/lang",
	"dojo/date/locale",
	"dojo/topic",
	"dojo/json",
	"dijit/_WidgetBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/page.html",
	"dijit/Toolbar",
	"dijit/form/Button",
	"dijit/form/Form",
	"dijit/form/TextBox",
	"dijit/Tooltip",
	"OoCmS/_treebase",
	"dojo/data/ItemFileReadStore",
	"dojo/store/Memory",
	"dijit/form/ComboBox",
	"dijit/Editor",
	
	"dojox/html/entities",
	"dojox/editor/plugins/Save",
	"dojox/editor/plugins/Preview",

	"dijit/_editor/plugins/EnterKeyHandling",
	"dijit/_editor/plugins/TextColor",
	"dijit/_editor/plugins/LinkDialog",
	"dijit/_editor/plugins/FontChoice",
	"dijit/_editor/plugins/ViewSource",
	"dijit/_editor/plugins/TextColor",

	"dojox/editor/plugins/FindReplace",
	"dojox/editor/plugins/InsertEntity",
	"dojox/editor/plugins/PasteFromWord",
	"dojox/editor/plugins/PrettyPrint",
	"dojox/editor/plugins/ToolbarLineBreak"
	], function(declare, djborderlayout, registry, ddom, ddomctor, ddomgeom, ddomstyle, ddomform, dquery, xhr, darr, dconnect, dlang, locale, dtopic, djson,
		djwidgetbase, djtemplated, oopagetemplate, toolbar, button, djform, djtextbox, ttip, treebase, readstore, memorystore, combobox, editor, dxhtmlentities, dxplugSave, dxplugPreview){
		
		var InfoText = "Du befinder dig her : <b> Side -&gt; Redigering</b><hr/>"
		+ "<i>Sidetræet</i> - Venstre sides træ vil ved valg indlæse det dokument du klikker på i editoren.<br/>"
		+"<i>Editoren</i> - Du kan også starte et nyt dokument op, første indlæsning editoren "
		+"starter med et tomt dokument der vil lagres i databasen ved valg af <b>'Gem'</b>, "
		+" alternativt kan editoren ryddes ved <b>'Ryd form'</b> knappen for at starte på en frisk<br>"
		// hook ctrl+s and save button in editor up with a topic
		//		var mySavePlugin = declare("mySavePlugin", [dxplugSave],{
		//			save: function(content){
		//				dtopic.publish("editor/save")
		//			}
		//		});
		//		dtopic.subscribe("dijit.Editor.getPlugin", null, function(o){
		//			if(o.plugin)
		//				return;
		//			var name = o.args.name.toLowerCase();
		//			if(name ===  "customsave")
		//				o.plugin = new mySavePlugin({
		//					url: ("url" in o.args)?o.args.url:""
		//				});
		//		});



		var ooplugSave = declare("_custom_save",dxplugSave,{
			url: 'override',
			save: function(content){
				dtopic.publish("editor/save")
			}

		});
		var ooplugPreview = declare("_custom_preview", dxplugPreview, {
			_preview: function() {
				// summary:
				//		Function to trigger previewing of the editor document
				// tags:
				//		private
				xhr.post({
					url: gPage.baseURI + "index.php?OpenDoc&previewfetch=1",
					content: this.getContents(),
					load: function(content) {
						try{
							var win = window.open("javascript: ''", this._nlsResources["preview"], "status=1,menubar=0,location=0,toolbar=0");
							win.document.open();
							win.document.write(content);
							win.document.close();							
						}catch(e){
							console.warn(e);
						}
					}
				})
			}
		})
		// Register this plugin.
		// fails if local AMD fetched variable is used
		dojo.subscribe(dijit._scopeName + ".Editor.getPlugin",null,function(o){
			if(!o||o.plugin){
				return;
			}
			var name = o.args.name.toLowerCase();
			if(name === "oosave"){
				o.plugin = new ooplugSave({
					url: ("url" in o.args)?o.args.url:"",
					logResults: ("logResults" in o.args)?o.args.logResults:true
				});
			} else if(name == "oopreview") {
				o.plugin = new ooplugPreview({
					url: ("url" in o.args)?o.args.url:"",
					logResults: ("logResults" in o.args)?o.args.logResults:true,
					getContents: o.args.getPageValues ? o.args.getPageValues : null
				});
			}
		});


		var pageform = declare("OoCmS._pageform", [djwidgetbase, djtemplated], {
			templateString: oopagetemplate,
			destroy: function() {
				traceLog(this,arguments)
				this._doctitle.destroy();
				this._alias.destroy();
				this._isdraft.destroyRecursive();
				this._attachid.destroyRecursive();
				this._form.destroyRecursive();
				console.log('FORM' , this.getChildren())
				this.inherited(arguments);
			},
			getValues : function() {
				var qisdraft =this._isdraft.store.query({
					name:this._isdraft.get("value")
				}),
				qattachid = this._attachid.store.query({
					name:this._attachid.get("value", "")
				})
				return {
					body : this._editor.get("value"),
					doctitle: this._doctitle.get("value"),
					alias: this._alias.get("value"),
					isdraft: qisdraft.length > 0 ? qisdraft[0].id : 0,
					attachId: qattachid.length > 0 ? qattachid[0].id : 0
				};
			},

			setValues: function(values) {
				values.isdraft = typeof values.isdraft == "undefined" ? "0" : values.isdraft;
				values.attachId = typeof values.attachId == "undefined" ? "" : values.attachId;
				var attach = (values.attachId == "" ? "Øverst" : this._attachid.store.query({
					id:values.attachId
				})[0].name),
				isdraft = this._isdraft.store.query({
					id:values.isdraft ? 1 : 0
				})[0].name;
				this._doctitle.set("value", values.doctitle);
				this._alias.set("value", values.alias);
				this._attachid.set("value", attach);
				this._editor.set("value", values.body)
				this._isdraft.set("value", isdraft)
			},	
			postCreate: function() {
				
				traceLog(this,arguments)
				this.domNode.className += " OoCmSPageForm"
				this._doctitle = new djtextbox({}, this._doctitle);
				this._alias = new djtextbox({}, this._alias);
				this._isdraft = new combobox({}, this._isdraft);
				this._form = new djtextbox({
					hidden: true, 
					type:"hidden"
				}, this._form);
				// set in page construct
				//	this._form._attachid = this._attachbox; 
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/TextColor.css"));
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/PasteFromWord.css"));
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/FindReplace.css"));
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/InsertEntity.css"));
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/Save.css"));
				loadCSS(require.toUrl("dojox/editor/plugins/resources/css/Preview.css"));
				var ed = new editor({
					name: 'body',
					extraPlugins:[ '||',
					{
						name: 'oopreview',
						getPageValues: this.getPageValues
					},
					'createLink',
					'unlink',
					'|',
					'insertImage',
					'insertEntity',
					//					'pastefromword',
					//					'|',
					'foreColor',
					'hiliteColor',
					'findreplace',
					{
						name: 'prettyprint',
						entityMap: dxhtmlentities.html.concat(dxhtmlentities.latin),
						indentBy: 3,
						lineLength: 80,
						xhtml: true
					},
					'oosave', 

					'viewsource','|',
					'||',
					{
						name: 'fontName', 
						plainText: true
					}, '|',

					{
						name: 'fontSize', 
						plainText: true
					}, '|',

					{
						name: 'formatBlock', 
						plainText: true
					}], 
					styleSheets: gPage.baseURI + 'css/oocms.css'
				}, this._editor);
				this._editor = ed;
			}
		//			_doctitle dijit.form.TextBox
		//_alias dijit.form.TextBox
		//_isdraft dijit.form.ComboBox
		//_editor

		});

		var Page = declare("OoCmS.page", [djborderlayout], {
			pageid: -1,
			attachId: null,
			position: null,
			body: '',
			alias: '',
			title: '',
			type: 'page',
			created: null,
			lastmodified: null,
			creator: '',
			lasteditedby: '',
			editors: '',
			isdraft: 0,
			showtitle: false,
			keywords: '',
			editurl : '',

			_dateFromSQL : {
				selector: 'date', 
				formatLength:'full', 
				datePattern: 'EEE, d MMM y HH:mm:ss Z', 
				locale: 'da-dk'
			},
			_dateToScreen : {
				selector: 'date', 
				formatLength:'full', 
				datePattern: 'd MMM y HH:mm:ss', 
				locale: 'da-dk'
			},
			_toolbar: undefined,
			_form : undefined,	
			_editor: undefined,

			constructor: function constructor(/*Object*/ args){
				if(args) dlang.mixin(this, args);
				traceLog(this,arguments)
				//				this.pageid = this.pageid + "_" + Page.instancecount;


				this.dijitrdyId = null; //'pageformEditor'
				this.inherited(arguments);
				
			},
			bindDom : function bindDom(node) {
				this.attachTo = node;
			},
			_create : function create() {
				traceLog(this,arguments)
				var self = this;
				xhr.post({
					content: this.getValues(),
					url: this.editurl,
					load : function(res) {
						var id = res.replace(/.*ID=\"/, "").replace(/\".*/, "");
						self.pageid = ((self.pageid==-1) ? id : self.pageid);
						if(res.indexOf("SAVED") == -1) {
							dtopic.publish("notify/save/error", "Fejl!", res, [ {
								id:'saveoption',
								cb:function() {
									self.create();
								},
								classes:'dijitEditorIcon dijitEditorIconSave'
							}, {
								id:'canceloption',
								classes:'dijitEditorIcon dijitEditorIconUndo'
							}
							]);
							return;
						}
						dtopic.publish("notify/save/success", "Oprettet!", res, [ {
							id:'canceloption',
							classes:'dijitEditorIcon dijitEditorIconUndo'
						} ]);
						self.read(id);
						self._pageselector.update();

					}
				});

			},
			read : function read( id ) {
				traceLog(this,arguments)
				var self = this,
				store = this._pageselector.model.store,
				type= store._getItemByIdentity(id);
				
				if(!store._loadFinished) {
					var handle = this.getSelector().on("load", function() {
						handle.remove();
						self.read(id);
					});
					return;
				} else if (!type) return;
				type = type._S.getValue(type, "type");

				xhr.get( {
					load: function(body) { // on contents loaded
						xhr.get( {
							load: function(json) { // on dataobj loaded
								self.onPageLoaded(self, json, body);
							},
							url: '../openView/Documents.php?type='+type+'&searchid='+id+'&format=json'
						});
				
					},
					url: '../openView/Documents.php?type='+type+'&searchid='+id+'&format=contents'
				});

			},
			update : function update() {
				traceLog(this,arguments)
				var self = this;
				xhr.post({
					content: this.getValues(),
					url: this.editurl + (this.editurl.indexOf('?')!=-1?"&":"?") + "id="+this.pageid,
					load : function(res) {
						if(res.indexOf("SAVED") == -1) {
							dtopic.publish("notify/save/error", "Fejl!", res, [ {
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
						dtopic.publish("notify/save/success", "Dokument opdateret!", [ {
							id:'canceloption',
							classes:'dijitEditorIcon dijitEditorIconUndo'
						} ]);
						var id = res.replace(/.*ID=\"/, "").replace(/\".*/, "");
						self.pageid = ((self.pageid==-1) ? id : self.pageid);
						self.saveState = new Date();
						self.updateUI();
						self._pageselector.update();
					//				updateUI(gDocument);

					}
				});
				return false;

			},
			reset: function reset() {
				this.pageid= -1;
				this.attachId= null;
				this.position= null;
				this.body= '';
				this.alias= '';
				this.title= '';
				this.type= 'page';
				this.created= null;
				this.lastmodified= null;
				this.creator= '';
				this.lasteditedby= '';
				this.editors= '';
				this.isdraft= 0;
				this.showtitle= false;
				this.keywords= '';
				this.savedState = null;
				this.resetForm();
				this.updateUI();
				console.log('hier')
				this._pageselector.update();
			},
			_performDelete: function _performDelete() {
				// scope of application
				var dialog = this._dialog,	// oocms.application
				self = this._widgetInUse,	// oocms.page
				recurse = self.isdraft&&dquery(
					"input[type=checkbox]", dialog.domNode)[0].checked;
				// TODO if draft, we cannot have contain subpages?
				xhr.post({
					url:self.editurl.replace(/\?.*/, "") + "?delElement",
					content:  {
						id: self.pageid,
						type : self.type,
						partial:true,
						recursive: recurse
					},
					load: function(res) {
						if(/DELETED/.test(res)) {
							self.reset();
							dtopic.publish("notify/delete/success", "Sletning udført", res.replace(/\n/g, "<br>"), [ {
								id:'canceloption',
								classes:'dijitEditorIcon dijitEditorIconUndo'
							} ]);
							registry.byId("continueoption") && registry.byId("continueoption").destroy();
							dialog.hide();
						} else {
							dtopic.publish("notify/delete/error", "Fejl!", res, [ {
								id:'canceloption',
								classes:'dijitEditorIcon dijitEditorIconUndo'
							}]);
					
						}
					} // load
				});
			},
			del : function del( ) {
				traceLog(this,arguments)
				dtopic.publish("notify/delete/confirm", "Advarsel, sletter dokument",
					'<div style="vertical-align:middle;line-height:14px"><label for="recurseDelete">'+
					'Slet alle underdokumenter</label><input type="checkbox" '+
					'id="recurseDelete" style="float:right"/></div>', [ {
						id:'continueoption',
						cb:this._performDelete,
						scope: ctrl,
						classes:'dijitEditorIcon dijitEditorIconDelete'
					}, {
						id:'canceloption',
						classes:'dijitEditorIcon dijitEditorIconUndo'
					}
					]);
			},
			// gets form values an asserts them for proper format before sending to
			// server. use this instead of calling _form.getValues() directly
			getValues : function getValues() {
				var values = this._form.getValues();
				// if a draft, it cannot be attached to a page
				if(values.attachId == 0 || values.isdraft == 1) values.attachId = "";
				// if a draft it must match toplevel queries (cannot be subpage)
				// _form.getValues() does not supply this attribute
				if(values.isdraft == 1 || values.attachId == "") values.form = "page"
				else if(values.attachId != "") values.form = "subpage"
				return values;
			},
			onPageLoaded: function onPageLoaded(self, data, body) {
				traceLog(this,arguments)

				data = djson.parse(data);
				console.log(data);
				if(!data || !data.items) return;
				data=data.items[0];
				self.title			= data.title?data.title:"";
				self.alias			= data.alias ? data.alias : "";
				self.keywords		= data.keywords ? data.keywords : "";
				self.attachId		= data.attachId ? parseInt(data.attachId) : null; 
				self.position		= data.position ? parseInt(data.position) : null;
				self.editors		= data.editors ? data.editors : "";
				self.creator		= data.creator ? data.creator : "";
				self.lasteditedby	= data.lasteditedby ? data.lasteditedby : "";
				self.created		= locale.parse(data.created, self._dateFromSQL);
				self.lastmodified	= locale.parse(data.lastmodified, self._dateFromSQL);
				self.isdraft		= data.isdraft;
				self.showtitle		= !!data.showtitle;
				self.pageid				= parseInt(data.id);
				self.type			= data.type;
				self.body			= body;
				self.saveState		= new Date();
				
				self.resetForm();
				self.updateUI();

			},
			resetForm: function resetForm() {
				traceLog(this,arguments)
	
				this._form.setValues({
					doctitle: this.title,
					alias: this.alias,
					isdraft: this.isdraft,
					body: this.body
				})		
			},
		
			updateUI : function updateUI() {
				traceLog(this,arguments)
				var t = "page",
				self = this,
				actions = this.getToolbar().getChildren();
				//				// for some reason, we some how get a display:none on select..
				//				if(registry.byId('isdraftcombo', this._form))
				//					dojo.style(registry.byId('isdraftcombo', this._form), {
				//						display:''
				//					});
				//console.log("get"+gPage.baseURI+"/openView/"+(t == "include" ? "Resources":"Documents")+".php"+ "?format=json&type="+t+"&searchid="+gDocument.id);
				////////////// FAIL TODO: create logic for searchid's
				//nAttachments
				if(this.pageid != -1) {
					xhr.get({

						url:gPage.baseURI+'/openView/Documents.php?format=json&searchid='+this.pageid,
						load:function(res) {
							var js = eval('('+res+')'), children = 0; 
							darr.forEach(js.items, function(it) {
								if(it.id==self.pageid) {
									children++;
								}
							});
							ddom.byId('nSubPages').innerHTML = children
						}
					});
					// nResources
					xhr.get({
						url:gPage.baseURI+'/openView/Resources.php?format=json&searchdoc='+(this.attachId != "" ? this.attachId : this.pageid),
						load:function(res) {
							var js = eval('('+res+')'), children=0;
							ddom.byId('nResources').innerHTML = js.items.length;
							return;
						///// server does the lookup no need to iterate
						//							darr.forEach(js.items, function(it) {
						//								if(RegExp(","+self.pageid+",").test(","+it.attachId+","))
						//								{
						//									children++;
						//								}
						//							});
						//							ddom.byId('nResources').innerHTML = children;
						}
					});
				}
				ddom.byId('onfly-restorepoint').innerHTML = (typeof this.saveState != "undefined") ? locale.format((this.saveState), this._dateToScreen) : "";
				if((this.attachId && this.attachId.toString().search(/^-?[0-9]+$/) == 0)) {
					var store = this.getSelector().model.store;
					if(!store._loadInProgress) {
						var attachItem = store._getItemByIdentity(this.attachId),
						id = store.getValue(attachItem, "id"),
						title = store.getValue(attachItem, "title");
						this.getAttachBox().set("value", id + " - " + title);
					}
				} else {
					this.getAttachBox().set("value", "Øverst");
				}
				//	ddom.byId('onfly-attachId').innerHTML =  ? this.attachId : "Øverst"
				ddom.byId("onfly-creator").innerHTML = this.creator;
				ddom.byId("onfly-lastmodified").innerHTML = (this.lastmodified != null && typeof this.lastmodified == "object") ? locale.format(this.lastmodified, this._dateToScreen) : "Ukendt";
				ddom.byId("onfly-created").innerHTML = (this.created != null && typeof this.created == "object") ? locale.format(this.created, this._dateToScreen) : "Ukendt";
				this._isdraftcombo.setValue(this.isdraft == 1 ? "Kladde" : "Publiceret");

				if(this.pageid != -1) {
					actions[0].set("disabled", true);
					actions[1].set("disabled", true);
					actions[2].set("disabled", false);
					actions[3].set("disabled", false);
				}else{
					actions[0].set("disabled", false);
					actions[1].set("disabled", false);
					actions[2].set("disabled", true);
					actions[3].set("disabled", true);
				}
			},
			isDirty : function isDirty() {
				traceLog(this,arguments)
				// not running body through editor prettyprint to validate alikeness
				var cur = this.getValues()
				var titlechange = cur.doctitle != this.title,
				aliaschange = cur.alias != this.alias,
				bodyblank = cur.body.trim() == "<br />" && this.body == "",
				bodychange = cur.body != this.body;
				return (!bodyblank && bodychange) || titlechange || aliaschange;
			},

			getToolbar : function getToolbar(mixin) {
				traceLog(this,arguments)
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new toolbar(dlang.mixin({
					style:'heigth: 24px',
					id:'pageToolbar'
				},mixin));
				bt = new button({
					id:'pageToolbar-clear',
					label: "Ryd form",
					showLabel: true,
					onClick: dlang.hitch(this, "reset", true),
					iconClass: "dijitEditorIcon dijitEditorIconUndo"
				});
				tb.addChild(bt);
				bt = new button({
					id:'pageToolbar-create',
					label: "Opret",
					showLabel: true,
					onClick: dlang.hitch(this, "create"),
					iconClass: "dijitEditorIcon dijitEditorIconNewPage"
				});
				tb.addChild(bt);
				bt = new button({
					id:'pageToolbar-save',
					label: "Gem",
					showLabel: true,
					onClick: dlang.hitch(this, "update"),
					iconClass: "dijitIconSave"
				});
				tb.addChild(bt);
				bt = new button({
					id:'pageToolbar-delete',
					label: "Slet",
					showLabel: true,
					onClick: dlang.hitch(this, "del"),
					iconClass: "dijitIconDelete"
				});
				tb.addChild(bt);
				bt = new button({
					id:'pageToolbar-info',
					showLabel: false,
					iconClass: "OoIcon-18 OoIconInfo",
					style:'float:right'
				});
				bt.startup();
				tb.addChild(bt);
				

				
				return this._toolbar;
			},
			getEditor: function() {
				if(this._form._editor) return this._form._editor;
				return null
			},
			getForm: function getForm(mixin) {
				traceLog(this,arguments)
				if(this._form) return this._form;

				this._form = new pageform(dlang.mixin({
					}, mixin));
				this._form.startup()
				new ttip({
					id:'pageToolbar-infoTooltip',
					label: "<div style=\"width:450px\">"+InfoText+"</div>",
					connectId: ['pageToolbar-info']
				}).startup()
				this.getAttachBox()
				this.editurl = this.editurl || this._form.formNode.action;
				return this._form;

			},
			getIsDraftBox: function getIsDraftBox() {
				traceLog(this,arguments)
				var box = this.getForm()._isdraft;
				if(box && !box.nodeType) return box;
			},
			getAttachBox: function getAttachBox() {
				traceLog(this,arguments)
				var box = this.getForm()._attachid;
				if(box && !box.nodeType) return box;
				var self = this;
				this._attachbox = new combobox({
					id: 'attachIdComboBox',
					data:[]
				}, this._form._attachid);
				this._form._attachid = this._attachbox;
				console.log(this._form._attachid)
				//				this._attachbox.placeAt(this._form._attachid.parentNode);
				//				console.log(this._form._isdraft)
				this.getSelector().on("load", dlang.hitch(this, function() {


					var data = [{
						id:0,
						name:'Øverst'
					}], 
					cur = this.attachId,
					setVal = "",
					src= this._pageselector.model.store._arrayOfAllItems;
			
					darr.forEach(src, function(item) {
						var s = item._S, isdraft = s.getValue(item, "isdraft"), id = s.getValue(item, "id")
						if(isdraft == "0") {
							if(cur == id)
								setVal = id + " - " + s.getValue(item, "title");
							data.push({
								name: id + " - " + s.getValue(item, "title"),
								id: id
							});
						}
					});
					this._attachbox.set("store", new memorystore({
						data:data
					}));
					this._attachbox.set("value", setVal == "" ? "Øverst" : setVal);
				}));
				this._attachbox.onChange = function(val) {
					if(val == "Øverst") self.attachId = "";
					else if(val != "") self.attachId = parseInt(val.split(" - ")[0]);
				}
				return this._attachbox;
			},
			getSelectorToolbar : function getSelectorToolbar(mixin){
				dlang.mixin({
					id: 'pageSelectorToolbar',
					style:'padding-right:5px; height:24px'
				}, mixin)
				if(this._pageselectortbar) return this._pageselectortbar
				var w, tb = new toolbar(mixin),
				ps=this.getSelector()

				w = new button({
					id: 'pageSelectorToolbar-update',
					iconClass:"dijitIconUndo",
					label : '&thinsp;',
					title: 'Genindlæs sidetræ',
					style: 'float: right;margin-top:2px; height: 18px', /// << match dijitEditorIcon 'pr theme'
					onClick: dlang.hitch(ps,ps.update)
				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				});
				tb.addChild(w);
				ddomctor.create('div', {
					style:'clear:both'
				},tb.domNode, "last");
				return (this._pageselectortbar = tb);
			},
			getSelector: function getSelector(mixin) {
				traceLog(this,arguments)
				if(this._pageselector) return this._pageselector;
				dlang.mixin(mixin,{
					id: 'pageSelectorTree',
					rootLabel : "Dokumenter",
					store: new readstore({
						clearOnClose:true,
						url: gPage.baseURI + '/openView/Documents.php?format=json',
						urlPreventCache: true,
						hierarchical: true
					})
				})
				this._pageselector = new treebase(mixin);
				
				this._pageselector.model.store.fetch();
				this._pageselector.on("click", dlang.hitch(this, this.onPageSelected));
				return this._pageselector;
				
			},
			onDraftChanged: function(val) {
				//dont do this, have backstep capeability
				//this.isdraft = (val == "Kladde") ? 1 : 0; 
				if(val == "Kladde") this.getAttachBox().set("value", "Øverst");
			},
			onAttachChanged: function(val) {
				if(val != "Øverst") this.getIsDraftBox().set("value", "Publiceret")
			},
			onPageSelected: function onPageSelected(item) {
				traceLog(this,arguments)
				if(!item || !item._S) return;
				var id = item._S.getValue(item, "id"),
				self = this;
				
				if(!id || id == "" || id > 9990) return;
				if(this.isDirty()) {
					dtopic.publish("notify/dirty/confirm", "Vigtigt!", null, [{
						id:'continueoption',
						label: 'Forlad nuværende',
						cb: function() {
							console.log('hier')
							self.read(id);
						}
					},{
						id:'canceloption',
						label: 'Fortsæt redigering'
					}]);
				} else self.read(id);
				
			},
			editor_saveaction: function() {
				this.pageid == -1 && this.create() || this.update();
			},
			postCreate: function postCreate() {
				traceLog(this,arguments)
				var self = this,
				left_layout = new djborderlayout({
					region:'left', 
					style:'width: 190px;overflow-x:hidden', 
					splitter:false,
					id: 'leftcolumn_layout'
				}),
				center_layout = new djborderlayout({
					region: 'center', 
					gutters:false, 
					splitters:false
				}),
				selector = this.getSelector({
					region: 'center',
					gutter:false
				});

				this.domNode.className += " OoCmSPage"

				left_layout.addChild(this.getSelectorToolbar({
					splitter: false,
					gutter: false,
					region: 'top'
				}));
				left_layout.addChild(selector);
				center_layout.addChild(this.getToolbar({
					region: 'top',
					splitter:false,
					gutter:false
				}));
				center_layout.addChild(this.getForm({
					region:'center',
					splitter:false,
					gutter:false
				}));
				this.addChild(new djwidgetbase({
					id:'pageHeader',
					region:'top',
					splitter: false,
					gutter: false
				}, ddomctor.create('div', {
					className:'paneHeader',
					innerHTML:'Sider &gt; Opsætning'
				})));
				this.addChild(left_layout);
				this.addChild(center_layout);
				try {
					
					this._form._isdraft.on("change", dlang.hitch(this, this.onDraftChange));
					var save_action = dlang.hitch(this, this.editor_saveaction)
					dtopic.subscribe("editor/save", save_action);
					this._form._editor.save = save_action;
				} catch(e) {
					console.error(e)
				}
				this.inherited(arguments);
				return;
				
				if(!this.getSelector()) console.error("Error occured while instantiating pageselector...");
				if(!this.getAttachBox()) console.error("Error occured while instantiating attachId combobox...");
				this.updateUI();
				this.layout();
			},
			startup: function startup() {
				traceLog(this,arguments)
				this.inherited(arguments);
				return;				

				this.inherited(arguments);
			},
			//			layout: function() {
			//				this.inherited(arguments)
			//				var ed = this.getEditor();
			//				
			//				//				ddomstyle.set(this._pageframe.formwrapper, {
			//				//					height: (djborderlayout.calcmarginbox(this._pageframe.center).h
			//				//						- ddomgeom.getMarginBox(this._pageframe.formwrapper).t 
			//				//						-djborderlayout.calcextents(this._pageframe.center).h
			//				//						-djborderlayout.calcextents(this._pageframe.formwrapper).h) + "px"
			//				//				})
			//				if(ed)
			//					ed.resize({
			//						h: djborderlayout.calcmarginbox(this._pageframe.center).h // outer height
			//						- ddomgeom.getMarginBox(this._editor.domNode).t // minus offset editor node
			//						-  djborderlayout.calcextents(this._editor.domNode).h - 5
			//						-  djborderlayout.calcextents(this._pageframe.center).h 
			//					})
			//			},
			unload: function unload() {
				traceLog(this,arguments)
				this._form.destroy();
				var ttip = registry.byId('pageToolbar-infoTooltip')
				if(ttip) ttip.destroy();
				darr.forEach(this.observers, dconnect.disconnect)
				this._pageselector.store.close()
				try {
					dtopic.unsubscribe("editor/save");
				}catch(e){}
			}
			
		});
		Page.instancecount = 0;
		return Page;
	});
console.log('eval page.js');