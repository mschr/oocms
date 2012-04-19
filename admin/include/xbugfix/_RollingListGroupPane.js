define(["dojo/_base/declare",
	"dojo/window",
	"OoCmS/xbugfix/_RollingListPane"
], function(declare, dwin,listpane) {

		return declare("OoCmS.xbugfix._RollingListGroupPane",[listpane],{
			// summary: a pane that will handle groups (treats them as menu items)
	
			// templateString: string
			//	our template
			templateString: '<div><div data-dojo-attach-point="containerNode"></div>' +
			'<div data-dojo-attach-point="menuContainer">' +
			'<div data-dojo-attach-point="menuNode"></div>' +
			'</div></div>',

			// _menu: dijit.Menu
			//  The menu that we will call addChild() on for adding items
			_menu: null,
	
			_setContent: function(/*String|DomNode|Nodelist*/cont){
				if(!this._menu){
					// Only set the content if we don't already have a menu
					this.inherited(arguments);
				}
			},

			_onMinWidthChange: function(v){
				// override and resize the menu instead
				if(!this._menu){
					return;
				}
				var dWidth = dojo.marginBox(this.domNode).w;
				var mWidth = dojo.marginBox(this._menu.domNode).w;
				this._updateNodeWidth(this._menu.domNode, v - (dWidth - mWidth));
			},

			onItems: function(){
				// summary:
				//	called after a fetch or load
				var selectItem, hadChildren = false;
				if(this._menu){
					selectItem = this._getSelected();
					this._menu.destroyRecursive();
				}
				this._menu = this._getMenu();
				var child, selectMenuItem;
				if(this.items.length){
					dojo.forEach(this.items, function(item){
						child = this.parentWidget._getMenuItemForItem(item, this);
						if(child){
							if(selectItem && this.parentWidget._itemsMatch(child.item, selectItem.item)){
								selectMenuItem = child;
							}
							this._menu.addChild(child);
						}
					}, this);
				}else{
					child = this.parentWidget._getMenuItemForItem(null, this);
					if(child){
						this._menu.addChild(child);
					}
				}
				if(selectMenuItem){
					this._setSelected(selectMenuItem);
					if((selectItem && !selectItem.children && selectMenuItem.children) ||
						(selectItem && selectItem.children && !selectMenuItem.children)){
						var itemPane = this.parentWidget._getPaneForItem(selectMenuItem.item, this, selectMenuItem.children);
						if(itemPane){
							this.parentWidget.addChild(itemPane, this.getIndexInParent() + 1);
						}else{
							this.parentWidget._removeAfter(this);
							this.parentWidget._onItemClick(null, this, selectMenuItem.item, selectMenuItem.children);
						}
					}
				}else if(selectItem){
					this.parentWidget._removeAfter(this);
				}
				this.containerNode.innerHTML = "";
				this.containerNode.appendChild(this._menu.domNode);
				this.parentWidget.scrollIntoView(this);
				this._checkScrollConnection(true);
				this.inherited(arguments);
				this._onMinWidthChange(this.minWidth);
			},
	
			_checkScrollConnection: function(doLoad){
				// summary: checks whether or not we need to connect to our onscroll
				//		function
				var store = this.store
				if(this._scrollConn){
					this.disconnect(this._scrollConn);
				}
				delete this._scrollConn;
				if(!dojo.every(this.items, function(i){
					return store.isItemLoaded(i);
				})){
					if(doLoad){
						this._loadVisibleItems();
					}
					this._scrollConn = this.connect(this.domNode, "onscroll", "_onScrollPane");
				}
			},
	
			startup: function(){
				this.inherited(arguments);
				this.parentWidget._updateClass(this.domNode, "GroupPane");
			},
	
			focus: function(/*boolean*/force){
				// summary: sets the focus to this current widget
				if(this._menu){
					if(this._pendingFocus){
						this.disconnect(this._pendingFocus);
					}
					delete this._pendingFocus;
			
					// We focus the right widget - either the focusedChild, the
					//   selected node, the first menu item, or the menu itself
					var focusWidget = this._menu.focusedChild;
					if(!focusWidget){
						var focusNode = dojo.query(".dojoxRollingListItemSelected", this.domNode)[0];
						if(focusNode){
							focusWidget = dijit.byNode(focusNode);
						}
					}
					if(!focusWidget){
						focusWidget = this._menu.getChildren()[0] || this._menu;
					}
					this._focusByNode = false;
					if(focusWidget.focusNode){
						if(!this.parentWidget._savedFocus || force){
							try{
								focusWidget.focusNode.focus();
							}catch(e){}
						}
						window.setTimeout(function(){
							try{
								dojo.window.scrollIntoView(focusWidget.focusNode);
							}catch(e){}
						}, 1);
					}else if(focusWidget.focus){
						if(!this.parentWidget._savedFocus || force){
							focusWidget.focus();
						}
					}else{
						this._focusByNode = true;
					}
					this.inherited(arguments);
				}else if(!this._pendingFocus){
					this._pendingFocus = this.connect(this, "onItems", "focus");
				}
			},
	
			_getMenu: function(){
				// summary: returns a widget to be used for the container widget.
				var self = this;
				var menu = new dijit.Menu({
					parentMenu: this.parentPane ? this.parentPane._menu : null,
					onCancel: function(/*Boolean*/ closeAll){
						if(self.parentPane){
							self.parentPane.focus(true);
						}
					},
					_moveToPopup: function(/*Event*/ evt){
						if(this.focusedChild && !this.focusedChild.disabled){
							this.focusedChild._onClick(evt);
						}
					}
				}, this.menuNode);
				this.connect(menu, "onItemClick", function(/*dijit.MenuItem*/ item, /*Event*/ evt){
					if(item.disabled){
						return;
					}
					evt.alreadySelected = dojo.hasClass(item.domNode, "dojoxRollingListItemSelected");
					if(evt.alreadySelected &&
						((evt.type == "keypress" && evt.charOrCode != dojo.keys.ENTER) ||
							(evt.type == "internal"))){
						var p = this.parentWidget.getChildren()[this.getIndexInParent() + 1];
						if(p){
							p.focus(true);
							this.parentWidget.scrollIntoView(p);
						}
					}else{
						this._setSelected(item, menu);
						this.parentWidget._onItemClick(evt, this, item.item, item.children);
						if(evt.type == "keypress" && evt.charOrCode == dojo.keys.ENTER){
							this.parentWidget._onExecute();
						}
					}
				});
				if(!menu._started){
					menu.startup();
				}
				return menu;
			},
	
			_onScrollPane: function(){
				// summary: called when the pane has been scrolled - it sets a timeout
				//		so that we don't try and load our visible items too often during
				//		a scroll
				if(this._visibleLoadPending){
					window.clearTimeout(this._visibleLoadPending);
				}
				this._visibleLoadPending = window.setTimeout(dojo.hitch(this, "_loadVisibleItems"), 500);
			},
	
			_loadVisibleItems: function(){
				// summary: loads the items that are currently visible in the pane
				delete this._visibleLoadPending
				var menu = this._menu;
				if(!menu){
					return;
				}
				var children = menu.getChildren();
				if(!children || !children.length){
					return;
				}
				var gpbme = function(n, m, pb){
					var s = dojo.getComputedStyle(n);
					var r = 0;
					if(m){
						r += dojo._getMarginExtents(n, s).t;
					}
					if(pb){
						r += dojo._getPadBorderExtents(n, s).t;
					}
					return r;
				};
				var topOffset = gpbme(this.domNode, false, true) +
				gpbme(this.containerNode, true, true) +
				gpbme(menu.domNode, true, true) +
				gpbme(children[0].domNode, true, false);
				var h = dojo.contentBox(this.domNode).h;
				var minOffset = this.domNode.scrollTop - topOffset - (h/2);
				var maxOffset = minOffset + (3*h/2);
				var menuItemsToLoad = dojo.filter(children, function(c){
					var cnt = c.domNode.offsetTop;
					var s = c.store;
					var i = c.item;
					return (cnt >= minOffset && cnt <= maxOffset && !s.isItemLoaded(i));
				})
				var itemsToLoad = dojo.map(menuItemsToLoad, function(c){
					return c.item;
				});
				var onItems = dojo.hitch(this, function(){
					var selectItem = this._getSelected();
					var selectMenuItem;
					dojo.forEach(itemsToLoad, function(item, idx){
						var newItem = this.parentWidget._getMenuItemForItem(item, this);
						var oItem = menuItemsToLoad[idx];
						var oIdx = oItem.getIndexInParent();
						menu.removeChild(oItem);
						if(newItem){
							if(selectItem && this.parentWidget._itemsMatch(newItem.item, selectItem.item)){
								selectMenuItem = newItem;
							}
							menu.addChild(newItem, oIdx);
							if(menu.focusedChild == oItem){
								menu.focusChild(newItem);
							}
						}
						oItem.destroy();
					}, this);
					this._checkScrollConnection(false);
				});
				this._doLoadItems(itemsToLoad, onItems);
			},
	
			_getSelected: function(/*dijit.Menu?*/ menu){
				// summary:
				//	returns the selected menu item - or null if none are selected
				if(!menu){
					menu = this._menu;
				}
				if(menu){
					var children = this._menu.getChildren();
					for(var i = 0, item; (item = children[i]); i++){
						if(dojo.hasClass(item.domNode, "dojoxRollingListItemSelected")){
							return item;
						}
					}
				}
				return null;
			},
	
			_setSelected: function(/*dijit.MenuItem?*/ item, /*dijit.Menu?*/ menu){
				// summary:
				//	selectes the given item in the given menu (defaults to pane's menu)
				if(!menu){
					menu = this._menu;
				}
				if(menu){
					dojo.forEach(menu.getChildren(), function(i){
						this.parentWidget._updateClass(i.domNode, "Item", {
							"Selected": (item && (i == item && !i.disabled))
							});
					}, this);
				}
			}
		});
	});
