define(["dojo/_base/declare",
	"dojo/_base/array",
	"dojo/_base/lang",
	"dojo/dom",
	"dojo/dom-style",
	"dojo/topic",
	"dijit/registry",
	"dijit/Dialog",
	"dijit/form/Button",
	"OoCmS/_messagebus"
	

	], function(declare, array, lang, dom, domstyle, dtopic, registry, dialog, button, MessageBusBase) {
		var MessageBus = declare("OoCmS.messagebus", [MessageBusBase], {
			_dialog: undefined,
			constructor: function(args) {
				this.subscribe();
			},
			destroy: function() {
				if(this._dialog) this._dialog.destroyRecursive();
				this.inherited(arguments);
			},
			/**
		 * presents a dialog given a title, contents and an array of button objects
		 * {String} title: 'headline' in dialog
		 * {String} szHtml: contents-html, may contain button.id's as contents are evaluated prior to creating buttons
		 * {Mixed} oButtonCB: an array of hashes, each describing how to instantiate the given button. Structure follows
		 * 
		 * In order for placing buttons and hooking them up with the cleanup routines upon closing dialog,
		 * plant a <span id=** /> on its place and define in the button-array
		 * 
		 * example buttonCB: [{
		 *    id: 'customAccept',												// required
		 *    classes: 'mybutton-class button-accept',					// optional
		 *    cb: function(evt) { this.mObjCallable(evt.target); }, // optional
		 *    scope: mObj															// optional
		 * },{
		 *    id: 'customClose',												// required
		 *		label: 'i must destroy myself to clean memory'			// optional
		 *    classes: 'mybutton-class button-close',					// optional
		 *    cb: funcObj															// optional
		 * },{
		 *		id: 'customWithLabel',
		 *		label: 'i will destroy() on close (since no callback)'
		 * }]
		 */
			notify: function(title, szHtml, /* i.e. [{ id:'s1', cb:'s2', classes:'cls' }] */ oButtonCB) {
				console.info(traceLog(this,arguments));
				var self = this
				//make sure no leftover widgets reside
				this._wPurge(this._dialog, true);
				//create new attachment
				this._dialog = new dialog({
					title: title,
					content: szHtml,
					id:'notifyDialog',
					onCancel : function () {
						array.forEach(oButtonCB, function(b) {
							self._wPurge(b.id)
						})
					},
					onSubmit : function () {
						array.forEach(oButtonCB, function(b) {
							self._wPurge(b.id)
						})
					},
					onExecute : function () {
						array.forEach(oButtonCB, function(b) {
							self._wPurge(b.id)
						})
					}
				});
				
				this._dialog.startup();
				this._dialog.show();
				this._notifyCreateButtons(oButtonCB)
				return this._dialog;
			},
			error: function(msg, duration) {
				var node = dom.byId('appErrorDiv');
				
				dojo.animateProperty({
					node: node,
					onBegin: function() {
						node.innerHTML = msg;
						domstyle.set(node, {
							height : "0",
							opacity: 0
						});
					},
					onEnd: function() {
						dojo.animateProperty({
							node: node,
							properties: {
								height: 0, 
								opacity: 0
							}
						}).play((duration ? duration : 2000))

					},
					properties: {
						height: domstyle.get(node, "line-height"), 
						opacity: 1
					}
				}).play()
			},
			_wPurge: function(b, recurse) {
				b=(typeof b == "string" ? registry.byId(b.id) : b);
				if(b) {
					if(recurse)b.destroyRecursive();
					else b.destroy();
				}
			},
			_notifyCreateButtons : function(oButtonCB) {
				var self = this;				
				array.forEach(oButtonCB, function(_button) {
					
					var node = dom.byId(_button.id);
					if(!node && console && console.debug) {
						
						console.debug("Warning, button[id="+_button.id+",cb="+_button.cb+"] could not be initialized in notifyDialog");
						
					} else {
						self._wPurge(_button.id);
						var b = new button({
							iconClass: _button.classes || undefined,
							onClick: function() {
								if(typeof(_button.cb) == 'function')
									lang.hitch((_button.scope?_button.scope:self._dialog), _button.cb)();
								array.forEach(oButtonCB, function(b) {
									self._wPurge(b.id)
								});
								self._dialog.hide();

							}
						},node)
						b.startup();
						if(_button.label) {
							b.setLabel(_button.label);
						}
					}
				}) // each
			},
			
			subscribe: function subscribe() {
				if(this.hasSubscribed) return;
				var mbus = this;
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/save/success", function(title, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['canceloption']
					mbus.notify(title,MessageBus.notifyTemplates.saveSuccess, buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/save/error", function(title, resReplace, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['canceloption', 'saveoption']
					if(typeof resReplace != "string") resReplace = "";
					mbus.notify(title, MessageBus.notifyTemplates.saveErr.replace("{RESPONSE}", resReplace), buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/delete/confirm", function(title, resReplace, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['continueoption', 'canceloption']
					if(typeof resReplace != "string") resReplace = "";
					mbus.notify(title, MessageBus.notifyTemplates.confirmDelete.replace("{RESPONSE}", resReplace), buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/delete/error", function(title, resReplace, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['canceloption']
					if(typeof resReplace != "string") resReplace = "";
					mbus.notify(title, MessageBus.notifyTemplates.deleteErr.replace("{RESPONSE}", resReplace), buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/delete/success", function(title, resReplace, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['canceloption']
					if(typeof resReplace != "string") resReplace = "";
					mbus.notify(title, MessageBus.notifyTemplates.deleteErr.replace("{RESPONSE}", resReplace), buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/dirty/confirm", function(title, resReplace, buttonObjArray){
					if(MessageBus.isLoading()) MessageBus.loading(false);
					// buttonids : ['continueoption', 'canceloption']
					if(typeof resReplace != "string") resReplace = "";
					mbus.notify(title, MessageBus.notifyTemplates.confirmCancel.replace("{RESPONSE}", resReplace), buttonObjArray);
				});
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/error", function(shortMsg,optionalDuration) {
					mbus.error(shortMsg, optionalDuration);
				});
				this.inherited(arguments);
				this.hasSubscribed = true;
			}
		});
		MessageBus.notifyTemplates = {
			confirmCancel: "<div class=\"popup-notify\"><h4>Ændringer vil gå tabt, sikker?</h4>" +
			"<p class=\"popup-notify\">De seneste ændringer vil gå tabt hvis du vælger at annullére, og kan på ingen måde genskabes.<br /><br />"+
			"Du kan vælge at <i>Rediger videre</i> og i stedet lagre dette som kladde<br><b>NB</b>, kladder er offentlig utilgængeligt<br /><br />{RESPONSE}<br />" +
			"</p><div class=\"popup-buttonbar\">"+
			"<span id=\"continueoption\">Rediger videre</span>"+
			"<span id=\"canceloption\">Afslut</span>"+
			"</div></div>",
			saveErr:"<div class=\"popup-error\"><h4>Kan ikke gemme dokument</h4>" +
			"<p class=\"popup-notify\">Dokumentet er endnu ikke oprettet i databasen eller én af følgende parametre er ikke sat:<br/>"+
			"&nbsp;&nbsp;&nbsp;(<b>titel</b>,<b>indhold</b>,<b>id</b>)<br/><br/>"+
			"Systemet returnerede følgende fejl:<br><font color=red>{RESPONSE}</font>"+
			"<div class=\"popup-buttonbar\">"+
			"<span id=\"canceloption\">Rediger videre</span>"+
			"<span id=\"saveoption\">Forsøg igen</span>"+
			"</div></div>",
			saveSuccess : "<div class=\"popup-notify\"><h4>Ændringer gemt</h4>" +
			"<p class=\"popup-notify\">Dine ændringer til dokumentet er udført!<br/>"+
			"<div class=\"popup-buttonbar\">"+
			"<span id=\"canceloption\">Gå tilbage</span>"+
			"</div></div>",
			confirmDelete: "<div class=\"popup-notify\"><h4>Elementet vil være fuldstændigt tabt, sikker?</h4>" +
			"<p class=\"popup-notify\">Det er ikke muligt at gå tilbage efter en sletning, indholdet kan ikke genskabes.<br /><br />"+
			"Du kan vælge at <i>annulér</i> og i stedet lagre dette som kladde<br><b>NB</b>, kladder er ikke offentlig tilgængelige<br /><br />{RESPONSE}<br />" +
			"</p><div class=\"popup-buttonbar\">"+
			"<span id=\"continueoption\">Slet!</span>"+
			"<span id=\"canceloption\">Annulér</span>"+
			"</div></div>",
			deleteErr : "<div class=\"popup-error\"><h4>Der er sket en fejl</h4>" +
			"<p class=\"popup-notify\">Der opstod en fejl...<br/>{RESPONSE}"+
			"<div class=\"popup-buttonbar\">"+
			"<span id=\"canceloption\">Gå tilbage</span>"+
			"</div></div>",
			deleteSuccess : "<div class=\"popup-notify\"><h4>Ændringer gemt</h4>" +
		"<p class=\"popup-notify\">Dokumentet er slettet fra databasen.<br/>"+
		"Systemet returnerede følgende:<br><font color=red>{RESPONSE}</font>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Gå tilbage</span>"+
		"</div></div>"
		}
		
		MessageBus.isLoading = MessageBusBase.isLoading;
		MessageBus.loading = MessageBusBase.loading
		return MessageBus;
	});
console.log('eval messagebus.js');