define(["dojo/_base/declare",
	"dojo/window",
	"dijit/layout/ContentPane",
	"dijit/_Templated",
	"dijit/_Contained"
	], function(declare, dwin, djcontentpane, dj_templated, dj_contained) {

		return declare("OoCmS.xbugfix._RollingListPane", [djcontentpane, dj_templated, dj_contained], {
				// summary: a core pane that can be attached to a RollingList.  All panes
				//  should extend this one

				// templateString: string
				//	our template
				templateString: '<div class="dojoxRollingListPane"><table><tbody>'+
					'<tr><td data-dojo-attach-point="containerNode"></td></tr></tbody></div>',

				// parentWidget: dojox.widget.RollingList
				//  Our rolling list widget
				parentWidget: null,
	
				// parentPane: dojox.widget._RollingListPane
				//  The pane that immediately precedes ours
				parentPane: null,
			
				// store: store
				//  the store we must use
				store: null,

				// items: item[]
				//  an array of (possibly not-yet-loaded) items to display in this.
				//  If this array is null, then the query and query options are used to
				//  get the top-level items to use.  This array is also used to watch and
				//  see if the pane needs to be reloaded (store notifications are handled)
				//  by the pane
				items: null,
	
				// query: object
				//  a query to pass to the datastore.  This is only used if items are null
				query: null,
	
				// queryOptions: object
				//  query options to be passed to the datastore
				queryOptions: null,
	
				// focusByNode: boolean
				//  set to false if the subclass will handle its own node focusing
				_focusByNode: true,
	
				// minWidth: integer
				//	the width (in px) for this pane
				minWidth: 0,
	
				_setContentAndScroll: function(/*String|DomNode|Nodelist*/cont, /*Boolean?*/isFakeContent){
					// summary: sets the value of the content and scrolls it into view
					this._setContent(cont, isFakeContent);
					this.parentWidget.scrollIntoView(this);
				},

				_updateNodeWidth: function(n, min){
					// summary: updates the min width of the pane to be minPaneWidth
					n.style.width = "";
					var nWidth = dojo.marginBox(n).w;
					if(nWidth < min){
						dojo.marginBox(n, {
							w: min
						});
					}
				},
	
				_onMinWidthChange: function(v){
					// Called when the min width of a pane has changed
					this._updateNodeWidth(this.domNode, v);
				},
	
				_setMinWidthAttr: function(v){
					if(v !== this.minWidth){
						this.minWidth = v;
						this._onMinWidthChange(v);
					}
				},
	
				startup: function(){
					if(this._started){
						return;
					}
					if(this.store && this.store.getFeatures()["dojo.data.api.Notification"]){
						window.setTimeout(dojo.hitch(this, function(){
							// Set connections after a slight timeout to avoid getting in the
							// condition where we are setting them while events are still
							// being fired
							this.connect(this.store, "onSet", "_onSetItem");
							this.connect(this.store, "onNew", "_onNewItem");
							this.connect(this.store, "onDelete", "_onDeleteItem");
						}), 1);
					}
					this.connect(this.focusNode||this.domNode, "onkeypress", "_focusKey");
					this.parentWidget._updateClass(this.domNode, "Pane");
					this.inherited(arguments);
					this._onMinWidthChange(this.minWidth);
				},

				_focusKey: function(/*Event*/e){
					// summary: called when a keypress happens on the widget
					if(e.charOrCode == dojo.keys.BACKSPACE){
						dojo.stopEvent(e);
						return;
					}else if(e.charOrCode == dojo.keys.LEFT_ARROW && this.parentPane){
						this.parentPane.focus();
						this.parentWidget.scrollIntoView(this.parentPane);
					}else if(e.charOrCode == dojo.keys.ENTER){
						this.parentWidget._onExecute();
					}
				},
	
				focus: function(/*boolean*/force){
					// summary: sets the focus to this current widget
					if(this.parentWidget._focusedPane != this){
						this.parentWidget._focusedPane = this;
						this.parentWidget.scrollIntoView(this);
						if(this._focusByNode && (!this.parentWidget._savedFocus || force)){
							try{
								(this.focusNode||this.domNode).focus();
							}catch(e){}
						}
					}
				},
	
				_onShow: function(){
					// summary: checks that the store is loaded
					if((this.store || this.items) && ((this.refreshOnShow && this.domNode) || (!this.isLoaded && this.domNode))){
						this.refresh();
					}
				},
	
				_load: function(){
					// summary: sets the "loading" message and then kicks off a query asyncronously
					this.isLoaded = false;
					if(this.items){
						this._setContentAndScroll(this.onLoadStart(), true);
						window.setTimeout(dojo.hitch(this, "_doQuery"), 1);
					}else{
						this._doQuery();
					}
				},
	
				_doLoadItems: function(/*item[]*/items, /*function*/callback){
					// summary: loads the given items, and then calls the callback when they
					//		are finished.
					var _waitCount = 0, store = this.store;
					dojo.forEach(items, function(item){
						if(!store.isItemLoaded(item)){
							_waitCount++;
						}
					});
					if(_waitCount === 0){
						callback();
					}else{
						var onItem = function(item){
							_waitCount--;
							if((_waitCount) === 0){
								callback();
							}
						};
						dojo.forEach(items, function(item){
							if(!store.isItemLoaded(item)){
								store.loadItem({
									item: item, 
									onItem: onItem
								});
							}
						});
					}
				},
	
				_doQuery: function(){
					// summary: either runs the query or loads potentially not-yet-loaded items.
					if(!this.domNode){
						return;
					}
					var preload = this.parentWidget.preloadItems;
					preload = (preload === true || (this.items && this.items.length <= Number(preload)));
					if(this.items && preload){
						this._doLoadItems(this.items, dojo.hitch(this, "onItems"));
					}else if(this.items){
						this.onItems();
					}else{
						this._setContentAndScroll(this.onFetchStart(), true);
						this.store.fetch({
							query: this.query,
							onComplete: function(items){
								this.items = items;
								this.onItems();
							},
							onError: function(e){
								this._onError("Fetch", e);
							},
							scope: this
						});
					}
				},

				_hasItem: function(/* item */ item){
					// summary: returns whether or not the given item is handled by this
					//  pane
					var items = this.items || [];
					for(var i = 0, myItem; (myItem = items[i]); i++){
						if(this.parentWidget._itemsMatch(myItem, item)){
							return true;
						}
					}
					return false;
				},
	
				_onSetItem: function(/* item */ item,
					/* attribute-name-string */ attribute,
					/* object | array */ oldValue,
					/* object | array */ newValue){
					// Summary: called when an item in the store has changed
					if(this._hasItem(item)){
						this.refresh();
					}
				},
	
				_onNewItem: function(/* item */ newItem, /*object?*/ parentInfo){
					// Summary: called when an item is added to the store
					var sel;
					if((!parentInfo && !this.parentPane) ||
						(parentInfo && this.parentPane && this.parentPane._hasItem(parentInfo.item) &&
							(sel = this.parentPane._getSelected()) && this.parentWidget._itemsMatch(sel.item, parentInfo.item))){
						this.items.push(newItem);
						this.refresh();
					}else if(parentInfo && this.parentPane && this._hasItem(parentInfo.item)){
						this.refresh();
					}
				},
	
				_onDeleteItem: function(/* item */ deletedItem){
					// Summary: called when an item is removed from the store
					if(this._hasItem(deletedItem)){
						this.items = dojo.filter(this.items, function(i){
							return (i != deletedItem);
						});
						this.refresh();
					}
				},
	
				onFetchStart: function(){
					// summary:
					//		called before a fetch starts
					return this.loadingMessage;
				},
	
				onFetchError: function(/*Error*/ error){
					// summary:
					//	called when a fetch error occurs.
					return this.errorMessage;
				},

				onLoadStart: function(){
					// summary:
					//		called before a load starts
					return this.loadingMessage;
				},
	
				onLoadError: function(/*Error*/ error){
					// summary:
					//	called when a load error occurs.
					return this.errorMessage;
				},
	
				onItems: function(){
					// summary:
					//	called after a fetch or load - at this point, this.items should be
					//  set and loaded.  Override this function to "do your stuff"
					if(!this.onLoadDeferred){
						this.cancel();
						this.onLoadDeferred = new dojo.Deferred(dojo.hitch(this, "cancel"));
					}
					this._onLoadHandler();
				}
			
			});
	});
