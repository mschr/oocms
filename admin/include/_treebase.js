define(["dojo/_base/declare",
	"dojo/_base/lang",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/date/locale",

	/*	"dojo/fx",
	"dijit/_WidgetBase",*/
	"dijit/Tooltip",
	"dojo/data/ItemFileReadStore",
	"dijit/tree/ForestStoreModel",
	"dijit/Tree"
	], function(declare, lang, array, connect, dlocale, /* djfx, djwidgetbase, */ djttip, djdatareadstore, forestmodel, djtree){
		var Tree = declare("OoCmS._treebase", [dijit.Tree], {
			_hlAnimation : null,
			_dateFromSQL : {
				selector: 'date', 
				formatLength:'full', 
				datePattern: 'EEE, d MMM y HH:mm:ss Z', 
				locale: 'da-dk'
			},
			_dateToScreen : {
				selector: 'date', 
				formatLength:'short', 
				datePattern: 'd MMM y HH:mm:ss', 
				locale: 'da-dk'
			},
			observers: [],
			canUpdate : true,
			constructor: function(args) {
				traceLog(this,arguments);
				var params = args || {};
				if(!params.store && !params.model.store) console.error(this.declaredClass + " ]> Required store argument in construct")

				if(params.model && typeof params.model.declaredClass != "undefined") {
					this.canUpdate = false
				}
				this.model = params.model || new forestmodel({
					rootLabel : (params.rootLabel ? params.rootLabel : undefined),
					store : params.store
				});
				this.observers.push(dojo.connect(this, "onLoad", this, function() {
					// realize all
					this.expandChildren(this.rootNode)
					this.__postCreate();
				}));
				console.log('ctor', args)
				this.__beforeBegin();

			},
			/* Carefull, not to make recursive calls in the callback function..
		 * fun_ptr : function(TreeNode) { ... }
		 */
			forAllNodes : function(parentTreeNode, fun_ptr) {
				traceLog(this,arguments)
				parentTreeNode.getChildren().forEach(function(n) {
					fun_ptr(n);
					if(n.item.children) {
						n.tree.forAllNodes(fun_ptr);
					}
				})
			},
			__beforeBegin : function() {
				traceLog(this,arguments)
				if(!this._started) {
					this.observers.push(dojo.connect(this, "_onNodeMouseEnter", this, this.showTooltip));
					this.observers.push(dojo.connect(this, "_onNodeMouseLeave", this, this.hideTooltip));
				}
			},
			showTooltip: function(node, e) {
				//								traceLog(this,arguments)
				if(node.item.root || !node.item 
					|| node.item._S.getValue(node.item, "id") >= 9995
					|| (this.dndController && this.dndController.isDragging)) return

				dijit.showTooltip(this.getTooltipContents(node.item), node.domNode)
			},
			hideTooltip: function(node, e) {
				dijit.hideTooltip( node.domNode )
			},
			__postCreate: function() {
				//				traceLog(this,arguments)
				try {
					var sepRow = this.getNodesByItem(this.model.store._itemsByIdentity[9995])[0]
					if(sepRow) {
						dojo.style(sepRow.iconNode, {
							display:"none"
						});
						dojo.style(sepRow.labelNode, {
							position:'relative',
							left: '-4px',
							top:'1px',
							color:'rgb(102,102,102)'
						});
					}
				} catch(e) { }
			},
			destroy: function() {
				array.forEach(this.observers, dojo.disconnect);
				this.inherited(arguments);
			},
			update : function() {
				traceLog(this,arguments)
				// Credit to this discussion: http://mail.dojotoolkit.org/pipermail/dojo-interest/2010-April/045180.html
				// Close the store (So that the store will do a new fetch()).
				this.model.store.clearOnClose = true;
				this.model.store.close();

				// Completely delete every node from the dijit.Tree
				delete this._itemNodesMap;
				this._itemNodesMap = {};
				this.rootNode.state = "UNCHECKED";
				delete this.model.root.children;
				this.model.root.children = null;

				// Destroy the widget
				this.rootNode.destroyRecursive();

				// Recreate the model, (with the model again)
				this.model.constructor(this.model)

				// Rebuild the tree
				this.postMixInProperties();
				this._load();
			},
			expandChildren : function (top) {
				var self = this,
				model = this.model;
				if(!top && !model.mayHaveChildren(top.item)) return;
				top.getChildren().forEach(function(n) {
					if(n.item.children) {
						self._expandNode(n);
						//dojo.style(n.getParent().containerNode, { overflowY: 'visible', overflowX: 'visible'});
						self.expandChildren(n);
					}
				});
			},
			collapseChildren : function(top) {
				var self = this,
				model = this.model;
				if(!top || !model.mayHaveChildren(top.item)) return;
				top.getChildren().forEach(function(n) {
					if(n.item.children) {
						//dojo.style(n.getParent().containerNode, { overflowY: 'hidden', overflowX: 'hidden'});
						self._collapseNode(n);
						self.collapseChildren(n);
					}
				});
			},
			getTooltipContents: function(item) {
				//				traceLog(this,arguments)
				var szHtml = "",
				type=type = item._S.getValue(item, "type");
				var l = function(s,pct) {
					//var w = Math.floor(containerWidth / 100 * pct);
					return '<td align="left" style="border:transparent none;'+ (pct?'width:'+pct+'%':"")+ '">'+
					'<label style="text-transform:capitalize" class="tiny">'+s+'</label></td>';
				};
				var i = function(s,pct) {
					//var w = Math.floor(containerWidth / 100 * pct);
					return '<td align="left" '+(pct?'width="'+pct+'%" ':"")+ 'style="min-width: 25%; border:transparent none;">'+
					'<span class="tiny urlformat">'+s+'</span></td>';
				}
				
				szHtml  ="<table style=\"background-color:transparent;\" width=\"250px\"><tbody>"
				if(!/file|dir/.test(type))
					for(var key in item) if(item.hasOwnProperty(key)) {
						if(/^_/.test(key) || item[key] == "")
							continue;
						else if(!/(include|media)/.test(type) && (/(showtitle|created|children|edit)/i.test(key)) )
							continue;
						else if(/(include|media)/.test(type) && (/(title|lastmodified)/i.test(key)) )
							continue;
						else if(/file|dir/.test(type) && /type|id|title|icon/.test(key))
							continue;
						else if(/created|lastmodified/.test(key)) {
							try {
								var date = dlocale.parse(item._S.getValue(item, key), this._dateFromSQL);
								szHtml += '<tr>'+l(key,30)  +  i(dlocale.format(date,this._dateToScreen)) + '</tr>';
							}catch(e) { 
								if(!window.localeserr) window.localeserr =0
								if((window.localeserr++) % 10 == 0) console.log(e.message, "date parse failed, check i118 nls files")
							}
						} else {
							szHtml += '<tr>'+l(key,30)  +  i( item._S.getValue(item, key),70)+'</tr>'
						//break
						}
					}
					else if(parseInt(item._S.getValue(item, "id")) < 9995) {
						var store = this.tree.model.store
						if(/file/.test(type)) {
							var name=store.getValue(item,'filename');
							var p = store.getValue(item,'abspath')
							var n = name.substr(name.lastIndexOf('/')+1)
							szHtml += "<tr>"+l('Navn',10)+l('Absolut sti for sitet')+l('Størrelse',9)+l('Sidst ændret',20)+'</tr>'
							szHtml += "<tr title=\""+n+"\">"+ i(item.title) + i(p) +i (item.size) + i(item.modified) + '</tr>';
						} else {
							szHtml += '<tr>'+l('Fulde sti',100) + '</tr><tr>'+ i(store.getValue(item, "abspath")) +'</tr>';
						}
					//			var d = this.getFiletypeDescription(item.icon)
					//			if(d)
					//				szHtml += '<tr><td colspan="3" style="width: 80px  !important;border:transparent none;"><label class="tiny">Filtype</label></td>'+
					//				'<td style="width: auto !important; border:transparent none;"><span class="tiny urlformat">'+d+'</span></td></tr>'
					} else {
						szHtml += "<tr>"+l("Virtuelt element, strukturerer underelementer i trævisning") + "</tr>";
					}
				szHtml += "</tbody></table>";
			
				return szHtml;
			}
		});
		return Tree;
	});
console.log('eval _treebase.js');