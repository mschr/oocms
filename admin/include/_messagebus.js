define(["dojo/_base/declare",
	"dojo/dom-style",
	"dojo/_base/array",
	"dojo/_base/connect",
	"dojo/topic",
	"dojo/fx",
	"dijit/ProgressBar",
	"dijit/DialogUnderlay"
	
	/**
 * bootstrap bit of the messagebus, supplies an underlay with progressbar api
 */
	], function(declare, domstyle, darray, dconnect, dtopic, dfx, progressbar, djdialogunderlay) {
		var ajax_notification_templateString = '<div><div class="ajax_notification">\
<div class="ajax_notification_title" data-dojo-attach-point="titleNode"></div>\
<div style="height: 30px" class="dijitProgressBar dijitProgressBarEmpty" role="progressbar">\
<div style="height: 60px" data-dojo-attach-point="internalProgress" class="dijitProgressBarFull">\
<div class="dijitProgressBarTile" role="presentation">\
</div><span style="visibility:hidden">&#160;</spa>></div>\
<div data-dojo-attach-point="labelNode" class="dijitProgressBarLabel" id="${id}_label"></div>\
<img data-dojo-attach-point="indeterminateHighContrastImage" \
class="dijitProgressBarIndeterminateHighContrastImage" alt=""\
/></div></div></div>';
		var MessageBus = declare("OoCmS._messagebus", [], {
			
			handles: [],
			hasSubscribed: false,
			
			constructor: function(args) {
				if(args) for(var i in args) 
					if(args.hasOwnProperty(i)) this[i] = args[i];
				this.subscribe();
			},
			destroy: function() {
					darray.forEach(this.handles, dconnect.disconnect)
			},			
			subscribe: function subscribe() {
	
				if(this.hasSubscribed) return;
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/progress/done", function() {
					MessageBus.loading(false);
				})
				this.handles[this.handles.length] = 
				dtopic.subscribe("notify/progress/loading", function(params) {
					MessageBus.loading(params || {
						indeterminate: true
					});
				});

			}
		});
		MessageBus.isLoading = function() {
			return (MessageBus._progressmeter
				&& MessageBus._progressmeter.domNode 
				&& MessageBus._progressmeter.domNode.isShowing)
		}
		MessageBus.underlayAttrs = {};
		MessageBus.loading = function loading(params) {
			if(params != false && !MessageBus._progressmeter) {
				MessageBus._progressmeter = new progressbar({
					templateString : ajax_notification_templateString,
					id: 'loading_parent'
				});
				MessageBus.underlayAttrs = {
					dialogId: MessageBus._progressmeter.id,
					"class": "OoCmS.messagebus_underlay"
				};
				var underlay = dijit._underlay;
				if(!underlay || underlay._destroyed){
					underlay = dijit._underlay = new djdialogunderlay(MessageBus.underlayAttrs);
				}else{
					underlay.set(MessageBus.underlayAttrs);
				}
				MessageBus._progressmeter.show = {
					play: function() {
						var zIndex = 104;
						domstyle.set(dijit._underlay.domNode,"zIndex",zIndex -1);
						domstyle.set(MessageBus._progressmeter.domNode, {
							display:'block',
							opacity: '1',
							zIndex: zIndex
						})
						underlay.show();
						MessageBus._progressmeter.domNode.isShowing = true;
					}
				};
				MessageBus._progressmeter.startup();
				MessageBus._progressmeter.hide = dfx.combine(
					[
					dojo.fadeOut({
						node:MessageBus._progressmeter.domNode,
						duration:1800,
						onEnd: function() {
							MessageBus._progressmeter.domNode.style.display='none';
							MessageBus._progressmeter.domNode.isShowing = false;
						}
					}),
					dojo.fadeOut({
						node: dijit._underlay.domNode,
						duration: 1800,
						onEnd: function() {
							dijit._underlay.hide();
							domstyle.set(dijit._underlay.domNode, "opacity", "")
						}
					})
					]);
				MessageBus._progressmeter.placeAt(dojo.body());
			}
			if(typeof params == "boolean" && params == false) {
				MessageBus._progressmeter.update({
					maximum: 1, 
					progress:1
				});
				if(MessageBus._progressmeter.domNode.isShowing) 
					MessageBus._progressmeter.hide.play(250);
			} else {
				if(!MessageBus._progressmeter.domNode.isShowing) 
					MessageBus._progressmeter.show.play();
				MessageBus._progressmeter.titleNode.innerHTML= (params.title) ? params.title : "Indlæser, vent et øjeblik...";
				MessageBus._progressmeter.set("maximum", (params.maximum) ? params.maximum : MessageBus._progressmeter.maximum);
				MessageBus._progressmeter.set("value", (params.indeterminate) ? Infinity : params.progress);
			//this._progressmeter.update(params);
			}
		}
		return MessageBus;
	});