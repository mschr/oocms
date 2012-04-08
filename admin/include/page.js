require(["dojo/_base/declare",
	"dijit/Editor",
	"dijit/_editor/plugins/EnterKeyHandling",
	"dijit/_editor/plugins/TextColor",
	"dijit/_editor/plugins/LinkDialog",
	"dijit/_editor/plugins/FontChoice",
	"dijit/_editor/plugins/ViewSource",
	"dojox/editor/plugins/Save",
	"dojox/html/entities",
	"dojox/editor/plugins/TextColor",
	"dojox/editor/plugins/FindReplace",
	"dojox/editor/plugins/InsertEntity",
	"dojox/editor/plugins/PasteFromWord",
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
	"dijit/Toolbar",
	"dijit/form/Button",
	//	"dijit/form/TextBox",
	"dijit/Tooltip",
	"OoCmS/_treebase",
	"dojo/data/ItemFileReadStore",
	"dojo/store/Memory",
	"dijit/form/ComboBox",// markup require
	"dijit/Editor" // markup require
	], function(declare, baseinterface, registry, ddom, ddomctor, ddomgeom, ddomstyle, dquery, xhr, darr, dconnect, lang, locale, dtopic, toolbar, button, ttip, treebase, readstore, memorystore, combobox, editor){
		
		var InfoText = "Du befinder dig her : <b> Side -&gt; Redigering</b><hr/>"
		+ "<i>Sidetræet</i> - Venstre sides træ vil ved valg indlæse det dokument du klikker på i editoren.<br/>"
		+"<i>Editoren</i> - Du kan også starte et nyt dokument op, første indlæsning editoren "
		+"starter med et tomt dokument der vil lagres i databasen ved valg af <b>'Gem'</b>, "
		+" alternativt kan editoren ryddes ved <b>'Ryd form'</b> knappen for at starte på en frisk<br>"

		var Page = declare("OoCmS.page", [baseinterface], {
			id: -1,
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
				if(args) lang.mixin(this, args);
				console.log('ctor')

				this.dijitrdyId = 'pageformEditor'
			},
			bindDom : function bindDom(node) {
				this.attachTo = node;
			},
			create : function create() {
				console.info(traceLog(this,arguments));
				var self = this;
				xhr.post({
					content: this.getValues(),
					url: this.editurl,
					load : function(res) {
						var id = res.replace(/.*ID=\"/, "").replace(/\".*/, "");
						self.id = ((self.id==-1) ? id : self.id);
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
				console.info(traceLog(this,arguments));
				var self = this,
				type = this._pageselector.model.store._getItemByIdentity(id);
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
				console.info(traceLog(this,arguments));
				var self = this;
				// why? purpose is ability to backstep, look into it
				dquery("[name=\"body\"]", this._form)[0].value = this._editor.getValue();
				
						

				xhr.post({
					content: this.getValues(),
					url: this.editurl + (this.editurl.indexOf('?')!=-1?"&":"?") + "id="+this.id,
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
						self.id = ((self.id==-1) ? id : self.id);
						self.saveState = new Date();
						self.updateUI();
						self._pageselector.update();
					//				updateUI(gDocument);

					}
				});
				return false;

			},
			reset: function reset() {
				this.id= -1;
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
						id: self.id,
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
				console.info(traceLog(this,arguments));
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
			onPageLoaded: function onPageLoaded(self, data, body) {
				console.info(traceLog(this,arguments));

				data = dojo.fromJson(data);
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
				self.id				= parseInt(data.id);
				self.type			= data.type;
				self.body			= body;
				self.saveState		= new Date();
				
				self.resetForm();
				self.updateUI();

			},
			resetForm: function resetForm() {
				console.info(traceLog(this,arguments));
				var form = this._form,
				self = this,
				alias = this.alias,
				title = this.title;
				dquery("[name=\"body\"]", this._form)[0].value = this.body;
				console.log(this.ready)
				this.ready.then(function() {
					self._editor.setValue(self.body);
					dijit.getEnclosingWidget(dquery("[name=\"doctitle\"]", form)[0])
					.set("value", title);
					dijit.getEnclosingWidget(dquery("[name=\"alias\"]", form)[0])
					.set("value", alias);
				});
		
			},
		
			updateUI : function updateUI() {
				console.info(traceLog(this,arguments));
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
				if(this.id != -1) {
					xhr.get({

						url:gPage.baseURI+'/openView/Documents.php?format=json&searchid='+this.id,
						load:function(res) {
							var js = eval('('+res+')'); 
							darr.forEach(js.items, function(it) {
								if(it.id==self.id) {
									ddom.byId('nSubPages').innerHTML = it.children ? it.children.length : 0;
									self.nResources = it.children ? it.children.length : 0;
								}
							});
						}
					});
					// nResources
					xhr.get({
						url:gPage.baseURI+'/openView/Resources.php?format=json&searchdoc='+this.id,
						load:function(res) {
							var js = eval('('+res+')'), children=0;
							var resourceList = [];
							darr.forEach(js.items, function(it) {
								if(RegExp(","+self.id+",").test(","+it.attachId+","))
								{
									children++;
								}
								resourceList.push(it.id);
							});
							ddom.byId('nResources').innerHTML = children;
							self.nResources = children;
							resourceList = "," + resourceList.join(",") + ",";
							dquery("tbody [id*='resource_']", ddom.byId('actionCol')).forEach(function(body) {
								if(!RegExp(","+body.id.replace(/.*_/, "")+",").test(resourceList))
									body.parentNode.removeChild(body);
							});
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

				if(this.id != -1) {
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
				console.info(traceLog(this,arguments));
				// not running body through editor prettyprint to validate alikeness
				var titlechange =dijit.getEnclosingWidget(this._form.elements['doctitle']).get("value") != this.title,
				aliaschange = dijit.getEnclosingWidget(this._form.elements[ 'alias'  ] ).get("value") != this.alias,
				bodyblank = this._editor.getValue().trim() == "<br />" && this.body == "",
				bodychange = this._editor.getValue() != this.body;
				return (!bodyblank && bodychange) || titlechange || aliaschange;
			},
			getToolbar : function getToolbar() {
				console.info(traceLog(this,arguments));
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new dijit.Toolbar({});
				bt = new button({
					label: "Ryd form",
					showLabel: true,
					onClick: lang.hitch(this, "reset", true),
					iconClass: "dijitEditorIcon dijitEditorIconUndo"
				});
				tb.addChild(bt);
				bt = new button({
					label: "Opret",
					showLabel: true,
					onClick: lang.hitch(this, "create"),
					iconClass: "dijitEditorIcon dijitEditorIconNewPage"
				});
				tb.addChild(bt);
				bt = new button({
					label: "Gem",
					showLabel: true,
					onClick: lang.hitch(this, "update"),
					iconClass: "dijitIconSave"
				});
				tb.addChild(bt);
				bt = new button({
					label: "Slet",
					showLabel: true,
					onClick: lang.hitch(this, "del"),
					iconClass: "dijitIconDelete"
				});
				tb.addChild(bt);
				bt = new button({
					showLabel: false,
					iconClass: "OoIcon-18 OoIconInfo",
					style:'float:right',
					id: 'infobutton'
				});
				bt.startup();
				tb.addChild(bt);
				this.ready.then(function() {
					new ttip({
						label: "<div style=\"width:450px\">"+InfoText+"</div>",
						connectId: ['infobutton']
					})
				})
				return this._toolbar;
			},
			getEditor: function() {
				if(this._editor) return this._editor;
				return (this._editor = registry.byId('pageformEditor'));
			},
			getForm: function getForm() {
				console.info(traceLog(this,arguments));
				if(this._form) return this._form;
				this._form = this.attachTo.getElementsByTagName('form')[0];
				this.editurl = this.editurl || this._form.action;
				return this._form;

			},
			getAttachBox: function getAttachBox() {
				console.info(traceLog(this,arguments));
				if(this._attachbox) return this._attachbox;
				var self = this;
				this._attachbox = new combobox({
					data:[]
				}, 'attachidcombo');
				this.observers.push(dconnect.connect(this._pageselector, "onLoad", this, function() {
				console.log("store load");

					var data = [{
						id:9999,
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
					this._attachbox.set("value", setVal);
				}));
				this._attachbox.onChange = function(val) {
					if(val == "Øverst") self.attachId = "";
					else if(val != "") self.attachId = parseInt(val.split(" - ")[0]);
				}
				return this._attachbox;
			},
			getSelector: function getSelector() {
				console.info(traceLog(this,arguments));
				if(this._pageselector) return this._pageselector;
				this._pageselector = new treebase({
					rootLabel : "Dokumenter",
					store: new readstore({
						clearOnClose:true,
						url: gPage.baseURI + '/openView/Documents.php?format=json',
						urlPreventCache: true,
						hierarchical: true
					}),
					actionText: {
						page : '<h4>Kategori (Page)</h4><ul style="max-width: 520px;">'+
						'<li>Dobbeltklik på elementet for at visuelt at se indholdet (uden menu-navigation).</li>'+
						'<li>Højreklik for yderligere muligheder, kan f.eks. konverteres til '+
						'under-side og derved tilknyttes en anden side ved træk-og-slip.</li></ul>',
						subpage : '<h4>Underside (SubPage)</h4><ul style="max-width: 520px;">'+
					'<li>Træk undersiden over en <b>Page</b> i første niveau for at tilknytte til en kategori.</li>'+
					'<li>Dobbeltklik for at hente og vise selve indholdet i popup.</li>'+
					'<li>Højreklik for yderligere muligheder, kan således f.eks. '+
					'slettes eller konverteres til top-niveau kategori-side</li></ul>'
					
					}
				});
				var w, tb = this._pageselectortoolbar = new toolbar({
					style:'padding-right:5px'
				}, "pagetoolbar");
				//				w = new dijit.form.TextBox({
				//					store: this._pageselector.model.store,
				//					searchAttr: "title",
				//					
				//				})
				//				console.log(w)
				//				tb.addChild(w);
				w = new button({
					iconClass:"dijitIconUndo",
					label : '&thinsp;',
					title: 'Genindlæs sidetræ',
					style: 'float: right;margin-top:2px; height: 18px', /// << match dijitEditorIcon 'pr theme'
					onClick: lang.hitch(this._pageselector,this._pageselector.update)
				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				});
				tb.addChild(w);
				ddomctor.create('div', {
					style:'clear:both'
				},tb.domNode, "last");
				//				this._pageselector.infoHelpNode = dquery("#pageleftcolumn .helpNode")[0];
				//				this._pageselector.infoHoverNode = dquery("#pageleftcolumn .infoNode")[0];
				this._pageselector.placeAt('pageselector').startup();
				this._pageselector.model.store.fetch();
				this._pageselector.onClick = lang.hitch(this, this.onPageSelected);
				this._pageselectortoolbar.placeAt("pageselectortbar").startup()
				return this._pageselector;
			},
//			getEditor: function getEditor() {
//				if(this._editor) return this._editor;
//				this._editor = new dijit.Editor({
//					plugins:[
//					'|',
//					'bold','italic','underline','|',
//					'createLink',
//					'unlink',
//					'|',
//					'insertImage',
//					'insertEntity',
//					'pastefromword',
//					'|',
//					'foreColor',
//					'hiliteColor',
//					'findreplace',
//					{
//						name: 'prettyprint',
//						entityMap: dojox.html.entities.html.concat(dojox.html.entities.latin),
//						indentBy: 3,
//						lineLength: 80,
//						xhtml: true
//					},
//					'customsave',
//					{
//						name: 'preview',
//						stylesheets: [
//						'{{dataUrl}}dojox/editor/tests/testBodySheet.css',
//						'{{dataUrl}}dojox/editor/tests/testContentSheet.css'
//						]
//					},
//					'viewsource','|',
//					'||',
//					'|',
//					{
//						name: 'fontName', 
//						plainText: true
//					}, '|', {
//						name: 'fontSize', 
//						plainText: true
//					}, '|', {
//						name: 'formatBlock', 
//						plainText: true
//					},'|'
//					], 
//					styleSheets:'http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojo/resources/dojo.css'
//				});
//			},
			onPageSelected: function onPageSelected(item) {
				console.info(traceLog(this,arguments));
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
				this.id == -1 && this.create() || this.update();
			},
			postCreate: function postCreate() {
				console.info(traceLog(this,arguments));
				console.log('postcreate')
				this.inherited(arguments);
				this._isdraftcombo = registry.byId('isdraftcombo');
				this._isdraftcombo.onChange = function(val) {
					this.isdraft = (val == "Kladde") ? 1 : 0;
					if(this.isdraft == 1) this.getAttachBox().set("value", "Øverst");
				}
				if(!this.getSelector()) console.error("Error occured while instantiating pageselector...");
				if(!this.getAttachBox()) console.error("Error occured while instantiating attachId combobox...");
				var save_action = lang.hitch(this, this.editor_saveaction)
				dojo.subscribe("editor/save", save_action);
				this._editor = this.getEditor();
				this._editor.save = save_action;
				this.updateUI();
				this.layout();
			},
			startup: function startup() {
				console.log('startup')

				console.info(traceLog(this,arguments));
				if(!this.getForm()) console.error("No form with submit instructions underneath given element or error occured while instantiating...");
				if(!this.getToolbar()) console.error("Error occured while instantiating pagetoolbar...");
				ddom.byId('pagetoolbarWrapper').appendChild(this._toolbar.domNode);
				this.inherited(arguments);
			},
			layout: function() {
				this.inherited(arguments)
				var ed = this.getEditor();
				if(!this._pageframe.formwrapper) this._pageframe.formwrapper = ddom.byId('formWrapper');

				ddomstyle.set(this._pageframe.formwrapper, {
					height: (baseinterface.calcmarginbox(this._pageframe.center).h
						- ddomgeom.getMarginBox(this._pageframe.formwrapper).t 
						-baseinterface.calcextents(this._pageframe.center).h
						-baseinterface.calcextents(this._pageframe.formwrapper).h) + "px"
				})
				if(ed)
					ed.resize({
						h: baseinterface.calcmarginbox(this._pageframe.center).h // outer height
						- ddomgeom.getMarginBox(this._editor.domNode).t // minus offset editor node
						-  baseinterface.calcextents(this._editor.domNode).h - 5
						-  baseinterface.calcextents(this._pageframe.center).h 
					})
			},
			unload: function unload() {
				console.info(traceLog(this,arguments));
				darr.forEach(this.observers, dconnect.disconnect)
				this._toolbar.destroyRecursive();
				this._editor.destroyRecursive();
				this._pageselector.store.close()
				this._pageselector.destroyRecursive();
				this._isdraftcombo.destroy();
				this._attachbox.destroy();
				try {
					dojo.unsubscribe("editor/save");
				}catch(e){}
				ddomctor.destroy(this._form);
			}
			
		});
		return Page;
	});
console.log('eval page.js');