var conversion = false;
var itemHeld = null;
var itemDroppedTo = null;
var viewMode = 1;
var docTree, resTree, mediaTree;
if(typeof(window.console) != "object" || typeof(console.log) != "function") {
	window.console = {
		
		log: function() { },
		debug: function() { },
		info : function() { }
	}

}

dojo.addOnLoad(function() {
	var offsetHeightForSpace =dojo.byId('scrollSpacer').offsetHeight;
	dojo.connect(window, "onscroll", function(e) {
		var Y;
		if(document.all)
			Y=document.body.scrollTop;
		else
			Y=window.pageYOffset;
	
		var spc = dojo.byId('scrollSpacer');
		if(spc._anim != null) spc._anim.stop();
		spc._anim = dojo.animateProperty({
			node : spc,
			onEnd : function() {
				spc._anim = null;
			},
			duration: 300,
			properties : {
				height :  (offsetHeightForSpace < Y  ? Y-offsetHeightForSpace : 0),

				unit: 'px'
			},
			easing : dojo.fx.expoIn
		});
		spc._anim.play(250)

	})
});

function sExceptionOut(ex)
{
	try {
		var ret = ex.name + " : " + ex.message + " ## "+ ex.fileName.substring(ex.fileName.lastIndexOf('/')) + "# line: " + ex.lineNumber + " #\n";
		ret += ex.stack;
	} catch (e) {
		console && console.debug && console.debug(e) || alert(e);
	}
	return ret;
}

