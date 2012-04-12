define(["dojo/_base/declare",
	"OoCmS/AbstractController",
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
	"dojo/dnd/common",
	//	"dojo/date/locale",
	"dojo/dnd/Source",
	"dijit/tree/dndSource",
	//	"dijit/Editor",
	"dijit/_base/popup",
	"dijit/ProgressBar",
	"dijit/Toolbar",
	"dijit/form/Button",
	"dijit/TooltipDialog",
	"dijit/form/ComboBox",
	"dojo/data/ItemFileWriteStore",
	"dojo/store/Memory",
	"dijit/tree/ForestStoreModel",
	"dojo/fx/easing",
	"OoCmS/_treebase"
	], function(declare,abstractcontroller,dlang, darray, dconnect, dxhr, dfx, ddom,
		dstyle, dclass, ddomgeometry, ddomconstruct, registry, dtopic, dndCommon,
		dndSource, treedndSource, dpopup, progressbar, toolbar, button, ttipdialog,
		combobox, writestore, memorystore, forestmodel, dfxeasing){
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
		var dndModel = declare("OoCmS._dndmodel", [forestmodel], {
			_requeryTop: function _requeryTop(){
				// reruns the query for the children of the root node,
				// sending out an onSet notification if those children have changed
				var oldChildren = this.root.children || [];
				this.store.fetch({
					query: this.query,
					sort: this.sortKeys,
					onComplete: dlang.hitch(this, function(newChildren){
						this.root.children = newChildren;

						// If the list of children or the order of children has changed...
						if(oldChildren.length != newChildren.length ||
							darray.some(oldChildren, function(item, idx){
								return newChildren[idx] != item;
							})){
							this.onChildrenChange(this.root, newChildren);
						}
					})
				});
			},
			orderBy: function orderBy(array, property, numerical) {
				return array.sort(function(a,b) {
					if(numerical) {
						return numOrder(a._S.getValue(a,property),b._S.getValue(b,property));
					}else{
						return stringOrder(a._S.getValue(a,property),b._S.getValue(b,property));
					}
				});				
			},
			reIndex: function reIndex(item) {
				if(!item || !item.children) return;
				for(var i = 0; i < item.children.length; i++) {
					this.setPos(item.children[i], (i+1)*100);
				//if(item.children[i].children) this.reIndex(item.children[i]);
				}			
			},
			setPos: function setPos(item,value) {
				if(this.store.getValue(item, "position") != value)
					this.store.setValue(item, "position", value);
			},
			pasteItem: function pasteItem(childItem, oldParentItem, newParentItem, bCopy, insertIndex) {
				console.info(traceLog(this,arguments));
				insertIndex = (typeof insertIndex == "number" && ! isNaN(insertIndex) ? insertIndex : (
					(newParentItem && newParentItem.children) ? newParentItem.children.length : 0));
				this.setPos(childItem,  insertIndex * 100 + 100);
				// verify wheter to batch or save store on completion
				if(!this.batchInProgress) {
					this.batchCount = 0;
					for(var i in this.mDragController.selection) 
						if(this.mDragController.selection.hasOwnProperty(i)) 
							this.batchCount++;
					if(this.batchCount > 1) {
						this.batchInProgress = true;
						this.batchCompletions = 0;
					}
				}
				// if we're chaging attachment on childItem, effectuate
				if(oldParentItem != newParentItem) {
					this.store.setValue(childItem, 'attachId', newParentItem == this.root ? "0" : this.store.getValue(newParentItem, 'id'));
				}
				if (oldParentItem == this.root && newParentItem == this.root) {
					if (!bCopy) {
						this.onLeaveRoot(childItem);
					}
					//					for(var i in this.root.children) console.log(i, this.root.children[i].title[0]);

					this.onAddToRoot(childItem);
					// manipulate this.root.children to reorder childItem
					// remove child from the current position
					var children = darray.filter(this.root.children, function(x) {
						return x != childItem;
					});
					// and insert it into the new index
					children.splice(insertIndex, 0, childItem);
					this.root.children = children;

					// notify views
					this.onChildrenChange(this.root, children);
					this.reIndex(this.root);
					this._requeryTop();
				} else {
					// call super
					this.inherited(arguments);
					this.reIndex(oldParentItem);
					if(oldParentItem != newParentItem) this.reIndex(newParentItem);
				}

				// only save if last batch has completed (all selected nodes processed in pasteItem)
				if(this.batchInProgress) {

					this.batchCompletions++;
					if(this.batchCount == this.batchCompletions) {
						delete this.batchCompletions;
						this.batchInProgress = false;
						
						this.store.save();
					}
					
				} else {
					this.store.save();
				}
			},
		
			onAddToRoot: function onAddToRoot(/*item*/ item){
				console.info(traceLog(this,arguments));
				//				var ar = this.store._arrayOfTopLevelItems, i = 0;
				//				for(var idx in ar) {
				//					if(ar.hasOwnProperty(idx)){
				//						if(ar[i].isdraft[0] == "1" || ar[i].position[0] == item.position[0])
				//							break;
				//					}
				//					i++;
				//				}
				//				this.store._arrayOfTopLevelItems.splice(i+1, 0, item);
				this.store._arrayOfTopLevelItems.push(item);
				item[this.store._rootItemPropName]=true;
				this.store.setValue(item, "type", "page");
			},
			onLeaveRoot: function onLeaveRoot(/*item*/ item){
				console.info(traceLog(this,arguments));
				// manipulate store toplevel properties, add item and sort
				this.store._removeArrayElement(this.store._arrayOfTopLevelItems, item);
				delete item[this.store._rootItemPropName];
				this.store.setValue(item, "type", "subpage");
			},
			_onNewItem : function _onNewItem() {
				console.log(traceLog(this, arguments));
				this.store.save()

			},
			_onDeleteItem : function _onDeleteItem() { /* allways sync store */
				console.log(traceLog(this, arguments));
				this.store.save()
			},
			onNewRootItem: function onNewRootItem() { /* allways sync store */
				console.log(traceLog(this, arguments));
				this.store.save()

			},
			ignoreSetter: false,
			onNewItem: function onNewItem(item, parentInfo) { /* allways sync store */
				console.log(traceLog(this, arguments));
				var sourceItem = /_TreeNode_/.test(item.id[0]) ? registry.byId(item.id[0]).item : item,
				srcstore = sourceItem._S,
				type = sourceItem._S.getValue(sourceItem, "type"),
				comment = sourceItem._S.getValue(sourceItem, "comment", ""),
				id = this.store.getValue(parentInfo.item, "id");
				
				if(item === sourceItem) {
					console.error("Widget ID ["+item.id+"] of dropped item cannot be found");
					return;
				}
				var oAttach = srcstore.getValue(sourceItem, "attachId", "").split(",");
				// we will now make change to Resources-store and submit them
				for(var i = 0; i < oAttach.length; i++) {
					if(oAttach[i] == id) {
						this.ignoreSetter = false;
						return;
					}
				}
				oAttach.push(id);
				oAttach.sort(function(a,b) {
					return parseInt(a) > parseInt(b);
				})
				srcstore.setValue(sourceItem, "attachId", oAttach.join(","));
				srcstore.save();
				this.store.revert();
			//this.inherited(arguments);

			},
			onSetItem : function onSetItem(/* item */ item, attribute, oldValue, newValue) {
				console.log(traceLog(this, arguments));
				// we implement this catch to avoid sending a submit for a node changing its children
				// when an element receives new / more children, the element itself has nothing to change
				// only new / changed child-elements must set attachId
				if(attribute == "children" || this.ignoreSetter) {
					delete this.store._pending._modifiedItems[this.store.getValue(item, "id")]
				} else 
					console.log(item.title+'', attribute + ' changed from ' + oldValue + " to " + newValue)
				this.inherited(arguments);
			}
		});
				
		var PageSelectTree, _dndtree = PageSelectTree = 
		declare("OoCmS._dndtree", [OoCmS._treebase],{
			observers: [],
			loaded: false,
			initialized: false,
			dndController: treedndSource,
			observers : [],
			constructor: function constructor(args) {
				window.docTree = this;
				if(args.dndController) this.dndController = args.dndController;
				if(dojo.isString(this.dndController)){
					this.dndController = lang.getObject(this.dndController);
				}
				console.info(traceLog(this,arguments));
			},
			__postCreate : function __postCreate() {
				this.inherited(arguments);
				console.info(traceLog(this,arguments));
				this.model.mDragController = this.dndController;
				if(typeof this.menu == "string")
				{
					switch(this.menu) {
						case "OoCmS.RelationsMenu":
							this.menu = new OoCmS.RelationsMenu({
								tree : this,
								toolbox: this.toolbox
							});
							break;
						case "OoCmS.ResourcesMenu":
							this.menu = new OoCmS.ResourcesMenu({
								tree : this,
								toolbox: this.toolbox
							});
							break;
						case "OoCmS.FileAdminMenu":
							this.menu = new OoCmS.FileAdminMenu({
								tree : this,
								toolbox: this.toolbox
							});
					}
				}
			},
			checkAcceptance : function(sourceTree, nodes)
			{
				return true;
			},
			checkItemAcceptance:function (targetDomNode, from_dndSource)
			{
				try {
					var rowNode = dijit.getEnclosingWidget(targetDomNode);
				} catch(err) {
					console.log(err, "cannot accept drag on unknown widget")
				}
				// assert that we're targeting a valid treenode
				if(!rowNode || !rowNode.isTreeNode ) return false;
				var to_dndSource = this,
				toTree = to_dndSource.tree,
				fromTree = from_dndSource.tree;
				console.log('to', rowNode ? rowNode : targetDomNode, 'on', toTree.id, 'from', from_dndSource.selection, 'in', fromTree.id)
				if(toTree.id == "resourceselectorTree") {
					// resourcetree will not accept any drops at all
					return false;
				} else if(fromTree.id == "resourceselectorTree") {
					// pr definition; subpages cannot relate to resources, a page and 
					// all its contained sub's however, will 'inherit' the resource
					if(rowNode.item.root || (rowNode.item.type && rowNode.item.type[0] == "subpage")) return false;
					// we need to check if allready attached
					for(var i in from_dndSource.selection)
						if(new RegExp("(^{ID}$|^{ID},|,{ID},|,{ID}$)".replace(/\{ID\}/g, rowNode.item.id)).test(from_dndSource.selection[i].item.attachId[0]))
							return false;
				} else if(fromTree.id == "pageselectorTree") {
					// 
					if(toTree.id == "resourceselectorTree") return false
				}
				// fixme function reference
				ctrl._widgetInUse.onItemStateEdit(rowNode.item, rowNode)
				return true;
			}
		})
		var ResourceSelectTree = declare("OoCmS._restree", [_dndtree],{
			observers: [],
			loaded: false,
			initialized: false,
			constructor: function constructor(args) {
				window.resTree = this;
				console.info(traceLog(this,arguments));

			}
		});
		var PageSelectStore = declare("OoCmS._dndtreeStore", [writestore], {
			comparatorMap: {
				position: numOrder
			},
			constructor: function(args) {
				console.info(traceLog(this,arguments));
				this.clearOnClose=	true;
				this.url=				gPage.baseURI + '/openView/Documents.php?format=json';
				this.urlPreventCache=true;
				if(args) dlang.mixin(this, args);
				
			},
			getSubmitValues : function(item) {
				return {
					id: item._S.getValue(item, "id"),
					attachId: item._S.getValue(item, "attachId"),
					tocpos: item._S.getValue(item, "position"),
					doctitle : item._S.getValue(item, "title"),
					form: (item._S.getValue(item, "attachId") == "0" || item._S.getValue(item, "isdraft") == "1") ? 'page' : 'subpage',
					partial: true
				}
			},
			_saveCustom: function(saveCompleteCallback,saveFailedCallback) {
				console.info(traceLog(this,arguments));
				var item, i 
				batches = 0, progress = 0;
				// count pending requests
				for(i in this._pending._deletedItems)
					if(this._pending._deletedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._modifiedItems)
					if(this._pending._modifiedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._newItems)
					if(this._pending._newItems.hasOwnProperty(i)) batches++;
				
				for(i in this._pending._deletedItems) {
					if(this._pending._deletedItems.hasOwnProperty(i)) {
						item = this._getItemByIdentity(i);
						dxhr.post({
							url:gPage.baseURI + '/admin/save.php?delElement',
							content:  {
								id: this.getValue(item, "id"),
								type : this.getValue(item, "type"),
								partial:true
							},
							load: function(res) {
								dtopic.publish("notify/progress/" + 
									(batches == ++progress ?"done":"loading"),{
										maximum:batches, 
										progress:progress
									});
								if(! /DELETED/.test(res)) {
									dtopic.publish("notify/delete/error", "Fejl!", res, [ {
										id:'canceloption',
										classes:'dijitEditorIcon dijitEditorIconUndo'
									}]);
								}
							} // load
						});
					}
				};
				for(i in this._pending._modifiedItems) {
					if(this._pending._modifiedItems.hasOwnProperty(i)) {
						item = this._getItemByIdentity(i);
						dxhr.post({
							content: this.getSubmitValues(item),
							url:gPage.baseURI + '/admin/save.php?EditDoc&id='+i,
							load : function(res) {
								dtopic.publish("notify/progress/" + 
									(batches == ++progress ?"done":"loading"),{
										maximum:batches, 
										progress:progress
									});
									

								if(res.indexOf("SAVED") == -1) {
									dtopic.publish("notify/save/error", "Fejl!", res, [ {
										id:'canceloption',
										classes:'dijitEditorIcon dijitEditorIconUndo'
									}
									]);
									return;
								}
							}
						});
					}
				};
				for(i in this._pending._newItems) {
					if(this._pending._newItems.hasOwnProperty(i))
						console.log('newItem WTF?', this._pending._newItems[i]);
				};
				saveCompleteCallback();

			}
		});
		var ResourceSelectStore = declare("OoCmS._restreeStore", [writestore], {
			constructor: function(args) {
				console.info(traceLog(this,arguments));
				this.clearOnClose=	true;
				this.url=				gPage.baseURI + '/openView/Resources.php?format=json';
				this.urlPreventCache=true;
				if(args) dlang.mixin(this, args);
			},
			_saveCustom: function(saveCompleteCallback,saveFailedCallback) {
				console.info(traceLog(this,arguments));
				var item, i, batches = 0, progress = 0;
				// count pending requests
				for(i in this._pending._deletedItems)
					if(this._pending._deletedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._modifiedItems)
					if(this._pending._modifiedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._newItems)
					if(this._pending._newItems.hasOwnProperty(i)) batches++;
				
				for(i in this._pending._deletedItems) {
					if(this._pending._deletedItems.hasOwnProperty(i)) {
						item = this._getItemByIdentity(i);
						dxhr.post({
							url:gPage.baseURI + '/admin/save.php?delElement',
							content:  {
								id: this.getValue(item, "id"),
								type : 'include',
								partial:true
							},
							load: function(res) {
								dtopic.publish("notify/progress/" + 
									(batches == ++progress ?"done":"loading"),{
										maximum:batches, 
										progress:progress
									});
								if(! /DELETED/.test(res)) {
									dtopic.publish("notify/delete/error", "Fejl!", res, [ {
										id:'canceloption',
										classes:'dijitEditorIcon dijitEditorIconUndo'
									}]);
								}
							} // load
						});
					}
				};
				for(i in this._pending._modifiedItems) {
					if(this._pending._modifiedItems.hasOwnProperty(i)) {
						item = this._getItemByIdentity(i);
						dxhr.post({
							content: {
								partial: true,
								form: 'include',
								attachId: item._S.getValue(item, "attachId")
							},
							url:gPage.baseURI + '/admin/save.php?EditResource&id='+i,
							load : function(res) {
								dtopic.publish("notify/progress/" + 
									(batches == ++progress ?"done":"loading"),{
										maximum:batches, 
										progress:progress
									});
								if(res.indexOf("SAVED") == -1) {
									dtopic.publish("notify/save/error", "Fejl!", res, [ {
										id:'canceloption',
										classes:'dijitEditorIcon dijitEditorIconUndo'
									}
									]);
									return;
								}
							}
						});
					}
				};
				for(i in this._pending._newItems) {
					if(this._pending._newItems.hasOwnProperty(i)) {
						var content = {}
						for(var key in item) 
							if(item.hastOwnProperty(key) && key.indexOf("_") != 0) 
								content[key] = item._S.getValue(item, key);
					}
					dxhr.post({
						content: content,
						url:gPage.baseURI + '/admin/save.php?EditDoc',
						load : function(res) {
							if(res.indexOf("SAVED") == -1) {
								dtopic.publish("notify/save/error", "Fejl!", res, [ {
									id:'saveoption',
									cb:function() {
										alert('Understøttes desværre ikke');
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
						}
					});
				};
				saveCompleteCallback();

			},
			onDeleteItem: function onDeleteItem(item) {
				this.inherited(arguments);
				this.store.save();
			},
			onNewItem: function onNewItem(item, parentInfo) {
				this.inherited(arguments);
				this.store.save();
			}
		});



		
		var PageTree = declare("OoCmS.pagetree", [abstractcontroller], {
			constructor: function constructor(args) {
				window.pagetree = this;
				console.info(traceLog(this,arguments));
				this.dijitrdyId = "resourceselectorTree";
			},
			isDirty: function isDirty() {
				console.info(traceLog(this,arguments));
				return false;
			},
			unload: function unload() {
				console.info(traceLog(this,arguments));
				
				darray.forEach(this.modelObservers, dconnect.disconnect)
				darray.forEach(this.observers, dconnect.disconnect)
				darray.forEach(this._resourcedescription, function(w){
					w.popup.destroyRecursive();
					w.destroyRecursive();
				});
				this._toolbar.destroyRecursive();
				this._pageselector.model.store.close()
				this._pageselector.destroyRecursive();
				this._resourceselector.model.store.close();
				this._resourceselector.destroyRecursive();
				this._toolbar.destroyRecursive();
				this._pageselectortoolbar.destroyRecursive();
				this._resourceselectortoolbar.destroyRecursive();

			},
			ready: function ready(funcObject /*(self)*/) {
				var self = this,
				test = self._resourceselector;
				if(test) {
					console.info(traceLog(this,arguments));
					if(typeof funcObject == "function") {

						funcObject(self);
					}
					return;
				}
				var _interval = setInterval(function() {
					test = registry.byId('resourceselectorTree');
					if (eval(test)) { //does the object exist?
						clearInterval(_interval);
						console.info(traceLog(this,arguments));
						if(funcObject) {
							funcObject(self);
						}
					}
				}, 150);
			},
			bindDom: function bindDom(node) {
				console.info(traceLog(this,arguments));
				this.attachTo = node;
			},
			startup: function startup() {
				console.info(traceLog(this,arguments));
				ddom.byId('pagetoolbarWrapper').appendChild(this.getToolbar().domNode);
				this.getPageSelector()./*placeAt('pageselector').*/startup();
				this.getResourceSelector()./*placeAt('resourceselector').*/startup();
				this.inherited(arguments);
								
			},
			//			postCreate: function postCreate() {
			//				console.info(traceLog(this,arguments));
			//				this.inherited(arguments);
			//			},
			getToolbar : function getToolbar() {
				console.info(traceLog(this,arguments));
				if(this._toolbar) return this._toolbar
				var bt, tb = this._toolbar = new toolbar({id:'pageTreeToolbar'}, "pagetoolbar");
				//				dojo.place('<span class="label dijitButtonText">Position</span>', tb.domNode, "last");
				// TODO: phase out togglePosition as toolkit functionality
				bt = new button({
					id: 'pageTreeToolbar-up',
					label: "Op",
					showLabel: true,
					onClick: dlang.hitch(this, function(){
						ctrl.tree = ctrl._widgetInUse._pageselector;
						ctrl.togglePosition(this.editItem, -1);
					}),
					iconClass: "dijitArrowButtonInner"
				});
				dclass.add(bt.domNode, "dijitUpArrowButton")
				tb.addChild(bt);
				bt = new button({
					id: 'pageTreeToolbar-down',
					label: "Ned",
					showLabel: true,
					onClick: dlang.hitch(this, function(){
						//						ctrl.tree = ctrl._widgetInUse._pageselector;
						// TODO; implement model 'onChange' + store 'sync
						ctrl.togglePosition(this.editItem, 1);
					}),
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
			getPageSelector: function getPageSelector() {
				console.info(traceLog(this,arguments));
				if(this._pageselector) return this._pageselector;
				
				var w, h = (this.getToolbar() ? ddomgeometry.getMarginBox(this._toolbar.domNode).h - 5: 23),
				queryArgs = {
					attachId: '0', 
					isdraft: '0'
				},
				sortKeys = [{
					attribute:'position'
				}];
				var tb = this._pageselectortoolbar = new toolbar({
					id: 'pageselectorToolbar',
					style:'height:'+h+'px;padding-right:5px'
				}, "pageselectortbar");

				this._pageselector = new PageSelectTree({
					model: new dndModel({
						store: new PageSelectStore({}),
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
					dndController: treedndSource,
					betweenThreshold: 3
				}, 'pageselector');
				w = new button({
					id: 'pageselectorToolbar-newresource',
					label: "Opret ressource",
					showLabel: true,
					style: 'float: right',
					iconClass: "OoCmSIconAsset OoCmSIconAsset-bin",
					onClick: function() {
						alert('todo')
					}
				})
				tb.addChild(w);
				w = new button({
					id: 'pageselectorToolbar-update',
					iconClass:"dijitIconDatabase",
					showLabel: false,
					title: 'DbFixup - indexér og sorter sideelementer i databasen',
					style: 'float: right',
					onClick: dlang.hitch(this._pageselector,this._pageselector.update)
				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				});
				tb.addChild(w);

				this._pageselector.model.store.fetch({
					query: queryArgs,
					sort: sortKeys
				});
				//				this.observers.push(dojo.connect(this.getPageSelector(), "onClick", this, this.onItemStateFocus));
				this.observers.push(dconnect.connect(this.getResourceSelector(), "onClick", this, this.onItemStateFocus));
				this.observers.push(dconnect.connect(this.getPageSelector(), "onDblClick", this, this.onItemStateEdit));
				this._pageselectortoolbar.startup()
				return this._pageselector;
			},
			getResourceSelector : function getResourceSelector() {
				console.info(traceLog(this,arguments));
				if(this._resourceselector) return this._resourceselector;
				var tree = this._resourceselector = new ResourceSelectTree({
					model: new dndModel({
						store: new ResourceSelectStore({}),
						//					query: {
						//						"type": "page"
						//					},
					
						rootId: "root",
						rootLabel: "Resourcer",
						showRoot: true
					}),
					rootLabel : "Sidetræ",
					id: 'resourceselectorTree'
				}, 'resourceselector');
				var w, tb = this._resourceselectortoolbar = new toolbar({
					id: 'resourceselectorToolbar',
					style:'height:28px;padding-right:5px'
				}, "resourceselectortbar");
				w = new button({
					id: 'resourceselectorToolbar-update',
					iconClass:"dijitIconDatabase",
					showLabel: false,
					title: 'Genindlæs sidetræ',
					style: 'float: right;margin-top:2px',
					onClick: dlang.hitch(this._resourceselector,this._resourceselector.update)
				//   iconClass: "dijitEditorIcon dijitEditorIcon"+label
				});
				tb.addChild(w);
				
				w = new combobox({
					id: 'resourceselectorToolbar-selectview',
					store: new memorystore( {
						data: [{
							name:'Resourcefiler', 
							id:'R',
							url: gPage.baseURI + '/openView/Resources.php?format=json'
						},{
							name:'Produktkategorier', 
							id:'P',
							url: gPage.baseURI + '/openView/Products.php?format=json&type=category'
						}]
					}),
					onChange: function(newVal) {
						var store = this.store
						darray.forEach(store.data, function(item) {
							if(item.name == newVal) {
								tree.model.store._jsonFileUrl = item.url
								tree.update();
							}
						})
						console.log("change:", arguments, this)
					},
					value: 'Resourcefiler',
					searchAttr:'name',
					style: 'width:135px;margin:4px;'
				});
				w.startup();
				tb.addChild(w);
				this._resourceselector.model.store.fetch();
				this._resourceselector.onClick = dlang.hitch(this, this.onItemStateFocus);
				this._resourceselectortoolbar.startup();
				return this._resourceselector;
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
			editNodeWidgets : [],
			describeAttachments : function(docId) {
				if(!docId || isNaN(parseInt(docId))) 
					console.warn("Warning, matching all resources, please supply docId");
				console.info(traceLog(this, arguments));
				var self = this,
				resStore = registry.byId('resourceselectorTree').model.store,
				containerNode = ddom.byId('edititem-attachmentlist');
				this._resourcedescription = this._resourcedescription || [];
				darray.forEach(this.editNodeWidgets, function(w) {
					w.destroy()
				});
		
				resStore.fetch({
					query: {
						attachId: new RegExp("("+
							"^"+docId+"$|"+
							"^"+docId+",|"+
							","+docId+","+
							","+docId+"$|"+
							")")
					},
					
					onComplete: function(matches) {
						var dia, btn;
						for(var i in matches) if(matches.hasOwnProperty(i)) {
					
							self._resourcedescription.push(btn = new button({
								id: 'resourceDescriptionButton-'+i.id[0],
								iconClass: 'OoCmSIconAsset OoCmSIconAsset-'+(matches[i].relation[0] == "text/javascript" ? "js" : "css"),
								label: matches[i].comment != "" ? matches[i].comment : (matches[i].alias != "" ? matches[i].alias : matches[i].title),

								poppedOpen: false,
								popup: (dia = new ttipdialog({
									id: 'resourceDescriptionPopup-'+i.id[0],
									content: '<div style="">'+ OoCmS._treebase.prototype.getTooltipContents(matches[i])+'</div>'
								})),
								onClick: dlang.hitch(btn, function() {
									if(this.poppedOpen)
										dpopup.close(this.popup)
									else
										dpopup.open({
											popup:this.popup, 
											around:this.domNode,
											orient: ['below', 'below-alt']
										});
									this.poppedOpen = !this.poppedOpen
								})
							}));
							btn.startup();
							btn.containerNode.style.display = "block"; // put label below icon
							containerNode.appendChild(btn.domNode)
						}
					}
				})
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
				console.info(traceLog(this,arguments));
				this.dblclicktimeout = null;
				if(!item.root && (!item || !item._S)) return;
				console.log('onClick', item, treeNode.tree.id);
				ddom.byId('focusitem-text').innerHTML = this.describeItem(item);
				this.focusItem = item;
			},

			onItemStateEdit: function onItemStateEdit(item, treeRow) { // dblclick page
				console.info(traceLog(this,arguments));
				if(this.dblclicktimeout) clearTimeout(this.dblclicktimeout);
				this.dblclicktimeout = null;
				if(!item || !item._S) return;
				var id = item._S.getValue(item, "id"),
				type = item._S.getValue(item, "type");
				if(!id || id == "" || id > 9990) return;
				console.log('pageselector onDblClick', item)
				ddom.byId('edititem-text').innerHTML = this.describeItem(item);
				darray.forEach(this._resourcedescription, function(w) {
					w.popup.destroy();
					w.destroyRecursive();
				})
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
						src : dojo.baseUrl+"resources/blank.gif",
						className : "OoIcon-18 OoIconEditFocus",
						style: 'position: absolute;top:2px;'
					}, this.editTreeRow, 'last');
					bounce.bounce = dfx.animateProperty({
						node: bounce,
						properties: {
							right: {
								start:0,
								end:22, 
								unit: 'px'
							}
						},
						duration: 500,
						onEnd: function() {
							dfx.animateProperty({
								node: bounce,
								properties: {
									right: {
										start:22,
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
				console.info(traceLog(this,arguments));
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
