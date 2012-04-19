define(["dojo/_base/declare",
	//	"OoCmS/AbstractController",
	"dijit/layout/BorderContainer",

	"dojo/_base/lang",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/_base/xhr",
	"dojo/_base/fx",
	"dojo/dom",
	"dojo/dom-style",
	"dojo/dom-class",
	"dojo/dom-geometry",
	"dojo/dom-construct",
	"dijit/registry",
	"dojo/topic",
	//	"dojo/date/locale",
	"dijit/_base/popup",
	"dijit/Toolbar",
	"dijit/form/Button",
	"dijit/TooltipDialog",
	"dijit/_WidgetBase",
	"dijit/_TemplatedMixin",
	"dijit/_WidgetsInTemplateMixin",
	"dojo/text!./templates/pagetree.html",
	"dojo/fx/easing",
	"OoCmS/_writestore",
	"OoCmS/_dndmodel",
	"OoCmS/_dndtree",
	"OoCmS/formdialog/Resources",

	], function(declare, djborderlayout,dlang, darray, dconnect, dxhr, dfx,
		ddom, dstyle, dclass, ddomgeometry, ddomconstruct, registry, dtopic,
		dpopup, toolbar, button, ttipdialog,
		djwidgetbase,djtemplated,djwidgetintemplate,oopagetreetemplate,
		dfxeasing, oowritestore, oodndmodel, oodndtree, resourceDialog){
		var numOrder = function(a,b) {
			if(!b) return -1;
			return parseFloat(a)-parseFloat(b);
		}
		var charOrder = function(a,b) {
			a = a.toLowerCase();
			b = b.toLowerCase();
			if (a>b) return 1;
			if (a <b) return -1;
			return 0;
		}
		var stringOrder = function(a,b) {
			if(!b) return -1;
			var res = 0, i=0, maxlen = Math.min(a.length,b.length)
			while(res == 0 && i < maxlen) {
				res = charOrder(a[i],b[i++]);
			}
			if(i == maxlen)
				res = b.length < a.length ? 1 : (b.length==a.length ? 0 : -1);
			return res;
		}

		var PageTreeContents = declare("OoCmS._pagetreeform", [djwidgetbase,djtemplated],{

			templateString: oopagetreetemplate,
			//			constructor: function() {
			//				this.inherited(arguments);
			//			}
			//			buildRendering: function() {
			//				this.inherited(arguments);
			//			},
			//			postCreate: function() {
			//				this.inherited(arguments);
			//			},
			startup: function() {
				this.inherited(arguments);
				dclass.add(this.domNode, "OoCmSPageTreeForm");
			}
		});

		
		var PageTree = declare("OoCmS.pagetree", [djborderlayout], {
			observers: [],
			constructor: function constructor(args) {

				traceLog(this,arguments)
				this.dijitrdyId = "resourceselectorTree";
			},
			isDirty: function isDirty() {
				traceLog(this,arguments)
				return false;
			},
			unload: function unload() {
				traceLog(this,arguments)
				
				darray.forEach(this.modelObservers, dconnect.disconnect)
				darray.forEach(this.observers, dconnect.disconnect)
				darray.forEach(this._resourcedescription, function(w){
					w.popup.destroyRecursive();
					w.destroyRecursive();
				});
			//				this._toolbar.destroyRecursive();
			//				this._pageselector.model.store.close()
			//				this._pageselector.destroyRecursive();
			//				this._resourceselector.model.store.close();
			//				this._resourceselector.destroyRecursive();
			//				this._toolbar.destroyRecursive();
			//				this._pageselectortoolbar.destroyRecursive();
			//				this._resourceselectortoolbar.destroyRecursive();

			},

			postCreate: function postCreate(node) {
				traceLog(this,arguments)
				var top = new djwidgetbase({
					id:'pageHeader',
					region:'top',
					splitter: false,
					gutter: false
				}, ddomconstruct.create('div', {
					className:'paneHeader',
					innerHTML:'Sider &gt; Hieraki, relationer og inkluderede ressourcer'
				})),
				left = new djborderlayout({
					region:'left', 
					style:'width: 190px;overflow-x:hidden', 
					splitter: false,
					gutter: false,
					id: 'leftcolumn_layout'
				}),
				center = new djborderlayout({
					region:'center', 
					splitter:false,
					id: 'centercolumn_layout'
				}),
				pageselector= this.getPageSelector({
					region:'center',
					gutter:false,
					style:'overflow-x:hidden'
				}),
				resourceselector = this.getResourceSelector({
					style:'min-height: 200px',
					region:'bottom',
					splitter: true
				});
				this.addChild(top);
				this.addChild(left);
				left.addChild(this.getSelectorToolbar({
					region:'top',
					splitter: false,
					gutter: false
				}));
				//				left.addChild();
				left.addChild(pageselector)
				left.addChild(resourceselector);
				
				center.addChild(this.getToolbar({
					region: 'top'
				}));
				center.addChild(new PageTreeContents({
					region: 'center',
					splitter: true,
					gutter: true,
					id: 'centercolumn'
				}));
				this.addChild(center);

				this._pageselector.on("dblclick", dlang.hitch(this, this.onItemStateEdit));
				this._pageselector.on("load", dlang.hitch(this, this.renderTree()));
				this.observers.push(
					dconnect.connect(this._pageselector.model.store, "_saveCustom", this, this.renderTree));

				this._resourceselector.on("click", dlang.hitch(this, this.onItemStateFocus));
				this._resourceselector.on("load", dlang.hitch(this, this.renderTree));
				this.observers.push(
					dconnect.connect(this._resourceselector.model.store, "_saveCustom", this, this.renderTree));
				this.inherited(arguments);

			},
			renderTree: function renderTree(force) {
				console.log('render')
				var p = this.getPageSelector(),
				r = this.getResourceSelector(),
				pagesStore = p.model.store,
				resStore = r.model.store,
				self = this
				if(!force && 
					(pagesStore._loadInFinished 
						|| resStore._loadInFinished 
						|| pagesStore._isrendered)) return;
				pagesStore._isrendered = true;
				setTimeout(function() {
					delete  self._isrendered
				}, 1500);
				p.rootNode.getChildren().forEach(function(treeNode) {
					var id = pagesStore.getValue(treeNode.item, "id"), i;
					var exp = "("+"^"+id+"$|"+"^"+id+",|"+","+id+","+"|,"+id+"$"+")";
					dstyle.set(treeNode.domNode, {
						position:'relative'
					})
					resStore.fetch({
						query: {
							attachId: new RegExp(exp)
						},
						onComplete: function(resFound) {
							if(!resFound || resFound.length == 0) {
								if(treeNode.resourceIcon) ddomconstruct.destroy(treeNode.resourceIcon)
								if(treeNode.resourceText) ddomconstruct.destroy(treeNode.resourceText)
								return;
							}

							if(!treeNode.resourceIcon)
								treeNode.resourceIcon = ddomconstruct.create("img", {
									src : require.toUrl("dojo/resources/blank.gif"),
									className : "OoCmSIconAsset OoCmSIconAsset-bin",
									style: 'position: absolute;top:0; left:2px;'
								}, treeNode.rowNode, 'last');
							if(!treeNode.resourceText && resFound.length > 1)
								treeNode.resourceText = ddomconstruct.create("span", {
									innerHTML: resFound.length,
									style: 'font: 14px verdana; position: absolute;top:2px; left:14px;'
								}, treeNode.rowNode, 'last');
							
						}
					})
				})
				
			},
			startup: function startup() {
				traceLog(this,arguments)
				dclass.add(this.domNode, "OoCmSPageTree");
				this.inherited(arguments);
			},
			onToolbarUpDown : function(direction) {
				direction = typeof direction == "string" ? (direction == "up" ? 1 : -1) : direction;
				if(!direction || !this.editItem) 
					return;
				var index, model = this.getPageSelector().model,
				store = model.store,
				childItem = this.editItem,
				parentItem = store.getValue(childItem, "attachId", "");
				parentItem = (parentItem == "" || parentItem == 0 
					? model.root : store._getItemByIdentity(parentItem));
				index = darray.indexOf(parentItem.children, childItem);

				if(index + direction != parentItem.children.length && index + direction >= 0)
					model.pasteItem(childItem, parentItem, parentItem, false, index+direction);
			},
			//			postCreate: function postCreate() {
			//				traceLog(this,arguments)
			//				this.inherited(arguments);
			//			},
			getToolbar : function getToolbar(mixin) {
				traceLog(this,arguments)
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new toolbar(dlang.mixin({
					style:'height: 24px',
					id:'pageTreeToolbar'
				}, mixin));
				//				dojo.place('<span class="label dijitButtonText">Position</span>', tb.domNode, "last");
				// TODO: phase out togglePosition as toolkit functionality
				bt = new button({
					id: 'pageTreeToolbar-up',
					label: "Op",
					showLabel: true,
					onClick: dlang.hitch(this, this.onToolbarUpDown, -1),
					iconClass: "dijitArrowButtonInner"
				});
				dclass.add(bt.domNode, "dijitUpArrowButton")
				tb.addChild(bt);
				bt = new button({
					id: 'pageTreeToolbar-down',
					label: "Ned",
					showLabel: true,
					onClick: dlang.hitch(this, this.onToolbarUpDown, 1),
					iconClass: "dijitArrowButtonInner"
				});
				dclass.add(bt.domNode, "dijitDownArrowButton")
				tb.addChild(bt);
				bt = new button({
					id: 'pageTreeToolbar-attach',
					label: "Tilknyt",
					title: 'Marker en ressource, vælg et dokument som \'mål\' ved alm. klik og Tilknyt vedhæfter derved produkter/ressourcer til valgte dokument',
					showLabel: true,
					onClick: function(){
						ctrl.tree = ctrl._widgetInUse._pageselector;
						ctrl.attachResource(1);
						delete ctrl.tree;
					},
					iconClass: "dijitIconDelete"
				});
				tb.addChild(bt);

				return this._toolbar;
			},
			getSelectorToolbar: function(mixin) {
				if(this._pageselectortoolbar) return this._pageselectortoolbar
				traceLog(this,arguments)
				var tb = this._pageselectortoolbar = new toolbar(dlang.mixin({
					id: 'pageselectorToolbar',
					style:'height:24px;padding-right:5px'
				}, mixin)),
				ps = this.getPageSelector(),
				w = new button({
					id: 'pageselectorToolbar-newresource',
					label: "Opret ressource",
					showLabel: true,
					style: 'float: right',
					iconClass: "OoCmSIconAsset OoCmSIconAsset-bin",
					onClick: function() {
						
						var w = new resourceDialog({
							url: 'save.php?EditResource'
						});
						console.log(w._uri)
						w.startup()
						console.log(w._uri.inputNode)
						w.show();
					}
				})
				tb.addChild(w);
				w = new button({
					id: 'pageselectorToolbar-update',
					iconClass:"dijitIconDatabase",
					showLabel: false,
					title: 'DbFixup - indexér og sorter sideelementer i databasen',
					style: 'float: right',
					onClick: dlang.hitch(this,function() {
						this._pageselector.update()
						this._resourceselector.update()
					})
				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				});
				tb.addChild(w);
				return this._pageselectortoolbar
			},
			getPageSelector: function getPageSelector(mixin) {
				
				if(this._pageselector) return this._pageselector;
				traceLog(this,arguments)
				var queryArgs = {
					attachId: '0', 
					isdraft: '0'
				},
				sortKeys = [{
					attribute:'position'
				}];

				this._pageselector = new oodndtree(dlang.mixin({
					model: new oodndmodel({
						store: new oowritestore({
							url: gPage.baseURI + '/openView/Documents.php?format=json',
							comparatorMap: {
								position: numOrder
							},
							api : {
								del: {
									url: gPage.baseURI + '/admin/save.php?delElement',
									getContents: function(item) {
										return {
											id: item._S.getValue(item, "id"),
											type : item._S.getValue(item, "type"),
											partial:true
										}
									}
								},
								update: {
									url:gPage.baseURI + '/admin/save.php?EditDoc&id={id}',
									getContents: function(item) {
										return {
											id: item._S.getValue(item, "id"),
											attachId: item._S.getValue(item, "attachId"),
											tocpos: item._S.getValue(item, "position"),
											doctitle : item._S.getValue(item, "title"),
											form: (item._S.getValue(item, "attachId") == "0" 
												|| item._S.getValue(item, "isdraft") == "1") ? 'page' : 'subpage',
											partial: true
										}
									}
								}
							}
						}),
						//					query: {
						//						"type": "page"
						//					},
					
						rootId: "root",
						rootLabel: "Dokumenter",
						showRoot: true,
						query: queryArgs,
						sortKeys: sortKeys
					}),
					rootLabel : "Dokumenter",
					id: 'pageselectorTree',
					betweenThreshold: 3
				}, mixin));


				this._pageselector.model.store.fetch({
					query: queryArgs,
					sort: sortKeys
				});
				return this._pageselector;
			},
			getResourceSelector : function getResourceSelector(mixin) {
				if(this._resourceselector) return this._resourceselector;
				traceLog(this,arguments)
				this._resourceselector = new oodndtree(dlang.mixin({
					model: new oodndmodel({
						store: new oowritestore({
							onDeleteItem: function onDeleteItem(item) {
								this.inherited(arguments);
								this.store.save();
							},
							onNewItem: function onNewItem(item, parentInfo) {
								this.inherited(arguments);
								this.store.save();
							},
							url: gPage.baseURI + '/openView/Resources.php?format=json',
							api: {
								create: {
									url: '',
									getContents: function(item) {
										var content = {}
										for(var key in item) 
											if(item.hastOwnProperty(key) && key.indexOf("_") != 0) 
												content[key] = item._S.getValue(item, key);
										return content;
									}
								},
								update: {
									url: gPage.baseURI + '/admin/save.php?EditResource&id={id}',
									getContents: function(item) {
										return {
											partial: true,
											form: 'include',
											attachId: item._S.getValue(item, "attachId")
										}
									}
								},
								del: {
									url: gPage.baseURI + '/admin/save.php?delElement',
									getContents: function(item) {
										return {
											id: item._S.getValue(item, "id"),
											type : 'include',
											partial:true
										}
									}
								}
							}
						}),
				
						rootId: "root",
						rootLabel: "Resourcer",
						showRoot: true
					}),
					rootLabel : "Sidetræ",
					id: 'resourceselectorTree'
				}, mixin));
				//				var w, tb = this._resourceselectortoolbar = new toolbar({
				//					id: 'resourceselectorToolbar',
				//					style:'height:28px;padding-right:5px'
				//				}, "resourceselectortbar");
				//				w = new button({
				//					id: 'resourceselectorToolbar-update',
				//					iconClass:"dijitIconDatabase",
				//					showLabel: false,
				//					title: 'Genindlæs sidetræ',
				//					style: 'float: right;margin-top:2px',
				//					onClick: dlang.hitch(this._resourceselector,this._resourceselector.update)
				//				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				//				});
				//				tb.addChild(w);
				//				
				//				w = new combobox({
				//					id: 'resourceselectorToolbar-selectview',
				//					store: new memorystore( {
				//						data: [{
				//							name:'Resourcefiler', 
				//							id:'R',
				//							url: gPage.baseURI + '/openView/Resources.php?format=json'
				//						},{
				//							name:'Produktkategorier', 
				//							id:'P',
				//							url: gPage.baseURI + '/openView/Products.php?format=json&type=category'
				//						}]
				//					}),
				//					onChange: function(newVal) {
				//						var store = this.store
				//						darray.forEach(store.data, function(item) {
				//							if(item.name == newVal) {
				//								tree.model.store._jsonFileUrl = item.url
				//								tree.update();
				//							}
				//						})
				//						console.log("change:", arguments, this)
				//					},
				//					value: 'Resourcefiler',
				//					searchAttr:'name',
				//					style: 'width:135px;margin:4px;'
				//				});
				//				w.startup();
				//				tb.addChild(w);
				//				this._resourceselectortoolbar.startup();

				this._resourceselector.model.store.fetch();
				return this._resourceselector;
			},
			filterResourceOfId : function(docId) {
				var resStore = this.getResourceSelector().model.store;
				return resStore._arrayOfAllItems.filter(function(item) { 
					var attachments = resStore.getValue(item, "attachId", "");
					return attachments.match(RegExp("^"+docId+"$")) // only
					|| attachments.match(RegExp("^"+docId+",")) // first
					|| attachments.match(RegExp(","+docId+",")) // middle
					|| attachments.match(RegExp(","+docId+"$")) // last
				});
			},
			dblclicktimeout: null,
			describeItem: function describe(item) {
				if(item.root) return "<br/>[Rodelement] - " + item.label;
				var szHtml,
				type=  item._S.getValue(item, "type"),
				title = item._S.getValue(item, "title"),
				desc = item._S.getValue(item, (type=="include"?"comment" : (type=="category"?"description":"alias")));
				if(/page/.test(type)) {
					szHtml = "[Side] - " + title + "<br/>" + "something page"

				} else if(type=="category") {
					szHtml = "[Produktkategori] - " + title + "<br/>" + "something product"
				} else { 
					szHtml = "[Ressourcefil] - ";
					var file = item._S.getValue(item, "alias", "Intet alias");
					szHtml += file + "<br/>"; 
					file = item._S.getValue(item, "uri");
					file = file.substring(file.lastIndexOf('/')+1)
					file = ctrl.getFiletypeDescription(file);
					szHtml += "Filtype: " + (file ? file : item._S.getValue(item, "relation"));
				}
				if(item.type == "page") {
					szHtml += "<div style=\"position:absolute;top: 0; right: 0;\" id=\"edititem-attachmentlist\"></div>"
				}
				szHtml +=  "<br>Beskrivelse: " + desc + "<br/>";
				return "<br/>"+szHtml
			},
			_editNodeWidgets : [],
			describeAttachments : function describeAttachments(docId) {
				if(!docId || isNaN(parseInt(docId))) 
					console.warn("Warning, matching all resources, please supply docId");
				traceLog(this,arguments)
				var self = this,
				resStore = this.getResourceSelector().model.store,
				containerNode = ddom.byId('edititem-attachmentlist'),
				matches = this.filterResourceOfId(docId);
				darray.forEach(this._editNodeWidgets, function(w) {
					w.popup.destroy()
					w.remove.destroy()
					w.destroy()
				});
				
				var btn, resid, clsPostfix, comment, alias, label, szHtml, i
				for(i = 0; i < matches.length; i++) {
					resid = resStore.getValue(matches[i], "id");
					clsPostfix = resStore.getValue(matches[i], "relation") == "text/javascript" ? "js" : "css";
					comment = resStore.getValue(matches[i], "comment", "");
					alias = resStore.getValue(matches[i], "alias", "");
					label = comment != "" ? comment : 
					(alias != "" ? alias : resStore.getValue(matches[i], "title"));
					szHtml = OoCmS._treebase.prototype.getTooltipContents(matches[i]);
					
					btn = new button({
						id: 'resourceDescriptionButton-'+resid,
						iconClass: 'OoCmSIconAsset OoCmSIconAsset-'+clsPostfix,
						label: label,
						poppedOpen: false,
						popup: (new ttipdialog({
							id: 'resourceDescriptionPopup-'+resid,
							content: '<div style="">'+ szHtml + '</div>'
						})),
						onClick: function() {
							if(this.poppedOpen)
								dpopup.close(this.popup)
							else
								dpopup.open({
									popup:this.popup, 
									around:this.domNode,
									orient: ['below', 'below-alt']
								});
							this.poppedOpen = !this.poppedOpen
						}
					});
					btn.startup();
					// put label below icon, give ability to container abs pos
					dstyle.set(btn.containerNode, {
						display:'block', 
						position: 'relative'
					})
					btn.remove = new button({
						title: 'Frigør ressource',
						iconClass: 'dijitIconDelete',
						id: 'resourceDescriptionButtonRemove-'+resStore.getValue(matches[i], "id"),
						resItem: matches[i],
						docId: docId,
						resButton : btn,
						onClick: function() {
							var attachments = this.resItem._S.getValue(
								this.resItem, "attachId");
							attachments = darray.filter(attachments.split(","),
								function(id) {
									return id != this.docId
								}, this);
										
							this.resItem._S.setValue(
								this.resItem, "attachId", attachments.join(","));
							this.resItem._S.save();
							
							self._editNodeWidgets.splice(darray.indexOf(
								self._editNodeWidgets, this.resButton), 1);
							this.resButton.popup.destroyRecursive();
							this.resButton.destroyRecursive();
							delete this.resItem
							delete this.resButton
							this.destroy();
						}
					});
					containerNode.appendChild(btn.domNode);
					containerNode.appendChild(btn.remove.domNode);
					dclass.add(btn.remove.domNode, "OoCmSButtonAssetRemove");
					dclass.add(btn.domNode, "OoCmSButtonAsset");
					//					btn.domNode.appendChild(remove.domNode)
					this._editNodeWidgets.push(btn);
				}
			},
			onItemStateFocus: function onItemStateFocus(item, treeNode, evt) { // click any
				// if tree.id == pageselectorTree we must nest as we can expect a doubleclick
				// dblclick will cancel the timeout if it occurs
				
				if(this.dblclicktimeout)
					return;
				else if(treeNode.tree.id == "pageselectorTree")
					this.dblclicktimeout = setTimeout(dlang.hitch(this, this._onItemStateFocus,item, treeNode), 500);
				else
					this._onItemStateFocus(item, treeNode);
				

			},
			_onItemStateFocus : function(item, treeNode) {
				traceLog(this,arguments)
				this.dblclicktimeout = null;
				if(!item.root && (!item || !item._S)) return;
				ddom.byId('focusitem-text').innerHTML = this.describeItem(item);
				this.focusItem = item;
			},

			onItemStateEdit: function onItemStateEdit(item, treeRow) { // dblclick page
				traceLog(this,arguments)
				if(this.dblclicktimeout) clearTimeout(this.dblclicktimeout);
				this.dblclicktimeout = null;
				if(!item || !item._S) return;
				var id = item._S.getValue(item, "id"), w,
				type = item._S.getValue(item, "type");
				if(!id || id == "" || id > 9990) return;
				ddom.byId('edititem-text').innerHTML = this.describeItem(item);
				if(this._resourcedescription) 
					while((w = this._resourcedescription.shift())) {
						w.popup.destroy();
						w.destroyRecursive();
					}
				if(type == 'page') this.describeAttachments(id)
				this.editItem = item;
				var bounce;
				if(this.editTreeRow) {
					bounce = this.editTreeRow.lastChild;
					this.editTreeRow = treeRow.rowNode;
					ddomconstruct.place(bounce, this.editTreeRow, 'last');
				} else {
					this.editTreeRow = treeRow.rowNode
					bounce = ddomconstruct.create("img", {
						src : require.toUrl("dojo/resources/blank.gif"),
						className : "OoIcon-18 OoIconEditFocus",
						style: 'position: absolute;top:2px;'
					}, this.editTreeRow, 'last');
					bounce.bounce = dfx.animateProperty({
						node: bounce,
						properties: {
							right: {
								start:0,
								end:17, 
								unit: 'px'
							}
						},
						duration: 500,
						onEnd: function() {
							dfx.animateProperty({
								node: bounce,
								properties: {
									right: {
										start:17,
										end:10, 
										unit: 'px'
									}
								},
								duration: 700,
								easing: dfxeasing.elasticInOut
							}).play()	
						},
						easing: dfxeasing.elasticInOut
					});
				}
				dstyle.set(this.editTreeRow, {
					position:'relative'
				})
				bounce.bounce.play();
			}/*, 
			layout: function layout() {
				traceLog(this,arguments)
				//									left : dojo.byId('pageleftcolumn'),
				//					middle : dojo.byId('pagecentercolumn'),
				//					right : dojo.byId('pagerightcolumn'),
				//					outer : dojo.byId('pageformwrapper')
				//				};
				//				dstyle.set(this._pageframe.container, {
				//					width: (
				//						ddomgeometry.getMarginBox(this._pageframe.outer).w
				//						- ddomgeometry.getMarginBox(this._pageframe.left).w
				//						- ddomgeometry.getMarginExtents(this._pageframe.left).w
				//						- ddomgeometry.getMarginExtents(this._pageframe.container).w) + "px"
				//				});
				var c1 = dojo.coords(this._pageframe.outer),
				c2 = dojo.coords(this._pageframe.left),
				headerHeight = 32 // 18 + 4 + 4;
				c1.h -= headerHeight;
				dojo.style(this._pageframe.middle, { 
					//					width: (c1.w-(c2.w)-10) + "px",
					height: (c1.h) + "px"
				//					height: (c1.h < 580 ? 580 : c1.h) + "px"
				});
				dojo.style(this._pageframe.left, { 
					////					height: (c1.h < 580 ? 580 : c1.h) + "px"
					height: (c1.h) + "px"
				});
				dojo.style(this._pageframe.right, { 
					////					height: (c1.h < 580 ? 580 : c1.h) + "px"
					height: (c1.h) + "px"
				});
				dojo.style(dojo.query("div table:firstchild", this._pageframe.middle)[0].parentNode, {
					height: (c1.h-dojo.getMarginBox(this._toolbar.domNode.parentNode).h) + "px"
				})
			//				}catch(squelz) { }
			}*/
		});
		return PageTree;
	});
console.log("eval pagetree.js")
