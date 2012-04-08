/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


//	Preview functionality(item)
//		resourcePreview(item)
//		documentPreview(item)
//		anyPreview(item)
//		showPreview(xhrResposne)
//		showHighlightPreview(xhrResponse)

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
				dojo.require("dojox.highlight");
				dojo.require("dojox.highlight.languages.css");
				dojo.require("dojox.highlight.languages.javascript");

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
						dojox.highlight.init(p);
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
							w = new dojox.widget.Dialog({
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
			},

			/*
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
		*/