define(["dojo/_base/declare",
	"dojo/_base/lang",
	"dojo/_base/array",
	"dijit/registry",
	"dijit/tree/ForestStoreModel"
], function(declare, dlang, darray, registry, djforestmodel) {
	return declare("OoCmS._dndmodel", [djforestmodel], {
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
			traceLog(this,arguments)
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
			traceLog(this,arguments)
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
			traceLog(this,arguments)
			// manipulate store toplevel properties, add item and sort
			this.store._removeArrayElement(this.store._arrayOfTopLevelItems, item);
			delete item[this.store._rootItemPropName];
			this.store.setValue(item, "type", "subpage");
		},
		_onNewItem : function _onNewItem() {
			traceLog(this,arguments)
			this.store.save()

		},
		_onDeleteItem : function _onDeleteItem() { /* allways sync store */
			traceLog(this,arguments)
			this.store.save()
		},
		onNewRootItem: function onNewRootItem() { /* allways sync store */
			traceLog(this,arguments)
			this.store.save()

		},
		ignoreSetter: false,
		onNewItem: function onNewItem(item, parentInfo) { /* allways sync store */
			traceLog(this,arguments)
			var sourceItem = /_TreeNode_/.test(item.id[0]) ? registry.byId(item.id[0]).item : item,
			srcstore = sourceItem._S,
			type = sourceItem._S.getValue(sourceItem, "type"),
			comment = sourceItem._S.getValue(sourceItem, "comment", ""),
			id = this.store.getValue(parentInfo.item, "id");
				
			if(item === sourceItem) {
				console.error("Widget ID ["+item.id+"] of dropped item cannot be found");
				return;
			}
			var oAttach = srcstore.getValue(sourceItem, "attachId", "");
			oAttach = oAttach == "" ? [] : oAttach.split(",")
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
			traceLog(this,arguments)
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
})