function submitPartial(id, parameters, callback) {
	if(!parameters.form) alert('submitPartial: missing "form" (type)');

	if(/media|include/.test(parameters.form))
		parameters.editResource = true;
	parameters.partial = true;
console.log(gPage.baseURI+"/admin/save.php?"+
		(/media|include/.test(parameters.editResource)?"EditResource":"EditDoc")+"&id="+id);
	dojo.xhrPost({
		url: gPage.baseURI+"/admin/save.php?"+
		(/media|include/.test(parameters.editResource)?"EditResource":"EditDoc")
		+"&id="+id,
		content : parameters,
		load : function(res) {
			if(res.indexOf("SAVED") == -1) {
				openNotify(ExtendHTML.notifyTemplates.saveErr.replace("{RESPONSE}", res), [ {
					id:'saveoption',
					cb:function() {
						submitPartial(id, parameters, callback);
					},
					classes:'dijitEditorIcon dijitEditorIconSave'
				}, {
					id:'canceloption',
					classes:'dijitEditorIcon dijitEditorIconUndo'
				}
				]);
			} else {
				//					openNotify(ExtendHTML.notifyTemplates.saveSuccess, [ {
				//							id:'canceloption',
				//							classes:'dijitEditorIcon dijitEditorIconUndo'
				//						}
				//					]);
				typeof callback == "function" && callback();
			}
		}
	});
};
function openNotify(szHtml, oButtonCB) {
	dojo.require("dojox.widget.Dialog");
	dojo.require("dijit.form.Button");
	var previewNode;
	dojo.body().appendChild(previewNode = document.createElement('div'));

	var dialog = dijit.byId('notifyDialog');
	if(dialog) dialog.destroy();

	dialog = new dojox.widget.Dialog({
		node:previewNode,
		title:"OBS",
		dimensions: [480,280],
		content: szHtml,
		id:'notifyDialog',
		onCancel : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		},
		onSubmit : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		},
		onExecute : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		}
	});

	dialog.startup();
	dialog.show();

	// var o = [{ id:'s1', cb:'s2', classes:'cls' }]
	dojo.forEach(oButtonCB, function(button) {
		var node = dojo.byId(button.id);
		if(!node && console && console.debug) console.debug("Warning, button[id="+button.id+",cb="+button.cb+"] could not be initialized in notifyDialog");
		else {
			if(dijit.byId(button.id)) dijit.byId(button.id).destroy();
			new dijit.form.Button({
				iconClass: button.classes || null,
				onClick: function() {
					dijit.byId('notifyDialog').destroy()
					dojo.forEach(oButtonCB, function(button) {
						dijit.byId(button.id).destroy();
					});
					if(typeof(button.cb) == 'function') button.cb();
				}
			},node).startup()
		}
	}) // each
	return dialog;
};

	/*
	showInfo : function(item, selectedNode) {
		if(item.root) return;
		var szHtml = "";
		var block =selectedNode == true ? dojo.byId('nodeselectedElementInfo') : dojo.byId('nodehoveredElementInfo');
		if(parseInt(item.id) > 9999) {
			szHtml = ExtendHTML.actionTextReference;
		} else if(item.type == "include") {
			szHtml = ExtendHTML.actionTextResource;
		} else if(item.type == "page") {
			szHtml = ExtendHTML.actionTextPage;
		} else if(item.type == "media") {
			szHtml = ExtendHTML.actionTextMedia;
		} else
			szHtml = ExtendHTML.actionTextSubPage
		dojo.byId('nodeselectActionText').innerHTML = szHtml;
		szHtml  ="<table style=\"background-color:transparent;\"><tbody>";
		for(var key in item) {
			if(/^_/.test(key) || item[key] == "")
				continue;
			else if(!/(include|media)/.test(item.type) && (/(showtitle|created|children|edit)/i.test(key)) )
				continue;
			else if(/(include|media)/.test(item.type) && (/(title|lastmodified)/i.test(key)) )
				continue;
			else szHtml += '<tr><td style="width: 80px  !important;border:transparent none;"><label class="tiny">'+key+'</label></td>'+
				'<td style="width: auto !important; border:transparent none;"><span class="tiny urlformat">'+item[key]+'</span></td></tr>';
		}
		szHtml += "</tbody></table>";
		block.innerHTML = szHtml;
		dojo.animateProperty({
			node : block,
			onEnd : function() {
				dojo.animateProperty({
					node: block,
					duration: 1250,
					properties : {
						backgroundColor: {
							begin : '#CAbBEE',
							end : '#CADBEE'
						}
					},
					easing : dojo.fx.expoOut
				}).play();
			},
			duration: 300,
			properties : {
				backgroundColor: {
					begin : '#CADBEE',
					end : '#CAbBEE'
				}
			},
			easing : dojo.fx.expoIn
		}).play();
	//#CADBEE
	//#CAbBEE
	},

	// menu functionality
	addCreateMenu : function() {
		var tree = this;
		var subMenu = dijit.byId(this.id+"PopupSubMenu");
		if(subMenu) return subMenu;
		var addSubPage = function() {
			if(confirm("Opret en ny side til " + tree.focusItem.title + "?"))
				document.location = gPage.baseURI+"/admin/edit.php?EditDoc&type=subpage"+
				"&preset=attach_id;"+tree.focusItem.id + "," +
				"title;Underside til "+tree.focusItem.title;
		};
		var newResource = function() {
			if(confirm("Tilføj script eller stylesheet til " + tree.lastFocused.item.title + "?"))
				document.location = gPage.baseURI+"/admin/edit.php?EditDoc&type=include"+
				"&preset=attach_id;"+tree.focusItem.id + ',' +
				"alias;"+Math.floor(Math.random()*100000)+"_"+tree.focusItem.title;
		};
		var newMedia = function() {
			if(confirm("Tilføj medie til " + tree.focusItem.title + "?"))
				document.location = gPage.baseURI+"/admin/edit.php?EditDoc&type=media"+
				"&preset=attach_id;"+tree.focusItem.id + ',' +
				"alias;"+Math.floor(Math.random()*1000000)+"_"+tree.focusItem.title;
		};

		subMenu = new dijit.Menu({
			parentMenu:this.menu,
			id:this.id+"PopupSubMenu"
		});
		subMenu.addChild(new dijit.MenuItem({
			label:"Underside [subpage]",
			onClick:addSubPage,
			iconClass:"dijitEditorIcon dijitEditorIconCopy"
		}));
		subMenu.addChild(new dijit.MenuItem({
			label:"Tilknyt js/css [include]",
			onClick:newResource,
			iconClass:"dijitEditorIcon dijitEditorIconSelectAll"
		}));
		subMenu.addChild(new dijit.MenuItem({
			label:"Tilknyt medie [media]",
			onClick:newMedia,
			iconClass:"dijitEditorIcon dijitEditorIconSelectAll"
		}));
		return subMenu
	},
	addMenu : function(tree, only) {
		dojo.require("dijit.Menu");

		var instance = this;

		var editPage = function () {
			var item = instance.focusItem;
			if(confirm("Rediger " + item.title + "?")) {
				document.location = gPage.baseURI+"/admin/edit.php?EditDoc&type="+item.type+"&id="+item.id;
			}
		};
		var deleteElement = function() {
			var item = instance.focusItem;
			if(confirm("Sikker på du vil slette '" + item.title + "'?")) {
				dojo.xhrPost({
					url:gPage.baseURI+"/admin/save.php?delElement",
					content:  {
						id: item.id,
						type : item.type
					},
					load: function(res) {
						if(/DELETED/.test(res)) {
							//							openNotify(ExtendHTML.notifyTemplates.deleteSuccess, [ {
							//								id:'canceloption',
							//								classes:'dijitEditorIcon dijitEditorIconUndo'
							//							}]);
							instance.store.deleteItem(item);
						} else
							openNotify(ExtendHTML.notifyTemplates.deleteFailure.replace("{RESPONSE}", res), [ {
								id:'canceloption',
								classes:'dijitEditorIcon dijitEditorIconUndo'
							}]);
					}


				});
			}
		};

		var detach = function(e) {
			var item = instance.focusItem

			var attachId = ","+item.attachId[0]+",";
			console.log(attachId)
			attachId = attachId.replace(","+item.referenceId+",", ",");
			console.log(attachId)
			attachId = attachId.substring(1,attachId.length-1)
			console.log(attachId)
			if(instance.lastFocused.getParent().getChildren().length <= 1)
				instance.lastFocused.getParent().iconNode.className = "dijitTreeIcon dijitLeaf"

			submitPartial(item.resourceId, {
				form: item.type,
				attachId:attachId
			}, function() {
				instance.store.deleteItem(instance.lastFocused.item);
				instance.lastFocused.destroy();
			});

		}
		var attach = function(e) {
			var item = instance.focusItem
			var attachId = item.attachId[0].split(",")
			console.log(attachId)
			var newId = docTree.focusItem || null;
			console.log(newId)
			if(!newId) return;
			newId = newId.id;

			for(var i =0 ; i < attachId.lenght; i++) {
				if(attachId[i] == newId)
					var exists = true;
			}

			if(!exists) {
				attachId.push(newId);
				item.attachId[0] = attachId.join(",")
				submitPartial(item.id, {
					form: item.type,
					attachId:item.attachId
				}, function() {
					try {
						docTree.addChildFromItem(newId, item);
					} catch(e) {
						console.info(e);
					}
				});
			}

		};
		var convertDocument = function(e) {
			var item = instance.focusItem
			var oldParent = docTree.store._getItemByIdentity(item.attachId)
			var newParent = docTree.store._getItemByIdentity((item.type=="page"?"9999":"9998"));
			submitPartial(item.id, {
				form: (item.type=="page")?"subpage":"page",
				attach_id:'0',
				isdraft:'1'
			}, function() {

				instance.update();

			});
		};

		var previewDocument = function(e) {
			var item = instance.focusItem
			if(item.type == "include")
				instance.resourcePreview(item);
			else
				instance.documentPreview(item);
		};

		this.menu.addChild(dijit.byId(this.id+'menuPreview') || new dijit.MenuItem({
			label:"Vis indhold",
			onClick:previewDocument,
			id: this.id+'menuPreview',
			iconClass:"dijitEditorIcon dijitEditorIconInsertTable"
		}));
		this.menu.addChild(dijit.byId(this.id+'menuEdit') || new dijit.MenuItem({
			label:"Rediger",
			onClick:editPage,
			id: this.id+'menuEdit',
			iconClass:"dijitEditorIcon dijitEditorIconSave"
		}));
		if(!/resource|media/.test(this.id))
		{
			var subMenu = dijit.byId(this.id+'PopupSubMenu') || this.addCreateMenu();
			this.menu.addChild(dijit.byId(this.id+'PopupMenuItem') || new dijit.PopupMenuItem({
				label:"Tilknyt nyt element",
				popup:subMenu,
				id:this.id+"PopupMenuItem"
			}));
			this.menu.addChild(dijit.byId(this.id+'menuDetach') || new dijit.MenuItem({
				label:"Frigør ressource",
				onClick:detach,
				id: this.id+'menuDetach',
				iconClass:"dijitEditorIcon dijitEditorIconDelete"
			}));
			this.menu.addChild(new dijit.MenuSeparator());

			this.menu.addChild(dijit.byId(this.id+'menuConvert') || new dijit.MenuItem({
				label:"Konv'",
				onClick:convertDocument,
				id: this.id+'menuConvert',
				iconClass:"dijitEditorIcon dijitEditorIconInsertTable"
			}));
		} else {
			this.menu.addChild(dijit.byId(this.id+'menuAttach') || new dijit.MenuItem({
				label:"Tilknyt til kategori-side",
				onClick:attach,
				id: this.id+'menuAttach',
				iconClass:"dijitEditorIcon dijitEditorIconCopy"
			}));
		}
		this.menu.addChild(dijit.byId(this.id+'menuDelete') || new dijit.MenuItem({
			label:"Slet",
			onClick:deleteElement,
			id: this.id+'menuDelete',
			onMouseOver:function(e) {
				var tg = dijit.getEnclosingWidget(e.target);
				if(!tg._timeoutEnable) {
					tg.disabled=true;
					tg._timeoutEnable = setTimeout(function() {
						tg.disabled = false; tg._timeoutEnable = null
					}, 600);
				}
			},
			iconClass:"dijitEditorIcon dijitEditorIconDelete"
		}));
		this.menu.bindDomNode(this.domNode);
		this.menu.startup();
		var m = this.menu;
		var toggleItem = function(item, mi) {
			if(/menuPreview/.test(mi.id)) return '';
			if(/menuConvert/.test(mi.id)) mi.attr('label',"Konvertér til "+(item.id >=10000 ? "" : item.type=="page"?"under-side":"kategori-side"));
			if(item.id >= 10000) return /menuDetach/.test(mi.id);
			if(item.id > 9990) return false;
			if(/page/.test(item.type))return !/menuDetach/.test(mi.id)|| /PoupupMenuItem/.test(mi.id);
		}
		var toggleSubItem = function(item, mi) {
			if(item.root) return false;
			if(item.type == "page") return true;
			if(item.type == "subpage" && !/subpage/.test(mi.label)) return false;
			return true;
		};

		if(!/resource|media/.test(this.id))
			dojo.connect(this.menu, "_openMyself", this, function(e) {
				var tn = dijit.getEnclosingWidget(e.target);
				this.focusItem = tn.item;
				this.lastFocused = tn;
				m.getChildren().forEach(function(i){
					if(/MenuSeparator/.test(i.id)) return;
					i.attr("disabled", (toggleItem(tn.item, i)?"":"true"));
				});
				subMenu.getChildren().forEach(function(i){
					if(/MenuSeparator/.test(i.id)) return;
					i.attr("disabled", (toggleSubItem(tn.item, i)?"":"true"));
				});

			});
		else
			dojo.connect(this.menu, "_openMyself", this, function(e) {
				var tn = dijit.getEnclosingWidget(e.target);
				this.focusItem = tn.item;
				this.lastFocused = tn;
			});
	},

	resourcePreview : function(item) {
		if(!ExtendHTML.hlCssLoaded) ExtendHTML.hlLoadCss();
		dojo.require("dojox.highlight");
		dojo.require("dojox.widget.Dialog");
		dojo.require("dojox.highlight.languages.css");
		dojo.require("dojox.highlight.languages.javascript");
		dojo.xhrGet({
			url:gPage.baseURI+"/openView/Resources.php?format=contents&id="+
			this.tree.store.getValue(item, 'resourceId') || this.tree.store.getValue(item,'id'),
			load: ExtendHTML.showPreview
		});
	},
	documentPreview : function(item) {
		//	console.log('preview' + item.id);
		dojo.require("dojox.widget.Dialog");
		dojo.xhrGet({
			url : gPage.baseURI+"/openView/Documents.php?format=contents&"+
			"searchid="+item.id+
			"type="+item.type,
			load:function(res) {
				var w = dijit.byId('docPreview');
				var dim = [800,700];
				if(w) {
					w.setContent(res);
					w.dimensions=dim;
					w.layout()
				} else {
					w = new dojox.widget.Dialog({
						title:"Preview",
						dimensions: dim,
						content: res,
						id:'docPreview'
					});
					w.startup();
				}
				w.show();
			}
		});
	}

	*/

