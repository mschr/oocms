define([
	"dojo/_base/kernel",
	"dojo/_base/declare",
	"dojo/_base/lang",
	"dojo/dom",
	"dojo/dom-construct",
	"dojo/query",
	"dijit/registry",
	"OoCmS/AbstractController",
	"dojox/widget/FilePicker",
	"dojox/data/FileStore",
	
	"dijit/form/Form",
	"dijit/tree/ForestStoreModel",
	"dijit/Tree",
	"dijit/form/Button",
	"dijit/form/DropDownButton",
	"dijit/TooltipDialog",
	"dijit/popup",
	"dijit/Toolbar",
	//	"OoCmS/xMod/xUploader",
	"dojox/form/Uploader",
	"OoCmS/xMod/xFileList",
	//	"dojox/form/uploader/FileList",
	"dojox/embed/Flash",
	"dojox/form/uploader/plugins/Flash",
	"dojox/css3/fx"
	

	], function(kernel, declare, dlang, ddom, ddomconstruct, $, registry, basecontroller, xfilepicker, xfilestore, 
		djform, forestmodel, djtree, djbutton, djdropdownbutton, djttipdialog, djpopup, djtoolbar, orgxuploader, xfilelist, flashEmbed, flashPlug, css3fx){

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
		//				console.info(traceLog(this,arguments));
		//				var totalFiles = dataArray.length;
		//				var filesLoaded = 0;
		//				dojo.forEach(dataArray, function(d){
		//					console.log('file', d);
		//				});
		//			}
		//		})
		var xuploader;
		var Assets = declare("OoCmS.assets", [basecontroller], {
			variable : 1,
			constructor: function(/*Object*/ args){
				console.info(traceLog(this,arguments));
				args = args || {}
				dlang.mixin(this, args);
				this.uploadUrl = this.uploadUrl || "upload.php";
				this.dijitrdyId = 'assetsuploader';
				
			},
			//a:[{"pk": 98, "model": "bbnt.country", "fields": {"retrieved": "2012-03-24 22:43:23", "name": "Bahamas", "users": 9}}],

			startup: function startup() {
				console.info(traceLog(this,arguments));
				this.inherited(arguments);
				// create toolbar for keeping uploader
				this.getUploadToolbar().startup()
				// create tree traversing
				this.getFileSelector().startup();
				// create uploader and its filelist
				var upl = this.getUploader();
				upl.startup();
				upl.connectForm();
				this.getUploadList().startup()
			},
			postCreate: function() {

				var upl = this.getUploader();
				// uff, if HTML5 capeable, add a drag 
				if(upl.uploadType == "html5") {
					var b = ddom.byId("assetsDnDopener");
					this.observers.push(dojo.connect(b, "click", this, this.toggleDnD));
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
						popup: this._filednddialog,
						around: this._filednddialog._openby
					});
					djpopup.close(this._filednddialog);
					var dndTarget = dojo.byId('assetsuploaderdragdrop').firstChild;
					//					upl.addDropTarget(dndTarget, true);
					this.observers.push(dojo.connect(dndTarget, 'dragenter', function(e){
						console.log('dragenter')
						dojo.addClass(dndTarget, "hover");
						dojo.stopEvent(e)
					}));
					this.observers.push(dojo.connect(dndTarget, 'dragleave', function(e){
						console.log('dragleave')
						dojo.removeClass(dndTarget, "hover");
						dojo.stopEvent(e)
					}));

					this.observers.push(dojo.connect(dndTarget, 'dragover', dojo.stopEvent));
					this.observers.push(dojo.connect(dndTarget, 'drop', upl, function(e){
						console.log('drop')
						dojo.stopEvent(e);
						dojo.removeClass(dndTarget, "hover");
						var dt = e.dataTransfer;
						this._files = dt.files;
						this.onChange(this.getFileList());
					}));
				}
				
				this.inherited(arguments);
			},

			getFileSelector: function getFileSelector() {
				console.info(traceLog(this,arguments));

				if(this._fileselector) return this._fileselector;
				
				this._store = new xfilestore({
					url: gPage.baseURI + "/openView/Files.php",
					pathAsQueryParam: true
				});
				this._model = new forestmodel({
					store: this._store,
					query: {},
					rootLabel: 'fileadmin/',
					rootId: 'fileadminRoot'
				});
				this._fileselector = new djtree({
					id: 'fileadminTree',
					model: this._model,
					getIconClass: this.fileIconClass
				}, 'assetstree');
				
				return this._fileselector
			},
			getUploader: function getUploader() {
				console.info(traceLog(this,arguments));
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
				console.info(traceLog(this,arguments));
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
				this.observers.push(dojo.connect(this._fileuploader, "onError", this, this.onError));
				return this._fileuploadtbar
			},
			getUploadList: function getUploadList() {
				console.info(traceLog(this,arguments));

				if(this._fileuploadlist) return this._fileuploadlist;
				
				this._fileuploadlist = new xfilelist({
					headerIndex:'&nbsp;-&nbsp;',
					headerFilename:'Filnavn', 
					headerFilesize: 'Filstørrelse',
					uploaderId: this._fileuploader ? this._fileuploader.get("id") : undefined
				}, 'assetsuploaderfilelist')
				
				return this._fileuploadlist;
			},
			unload: function() {
			
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
//					dojo.stopEvent(dndEvent);
//				} else if(dndEvent.type && dndEvent.type == "dragleave"){
//					// will it fire as _drop does, before so can use self.playing? wat wat
//					//					setTimeout(function() {
//					//						dojo.addClass(node, "hidden")
//					//					}, 500);
//					console.log('leave', dndEvent);
//					dojo.stopEvent(dndEvent);
//
//				} else if(dndEvent.type && dndEvent.type == "dragdrop"){
//					console.log('drop', dndEvent.dataTransfer.files)
//					this.getUploader()._drop(dndEvent)
//				//					if(this.playingDnD) return;
//				//					else this.playingDnD = true
//				//					dojo.addClass(node,"dndDone");
//				//					css3fx.expand({
//				//						node:node, 
//				//						endScale: 0.3
//				//					}).play();
//				//					setTimeout(function() {
//				//						self.playingDnD = false
//				//						dojo.removeClass(node,"dndDone");
//				//						css3fx.expand({
//				//							node:node, 
//				//							endScale: 1
//				//						}).play();
//				//					}, 1800);
//				} 
//			},
			toggleDnD: function toggleDnD() {
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
			onError: function onError() {
				alert('Fejlede upload, besked fra systemet: ' + arguments[0].message);
				console.error("Upload failed for", arguments[0].filesInError);
			},
			createFolder: function createFolder(parentFolder) {
				console.info(traceLog(this,arguments));
				var dia = new dijit.Dialog(); //this.getDialog();
				var w = false;
				dia.attr("title", "Navn på folder");
				if(!w) {
					dia.attr("content",
						'<label for="foldername">Ny folder:</label>'+
						'<input type="text" name="foldername" dojoType="dijit.form.TextBox" trim="true" id="createfolderInput">' +
						'<center><span dojoType="dijit.form.Button" id="createfolderButton">Opret folder</span></center>');
					dojo.connect(registry.byId('createfolderButton'), "onClick", function() {
						var v = ddom.byId('createfolderInput').value;
						if(v == "") {
							var d = dojo.place(dojo.create('div'), dijit.byId('createfolderButton').domNode, "before");
							d.style.color = "red";
							d.style.font = "11px verdana";
							d.innerHTML = "* en folder ved navn 'ingenting'??"
							return;
						} else {
							for(var i = 0; i < v.length; i++) {
								if(v.charAt(i) == ' ') {
									var d = dojo.place(dojo.create('div'), dijit.byId('createfolderButton').domNode, "before");
									d.style.color = "red";
									d.style.font = "11px verdana";
									d.innerHTML = "* mellemrum tillades ikke"
									return;
								}
							}
						}
						dojo.xhrPost({
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
				console.info(traceLog(this,arguments));
				// 'pick up' file
				var sourceNode = this.tree.lastFocused;
				if(sourceNode.item.root) return
				var w = dijit.byId('movetoTree');
				var dia = new dijit.Dialog(); //this.getDialog();
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
					dojo.connect(dijit.byId('movefileButton'), "onClick", function() {
						var t = dijit.byId('movetoTree');
						var to = t.model.store.getValue(t.lastFocused.item, "abspath");
						var from = sourceNode.tree.model.store.getValue(sourceNode.item, "abspath");
						if(to == from) {
							dia.hide()
							return;
						}
						if(!t.focusNode) return;
						dojo.xhrPost({
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
					dijit.byId('movefileButton').placeAt(dia.containerNode);
				}
				dia.show();
			},


			deleteFile : function deleteFile(e) {
				console.info(traceLog(this,arguments));
				var dia = new dijit.Dialog(); //this.getDialog();
				var sourceNode = this.tree.lastFocused;
				if(sourceNode.item.root) return
				var w = dijit.byId('createfolderButton');
				dia.attr("title", "Navn på folder");
				if(!w) {
					dia.attr("content",
						'Sletter "'+sourceNode.tree.model.store.getValue(sourceNode.item, "title")+'", vil du fortsætte?' +
						(sourceNode.item.type == "dir" ? "<br><font color=red size=2>*NB slettes denne folder, slettes alle indeholdte filer og foldere!</font>":"") +
						'<center><span dojoType="dijit.form.Button" id="deletedoButton">Ok</span>'+
						'<span dojoType="dijit.form.Button" id="deletecancelButton">Fortryd</span></center>');
					dojo.connect(dijit.byId('deletedoButton'), "onClick", function() {
						dojo.xhrPost({
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
					dojo.connect(dijit.byId('deletecancelButton'), "onClick", function() {
						dia.hide();
					})
				} else {
					dia.attr("content",
						'Sletter "'+sourceNode.tree.model.store.getValue(sourceNode.item, "title")+'", vil du fortsætte?' +
						(sourceNode.item.type == "dir" ? "<br><font color=red size=2>*NB slettes denne folder, slettes alle indeholdte filer og foldere!</font>":""));			
					dijit.byId('createfolderInput').placeAt(dia.containerNode)
					dijit.byId('deletedoButton').placeAt(dia.containerNode)
					dijit.byId('deletecancelButton').placeAt(dia.containerNode)
				}
				dia.show();

			}

		});
		return Assets;
	});