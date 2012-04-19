define([
	"dojo/_base/declare",
	"dojo/_base/lang",
	"dojo/_base/array",
	"dojo/_base/connect", 
	"dojo/_base/event",
	"dojo/_base/xhr",
	"dojo/dom",
	"dojo/dom-construct",
	"dojo/dom-class",
	"dojo/query",
	"dijit/registry",
	
	"dijit/_WidgetBase",
	"dijit/_TemplatedMixin",

	"dojo/text!./templates/assets.html",
	"dijit/layout/BorderContainer",
	"dojox/data/FileStore",
	"dijit/form/Form",
	"dijit/tree/ForestStoreModel",
	"OoCmS/_treebase",
	"dijit/form/Button",
	"dijit/form/DropDownButton",
	"dijit/TooltipDialog",
	"dijit/Dialog",
	"dijit/popup",
	"dijit/Toolbar",
	"dojox/form/Uploader",
	"dojox/form/uploader/FileList",
	"dojox/embed/Flash",
	"dojox/form/uploader/plugins/Flash",
	"dojox/css3/fx",
	"OoCmS/xbugfix/FilePicker"

	

	], function(declare, dlang, darray, dconnect, devent, dxhr, ddom, ddomconstruct,
		ddomclass, $, registry, djwidgetbase, djtemplated, ooassetstemplate, djborderlayout, xfilestore, djform, forestmodel,
		ootreebase, djbutton, djdropdownbutton, djttipdialog, djdialog, djpopup, 
		djtoolbar, xuploader, xfilelist, flashEmbed, flashPlug, css3fx, basecontroller){

		//		var FlashUploader = declare("OoCmS._fileuploader", [xuploader,dojox.form.uploader.plugins[plugin]], {
		//			btnSize: {
		//				w: 143,
		//				h: 24
		//			},
		//			force: 'flash',
		//			deferredUploading: 2,
		//			isDebug: true,
		//			noReturnCheck: false,
		//			constructor: function() {
		//				console.log('****************')
		//			},
		//			postCreate: function() {
		//				this._createInput()
		//				this.inherited(arguments);
		//			},
		//			onComplete:function onComplete(dataArray){
		//				traceLog(this,arguments)
		//				var totalFiles = dataArray.length;
		//				var filesLoaded = 0;
		//				dojo.forEach(dataArray, function(d){
		//					console.log('file', d);
		//				});
		//			}
		//		})
		var assetsform = declare("OoCmS._assetsform", [djwidgetbase, djtemplated], {
			templateString: ooassetstemplate,
			observers: [],
			constructor: function(args) {
				traceLog(this,arguments);
				dlang.mixin(this, args);
				if(!this.treeId) console.warn("OoCmS._assetsform misses id linking to navigator tree")
				this.inherited(arguments);
			},
			destroy: function() {
				traceLog(this,arguments)
				darray.forEach(this.observers, dconnect.disconnect);
				this.uploader.destroyRecursive();
				this.uploadlist.destroyRecursive();
				this.uploadtbar.destroyRecursive();
				this.form.destroyRecursive();
				this.inherited(arguments);
			},
			buildRendering: function() {
				traceLog(this,arguments)
				this.inherited(arguments);
				this.uploadtbar = new djtoolbar({}),
				this.form = new djform({
					method:"POST",
					action:this.uploadUrl,
					id:"assetsuploadform",
					encType:"multipart/form-data"
				}),
				this.uploader = new dojox.form.Uploader({ // don't use AMD reference
					flashFieldName: 'flashUploadFiles',
					url: this.uploadUrl || 'upload.php',
					multiple: true,
					swfPath : 'resources/uploader_1.7.2.swf',
					isDebug: true,
					iconClass: 'dijitFolderOpened',
					label:'Vælg filer til upload',
					id: 'assetsuploader',
					/* NB override (missing function?) **/
					_getFileFieldName: function() {
						return (this.uploadType == "html5"
							? this.name+"s[]"
							: this.flashFieldName);
					}
					
				});

				this.uploader.startup();
				
				this.uploadlist = new xfilelist({
					headerIndex:'&nbsp;-&nbsp;',
					headerFilename:'Filnavn', 
					headerFilesize: 'Filstørrelse',
					uploaderId: this.uploader.get("id")
				}, this.filelistNode);
				
				this._directory = ddomconstruct.create("input", {
					type:'hidden', 
					name:'directory',
					value:''
				}, this.form.domNode, 'first');
				this._uploadtype = ddomconstruct.create("input", {
					type:'hidden', 
					name:'uploadtype',
					value:''
				}, this.form.domNode, 'first');
				
			},
			startup: function() {
				
				traceLog(this,arguments)
				this.inherited(arguments);

				this.domNode.className += " OoCmSAssetsForm"
					
				this.uploaderNode.appendChild(this.form.domNode);
				this.form.domNode.appendChild(this.uploadtbar.domNode);
				
				this.uploadtbar.addChild(this.uploader);
				this.uploadtbar.addChild(new djbutton({
					type:"submit",
					label:"Start upload",
					showLabel: true,
					onClick: dlang.hitch(this, "beforeUpload"),
					iconClass: 'dijitIconDatabase'
				}));

				
				// probably will never get called...
				this.uploader.connect("onError", this, this.onError);
				this.uploader.connectForm();
				this.uploader.url += ((this.uploader.url.indexOf("?") > -1
					? "&" : "?") 
				+ "uploadtype=" + this.uploader.uploadType 
				+ "&fieldname=" + (this.uploader.uploadType == "html5" 
					? this.uploader.name+"s" 
					: this.uploader.flashFieldName));

				if(this.uploader.uploadType == "html5") {
	
					this.dnddialog = new djttipdialog({
						content: this.dndDialogNode,
						id: 'dndDialog',
						_openby : this.dndOpenerContainerNode
					});

					// assert contents
					djpopup.open({
						popup: this.dnddialog,
						around: this.dnddialog._openby
					});
					djpopup.close(this.dnddialog);
					this.observers.push(dconnect.connect(this.dndOpenerFocusNode, "click", this, this.toggleDnD));

					this.observers.push(dconnect.connect(
						this.dndTargetNode, 'dragenter', this, this.dragenter));
					this.observers.push(dconnect.connect(
						this.dndTargetNode, 'dragleave', this, this.dragleave));
					this.observers.push(dconnect.connect(
						this.dndTargetNode, 'dragover',  devent.stop));
					this.observers.push(dconnect.connect(
						this.dndTargetNode,   'drop',    this, this.dragdrop));
				}
				// set in page construct
				//	this._form._attachid = this._attachbox; 
				loadCSS(require.toUrl("dojox/widget/FilePicker/FilePicker.css"));
				loadCSS(require.toUrl("dojox/form/resources/UploaderFileList.css"));
			},
			dragenter: function(e) {
				console.log('dragenter')
				ddomclass.add(this.dndTargetNode, "hover");
				devent.stop(e)
			},
			dragleave: function(e) {
				console.log('dragleave')
				ddomclass.remove(this.dndTargetNode, "hover");
				devent.stop(e)
			},
			dragdrop: function(e) {
				console.log('drop')
				devent.stop(e);
				ddomclass.remove(this.dndTargetNode, "hover");
				var dt = e.dataTransfer;
				this.uploader._files = dt.files;
				this.uploader.onChange(this.uploader.getFileList());
			},
			toggleDnD: function toggleDnD() {
				traceLog(this,arguments)
				var hidden = this.dnddialog.domNode.parentNode.style.display=="none";
				if(hidden) {
					djpopup.open({
						popup: this.dnddialog,
						around: this.dnddialog._openby
					});
				}else{
					djpopup.close(this.dnddialog)
				}
			},
			beforeUpload: function beforeUpload() {
				traceLog(this,arguments)
				var item =  registry.byId(this.treeId).selectedItem,
				value = "";
				console.log(item);
				if(item && ! item.root) {
					if(! item._S.getValue(item, "directory", false))
						value = item._S.getValue(item, "parentDir", "")  + "/";
					else 
						value = item._S.getValue(item, "path", "") + "/";
				}
				this._directory.value = value;
				this._uploadtype.value = this.uploader.uploadType;
			},
			onError: function onError() {
				alert('Fejlede upload, besked fra systemet: ' + arguments[0].message);
				console.error("Upload failed for", arguments[0].filesInError);
			}
		});
		var Assets = declare("OoCmS.assets", [djborderlayout], {
			observers: [],
			constructor: function(/*Object*/ args){
				traceLog(this,arguments)
				args = args || {}
				dlang.mixin(this, args);
				this.uploadUrl = this.uploadUrl || "upload.php";
				
			},
			//a:[{"pk": 98, "model": "bbnt.country", "fields": {"retrieved": "2012-03-24 22:43:23", "name": "Bahamas", "users": 9}}],

			startup: function startup() {
				traceLog(this,arguments)
				this.inherited(arguments);
				// create toolbar for keeping uploader
				this.domNode.className += " OoCmSAssetsForm";
				
				var left_layout = new djborderlayout({
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
				selector = this.getFileSelector({
					id: 'fileadminTree',
					region:'center'
				}),
				tb = this.getFileSelectorToolbar({
					region:'top'
				}),
				form = this._form = new assetsform({
					treeId: 'fileadminTree',
					region:'center'
				});
				left_layout.addChild(tb);
				left_layout.addChild(selector);
				center_layout.addChild(form);
				this.addChild(left_layout)
				this.addChild(center_layout)
			//				this.getUploadToolbar().startup()
				
			// create uploader and its filelist
			//				var upl = this.getUploader();
			//				upl.startup();
			//				upl.connectForm();
			//				this.getUploadList().startup()
			},
			postCreate: function() {
				this.inherited(arguments);

				return 
				var upl = this.getUploader();
				// uff, if HTML5 capeable, add a drag 
				if(upl.uploadType == "html5") {
					var b = ddom.byId("assetsDnDopener");
					this.observers.push(dconnect.connect(b, "click", this, this.toggleDnD));
					this._filednddialog = new djttipdialog({
						content: '<div class="assetsDnD hiddenBox" id="assetsuploaderdragdrop"><div class="dndAcceptance"></div></div>',
						id: 'dndDialog',
						_openby : b
					});
					//					this._filednddialog.startup();
					//					var tb = this.getUploadToolbar(),
					//					b = new djdropdownbutton( {
					//						dropDown : false,
					//						loadDropDown : function() { },
					//						label: 'Vis træk-slip felt',
					//						onClick: dlang.hitch(this, this.toggleDnD)
					//					})
					//					tb.addChild(b);
					//					
					// assert contents
					djpopup.open({
						popup: this.dnddialog,
						around: this.dnddialog._openby
					});
					djpopup.close(this.dnddialog);
					var dndTarget = ddom.byId('assetsuploaderdragdrop').firstChild;
					//					upl.addDropTarget(dndTarget, true);
					this.observers.push(dconnect.connect(dndTarget, 'dragenter', function(e){
						console.log('dragenter')
						ddomclass.add(dndTarget, "hover");
						devent.stop(e)
					}));
					this.observers.push(dconnect.connect(dndTarget, 'dragleave', function(e){
						console.log('dragleave')
						ddomclass.remove(dndTarget, "hover");
						devent.stop(e)
					}));

					this.observers.push(dconnect.connect(dndTarget, 'dragover', devent.stop));
					this.observers.push(dconnect.connect(dndTarget, 'drop', upl, function(e){
						console.log('drop')
						devent.stop(e);
						ddomclass.remove(dndTarget, "hover");
						var dt = e.dataTransfer;
						this._files = dt.files;
						this.onChange(this.getFileList());
					}));
				}
				
			},
			getForm: function() {
				return this._form;
			},
			getFileSelectorToolbar: function getFileSelectorToolbar(mixin) {
				if(this._fileselectortbar) return this._fileselectortbar;
				var tb = this._fileselectortbar = new djtoolbar(mixin),
				self = this,
				selector = this.getFileSelector(),
				b = new djbutton({
					showLabel: false,
					iconClass: 'OoCmSIconAsset OoCmSIconAsset-css',
					title: 'Filtrer på stylesheets',
					onClick: dlang.hitch(selector, 'filter', '{"name":"*.css"}')

				});
				ddomconstruct.place("<span style=\"position:relative;top:1px;padding: 0 10px;\">Filtre</span>", tb.domNode)
				tb.addChild(b);
				b = new djbutton({
					showLabel: false,
					iconClass: "OoCmSIconAsset OoCmSIconAsset-htm",
					title: 'Filtrer på HTML',
					onClick: dlang.hitch(selector, 'filter', '{"name":"*.\\(html\\|htm\\)"}')
				});
				tb.addChild(b);				
				b = new djbutton({
					showLabel: false,
					iconClass: "OoCmSIconAsset OoCmSIconAsset-js",
					title: 'Filtrer på javascript',
					onClick: dlang.hitch(selector, 'filter', '{"name":"*.js"}')
				});
				tb.addChild(b);				
				b = new djbutton({
					showLabel: false,
					iconClass: "OoCmSIconAsset OoCmSIconAsset-odg",
					title: 'Filtrer på billeder',
					onClick: dlang.hitch(selector, 'filter', '{"name":"*.\\(gif\\|jpg\\|jpeg\\|png\\|tiff\\|ico\\)"}')
				});
				tb.addChild(b);
				b = new djbutton({
					showLabel: false,
					iconClass: "OoCmSIconAsset OoCmSIconAsset-file",
					title: 'Ryd filter',
					onClick: dlang.hitch(selector, 'filter', '')
				});
				tb.addChild(b);
				return this._fileselectortbar;
			},
			getFileSelector: function getFileSelector(mixin) {
				traceLog(this,arguments)

				if(this._fileselector) return this._fileselector;
				
				this._store = new xfilestore({
					url: gPage.baseURI + "/openView/Files.php",
					pathAsQueryParam: true
				});
				this._model = new forestmodel({
					store: this._store,
					rootLabel: 'fileadmin/',
					rootId: 'fileadminRoot'
				});

				this._fileselector = new ootreebase(dlang.mixin({
					model: this._model,
					getIconClass: this.fileIconClass,
					baseUrl :  gPage.baseURI + "/openView/Files.php",
					filter : function(q) {

						if(!q || q.length == 0) this.model.store.url = this.baseUrl
						else this.model.store.url = this.baseUrl + '?query=' + q
						this.update();
					}
				}, mixin), 'assetstree');
				// shift out page/pagetree specific onLoad handler
				dconnect.disconnect(this._fileselector.observers.shift());
				return this._fileselector
			},
			getUploader: function getUploader() {
				return {};
				traceLog(this,arguments)
				if(this._fileuploader) return this._fileuploader;
				this._fileuploader = new dojox.form.Uploader({ // don't use AMD reference
					//					fieldname: 'flashUploadFiles',
					flashFieldName: 'flashUploadFiles',
					url: this.uploadUrl || 'upload.php',
					multiple: true,
					swfPath : 'resources/uploader_1.7.2.swf',
					isDebug: true,
					//					force:'flash',
					iconClass: 'dijitFolderOpened',
					label:'Vælg filer til upload',
					id: 'assetsuploader'

				});
				this._fileuploader.url += ((this._fileuploader.url.indexOf("?") > -1
					? "&" : "?") 
				+ "uploadtype=" + this._fileuploader.uploadType 
				+ "&fieldname=" + (this._fileuploader.uploadType == "html5" 
					? this._fileuploader.name+"s" 
					: this._fileuploader.flashFieldName));
				return this._fileuploader;
			},
			getUploadToolbar: function getUploadToolbar() {
				return {};
				traceLog(this,arguments)
				if(this._fileuploadtbar) return this._fileuploadtbar;
				
				var tb = this._fileuploadtbar = new djtoolbar({}),
				form = this._form = new djform({
					method:"POST",
					action:this.uploadUrl,
					id:"assetsuploadform",
					encType:"multipart/form-data"
				}),
				b = new djbutton({
					type:"submit",
					label:"Start upload",
					showLabel: true,
					onClick: dlang.hitch(this, "beforeUpload"),
					iconClass: 'dijitIconDatabase'
				}),
				u = this.getUploader();
				ddom.byId('assetsuploaderwrapper').appendChild(form.domNode);
				form.domNode.appendChild(tb.domNode);
				
				tb.addChild(u);
				tb.addChild(b);

				ddomconstruct.create("input", {
					type:'hidden', 
					name:'directory',
					value:''
				}, form.domNode, 'first');
				ddomconstruct.create("input", {
					type:'hidden', 
					name:'uploadtype',
					value:''
				}, form.domNode, 'first');
				
				// probably will never get called...
				this.observers.push(dconnect.connect(this._fileuploader, "onError", this, this.onError));
				return this._fileuploadtbar
			},
			getUploadList: function getUploadList() {
				return {};
				traceLog(this,arguments)

				if(this._fileuploadlist) return this._fileuploadlist;
				
				this._fileuploadlist = new xfilelist({
					headerIndex:'&nbsp;-&nbsp;',
					headerFilename:'Filnavn', 
					headerFilesize: 'Filstørrelse',
					uploaderId: this._fileuploader ? this.get("id") : undefined
				}, 'assetsuploaderfilelist')
				
				return this._fileuploadlist;
			},
			unload: function() {
				darray.forEach(this.observers,dconnect.disconnect);
			},
			fileIconClass: function fileIconClass(item, nodeExpanded) {
				// scope: dijit.Tree
				if(item.root || item.directory || typeof item.extension == "undefined")
					return (!item || this.model.mayHaveChildren(item)) ? (nodeExpanded ? "dijitFolderOpened" : "dijitFolderClosed") : "dijitLeaf"
				else {
					return "dijitLeaf OoCmSIconAsset OoCmSIconAsset-" + item._S.getValue(item, "extension");
				}
			},
			isDirty: function() {
				return false;
			},
			//			playingDnD : false,
			//			drag: function(dndEvent) {
			//				var self = this,
			//				node =this._filednddialog.containerNode.firstChild.firstChild;
			//
			//				if(dndEvent.type && dndEvent.type == "dragenter") {
			//					devent.stop(dndEvent);
			//				} else if(dndEvent.type && dndEvent.type == "dragleave"){
			//					// will it fire as _drop does, before so can use self.playing? wat wat
			//					//					setTimeout(function() {
			//					//						ddomclass.add(node, "hidden")
			//					//					}, 500);
			//					console.log('leave', dndEvent);
			//					devent.stop(dndEvent);
			//
			//				} else if(dndEvent.type && dndEvent.type == "dragdrop"){
			//					console.log('drop', dndEvent.dataTransfer.files)
			//					this.getUploader()._drop(dndEvent)
			//				//					if(this.playingDnD) return;
			//				//					else this.playingDnD = true
			//				//					ddomclass.add(node,"dndDone");
			//				//					css3fx.expand({
			//				//						node:node, 
			//				//						endScale: 0.3
			//				//					}).play();
			//				//					setTimeout(function() {
			//				//						self.playingDnD = false
			//				//						ddomclass.remove(node,"dndDone");
			//				//						css3fx.expand({
			//				//							node:node, 
			//				//							endScale: 1
			//				//						}).play();
			//				//					}, 1800);
			//				} 
			//			},
			/*toggleDnD: function toggleDnD() {
				var hidden = this._filednddialog.domNode.parentNode.style.display=="none";
				if(hidden) {
					djpopup.open({
						popup: this._filednddialog,
						around: this._filednddialog._openby
					});
				}else{
					djpopup.close(this._filednddialog)
				}
			},
			beforeUpload: function beforeUpload() {
				var item =  this.getFileSelector().selectedItem,
				value = "";
				if(item && ! item.root) {
					value = item._S.getValue(item, "parentDir", "");
				}
				$("input[name=\"directory\"]", this._form.domNode)[0].value = value;
				$("input[name=\"uploadtype\"]", this._form.domNode)[0].value = this.getUploader().uploadType;
			},
		 */
			onError: function onError() {
				alert('Fejlede upload, besked fra systemet: ' + arguments[0].message);
				console.error("Upload failed for", arguments[0].filesInError);
			},
			createFolder: function createFolder(parentFolder) {
				traceLog(this,arguments)
				var dia = new djdialog(); //this.getDialog();
				var w = false;
				dia.attr("title", "Navn på folder");
				if(!w) {
					dia.attr("content",
						'<label for="foldername">Ny folder:</label>'+
						'<input type="text" name="foldername" dojoType="dijit.form.TextBox" trim="true" id="createfolderInput">' +
						'<center><span dojoType="dijit.form.Button" id="createfolderButton">Opret folder</span></center>');
					registry.byId('createfolderButton').on("click", function() {
						var d,v = ddom.byId('createfolderInput').value;
						if(v == "") {
							d = ddomconstruct.place(ddomconstruct.create('div'), 
								registry.byId('createfolderButton').domNode, "before");
							d.style.color = "red";
							d.style.font = "11px verdana";
							d.innerHTML = "* en folder ved navn 'ingenting'??"
							return;
						} else {
							for(var i = 0; i < v.length; i++) {
								if(v.charAt(i) == ' ') {
									d = ddomconstruct.place(ddomconstruct.create('div'),
										registry.byId('createfolderButton').domNode, "before");
									d.style.color = "red";
									d.style.font = "11px verdana";
									d.innerHTML = "* mellemrum tillades ikke"
									return;
								}
							}
						}
						dxhr.post({
							url: gPage.baseURI + "/admin/AjaxAPI.php",
							content : {
								createfolder : 'true',
								createin : parentFolder,
								createas : v
							},
							load: function() {
								dia.destroyRecursive();
							}
						})
					});
				} else {
					dia.attr('content','<label for="foldername">Ny folder:</label>');
					registry.byId('createfolderInput').placeAt(dia.containerNode)
					registry.byId('createfolderButton').placeAt(dia.containerNode)
				}
				dia.show();

			},
			moveFile : function moveFile(e) {
				traceLog(this,arguments)
				// 'pick up' file
				var sourceNode = this.tree.lastFocused;
				if(sourceNode.item.root) return
				var w = registry.byId('movetoTree');
				var dia = new djdialog(); //this.getDialog();
				dia.attr("title", "Vælg den nye placering");
				if(!w) {
					var off = this.tree.model.store._jsonFileUrl.indexOf('&dir');
					if(off == -1) off = this.tree.model.store._jsonFileUrl.length;
					var url = this.tree.model.store._jsonFileUrl.substring(0,off)
					dia.attr("content",
						'<div dojoType="dojo.data.ItemFileWriteStore" jsId="movetoStore"'+
						'	 url="'+url+'&spec=directories">'+
						'</div>'+
						'<div dojoType="OoCmS.TreeModel" jsId="movetoModel" store="movetoStore" query="{}"'+
						'	 showRoot="true" labelAttr="title" rootId="moevtoRoot" childrenAttr="children" rootLabel="Position:">'+
						'</div>'+
						'<div style="width: 300px;" dojoType="dijit.Tree" model="movetoModel" id="movetoTree"'+
						'	 persist="true" showRoot="true">'+
						'	<script type="dojo/method" event="getIconClass" args="item">'+
						'		if(!item) return false;'+
						'		return "dijitFolderClosed";'+
						'	</script>'+
						'</div>'+
						'<center><span dojoType="dijit.form.Button" id="movefileButton">Flyt fil</span></center>');
					registry.byId('movefileButton').on("click", function() {
						var t = registry.byId('movetoTree');
						var to = t.model.store.getValue(t.lastFocused.item, "abspath");
						var from = sourceNode.tree.model.store.getValue(sourceNode.item, "abspath");
						if(to == from) {
							dia.hide()
							return;
						}
						if(!t.focusNode) return;
						dxhr.post({
							url: gPage.baseURI + "/admin/AjaxAPI.php",
							content : {
								movefile : 'true',
								movetopath : to,
								movefrompath : from
							},
							load: function() {
								dia.hide();
							}
						})
					});
				} else {
					w.placeAt(dia.containerNode);
					registry.byId('movefileButton').placeAt(dia.containerNode);
				}
				dia.show();
			},


			deleteFile : function deleteFile(e) {
				traceLog(this,arguments)
				var dia = new djdialog(); //this.getDialog();
				var sourceNode = this.tree.lastFocused;
				if(sourceNode.item.root) return
				var w = registry.byId('createfolderButton');
				dia.attr("title", "Navn på folder");
				if(!w) {
					dia.attr("content",
						'Sletter "'+sourceNode.tree.model.store.getValue(sourceNode.item, "title")+'", vil du fortsætte?' +
						(sourceNode.item.type == "dir" ? "<br><font color=red size=2>*NB slettes denne folder, slettes alle indeholdte filer og foldere!</font>":"") +
						'<center><span dojoType="dijit.form.Button" id="deletedoButton">Ok</span>'+
						'<span dojoType="dijit.form.Button" id="deletecancelButton">Fortryd</span></center>');
					registry.byId('deletedoButton').on("click", function() {
						dxhr.post({
							url: gPage.baseURI + "/admin/AjaxAPI.php",
							content : {
								deletefile : 'true',
								deletepath : sourceNode.tree.model.store.getValue(sourceNode.item, "abspath")
							},
							load: function() {
								dia.hide();
							}
						})
					});
					registry.byId('deletecancelButton').on("click", function() {
						dia.hide();
					});
				} else {
					dia.attr("content",
						'Sletter "'+sourceNode.tree.model.store.getValue(sourceNode.item, "title")+'", vil du fortsætte?' +
						(sourceNode.item.type == "dir" ? "<br><font color=red size=2>*NB slettes denne folder, slettes alle indeholdte filer og foldere!</font>":""));			
					registry.byId('createfolderInput').placeAt(dia.containerNode)
					registry.byId('deletedoButton').placeAt(dia.containerNode)
					registry.byId('deletecancelButton').placeAt(dia.containerNode)
				}
				dia.show();

			}

		});
		return Assets;
	});