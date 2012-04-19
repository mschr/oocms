define(["dojo/_base/declare",
	"dojo/data/ItemFileWriteStore",
	"dojo/_base/lang",
	"dojo/_base/xhr",
	"dojo/topic",
	], function(declare, ditemfilewritestore, dlang, dxhr, dtopic) {
		
		// implements a custom write api / _savecustom
		// pass an api parameter for how to handle saves, urls accepts {itemkey}
		// example api:
		// api: {
		//   create: {
		//     url: 'save.php?CreateItem'
		//     getContents: function(item) {
		//       return { id: item.id, action:'new'}
		//     }
		//   },
		//   update: {
		//     url: 'save.php?UpdateItem&id=${id}'
		//     getContents: function(item) { ... }
		//   },
		//   del : { ... }
		return declare("OoCmS._storeAPI", [ditemfilewritestore], {
			urlPreventCache:true,
			clearOnClose: true,
			constructor: function(args) {
				args = args || {}
				dlang.mixin(this, args);
				if(this.api && this.api.read && this.api.read.url)
					args.url = this.url = this.api.read.url;
				this.inherited(arguments)
			},
			_saveCustom: function(saveCompleteCallback,saveFailedCallback) {
				traceLog(this,arguments)
				var item, i,
				batches = 0, progress = 0;
				// count pending requests
				for(i in this._pending._deletedItems)
					if(this._pending._deletedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._modifiedItems)
					if(this._pending._modifiedItems.hasOwnProperty(i))batches++;
				for(i in this._pending._newItems)
					if(this._pending._newItems.hasOwnProperty(i)) batches++;
				dtopic.publish("notify/progress/loading", {
					maximum:batches, 
					progress:0
				});
				for(i in this._pending._deletedItems) {
					if(this._pending._deletedItems.hasOwnProperty(i)) {
						item = this._getItemByIdentity(i);
						dxhr.post({
							url: dlang.replace(this.api.del.url, item),
							content: typeof this.api.del.getContents == "function" 
							? this.api.del.getContents(item) : this.api.del.getContents,
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
							url: dlang.replace(this.api.update.url, item),
							content: typeof this.api.update.getContents == "function" 
							? this.api.update.getContents(item) : this.api.update.getContents,
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
						item = this._getItemByIdentity(i);
						dxhr.post({
							url: dlang.replace(this.api.create.url, item),
							content: typeof this.api.create.getContents == "function" 
							? this.api.create.getContents(item) : this.api.create.getContents,
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
				saveCompleteCallback();
			}
		})
	})