define(["dojo/_base/declare",	
	"dojo/_base/lang",
	"dojo/_base/array",
	"dojo/_base/xhr",
	"dojo/dom-construct",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dojo/dom-class",
	"dojo/_base/connect",
	"dijit/_WidgetsInTemplateMixin",
	"dojo/text!./resources/Resources.html",
	"./SimpleInput",
	"dijit/Dialog",
	"OoCmS/xbugfix/FilePicker",
	"dojox/data/FileStore",
	"dijit/Toolbar", // in own template
	"dijit/form/Form", // input types
	"dijit/form/Button",
	"dijit/form/Select",
	"dijit/form/TextBox", // fields/elements
	"dijit/form/ValidationTextBox", // fields/elements
	"dijit/form/Textarea"


	], function(declare, dlang, darray, dxhr, ddomconstruct, ddomgeometry, ddomstyle, ddomclass,
		dconnect, djwidgetintemplate, szTemplateString, oosimpleinput, djdialog,
		dxfilepicker, dxfilestore, djtoolbar,
		djform, djbutton, djselect, djtextbox, djvalidationtextbox, djtextarea) {

		return declare("OoCmS.formdialog.Resources", [djdialog, djwidgetintemplate], {
			templateString: szTemplateString,
			doLayout: true,
			url: 'save.php',
			observers: [],
			fields : [{
				name:"mimetype",
				label:"Resource kontekts",
				type:'dijit.form.Select',
				properties:{
					options: [{
						label:'Stylesheet',
						value: 'text/css',
						selected: true
					},{
						label:'Javascript',
						value: 'text/javascript'
					}],
					style: 'width:97%'
				}
			}, 
			"alias",  //TextBox default
			{
				name:"comment",
				label:"Kort beskrivelse",
				type: 'dijit.form.ValidationTextBox',
				properties: {
					required: true,
					invalidMessage: 'Kan ikke være tom'
				}
			}, {
				name:"uri",
				label:'Url',
				type: 'dijit.form.ValidationTextBox',
				properties: {
					regExp : "^(http[s]?://|/)(?:[A-Za-z0-9-._~!$&'()*+,;=:@]|%[0-9a-fA-F]{2})*(?:/(?:[A-Za-z0-9-._~!$&'()*+,;=:@]|%[0-9a-fA-F]{2})*|http[s]?:\\/\\/)*$",
					invalidMessage: "Prefix med enten http:// eller " + gPage.baseURI
				}
			}, {
				name:'body',
				label:'Indhold',
				type:'dijit.form.Textarea',
				properties: {
					required: true,
					invalidMessage: 'Kan ikke være tom'
				}
			}],
			swapState: function(values) {
				var value = values[0]
				traceLog(this, arguments);
				if(value == 0) { // href/src, no inline
					this._uri.set("disabled", false)
					this._body.set("disabled", true);
					ddomclass.remove(this._uri.domNode, "dijitDisabled")
				} else {
					this._uri.set("disabled", true);
					ddomclass.add(this._uri.domNode, "dijitDisabled")
					this._body.set("disabled", false);
				}
			},
			constructor: function(args) {
				args= args || {}
				if(args && args.item) {
					this.item = args.item;
					this.store = args.item._S
				}
				this.inherited(arguments);
			},
			doExecute: function(){
				traceLog(this, arguments);
				
				if(this.formWidget.validate()) {
					if(!this._body.get("disabled") && this._body.get("value").length > 0) {
						var enc = this.formWidget.domNode.getAttribute("enctype");
						dxhr.post({
							url: this.formWidget.get("action"),
							content: this.formWidget.getValues()
						});
						this.hide();
					}
					return false;
				}
				else return false;
			},
			startup: function startup() {
				traceLog(this, arguments);

				var w, item = this.item,
				store = (item?item._S:null),
				self = this;
				this.inherited(arguments);
				//				parser.parse(this.domNode);
				//				this.stateSelect = registry.byId(this.id + "_stateselect")
				this.stateselectWidget.on('change', function() {
					self.swapState(arguments);
				})
				darray.forEach(this.fields,function(field) {
					var name = typeof(field) == "string" ? field : field.name,
					label = typeof(field) == "string" ? field : field.label,
					input = new oosimpleinput({
						label: label.charAt(0).toUpperCase() + label.slice(1),
						name: name,
						value: (item ? item._S.getValue(item, field.name) : ""),
						type: field.type || null,
						properties:field.properties || {},
						region: 'center'
					});
					self['_'+name] = input;
					input.startup();
					if(item) {
						input.set("value", store.getValue(item, name));
					}
					self.formWidget.domNode.appendChild(input.domNode);
				});
				this.on("show", this.finaleCreate);
				this.saveButton.on("click", dlang.hitch(this, this.doExecute));
				this.cancelButton.on("click", dlang.hitch(this, this.hide));
				this.swapState([0])
				ddomclass.add(this.domNode, "OoCmSFormDialogResources");
			},
			finaleCreate: function() {
				var image = ddomconstruct.create("img", {
					src : require.toUrl("dojo/resources/blank.gif"),
					className : "OoCmSFormDialogResourcesPickerIcon dijitEditorIcon dijitEditorIconCreateLink",
					title: 'Vælg fra \'... /fileadmin\''
				}, this._uri.inputNode, 'last');
				ddomstyle.set(this._uri._input.domNode, {
					width: (ddomgeometry.getMarginBox(this._uri._input.domNode).w - 20) + "px"
				})
				this.observers.push(dconnect.connect(image, "onclick", this, this.pickFile));
			},
			pickFile: function(iconNode) {
				if(this._uri.get("disabled")) return;
				var mime = this._mimetype.get("value") == "text/css" ? "css" : "js";
				var self = this;
				
				if(!self.pickerdialog) {
					self.pickerdialog = new djdialog({
						id: 'resourceFilePickerDialog',
						title: 'Vælg fil',
						onHide: function() {
							self.pickerview.destroyRecursive();
							this.destroyRecursive();
							delete self.pickerdialog;
						},
						onShow: function() {
							// picker makes room for buttons and scroll overflow-x, 
							// fix it since we aint showing...
							var dimensions = ddomgeometry.position(self.pickerview.domNode)
							setTimeout(function() {
								self.pickerview.getChildren().forEach(function(w) {
									ddomstyle.set((w.domNode), {
										height: dimensions.h + "px"
									})
								});
							}, 200);
						//									self.pickerview.domNode.style.height = "206px";
						}
					});
					self.pickerview = new dxfilepicker({
						store: new dxfilestore({
							url: gPage.baseURI + '/openView/Files.php?AddDotDotDir=true&query={"name":"*.'+mime+'"}',
							pathAsQueryParam: true
						}),
						onExecute: function(item) {
							self._uri.set("value", gPage.baseURI + "fileadmin" + item.path.slice(1))
							self.pickerdialog.hide()
						},
						style: 'height: 150px; width: 300px;',
						_onItemClick: function(/* Event */ evt, /* dijit._Contained */ pane, /* item */ item, /* item[]? */ children){
							// summary: internally called when a widget should pop up its child
							if(evt){
								var itemPane = this._getPaneForItem(item, pane, children);
								var alreadySelected = (evt.type == "click" && evt.alreadySelected);
								//// mod 1
								//break on file clicks, dont show info just select
								if(itemPane.declaredClass == "dojox.widget._FileInfoPane") {
									this.onExecute(item);
								}
								//// mod 2
								// Files.php is modified with AddDotDotDir check
								// it will put a special item on children arrays
								if(item.size == 1337 && item.path == "..") {

									var prev = pane.getPreviousSibling();
									if(prev && prev._setSelected){
										prev._setSelected(null);
									}
									this.scrollIntoView(prev);
									this._removeAfter(prev.getIndexInParent());
								} else 
											
								if(alreadySelected && itemPane){
									this._removeAfter(pane.getIndexInParent() + 1);
									var next = pane.getNextSibling();
									if(next && next._setSelected){
										next._setSelected(null);
									}
									this.scrollIntoView(next);
								}else if(itemPane){
									this.addChild(itemPane, pane.getIndexInParent() + 1);
									if(this._savedFocus){
										itemPane.focus(true);
									}
								}else{
									this._removeAfter(pane);
									this.scrollIntoView(pane);
								}
							}else if(pane){
								this._removeAfter(pane);
								this.scrollIntoView(pane);
							}
							if(!evt || evt.type != "internal"){
								this._setValue(item);
								this.onItemClick(item, pane, children);
							}
							this._visibleItem = item;
							// abnoxious scrollbar -,-
							var dimensions = this._dimensions 
							|| (this._dimensions=ddomgeometry.position(this.domNode));
							this.getChildren().forEach(function(w) {
								ddomstyle.set((w.domNode), {
									height: dimensions.h + "px"
								})
							});
						}
					});
					loadCSS(require.toUrl("dojox/widget/FilePicker/FilePicker.css"));
				}
				self.pickerdialog.set("content", self.pickerview);
				self.pickerdialog.show()
					
			},
			hide: function() {
				this.destroy();
			},
			destroy: function() {
				this.inherited(arguments);
				darray.forEach(this.observers, dconnect.disconnect);
				if(this.pickerdialog) {
					this.pickerdialog.destroyRecursive();
					this.pickerview.destroyRecursive();
					this.pickerstore.destroyRecursive();
				}
				this.destroyRecursive();
				
			}
		});
	});