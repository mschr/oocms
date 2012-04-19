define(['dojo/_base/declare', 
	"dojo/parser",
	"dijit/registry",
	"dojo/dom",
	"dojo/_base/lang",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dojo/_base/xhr",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/io-query",
	"dojo/query",
	"dojo/topic",
	"OoCmS/toolkit",
	"OoCmS/messagebus",
	"dijit/Tooltip",
	"dijit/Dialog",
	"dojo/data/ItemFileReadStore",
	"dijit/tree/ForestStoreModel",
	"dijit/Tree",
	"dijit/layout/ContentPane",
	"dijit/layout/BorderContainer", // markup require
	"dijit/form/ComboBox",				// markup require
	"dojox/layout/ContentPane",     // markup require
	"dijit/form/ValidationTextBox", // markup require
	"dijit/form/Button"             // markup require

	], function(declare, parser, dregistry, ddom, dlang, ddomgeometry, dstyle, dxhr,
		darray, dconnect, dioquery, $, dtopic, ootoolkit, oomessagebus,
		/* ooconfig, oopage, oopagetree, ooassets,*/ djttip, djdialog, dstore, djforestmodel, djtree, djcontentpane){

		var Controller = declare("OoCmS.application", [ootoolkit, oomessagebus], {

			menustore: null,
			menumodel: null,
			menutree: null,

			contentpane: null,

			urlPreloads: {},
			_widgetInUse: undefined,
			defaultPaneProps: {},
			/*
			page: null,
			pagetree: null,
	
			productcategory: null,
			product: null,
			asset: null,
	
			domain: null,
			template: null,
		 */
			constructor: function constructor(args) {
				traceLog(this,arguments)
				var tmp;
				this.URI = new dojo._Url(dojo.doc.location.href);
				
				if(this.URI.query) {
					tmp = dioquery.queryToObject(this.URI.query);
					this.urlPreloads.action = tmp["action"];
				}
				if(this.URI.fragment) {
					tmp = dioquery.queryToObject(this.URI.fragment);
					dlang.mixin(this.urlPreloads, {
						id : tmp["id"],
						cat : tmp["cat"],
						presets: tmp["presets"]
					});
				}
				this.defaultPaneProps = {
					region: 'center',
					id: 'mainContentPane',
					splitter:true,
					onLoad: dlang.hitch(this, this.paneLoaded)
				}

				this.buildRendering(args.menudata);
				
				if(this.urlPreloads.action) {
					this.paneRequested(this.urlPreloads.action);
				// do an extra lazy layout
				//					dojo.ready(function() {
				//						setTimeout(function() {
				//							self.layout();
				//						},1200)
				//					});
				} else { // debug
					//						setTimeout(unittest, 550);
					this.paneRequested();
				}
			},
			buildRendering: function buildRendering(menudata) {
				var self = this,
				adminmenutooltipbuild = function(node) { 
					var content, item = node.item;
					if(item && !item.root) {
						content = self.menustore.getValue(node.item, "tooltip");
						if(content != null && content.length > 0) {
							new djttip({
								label: "<div style=\"min-width: 120px; min-height: 64px;\">"+ content +"</div>",
								connectId: node.domNode
							}).startup();
						}
					}
					darray.forEach(node.getChildren(), adminmenutooltipbuild); 
				}
				this.menustore = new dstore( {
					id: "adminmenuStore",
					data: {
						identifier: 'id', 
						label : 'label', 
						items: menudata
					}

				});

				this.menumodel = new djforestmodel({
					id:"adminmenuModel",
					store:this.menustore
				});
				this.menutree = new djtree( {
					id:"adminmenuTree",
					openOnClick:true,
					model:this.menumodel,
					showRoot:false,
					persist:false,
					autoExpand:true,
					onClick: dlang.hitch(this, this.paneRequested),
					getIconClass : function getIconClass(item, opened) {
						var iconcls = (item&&item.icon ? item.icon + "" : "");
						if((!item || this.model.mayHaveChildren(item))) {
							if(opened) {
								return (iconcls && iconcls.length > 0) ? iconcls : "dijitFolderOpened";
							} else {
								return (iconcls && iconcls.length > 0) ? iconcls : "dijitFolderClosed"
							}
						} else {
							return (iconcls && iconcls.length > 0) ? iconcls : "dijitLeaf";
						}

					}
				}).placeAt('adminmenuTreeNode');
				//				this.fixWidget(this.menutree, true);
				
				adminmenutooltipbuild(this.menutree.rootNode);
				

			//				dconnect.connect(this.contentpane, "onLoad", dlang.hitch(this, this.paneLoaded));
			},
			destroy: function destroy() {
				if(this._widgetInUse) {
					this._widgetInUse.unload();
					this._widgetInUse.destroyRecursive();
				}
				this.menutree.model.store.close();
				this.menutree.destroyRecursive();
				this.inherited();
			},
			//			layout: function layout() {
			//				var mask = $(".colmask")[0];
			//				// set subtraction according to top header and bottom footer 
			//				dstyle.set(mask, "height", (dojo.position(dojo.body()).h - 40 - 20) + "px");
			//				$(".adminBox").forEach(function(box) {  
			//					dstyle.set(box, "height", (
			//						ddomgeometry.getMarginBox(mask).h
			//						- ddomgeometry.getMarginExtents(box).h
			//						- ddomgeometry.getBorderExtents(box).h)+"px")
			//				});
			//			},
			unloadPane: function unloadPane() {
				console.warn('================= UNLOADING ==============');
				try {
					if(this._widgetInUse) {
						
						if(dlang.isFunction(this._widgetInUse.unload))
							this._widgetInUse.unload();
						if(dlang.isFunction(this._widgetInUse.destroyRecursive))
							this._widgetInUse.destroyRecursive();

					} 
					if(this.contentpane!=null&&dlang.isObject(this.contentpane))
						this.contentpane.destroy();
					//					delete this._widgetInUse;
					//					this._widgetInUse = null;
					darray.forEach(this._widgetfixObservers, dconnect.disconnect);
				}catch(err) {
					console.error(err);
				}
			},
			
			showLoginDialog: function(args) {
				//				traceLog(this,arguments)
				args = args || {};
				dtopic.publish("notify/progress/done");
				if(this.contentpane == null) { // weird scope or something *bugfix*
					this.contentpane = dregistry.byId('mainContentPane')
				}
				var _dialog = dregistry.byId('loginDialog') || new djdialog({
					title: 'Login required',
					style:"width:90%;height:90%;", 
					className:'loginDialog',
					id: 'loginDialog',
					content: ' '
				});
				_dialog.startup();
				var loginParentNode = $("table.loginpage", this.contentpane.domNode)[0];
				// is this for real a login.php load in the contentpane?
				if(!loginParentNode) return false;
				// we assume a contentpane has loaded but redirected to login page
				// manipulate looks by hit the .dialog CSS selectors (reparent)
				_dialog.containerNode.appendChild(loginParentNode);
				// setup form callbacks so we wont 
				// end up reloading page on a weird pane/action/subview
				var form = $("div.login form", _dialog.containerNode)[0];
				form.onsubmit = function() {
					return false;
				}
				var subwidgets = _dialog.getChildren();
				var submitbutton = subwidgets[subwidgets.length-1];
				submitbutton.onClick = function() {
					if(_dialog.validate()) {
						var values =  _dialog.getValues();
						// make sure a passed login does not send 302 Location header
						values.returnUrl = "DONTREDIR" 
						dxhr.post({
							url: form.action,
							content: values,
							load: dlang.hitch((args.scope ? args.scope : _dialog), function(res) {
								if(res.substring(0, 20).match(/SUCCESS/)) {
									if(typeof args.onLogin == "function") {
										if(this != _dialog) dlang.hitch(args, args.onLogin)();
										else args.onLogin();
									}
									_dialog.destroyRecursive();
								} else {
									var pos = res.indexOf('errormsg')
									res = res
									.substring(pos,pos+150)
									.replace(/errormsg[^>]*>([^<]*).*/, "$1")
									.split("\n")[0];
									dtopic.publish("notify/error", res);
								}
							
							})
						});
					}
				}
				_dialog.show();
				_dialog.resize({
					w: 660, 
					h: 400
				})
				_dialog.layout()
				return true;

			},
			_init: function() {
				//	called upon modules load / pane request, we wait with rendering 'till
				// this late to hide body untill fully ready to show contents
				// bootstrap sequence:
				//  hide everything, await domReady whilst loading kernel plus static messagebus.loading modules
				//  load application and its requirements
				ddom.byId('border').style.display="";
				parser.parse();
				this.layout = dregistry.byId('border');
				this._initialized = true;
			},
			paneRequested: function paneRequested(item){
				if(this.loading) return
				this.loading = true;
				traceLog(this,arguments)
				
				var url,
				self = this,
				modules = [],
				renderLayout = function() {
					if(modules && modules.length > 0) {
						require(modules, function(toprequire) {
							console.warn('================= RENDERING ==============');
							if(!self._initialized) self._init()
							if(url) 
								self.layout.addChild(self.contentpane = new djcontentpane(
									dlang.mixin(self.defaultPaneProps, {
										href: url
									}))
								);
							else {
								self._widgetInUse = new toprequire({
									region: 'center', 
									splitter:true
								})
								self.layout.addChild(self._widgetInUse);
								self.paneLoaded();
							}
						});
					}
				},
				yes=function() {
					dtopic.publish("notify/progress/loading");
					
					self._wPurge(self._dialog, true);
					self.unloadPane();
					console.warn('================== LOADING ===============');
					if(modules && modules.length > 0) {
						// detach and give a moment to clear registry
						setTimeout(renderLayout, 450);
					} else {
						if(!self._initialized) self._init();
						self.layout.addChild( self.contentpane = new djcontentpane(
							dlang.mixin(self.defaultPaneProps, {
								href: url
							}))
						);
					}
				};
				if(!this._widgetInUse || !this._widgetInUse.isDirty()) {
					dtopic.publish("notify/progress/loading");
				}
				if(item) this.action = typeof item == "string" ? item : this.menustore.getValue(item, "action");
				/* evaluate the action on item clicked */
				switch(this.action) {
					case 'setup':
						modules = ["OoCmS/config"];
						url = "views/setup.php?form=config";
						break;
					case 'template':
						//						url = "views/setup.php?form=templateconfig";
						url = "/oocms_demo/login.php";
						break;
					
					case 'page':
						modules = ["OoCmS/page"];
						//						url = "views/page.php?form=page";
						break;
					case 'pagetree':
						modules = 	["OoCmS/pagetree"];
						//						url = "views/page.php?form=pagetree";
						break;

					case 'productcategory':
						break;
					case 'product':
						modules = 	["OoCmS/product"];
						url = "views/product.php?form=product";
						break;
					case 'assets':
						modules =["OoCmS/assets"];
						//						url = "views/assets.php"
						break;

					default:
						url = "views/frontpage.php";
				}
				if(this._widgetInUse && this._widgetInUse.isDirty()) {
					dtopic.publish("notify/dirty/confirm", "Vigtigt!", "", [{
						id:'continueoption',
						label: 'Forlad nuværende',
						cb:yes
					},{
						id:'canceloption',
						label: 'Fortsæt redigering'
					}]);
				} else {
					yes();
				}
			//				self.layout();
			},
			paneLoaded: function paneLoaded() {
				traceLog(this,arguments)
				//				parser.parse(this.contentpane.domNode)
				var self = this, w = null, notLoggedIn = ddom.byId('iamloginpage');
				if(notLoggedIn != null) {
					notLoggedIn.parentNode.removeChild(notLoggedIn)
					self.showLoginDialog({
						scope: self, 
						onLogin: self.paneLoaded
					});
					return;
				}
				
				switch(this.action) {
					case 'setup':
						w = self._widgetInUse = new OoCmS.config();
						break;
					case 'template':
						w = self._widgetInUse = new OoCmS.templatecfg();
						break;
					case 'page':
						w = self._widgetInUse;
						if(self.urlPreloads.id && self.urlPreloads.id.length > 0)
							w.read(self.urlPreloads.id)
						else if(self.urlPreloads.presets && self.urlPreloads.presets.length > 0) {
							darray.forEach(self.urlPreloads.presets.split(","), function(kv) {
								w[kv.split(";")[0]]=kv.split(";")[1]
							})
							w.resetForm();
							w.updateUI();
						}
						break;
					case 'pagetree':
						//							w.ready.then(function() {
						//								// TODO phase out...
						//								self.fixWidget(w.getPageSelector())
						//								self.fixWidget(w.getResourceSelector())
						//								dtopic.publish("notify/progress/done");
						//							})
						break;

					case 'productcategory':
						break;
					case 'product':
						w = self._widgetInUse = new OoCmS.product();
						w.bindDom(ddom.byId('productformWrapper'));
						break;
					case 'assets':
						w = self._widgetInUse
						break;

					default:
						dtopic.publish("notify/progress/done");
				}
				//				
				//				switch(this.action) {
				//					case 'setup':
				//						require(["OoCmS/config"], function(ooconfig) {
				//							w = self._widgetInUse = new ooconfig();
				//						});
				//						break;
				//					case 'template':
				//						require(["OoCmS/templatecfg"], function(ootemplatecfg) {
				//							w = self._widgetInUse = new ootemplatecfg();
				//						})
				//						break;
				//					case 'page':
				//						console.log(OoCmS.page);
				//						require(["OoCmS/page"], function(oopage) {
				//							w = self._widgetInUse = new oopage();
				//							w.bindDom(ddom.byId('formWrapper'));
				//							w.ready.then(function() {
				//								if(self.urlPreloads.id && self.urlPreloads.id.length > 0)
				//									w.read(self.urlPreloads.id)
				//								else if(self.urlPreloads.presets && self.urlPreloads.presets.length > 0) {
				//									darray.forEach(self.urlPreloads.presets.split(","), function(kv) {
				//										w[kv.split(";")[0]]=kv.split(";")[1]
				//									})
				//									w.resetForm();
				//									w.updateUI();
				//								}
				//							});
				//						});
				//						break;
				//					case 'pagetree':
				//						require(["OoCmS/pagetree"], function(oopagetree) {
				//							w = self._widgetInUse = new oopagetree();
				//							w.bindDom(ddom.byId('pagetreeWrapper'));
				//						//							w.ready.then(function() {
				//						//								// TODO phase out...
				//						//								self.fixWidget(w.getPageSelector())
				//						//								self.fixWidget(w.getResourceSelector())
				//						//								dtopic.publish("notify/progress/done");
				//						//							})
				//						});
				//						break;
				//
				//					case 'productcategory':
				//						break;
				//					case 'product':
				//						require(["OoCmS/product"], function(ooproduct) {
				//							w = self._widgetInUse = new ooproduct();
				//							w.bindDom(ddom.byId('productformWrapper'));
				//						});
				//						break;
				//					case 'assets':
				//						require(["OoCmS/assets"], function(ooassets) {
				//							w = self._widgetInUse = new ooassets();
				//						});
				//						break;
				//
				//					default:
				//						dtopic.publish("notify/progress/done");
				//				}
				if(w && w.ready) {
					w.startup();
					w.ready.then(function() {
						dtopic.publish("notify/progress/done");
					})
				} else dtopic.publish("notify/progress/done");
				console.warn('================   READY   ===============');
				this.loading = false;
			}
		});
		return Controller;
	/* funky aliasing due to lack of scope in contentpane script evaluation (OoCmS.* == 3) */
	//window._oocms = OoCmS
	});
console.log('eval application.js');
