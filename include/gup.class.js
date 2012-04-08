/*
 * 		TODO
 * Resume file upload from previous session?
 * Timeout handling
 * Return JSON status for server side error handling
 * Options for file namimg/renaming schemes
 * 
 */

GUP = function() {
	this.CHUNK_BYTES = 150000;
	
	this.isIE = google.gears.factory.getBuildInfo().indexOf(';ie') > -1;
	this.isFirefox = google.gears.factory.getBuildInfo().indexOf(';firefox') > -1;
	this.isSafari = google.gears.factory.getBuildInfo().indexOf(';safari') > -1;
	this.isNpapi = google.gears.factory.getBuildInfo().indexOf(';npapi') > -1;
	
	this.dropZone;
	this.desktop = google.gears.factory.create('beta.desktop');
	this.localServer = google.gears.factory.create('beta.localserver');
	this.gupStore = this.localServer.createStore('gupStorage');

	this.mayoverwrite = {};
	this.resumefileOffset = {};
	this.queue = new Array;
	this.index=0;
	this.processing=false;

	this.notifier = null;
	this.notifierEnabled = false;
	this.logindex = 0;
	this.getNotifyUI = function() {
		if(this.notifier == null) {
			try {
				this.notifier = google.gears.factory.create('beta.notifier', '1.0');
			} catch(e) {
				// fails, with unknown  object?
				document.getElementsByTagName('body')[0].appendChild(
					this.notifier = document.createElement('div'));
				with(this.notifier.style) {
					width="100%";height="54px";position = "absolute";bottom = "45px";left = "0";overflow="hidden";
					}
				var self = this;
				this.notifier.innerHTML = ("<table style=\"font-size:small;color:darkGrey\"><tbody id=\"console\"></tbody></table>")
				this.notifier.log = function(str) {
					var index = self.logindex++;
					var el = document.createElement('span');
					el.id = "logline_"+index;
					//el.appendChild(el = document.createElement('td'));
					el.innerHTML += "<br>"+this.serialize(str);
					document.getElementById('log').appendChild(el)
					dojo.fadeOut({
						node:"logline_"+index,
						duration:1400,
						onEnd:function() {this.node.style.display="none"}
					}).play(2400)
					setTimeout(function(){
						if(el.parentNode) el.parentNode.removeChild(el)
					}, 3000);
				}
				this.notifier.serialize = function(obj) {
					if(typeof(str) == "object") return obj.toString();
					else if(typeof(str) == "array") {
						var arr = obj;
						obj = "[ ";
						for(var i in arr)
							obj += arr[i]+(i!=arr.length-1?", ":" ")+"]";
					};
					return obj;
				}
			}
		}
		return this.notifier;
	}
	this.notify = function(title, text) {
		var n = this.getNotifyUI();
		if(n.toString().indexOf("Gears") != -1) {
			n.notify({
				application: "GearsUploader",
				title: title,
				description: text,
				priority: 2,
				sticky: 'True',
				password: 'Really Secure'
			});
		} else {
			n.log("<b>" +title+ "</b>: " + text);
		}
	}
	this.finishDrag = function(event, isDrop) {
		this.desktop.setDragCursor(event, 'copy');
		if (this.isFirefox) {
			if (isDrop) {
				event.stopPropagation();
				dojo.removeClass("dropZone", "hover");
			//				console.log("Event Stopped");
			}
		} else if (this.isIE || this.isSafari || this.isNpapi) {
			if (!isDrop) {
				event.returnValue = false;
			}
		}
		else {
			event.stopPropagation();
			return false;
		}
	};
	
	this.handleDragEnter = function(event) {
		dojo.addClass("dropZone","hover");
		this.finishDrag(event, false);
	};
	
	this.handleDragOver = function(event) {
		this.finishDrag(event, false);
	};
	
	this.handleDragLeave = function(event) {
		dojo.removeClass("dropZone", "hover");
	};
	
	this.sanitize = function(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	};
	
	this.fileFailed = function(reason) {
		var index = this.queue[0]['index'];

		this.notify("File aborted:"+"<font color=red><b>"+reason+"</b></font>");
		dojo.addClass("fileStatTr"+index,"failed");
		dojo.byId("fileStat"+index).innerHTML = "Failed";
		dojo.style("fileStatBar"+index, "width","100px");
		dojo.fadeOut({
			node:'fileStatTr'+index,
			duration:1400,
			onEnd:function() {this.node.style.display="none"}
		}).play(1500)
		this.mayoverwrite[this.queue[0]['title']] = null;
		this.resumefileOffset[this.queue[0]['title']] = null;
		this.queue.splice(0, 1);
		if(this.queue.length == 0) {
			if(document.getElementById('log').firstChild.tagName == "SPAN")
				setTimeout(function() {
					document.getElementById('log').innerHTML = "<font color=green>Standing by</font>";
				},1200);
		}
	}
	this.mayResume = function(filename) {
		return (typeof this.resumefileOffset[filename] != "undefined" && this.resumefileOffset[filename] != null)
	}
	this.mayOverwrite = function(filename) {
		return (typeof this.mayoverwrite[filename] != "undefined" && this.mayoverwrite[filename] != null);
	}
	
	this.sendChunk = function(filename, chunk, start, end, total) {
		var request = google.gears.factory.create('beta.httprequest');
		request.open('POST', 'upload.php');
		request.setRequestHeader('Content-Disposition', 'attachment; filename="' + filename + '"');
		request.setRequestHeader('Content-Type', 'application/octet-stream');
		if(this.mayOverwrite(filename))
			request.setRequestHeader('Content-Description', 'overwrite=true');
		else if(start != 0 && this.mayResume(filename))
			request.setRequestHeader('Content-Description', 'resume=true');
		request.setRequestHeader('Content-Range', 'bytes '+start+'-'+end+'/'+total);
	  
		var self = this;
		request.onreadystatechange = function() {
			if (request.readyState == 4) {
				//console.log(request.responseText);
				try {
					eval("var status = "+request.responseText+";");
				}catch(e) { 
					var status = {
						status : 102
					};
				}

				if(status['status'] == 100) {

					if(self.queue[0]['retryChunk'] > 0) self.queue[0]['retryChunk']=0;
					self.queue[0]['uploaded']+=1;
					
					var perc = Math.floor((end+1)/total*100);
					dojo.byId("fileStat"+self.queue[0]['index']).innerHTML = perc+"%";
					dojo.style("fileStatBar"+self.queue[0]['index'], "width",perc+"px");
					if(perc==100)  {
						//var fn = self.queue[0]['file'].name;
						//self.gupStore.remove('thumbnail_'+fn);
						//console.log(self.gupStore.isCaptured('thumbnail_'+fn), fn);
						dojo.fadeOut({
							node:'fileStatTr'+self.queue[0]['index'],
							duration:1400,
							onEnd:function() {this.node.style.display="none"}
						}).play();
						if(typeof status['thumb'] != "undefined")
							dojo.byId("thumbOut").innerHTML += '<div class="thumb"><a title="' +
							self.queue[0].title+ ' - klik for at anvende dette" href="javascript:useItem(\''+status['url']+'\')">'+
							'<img src="'+status['thumb']+'" /></a></div>';
						else {
							dojo.byId("thumbOut").innerHTML += ('<div class="thumb"><a  title="' +
								self.queue[0].title+ ' - klik for at anvende dette" href="javascript:useItem(\''+status['url']+'\')"><span class="'+
								self.getExtClass(self.queue[0].file.name, "Big")+'" ></span></a></div>');
						}
					}
				}
				else if(status['status'] == 101) {
					// resume? FIXME: still a TODO since chunk offsets are calculated with zero as base
					if(confirm("File exists and may be resumed, click 'yes' to continue, otherwise file will be overwritten")) {
						self.resumefileOffset[self.queue[0].title] = parseInt(status['offset']);
						var nBytes = self.queue[0]['bytes'] - parseInt(status['offset']);
						self.queue[0]['chunks'] = (nBytes > self.CHUNK_BYTES ? Math.ceil(nBytes/self.CHUNK_BYTES) : 1);
						self.queue[0]['uploaded'] = 0;

					} 
					else { 
						self.mayoverwrite[filename] = true; 
						self.processQueue();
						return;
					}
				}
				else if(status['status'] == 102) {
					if(self.queue[0]['retryChunk'] < 4) {
						self.queue[0]['retryChunk']++;
					//console.log("Chunk "+(self.queue[0]['uploaded']+1)+" / "+self.queue[0]['chunks']+" Failed to upload. Retry attempt #"+self.queue[0]['retryChunk']+".");
					}
					else {
						self.fileFailed("Upload failed after five retries. Server says: " + status.statusmessage);
					}
				}
				else if(status['status'] == 550) {
					if(confirm("File allready exists ("+filename+"), want to force overwrite?"))
						self.mayoverwrite[filename] = true;
					else self.fileFailed(status['statusmessage']);
				}
				else {
					self.fileFailed(status['statusmessage']);
				}
				
				self.processQueue();
			}
		};
		request.send( chunk );
	};
	
	this.processQueue = function() {
		this.processing=true;
		if(this.queue.length > 0) {
			var f=this.queue[0];
			if(f['uploaded'] == f['chunks']) {
				//console.log(f['title']+" has been uploaded.");
				this.mayoverwrite[f['title']] = null;
				this.resumefileOffset[f['title']] = null;
				this.queue.splice(0, 1);
				document.getElementById('log').innerHTML = "";
				//				console.log("Spliced, Length: "+this.queue.length);
				this.processQueue();
				return;
			}
			else {
				if(f['uploaded'] == 0) {
					document.getElementById('log').innerHTML = "Uploading <font color=\"green\">" + f['title'] + " </font>("+(this.queue.length-1)+" queued)";
					var thumb = this.createThumbnail(f['file']);
					if(thumb != false) 
						dojo.byId("fileStatThumb"+f['index']).innerHTML = ("<img src=\""+thumb+"\" alt=\"\" />");
					else 
						dojo.byId("fileStatThumb"+f['index']).innerHTML = ("<span class=\""+this.getExtClass(f['file'].name, "Small")+"\" ></span>")
					if(!this.mayResume(f['title'])) this.resumefileOffset[f['title']] = 0;
				}
				var skew = parseInt(this.resumefileOffset[f['title']]) || 0;
				var start = 0 + skew;

				var offset = this.CHUNK_BYTES;
				if(f['uploaded'] > 0) start = f['uploaded']*this.CHUNK_BYTES + skew; 
				var end = start+this.CHUNK_BYTES-1;

				if(end > f['bytes']) end = f['bytes']-1;
								
				//console.log("Sending: ",start,end,f['bytes']);
				if( (start+offset) > (f['bytes']-1) ) offset = (f['bytes']-start);
				var thischunk = f['file'].blob.slice(start,offset);
				this.sendChunk(f['title'],thischunk,start,end,f['bytes']);
			}
		}
		else {
			//console.log("All done!");
			dojo.byId('log').innerHTML = "<font color=green>Standing by</font>";
			this.processing=false;
		}
	};
	this.getExtClass = function(filename, size) {
		var prefix = "icon"+size;
		return  prefix + " " + prefix + "-" + filename.substring(filename.lastIndexOf('.')+1);
	}
	this.createThumbnail = function(file) {
		try {
			var md = this.desktop.extractMetaData(file.blob);
			var gearsCanvas = google.gears.factory.create('beta.canvas');
			var thumb_height = md.imageHeight;
			var thumb_width = md.imageWidth;
			gearsCanvas.decode(file.blob);

			if(thumb_height >50) {
				thumb_height=50;
				thumb_width = Math.floor(50*md.imageWidth/md.imageHeight);
			}
			if(thumb_width > 200) {
				thumb_width=200;
				thumb_height=Math.floor(md.imageHeight*200/md.imageWidth);
			}
	     
			gearsCanvas.resize(thumb_width, thumb_height, 'nearest');
			var thumbnailBlob = gearsCanvas.encode();
			var thumbName = 'thumbnail_'+file.name;
			this.gupStore.captureBlob(thumbnailBlob, thumbName);
			return thumbName;
		} catch (err) {
			// probably not an image, use css
			return false;
		}
	};
	
	this.handleDrop = function(event) {
		var data = this.desktop.getDragData(event, 'application/x-gears-files');
		var files = data && data.files;
	
		if (files) {
			for (i = 0; i < files.length; i++) {
				var file = files[i];
				var cont = true;
				console.log(file.name.substring(file.name.lastIndexOf(".")+1))
				if(typeof allowInfo == "string") {
					cont = RegExp(file.name.substring(file.name.lastIndexOf(".")+1)).test(allowInfo);
				}
				if(!cont) {
					this.notify("Filetype", "<font color=red><b>"+file.name + " not accepted</b></font>");
				} else {
					var chunks = (file.blob.length > this.CHUNK_BYTES) ? Math.ceil(file.blob.length/this.CHUNK_BYTES) : 1;
					this.queue[this.queue.length] = {
						"title": this.sanitize(file.name),
						"file": file,
						"bytes": file.blob.length,
						"chunks": chunks,
						"uploaded": 0,
						"index": this.index,
						"retryFile": 0,
						"retryChunk": 0
					};
				
					//				var thumbName = this.createThumbnail(file);
				
					dojo.byId("uploadStatus").innerHTML += "<tr id='fileStatTr"+this.index+"'>" +
					"<td valign=\"top\" id='fileStatThumb"+this.index+"'></td>"+
					"<td class=\ipad\" valign=\"top\">"+this.sanitize(file.name)+"</td><td class=\ipad\" valign=\"top\"><span id='fileStat"+this.index+"'>0%</span></td><td class=\ipad\" valign=\"top\"><div class='fileStatBarWrapper'> <div id='fileStatBar"+this.index+"' class='fileStatBar'></div></div></td>" +
					"</tr>";
				
					this.index++;
				}
			}
		}
		this.finishDrag(event, true);
		if(this.processing == false) this.processQueue();
		return false;
	};
	
	this.setup = function(dropZone) {
	var gobj = this;
	this.dropZone = document.getElementById(dropZone);
	if (this.isFirefox) {
	this.dropZone.addEventListener('dragenter', function(e){
		gobj.handleDragEnter(e);
		}, false);
	this.dropZone.addEventListener('dragover',  function(e){
		gobj.handleDragOver(e);
		},  false);
	this.dropZone.addEventListener('dragexit',  function(e){
		gobj.handleDragLeave(e);
		}, false);
	this.dropZone.addEventListener('dragdrop',  function(e){
		gobj.handleDrop(e);
		},      false);
	} else if (this.isIE) {
	this.dropZone.attachEvent('ondragenter', function(e){
		gobj.handleDragEnter(e);
		});
	this.dropZone.attachEvent('ondragover',  function(e){
		gobj.handleDragOver(e);
		} );
	this.dropZone.attachEvent('ondragleave', function(e){
		gobj.handleDragLeave(e);
		});
	this.dropZone.attachEvent('ondrop',      function(e){
		gobj.handleDrop(e);
		}     );
	} else if (this.isSafari || this.isNpapi) {
	this.dropZone.addEventListener('dragenter', function(e){
		gobj.handleDragEnter(e);
		}, false);
	this.dropZone.addEventListener('dragover',  function(e){
		gobj.handleDragOver(e);
		},  false);
	this.dropZone.addEventListener('dragleave', function(e){
		gobj.handleDragLeave(e);
		}, false);
	this.dropZone.addEventListener('drop',      function(e){
		gobj.handleDrop(e);
		},      false);
	}
	};
	};

