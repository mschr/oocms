define("OoCmS/xbugfix/RollingList", [
	"dijit","dojo","dojox",
	"OoCmS/xbugfix/_RollingListPane",
	"OoCmS/xbugfix/_RollingListGroupPane",
	"dojo/i18n!dijit/nls/common",
	"dojo/text!./RollingList/RollingList.html",
	"dijit/_Templated",
	"dijit/_Container",
	"dijit/_Widget",
	"dijit/layout/_LayoutWidget",
	"dijit/Menu",
	"dijit/form/Button",
	"dijit/focus",
	"dijit/_base/focus",
	"dojo/window",
	"dojox/html/metrics"
	], function(dijit,dojo,dojox, rollinglistpane, rollinglistgrouppane, nls, rollinglisttemplateString, djtemplated, djcontainer, djwidget){


		return dojo.declare("OoCmS.xbugfix.RollingList", [djwidget, djtemplated, djcontainer], {
			// summary: a rolling list that can be tied to a data store with children
		
			// templateString: String
			//		The template to be used to construct the widget.
			templateString: rollinglisttemplateString,
			widgetsInTemplate: true,
	
			// className: string
			//  an additional class (or space-separated classes) to add for our widget
			className: "",
	
			// store: store
			//  the store we must use
			store: null,
	
			// query: object
			//  a query to pass to the datastore.  This is only used if items are null
			query: null,
	
			// queryOptions: object
			//  query options to be passed to the datastore
			queryOptions: null,
	
			// childrenAttrs: String[]
			//		one ore more attributes that holds children of a node
			childrenAttrs: ["children"],

			// parentAttr: string
			//	the attribute to read for finding our parent item (if any)
			parentAttr: "",
	
			// value: item
			//		The value that has been selected
			value: null,
	
			// executeOnDblClick: boolean
			//		Set to true if you want to call onExecute when an item is
			//		double-clicked, false if you want to call onExecute yourself. (mainly
			//		used for popups to control how they want to be handled)
			executeOnDblClick: true,
	
			// preloadItems: boolean or int
			//		if set to true, then onItems will be called only *after* all items have
			//		been loaded (ie store.isLoaded will return true for all of them).  If
			//		false, then no preloading will occur.  If set to an integer, preloading
			//		will occur if the number of items is less than or equal to the value
			//		of the integer.  The onItems function will need to be aware of handling
			//		items that may not be loaded
			preloadItems: false,
	
			// showButtons: boolean
			//		if set to true, then buttons for "OK" and "Cancel" will be provided
			showButtons: false,
	
			// okButtonLabel: string
			//		The string to use for the OK button - will use dijit's common "OK" string
			//		if not set
			okButtonLabel: "",
	
			// cancelButtonLabel: string
			//		The string to use for the Cancel button - will use dijit's common
			//		"Cancel" string if not set
			cancelButtonLabel: "",

			// minPaneWidth: integer
			//	the minimum pane width (in px) for all child panes.  If they are narrower,
			//  the width will be increased to this value.
			minPaneWidth: 0,
	
			postMixInProperties: function(){
				// summary: Mix in our labels, if they are not set
				this.inherited(arguments);
				this.okButtonLabel = this.okButtonLabel || nls.buttonOk;
				this.cancelButtonLabel = this.cancelButtonLabel || nls.buttonCancel;
			},
	
			_setShowButtonsAttr: function(doShow){
				// summary: Sets the visibility of the buttons for the widget
				var needsLayout = false;
				if((this.showButtons != doShow && this._started) ||
					(this.showButtons == doShow && !this.started)){
					needsLayout = true;
				}
				dojo.toggleClass(this.domNode, "dojoxRollingListButtonsHidden", !doShow);
				this.showButtons = doShow;
				if(needsLayout){
					if(this._started){
						this.layout();
					}else{
						window.setTimeout(dojo.hitch(this, "layout"), 0);
					}
				}
			},
	
			_itemsMatch: function(/*item*/ item1, /*item*/ item2){
				// Summary: returns whether or not the two items match - checks ID if
				//  they aren't the exact same object
				if(!item1 && !item2){
					return true;
				}else if(!item1 || !item2){
					return false;
				}
				return (item1 == item2 ||
					(this._isIdentity && this.store.getIdentity(item1) == this.store.getIdentity(item2)));
			},
	
			_removeAfter: function(/*Widget or int*/ idx){
				// summary: removes all widgets after the given widget (or index)
				if(typeof idx != "number"){
					idx = this.getIndexOfChild(idx);
				}
				if(idx >= 0){
					dojo.forEach(this.getChildren(), function(c, i){
						if(i > idx){
							this.removeChild(c);
							c.destroyRecursive();
						}
					}, this);
				}
				var children = this.getChildren(), child = children[children.length - 1];
				var selItem = null;
				while(child && !selItem){
					var val = child._getSelected ? child._getSelected() : null;
					if(val){
						selItem = val.item;
					}
					child = child.parentPane;
				}
				if(!this._setInProgress){
					this._setValue(selItem);
				}
			},
	
			addChild: function(/*dijit._Widget*/ widget, /*int?*/ insertIndex){
				// summary: adds a child to this rolling list - if passed an insertIndex,
				//  then all children from that index on will be removed and destroyed
				//  before adding the child.
				if(insertIndex > 0){
					this._removeAfter(insertIndex - 1);
				}
				this.inherited(arguments);
				if(!widget._started){
					widget.startup();
				}
				widget.attr("minWidth", this.minPaneWidth);
				this.layout();
				if(!this._savedFocus){
					widget.focus();
				}
			},
	
			_setMinPaneWidthAttr: function(value){
				// summary:
				//		Sets the min pane width of all children
				if(value !== this.minPaneWidth){
					this.minPaneWidth = value;
					dojo.forEach(this.getChildren(), function(c){
						c.attr("minWidth", value);
					});
				}
			},
	
			_updateClass: function(/* Node */ node, /* String */ type, /* Object? */ options){
				// summary:
				//		sets the state of the given node with the given type and options
				// options:
				//		an object with key-value-pairs.  The values are boolean, if true,
				//		the key is added as a class, if false, it is removed.
				if(!this._declaredClasses){
					this._declaredClasses = ("dojoxRollingList " + this.className).split(" ");
				}
				dojo.forEach(this._declaredClasses, function(c){
					if(c){
						dojo.addClass(node, c + type);
						for(var k in options||{}){
							dojo.toggleClass(node, c + type + k, options[k]);
						}
						dojo.toggleClass(node, c + type + "FocusSelected",
							(dojo.hasClass(node, c + type + "Focus") && dojo.hasClass(node, c + type + "Selected")));
						dojo.toggleClass(node, c + type + "HoverSelected",
							(dojo.hasClass(node, c + type + "Hover") && dojo.hasClass(node, c + type + "Selected")));
					}
				});
			},
	
			scrollIntoView: function(/*dijit._Widget*/ childWidget){
				// summary: scrolls the given widget into view
				if(this._scrollingTimeout){
					window.clearTimeout(this._scrollingTimeout);
				}
				delete this._scrollingTimeout;
				this._scrollingTimeout = window.setTimeout(dojo.hitch(this, function(){
					if(childWidget.domNode){
						dojo.window.scrollIntoView(childWidget.domNode);
					}
					delete this._scrollingTimeout;
					return;
				}), 1);
			},
	
			resize: function(args){
				dijit.layout._LayoutWidget.prototype.resize.call(this, args);
			},
	
			layout: function(){
				var children = this.getChildren();
				if(this._contentBox){
					var bn = this.buttonsNode;
					var height = this._contentBox.h - dojo.marginBox(bn).h -
					dojox.html.metrics.getScrollbar().h;
					dojo.forEach(children, function(c){
						dojo.marginBox(c.domNode, {
							h: height
						});
					});
				}
				if(this._focusedPane){
					var foc = this._focusedPane;
					delete this._focusedPane;
					if(!this._savedFocus){
						foc.focus();
					}
				}else if(children && children.length){
					if(!this._savedFocus){
						children[0].focus();
					}
				}
			},
	
			_onChange: function(/*item*/ value){
				this.onChange(value);
			},

			_setValue: function(/* item */ value){
				// summary: internally sets the value and fires onchange
				delete this._setInProgress;
				if(!this._itemsMatch(this.value, value)){
					this.value = value;
					this._onChange(value);
				}
			},
	
			_setValueAttr: function(/* item */ value){
				// summary: sets the value of this widget to the given store item
				if(this._itemsMatch(this.value, value) && !value){
					return;
				}
				if(this._setInProgress && this._setInProgress === value){
					return;
				}
				this._setInProgress = value;
				if(!value || !this.store.isItem(value)){
					var pane = this.getChildren()[0];
					pane._setSelected(null);
					this._onItemClick(null, pane, null, null);
					return;
				}
		
				var fetchParentItems = dojo.hitch(this, function(/*item*/ item, /*function*/callback){
					// Summary: Fetchs the parent items for the given item
					var store = this.store, id;
					if(this.parentAttr && store.getFeatures()["dojo.data.api.Identity"] &&
						((id = this.store.getValue(item, this.parentAttr)) || id === "")){
						// Fetch by parent attribute
						var cb = function(i){
							if(store.getIdentity(i) == store.getIdentity(item)){
								callback(null);
							}else{
								callback([i]);
							}
						};
						if(id === ""){
							callback(null);
						}else if(typeof id == "string"){
							store.fetchItemByIdentity({
								identity: id, 
								onItem: cb
							});
						}else if(store.isItem(id)){
							cb(id);
						}
					}else{
						// Fetch by finding children
						var numCheck = this.childrenAttrs.length;
						var parents = [];
						dojo.forEach(this.childrenAttrs, function(attr){
							var q = {};
							q[attr] = item;
							store.fetch({
								query: q, 
								scope: this,
								onComplete: function(items){
									if(this._setInProgress !== value){
										return;
									}
									parents = parents.concat(items);
									numCheck--;
									if(numCheck === 0){
										callback(parents);
									}
								}
							});
						}, this);
					}
				});
		
				var setFromChain = dojo.hitch(this, function(/*item[]*/itemChain, /*integer*/idx){
					// Summary: Sets the value of the widget at the given index in the chain - onchanges are not
					// fired here
					var set = itemChain[idx];
					var child = this.getChildren()[idx];
					var conn;
					if(set && child){
						var fx = dojo.hitch(this, function(){
							if(conn){
								this.disconnect(conn);
							}
							delete conn;
							if(this._setInProgress !== value){
								return;
							}
							var selOpt = dojo.filter(child._menu.getChildren(), function(i){
								return this._itemsMatch(i.item, set);
							}, this)[0];
							if(selOpt){
								idx++;
								child._menu.onItemClick(selOpt, {
									type: "internal",
									stopPropagation: function(){},
									preventDefault: function(){}
								});
							if(itemChain[idx]){
								setFromChain(itemChain, idx);
							}else{
								this._setValue(set);
								this.onItemClick(set, child, this.getChildItems(set));
							}
						}
						});
					if(!child.isLoaded){
						conn = this.connect(child, "onLoad", fx);
					}else{
						fx();
					}
				}else if(idx === 0){
					this.set("value", null);
				}
				});
		
			var parentChain = [];
			var onParents = dojo.hitch(this, function(/*item[]*/ parents){
				// Summary: recursively grabs the parents - only the first one is followed
				if(parents && parents.length){
					parentChain.push(parents[0]);
					fetchParentItems(parents[0], onParents);
				}else{
					if(!parents){
						parentChain.pop();
					}
					parentChain.reverse();
					setFromChain(parentChain, 0);
				}
			});
		
			// Only set the value in display if we are shown - if we are in a dropdown,
			// and are hidden, don't actually do the scrolling in the display (it can
			// mess up layouts)
			var ns = this.domNode.style;
			if(ns.display == "none" || ns.visibility == "hidden"){
				this._setValue(value);
			}else if(!this._itemsMatch(value, this._visibleItem)){
				onParents([value]);
			}
		},
	
		_onItemClick: function(/* Event */ evt, /* dijit._Contained */ pane, /* item */ item, /* item[]? */ children){
			// summary: internally called when a widget should pop up its child
		
			if(evt){
				var itemPane = this._getPaneForItem(item, pane, children);
				var alreadySelected = (evt.type == "click" && evt.alreadySelected);

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
		},
	
		_getPaneForItem: function(/* item? */ item, /* dijit._Contained? */ parentPane, /* item[]? */ children){		// summary: gets the pane for the given item, and mixes in our needed parts
			// Returns the pane for the given item (null if the root pane) - after mixing in
			// its stuff.
			var ret = this.getPaneForItem(item, parentPane, children);
			ret.store = this.store;
			ret.parentWidget = this;
			ret.parentPane = parentPane||null;
			if(!item){
				ret.query = this.query;
				ret.queryOptions = this.queryOptions;
			}else if(children){
				ret.items = children;
			}else{
				ret.items = [item];
			}
			return ret;
		},
	
		_getMenuItemForItem: function(/*item*/ item, /* dijit._Contained */ parentPane){
			// summary: returns a widget for the given store item.  The returned
			//  item will be added to this widget's container widget.  null will
			//  be passed in for an "empty" item.
			var store = this.store;
			if(!item || !store || !store.isItem(item)){
				var i = new dijit.MenuItem({
					label: "---",
					disabled: true,
					iconClass: "dojoxEmpty",
					focus: function(){
					// Do nothing on focus of this guy...
					}
				});
				this._updateClass(i.domNode, "Item");
				return i;
			}else{
				var itemLoaded = store.isItemLoaded(item);
				var childItems = itemLoaded ? this.getChildItems(item) : undefined;
				var widgetItem;
				if(childItems){
					widgetItem = this.getMenuItemForItem(item, parentPane, childItems);
					widgetItem.children = childItems;
					this._updateClass(widgetItem.domNode, "Item", {
						"Expanding": true
					});
					if(!widgetItem._started){
						var c = widgetItem.connect(widgetItem, "startup", function(){
							this.disconnect(c);
							dojo.style(this.arrowWrapper, "display", "");
						});
					}else{
						dojo.style(widgetItem.arrowWrapper, "display", "");
					}
				}else{
					widgetItem = this.getMenuItemForItem(item, parentPane, null);
					if(itemLoaded){
						this._updateClass(widgetItem.domNode, "Item", {
							"Single": true
						});
					}else{
						this._updateClass(widgetItem.domNode, "Item", {
							"Unloaded": true
						});
						widgetItem.attr("disabled", true);
					}
				}
				widgetItem.store = this.store;
				widgetItem.item = item;
				if(!widgetItem.label){
					widgetItem.attr("label", this.store.getLabel(item).replace(/</,"&lt;"));
				}
				if(widgetItem.focusNode){
					var self = this;
					widgetItem.focus = function(){
						// Don't set our class
						if(!this.disabled){
							try{
								this.focusNode.focus();
							}catch(e){}
						}
				};
				widgetItem.connect(widgetItem.focusNode, "onmouseenter", function(){
					if(!this.disabled){
						self._updateClass(this.domNode, "Item", {
							"Hover": true
						});
					}
				});
				widgetItem.connect(widgetItem.focusNode, "onmouseleave", function(){
					if(!this.disabled){
						self._updateClass(this.domNode, "Item", {
							"Hover": false
						});
					}
				});
				widgetItem.connect(widgetItem.focusNode, "blur", function(){
					self._updateClass(this.domNode, "Item", {
						"Focus": false, 
						"Hover": false
					});
				});
				widgetItem.connect(widgetItem.focusNode, "focus", function(){
					self._updateClass(this.domNode, "Item", {
						"Focus": true
					});
					self._focusedPane = parentPane;
				});
				if(this.executeOnDblClick){
					widgetItem.connect(widgetItem.focusNode, "ondblclick", function(){
						self._onExecute();
					});
				}
			}
			return widgetItem;
		}
		},
	
		_setStore: function(/* dojo.data.api.Read */ store){
			// summary: sets the store for this widget */
			if(store === this.store && this._started){
				return;
			}
			this.store = store;
			this._isIdentity = store.getFeatures()["dojo.data.api.Identity"];
			var rootPane = this._getPaneForItem();
			this.addChild(rootPane, 0);
		},
	
		_onKey: function(/*Event*/ e){
			// summary: called when a keypress event happens on this widget
			if(e.charOrCode == dojo.keys.BACKSPACE){
				dojo.stopEvent(e);
				return;
			}else if(e.charOrCode == dojo.keys.ESCAPE && this._savedFocus){
				try{
					dijit.focus(this._savedFocus);
				}catch(e){}
				dojo.stopEvent(e);
				return;
			}else if(e.charOrCode == dojo.keys.LEFT_ARROW ||
				e.charOrCode == dojo.keys.RIGHT_ARROW){
				dojo.stopEvent(e);
				return;
			}
		},
	
		_resetValue: function(){
			// Summary: function called when the value is reset.
			this.set("value", this._lastExecutedValue);
		},
	
		_onCancel: function(){
			// Summary: function called when the cancel button is clicked.  It
			//		resets its value to whatever was last executed and then cancels
			this._resetValue();
			this.onCancel();
		},
	
		_onExecute: function(){
			// Summary: function called when the OK button is clicked or when an
			//		item is selected (double-clicked or "enter" pressed on it)
			this._lastExecutedValue = this.get("value");
			this.onExecute();
		},
	
		focus: function(){
			// summary: sets the focus state of this widget
			var wasSaved = this._savedFocus;
			this._savedFocus = dijit.getFocus(this);
			if(!this._savedFocus.node){
				delete this._savedFocus;
			}
			if(!this._focusedPane){
				var child = this.getChildren()[0];
				if(child && !wasSaved){
					child.focus(true);
				}
			}else{
				this._savedFocus = dijit.getFocus(this);
				var foc = this._focusedPane;
				delete this._focusedPane;
				if(!wasSaved){
					foc.focus(true);
				}
			}
		},
	
		handleKey:function(/*Event*/e){
			// summary: handle the key for the given event - called by dropdown
			//	widgets
			if(e.charOrCode == dojo.keys.DOWN_ARROW){
				delete this._savedFocus;
				this.focus();
				return false;
			}else if(e.charOrCode == dojo.keys.ESCAPE){
				this._onCancel();
				return false;
			}
			return true;
		},
	
		_updateChildClasses: function(){
			// summary: Called when a child is added or removed - so that we can
			//	update the classes for styling the "current" one differently than
			//	the others
			var children = this.getChildren();
			var length = children.length;
			dojo.forEach(children, function(c, idx){
				dojo.toggleClass(c.domNode, "dojoxRollingListPaneCurrentChild", (idx == (length - 1)));
				dojo.toggleClass(c.domNode, "dojoxRollingListPaneCurrentSelected", (idx == (length - 2)));
			});
		},

		startup: function(){
			if(this._started){
				return;
			}
			if(!this.getParent || !this.getParent()){
				this.resize();
				this.connect(dojo.global, "onresize", "resize");
			}
			this.connect(this, "addChild", "_updateChildClasses");
			this.connect(this, "removeChild", "_updateChildClasses");
			this._setStore(this.store);
			this.set("showButtons", this.showButtons);
			this.inherited(arguments);
			this._lastExecutedValue = this.get("value");
		},
	
		getChildItems: function(/*item*/ item){
			// summary: Returns the child items for the given store item
			var childItems, store = this.store;
			dojo.forEach(this.childrenAttrs, function(attr){
				var vals = store.getValues(item, attr);
				if(vals && vals.length){
					childItems = (childItems||[]).concat(vals);
				}
			});
			return childItems;
		},
	
		getMenuItemForItem: function(/*item*/ item, /* dijit._Contained */ parentPane, /* item[]? */ children){
			// summary: user overridable function to return a widget for the given item
			//  and its children.
			return new dijit.MenuItem({});
		},

		getPaneForItem: function(/* item? */ item, /* dijit._Contained? */ parentPane, /* item[]? */ children){
			// summary: user-overridable function to return a pane that corresponds
			//  to the given item in the store.  It can return null to not add a new pane
			//  (ie, you are planning on doing something else with it in onItemClick)
			//
			//  Item is undefined for the root pane, children is undefined for non-group panes
			if(!item || children){
				return new rollinglistgrouppane({});
			}else{
				return null;
			}
		},

		onItemClick: function(/* item */ item, /* dijit._Contained */ pane, /* item[]? */ children){
		// summary: called when an item is clicked - it receives the store item
		},
	
		onExecute: function(){
		// summary: exists so that popups don't disappear too soon
		},
	
		onCancel: function(){
		// summary: exists so that we can close ourselves if we wish
		},
	
		onChange: function(/* item */ value){
		// summary: called when the value of this widget has changed
		}
	
	});

});
