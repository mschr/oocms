define(["dojo/_base/declare"], function(declare){

	return declare("OoCmS.toolkit", null, {

		actionText : {
			include : '<h4>Script/Style</h4><ul style="max-width: 520px;"> \
<li>Markér en kategori-side (<b>Page</b>) og vælg \'Tilknyt til kategori-side\' fra kontekstmenuen for at referere til den valgte side og dennes undersider.</li> \
<li>Dobbeltklik på elementet for at visuelt at se indholdet.</li> \
<li>Hvis angivet anvendes \'Kommentar\'-feltet i træstrukturen.</li> \
<li>Højreklik for yderligere muligheder.</li></ul>',
			page : '<h4>Kategori (Page)</h4><ul style="max-width: 520px;"><li>Dobbeltklik på elementet for at visuelt at se indholdet (uden menu-navigation).</li>\
<li>Højreklik for yderligere muligheder, kan f.eks. konverteres til under-side og derved tilknyttes en anden side ved træk-og-slip.</li></ul>',
			subpage : '<h4>Underside (SubPage)</h4><ul style="max-width: 520px;"><li>Træk undersiden over en <b>Page</b> i første niveau for at tilknytte til en kategori.</li>\
<li>Dobbeltklik for at hente og vise selve indholdet i popup.</li>\
<li>Højreklik for yderligere muligheder, kan således f.eks. slettes eller konverteres til top-niveau kategori-side</li></ul>',
			media : '<h4>Billede/embed/misc</h4><ul style="max-width: 520px;"><li>Markér en kategori-side (<b>Page</b>) og vælg \'Tilknyt til kategori-side\' fra kontekstmenuen for at referere til den valgte side og dennes undersider.</li>\
<li>Dobbeltklik på elementet for at visuelt at se indholdet.</li>\
<li>Hvis angivet anvendes \'Kommentar\'-feltet i træstrukturen.</li> \
<li>Højreklik for yderligere muligheder</li></ul>',
			dir : '<h4>Upload folder</h4><ul style="max-width: 520px;">\
<li>Dobbeltklik på folderen for at skifte arbejdsfolder.</li>\
<li>Højreklik for yderligere muligheder</li></ul>',
			file : '<h4>Uploadet fil</h4><ul style="max-width: 520px;">\
<li>Dobbeltklik på elementet for at få vist indholdet af kendte filtyper.</li>\
<li>Højreklik/kontekstmenu giver yderligere muligheder</li></ul>',
			reference : '<h4>Tilknyttet ressource</h4><ul style="max-width: 520px;"> \
<li>Træk/slip er ikke muligt, find originalen i højre side hvis andre sider skal tilknyttes samme ressource.</li> \
<li>Dobbeltklik for at inspicere indholdet.</li></ul>'
		},
		mimetypes : [["pnt","Demo Print Plugin for Unix/Linux"],["swf","Shockwave Flash"],["spl","FutureSplash Player"],["divx","DivX Media Format"],["divx","DivX Media Format"],["mov","Quicktime"],["mov","Quicktime"],["mov","Quicktime"],["mp4","Quicktime"],["sdp","Quicktime - Session Description Protocol"],["mov","Quicktime"],["smil","SMIL"],["ram","RealAudio"],["rm","RealAudio"],["rm","RealMedia"],["ra","RealAudio"],["ram","RealAudio"],["rv","RealVideo"],["ra","RealAudio"],["rpm","RealAudio"],["smil","SMIL"],["avi","AVI"],["avi","AVI"],["wmv","Microsoft WMV video"],["asf","Media Files"],["asx","Media Files"],["wm","Media Files"],["wmv","Microsoft WMV video"],["wmv","Windows Media"],["wmp","Windows Media"],["wmp","Windows Media"],["wvx","Windows Media"],["wax","Windows Media"],["wma","Windows Media"],["asx","Windows Media"],["wav","Microsoft wave file"],["wav","Microsoft wave file"],["mpg","MPEG"],["mpeg","MPEG"],["mpg","MPEG"],["mpeg","MPEG"],["mpg","MPEG"],["mpeg","MPEG"],["mpv2","MPEG2"],["mp2ve","MPEG2"],["mpg","MPEG"],["mpeg","MPEG"],["mpg","MPEG"],["mpeg","MPEG"],["mp2","MPEG audio"],["mp2","MPEG audio"],["mp4","MPEG 4 audio"],["mp4","MPEG 4 audio"],["mp4","MPEG 4 Video"],["mp4","MPEG 4 Video"],["3gp","MPEG 4 Video"],["mp3","MPEG audio"],["mp3","MPEG audio"],["m3u","MPEG url"],["mp3","MPEG audio"],["ogg","Ogg Vorbis Media"],["ogg","Ogg Vorbis Audio"],["ogg","Ogg Vorbis Audio"],["ogg","Ogg Vorbis / Ogg Theora"],["flac","FLAC Audio"],["flac","FLAC Audio"],["fli","FLI animation"],["flc","FLI animation"],["fli","FLI animation"],["flc","FLI animation"],["flv","Flash Video"],["viv","VivoActive"],["vivo","VivoActive"],["nsv","Nullsoft Streaming Video"],["mod","Soundtracker"],["au","Basic Audio File"],["snd","Basic Audio File"],["au","Basic Audio File"],["snd","Basic Audio File"],["pls","Shoutcast Playlist"],["scr","Novell Moonlight"]],
		constructor : function(args) {
			dojo.mixin(this, args);
			window.tool = this;

			var self = this;
			setTimeout(function() {
				var mt = navigator.mimeTypes, ext
				for(var i = 0; i < mt.length; i++) {
					if(mt[i].suffixes) { // check if there's known filetype
						ext = mt[i].suffixes.split(","); // there may be multiple, ie mpg mpeg
						for(var j = 0; j < ext.length; j++) {
							if(!/\*/.test(ext[j])) // push on stack regardless of known occurance
								self.mimetypes.push([ext[j].toString().replace(/\./, ""),mt[i].description])
						}
					}
	
				}
	
			}, 2000);
		},
	
		hlCssLoaded : false,
		/*******

	Context Menu callback functions
	- expects focusItem to be set (onOpenMyMenuSelf) and the toolbox as scope
		addSubPage(event)
		newResource(event)
		newMedia(event)
		editPage(event)
		deleteElement(event)
		togglePosition(event)
		moveFile(event)
		deleteFile(event)
		detachResource(event)
		attachResource(event)
		convertDocument(event)
		previewDocument(event)

		 *********/
		
		addSubPage : function addSubPage(e) {
			console.info(traceLog(this,arguments));
			if(confirm("Opret en ny side til " + this.tree.focusItem.title + "?"))
				document.location = this.editorUrl + "&type=subpage"+
				"&preset=attach_id;"+this.tree.focusItem.id + "," +
				"title;Underside til "+this.tree.focusItem.title;
		},
		newResource : function newResource(e) {
			console.info(traceLog(this,arguments));
			if(confirm("Tilføj script eller stylesheet til " + this.tree.lastFocused.item.title + "?"))
				document.location = this.editorUrl + "&type=include"+
				"&preset=attach_id;"+this.tree.focusItem.id + ',' +
				"alias;"+Math.floor(Math.random()*100000)+"_"+this.tree.focusItem.title;
		},
		newMedia : function newMedia(e) {
			console.info(traceLog(this,arguments));
			if(confirm("Tilføj medie til " + this.tree.focusItem.title + "?"))
				document.location = this.editorUrl + "&type=media"+
				"&preset=attach_id;"+this.tree.focusItem.id + ',' +
				"alias;"+Math.floor(Math.random()*1000000)+"_"+this.tree.focusItem.title;
		},
		editPage :function editPage(e) {
			console.info(traceLog(this,arguments));
			if(confirm("Rediger " + this.tree.focusItem.title + "?")) {
				document.location = this.editorUrl + "&type="+this.tree.focusItem.type+"&id="+this.tree.focusItem.id;
			}
		},
		errorMessage : function errorMessage(message, timeout) {
			var div = dojo.byId('appErrorDiv');
			div.innerHTML = message;
			timeout = (timeout ? timeout : 3500);
			dojo.animateProperty( {
				node: div,
				beforeBegin: function() {
					dojo.style(div, {
						display:''
					});
				},
				onEnd: function() {
					dojo.animateProperty( {
						node: div,
						onEnd: function() {
							dojo.style(div, {
								display:'none'
							});
						},
						properties: {
							opacity: {
								start: 1, 
								end: 0
							},
							height: {
								start: 22, 
								end: 0
							}
						}
				
					}).play(timeout)
				},
				properties: {
					opacity: {
						start: 0, 
						end: 1
					},
					height: {
						start: 0, 
						end: 22
					}
				}
				
			}).play();
			
		},
		togglePosition : function togglePosition(itemOrEvt, direction) {
			
			console.info(traceLog(this,arguments));
			if(!itemOrEvt || !direction) return;
			var item, tg, tree = this.tree;
			if(itemOrEvt.target) { // if incoming caller is a context-menu
				item = this.tree.focusItem;
				var tg = (typeof e != "number" ? dijit.getEnclosingWidget(e.target) : e);
				direction = tg.params.direction
			} else {
				item = itemOrEvt;
			}

			dojo.xhrPost({
				url: ctrl.URI.path.substring(0,ctrl.URI.path.lastIndexOf('/')) + "/AjaxAPI.php",
				content:  {
					id: item.id,
					toggleposition : 'true',
					direction: direction
				},
				error: function(res) {
					alert(res.message)
				},
				load: function(res) {
					if(/DONE/.test(res)) {
						tree.update();
					} else if(!/NOP/.test(res)) {
						alert("Der opstod en fejl\n"+res);
						return;
					}
				} // load
			});
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
				dojo.connect(dijit.byId('createfolderButton'), "onClick", function() {
					var v = dojo.byId('createfolderInput').value;
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
				dijit.byId('createfolderInput').placeAt(dia.containerNode)
				dijit.byId('createfolderButton').placeAt(dia.containerNode)
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

		},

		detachResource : function detachResource(e) {
			console.info(traceLog(this,arguments));
			var tree = this.tree;
			var attachId = ","+tree.focusItem.attachId[0]+",";
			var orgTree = dijit.byId((/include/.test(tree.focusItem.type) ? "resource":"media")+"Tree");

			attachId = attachId.replace(","+tree.focusItem.referenceId+",", ",");
			attachId = attachId.substring(1,attachId.length-1)
			if(!/[0-9]+/.test(attachId))
				attachId = "";
			if(tree.lastFocused.getParent().getChildren().length <= 1)
				tree.lastFocused.getParent().iconNode.className = "dijitTreeIcon dijitLeaf"

			this.submitPartial(this.tree.focusItem.resourceId, {
				form: tree.focusItem.type,
				attachId:attachId
			}, function() {
				tree.model.store.deleteItem(tree.lastFocused.item);
				tree.lastFocused.destroy();
				//			orgTree.update() //||
				orgTree.model.store.setValue(
					orgTree._itemNodeMap[tree.focusItem.resourceId].item, "attachId", attachId);
			});

		},
		attachDocument : function attachDocument(e) {
			console.info(traceLog(this,arguments));
			var item = this.tree.focusItem;
			var attachId = this.tree.model.store.getValue(item, 'attachId')
			var newId = docTree.focusItem || null;
			if(!newId || newId.id > 9999) 
				return;
			newId = docTree.model.store.getValue(newId ,'id');

			if(attachId != "")
				for(var i = 0, attachId = attachId.split(","); i < attachId.length; i++)
					if(attachId[i] == newId)
						var exists = true;

			if(!exists) {
				if(typeof attachId == "object")
					attachId.push(newId)
				else
					attachId = [newId];
				this.tree.model.store.setValue(item, 'attachId', attachId.join(","))
				this.submitPartial(item.id, {
					form: item.type,
					attachId:item.attachId
				}, function() {
					docTree.addChildFromItem(newId, item);
				});
			}
		},
		attachResource : function attachResource(e) {
			console.info(traceLog(this,arguments));
			var item = this.tree.focusItem;
			var attachId = this.tree.model.store.getValue(item, 'attachId')
			var newId = docTree.focusItem || null;
			if(!newId || newId.id > 9999) 
				return;
			newId = docTree.model.store.getValue(newId ,'id');

			if(attachId != "")
				for(var i = 0, attachId = attachId.split(","); i < attachId.length; i++)
					if(attachId[i] == newId)
						var exists = true;

			if(!exists) {
				if(typeof attachId == "object")
					attachId.push(newId)
				else
					attachId = [newId];
				this.tree.model.store.setValue(item, 'attachId', attachId.join(","))
				this.submitPartial(item.id, {
					form: item.type,
					attachId:item.attachId
				}, function() {
					docTree.addChildFromItem(newId, item);
				});
			}
		},
		convertDocument : function convertDocument(e) {
			console.info(traceLog(this,arguments));
			var tree = this.tree
			this.submitPartial(this.tree.focusItem.id, {
				form: (this.tree.focusItem.type=="page")?"subpage":"page",
				attach_id:'0',
				isdraft:'1'
			}, function() {
				tree.update();
			});
		},

		previewDocument : function previewDocument(e) {
			console.info(traceLog(this,arguments));
			var item = this.tree.focusItem
			if(item.type == "include")
				this.resourcePreview(item);
			else if(/page/.test(item.type))
				this.documentPreview(item);
			else
				this.anyPreview(item);
		},

		/******

	Preview functionality(item)
		resourcePreview(item)
		documentPreview(item)
		anyPreview(item)
		showPreview(xhrResposne)
		showHighlightPreview(xhrResponse)

		 *******/

		resourcePreview : function resourcePreview(item) {
			console.info(traceLog(this,arguments));
			var id = this.tree.store.getValue(item, 'resourceId') || this.tree.store.getValue(item,'id');
			if(!this.hlCssLoaded) this.hlLoadCss();
			dojo.xhrGet({
				url:this.tree.model.store.getValue(item, 'abspath') || gPage.baseURI+"/openView/Resources.php?format=contents&id="+id,
				load: this.showHighlightPreview
			});
		},
		documentPreview : function documentPreview(item) {
			console.info(traceLog(this,arguments));
			dojo.xhrGet({
				url : gPage.baseURI+"/openView/Documents.php?format=contents&"+
				"searchid="+this.tree.store.getValue(item, 'id')+
				"type="+this.tree.store.getValue(item, 'type'),
				load: this.showPreview
			});
		},
		anyPreview : function anyPreview(item) {
			console.info(traceLog(this,arguments));
			if(!item.abspath) return;
			var path = this.tree.model.store.getValue(item,'abspath')
			window.open(path, "_openPreview","width=640,height=480,statusbar=1,resize=1,scrollbars=1");
			return; /*** **/
			if(/(js|css|htm|html|php)$/.test(path)) {

				this.resourcePreview(item)

			} else if(/gif|png|jpg|jpeg|tiff|bmp/i.test(path)) {

				// TODO: hook preview with img.onload event for proper preview dimensioning ( nb: img.src = img.src for IE to fire event)
				this.showPreview("<img src=\""+path+"\" />")

			} else

				window.open(path, "_openPreview","width=640,height=480,statusbar=1,resize=1,scrollbars=1");
		//if(this.getFiletypeDescription(this.tree.model.store.getValue(item, 'icon'))||/(gif|jpg|jpeg|png)$/i.test(path))
		},

		showPreview : function showPreview(res) {
			console.info(traceLog(this,arguments));
			var w = dijit.byId('docPreview');

			var bufEl = document.createElement('div');
			bufEl.style.width = "800px";
			bufEl.innerHTML = res;
			dojo.body().appendChild(bufEl);
			bufEl.style.display="block";
			var d = dojo.coords(bufEl);
			bufEl.parentNode.removeChild(bufEl);
			d.h = (d.h < 250) ? 250 : d.h;

			if(w) {
				w.setContent(res);
				w.dimensions=[d.w+19,d.h+29];
				w.layout()
			} else {
				w = new dojox.widget.Dialog({
					title:"Preview",
					dimensions: [d.w+19,d.h+29],
					content: res,
					id:'docPreview'
				});
				w.startup();
			}
			w.show();
		},
		hlLoadCss : function hlLoadCss() {
			console.info(traceLog(this,arguments));
			var l = document.createElement('link');
			l.href = "/dojo_tk/dojox/highlight/resources/pygments/default.css";
			l.rel = "stylesheet";
			document.getElementsByTagName('head')[0].appendChild(l);
			l = document.createElement('link');
			l.href = "/dojo_tk/dojox/highlight/resources/highlight.css";
			l.rel = "stylesheet";
			document.getElementsByTagName('head')[0].appendChild(l);
			this.hlCssLoaded=true;
		},
		showHighlightPreview: function showHighlightPreview(responseBody) {
			console.info(traceLog(this,arguments));
			//			dojo.require("dojox.highlight");
			//			dojo.require("dojox.highlight.languages.css");
			//			dojo.require("dojox.highlight.languages.javascript");
			require([
				"dojox/highlight",
				"dojox/highlight.languages.css",
				"dojox/highlight.languages.javascript",
				"dojox/widget/Dialog"
				], function(highlight, langcss, langjs, xdialog){
					var overlay = dojo.byId('loadOverlay');
					if(overlay) overlay.style.display="";
					var w = dijit.byId('hlPreview');
					var bufEl = document.createElement('div');
					bufEl.style.display="none";
					bufEl.style.width = "800px";
					try {
						var p;
						if(/404\ Not\ Found/.test(responseBody)) {
							bufEl.innerHTML = "<p style=\"height: 80px;text-align:center\"><font color=\"red\">404 File Not Found.</font><br/>Kontrollér kilden er korrekt</p>";
						} else {
							bufEl.appendChild(p=document.createElement('pre'));
							p.style.margin = "10px";
							p.appendChild(p=document.createElement('code'));
							p.innerHTML = responseBody;
							highlight.init(p);
						}
						dojo.body().appendChild(bufEl);
						setTimeout(function() {

							bufEl.style.display="block";
							var d = dojo.coords(bufEl);
							bufEl.style.display="none";
							d.h = (d.h < 250) ? 250 : d.h;
							if(w) {
								w.setContent(bufEl.innerHTML);
								w.dimensions=[d.w+19,d.h+29];
								w.layout()
							} else {
								w = new xdialog({
									title:"Preview",
									dimensions: [d.w+19,d.h+29],
									content: bufEl.innerHTML,
									id:'hlPreview'
								});
								w.containerNode.className += " " + bufEl.className;
								dojo.style(w.containerNode, {
									padding: '8px'
								})
								w.startup();
							}
							bufEl.parentNode.removeChild(bufEl);
							w.show();
							var overlay = dojo.byId('loadOverlay');
							if(overlay) overlay.style.display="none";


						}, 950);

					} catch(e) {
						console.debug(e)
					}
				})
		},
		/********
Misc handles
	 *********/
		getFiletypeDescription : function getFiletypeDescription(ext) {
			console.info(traceLog(this,arguments));
			var d;
			if(typeof(ext) != "string") ext = ext.toString()
			for(var i = 0; i < this.mimetypes.length; i++) {
				if(ext==this.mimetypes[i][0]) {
					d = this.mimetypes[i][1]
				}
			}

			return d;
		},
		/********
dojo override/fixes
	 *********/
		_widgetfixObservers: [],
		fixWidget: function(widget, dontHandleObserver) {
			var self = this;
			dojo.forEach(widget.constructor._meta.bases, function(base) {
				if(/dijit\.Tree/.test(base.prototype.declaredClass)) {
					if(!dontHandleObserver) self._widgetfixObservers.push(
						dojo.connect(widget, "_onNodeFocus", widget, self.treenodeselect));
					else dojo.connect(widget, "_onNodeFocus", widget, self.treenodeselect);
				}
			})
		},
		treenodeselect: function(treeNode) {
			var treeRow = treeNode.rowNode
			if(!treeRow) return;
			if(this.myLastFocused) {
				dojo.removeClass(this.myLastFocused, "_treenodeSelected")
			}
			dojo.addClass(treeRow, "_treenodeSelected")
			this.myLastFocused = treeRow
		}
	})
});