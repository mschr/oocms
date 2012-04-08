function submitDocument(formId, reload) {
	var theForm = dojo.byId(formId);
console.log('submitting ' , eval('(' +dojo.formToJson(theForm) +')'));
	var ed = tinyMCE.get('DocumentBody');
	var reload = !/[0-9]+/.test(gDocument.id);
	if(ed) {
		ed.setProgressState(1); // Show progress
		if(formId == 'document_form' && theForm.elements['body']) {
			theForm.elements['body'].value = ed.getContent();
		}
	}
	dojo.xhrPost({
		url: theForm.action + (reload ? gDocument.id : ""),
		content : dojo.formToObject(theForm),
		load : function(res) {
			if(res.indexOf("SAVED") == -1) {
				if (ed) ed.setProgressState(0);
				openNotify("Fejl!", _ExtendHTML.notifyTemplates.saveErr.replace("{RESPONSE}", res), [ {
					id:'saveoption',
					cb:function() {
						submitDocument(formId, true);
					},
					classes:'dijitEditorIcon dijitEditorIconSave'
				}, {
					id:'canceloption',
					classes:'dijitEditorIcon dijitEditorIconUndo'
				}
				]);
				return;
			}
			if(ed) ed.setProgressState(0);
			var id = res.replace(/.*ID=\"/, "").replace(/\".*/, "");
			if(reload)
				setTimeout(function() {
					location = document.location.href.replace(/&preset[^&]*/,"")+"&id="+id;
				}, 1500);
			gDocument.id = ((gDocument.id=="") ? id : gDocument.id);
			gDocument.saveState = new Date();
			gDocument.savedLength = theForm.elements['body'].value.length;
			updateUI(gDocument);

		}
	});
	return false;
}

function submitPartial(id, parameters, reload, onSuccess) {
	parameters = dojo.mixin(parameters, { partial: 'true' });
console.log('submitting ' , parameters);		
	dojo.xhrPost({
		url: gPage.baseURI+'/admin/save.php?EditDoc&id='+id,
		content : parameters,
		load : function(res) {
			if(res.indexOf("|SAVED") == -1) {
				openNotify("Fejl!", _ExtendHTML.notifyTemplates.saveErr.replace("{RESPONSE}", res), [ {
					id:'saveoption',
					cb:function() {
						submitPartial(id, formId, reload);
					},
					classes:'dijitEditorIcon dijitEditorIconSave'
				}, {
					id:'canceloption',
					classes:'dijitEditorIcon dijitEditorIconUndo'
				}
				]);
			} else {
				openNotify("Success!", _ExtendHTML.notifyTemplates.saveSuccess, [ {
					id:'canceloption',
					classes:'dijitEditorIcon dijitEditorIconUndo',
					cb:function() { 
						if(reload) location = location;
						if(typeof onSuccess == "function") onSuccess(res); 
					}
				}
				]);
			}
		}
	});
}
function selectForm(formName) {
	var base = document.location.href.replace(/\/admin\/.*/, "/admin/");
	if(/(edit\.php)/.test(document.location.href))
	{
		document.location = base + "edit.php?EditDoc&type=" + formName;
	} else {

		if(formName == 'new') {
			document.location = base + "edit.php";
		} else if(formName == 'admin') {
			document.location = base + "admin.php?TreeView";
		} else if(formName == 'crontab') {
			document.location = base + "admin.php?Crontab";
		} else if(formName == 'fileadmin') {
			document.location = base + "edit.php?EditDoc&type=upload";//admin.php?FileAdmin
		}
	}
}
function updateForm() {
	var f = document.forms['document_form'];
	f.lastedited = gDocument.lastmodified;
	f.editors = gDocument.editors;
	f.attach_id = gDocument.attachId;
	f.creator = gDocument.created;
	f.isdraft = gDocument.isdraft;
	f.lasteditedby = gDocument.lasteditedby;
	f.form = gDocument.type;
}
function getType() {
	if(gDocument.type != "") return gDocument.type;
	var s = document.location.search
	if(/type=[a-zA-Z0-9]/.test(s)) {
		var b, e;
		b = s.indexOf("type=");
		if(b == -1) return null;
		e = s.indexOf('&', b);
		if(e == -1)
			e = s.length;
		return s.substring(b + 5, e);
	}
	return false;
}
function unloadPage() {
	var clen = tinyMCE.activeEditor.getContent().length;
	var confirmit = "Du navigerer væk fra en editor-session som har ændret dokumentets indhold, " +
		"ønsker du at gemme disse ændringer før du fortsætter?";
	if(typeof(gDocument.saveState) == 'object' && gDocument.savedLength != clen) {
		if(confirm(confirmit)) {
			jumpStep("save");
		}
	} else if(clen > 0 && gDocument.id == "") {
		if(confirm(confirmit)) {
			gDocument.id = "999";
			submitDocument("document_form");
		}
	} else if(gDocument.savedLength != clen && gDocument.id != "") {
		if(confirm(confirmit)) {
			jumpStep("save");
		}
	}
}
function updateUI() {
	try {
		var t = getType();
		//console.log("get"+gPage.baseURI+"/openView/"+(t == "include" ? "Resources":"Documents")+".php"+ "?format=json&type="+t+"&searchid="+gDocument.id);
		dojo.xhrGet({
			url:gPage.baseURI+"/openView/"+(t == "include" ? "Resources":"Documents")+".php"+
			"?format=json&type="+t+"&searchid="+gDocument.id,
			load: function(res){
				var js = eval('('+res+')');
				gDocument = js.items[0];
				//for(var key in gDocument) {
				//	console.log(key+"=>"+gDocument[key])
				//}
				updateForm();
				if(gDocument.type == "page") {
					// nSubDocuments

					dojo.xhrGet({
						url:gPage.baseURI+'/openView/Documents.php?format=json&searchid='+gDocument.id,
						load:function(res) {
							var js = eval('('+res+')'); dojo.forEach(js.items, function(it) {
								if(it.id==gDocument.id) {
									dojo.byId('nSubPages').innerHTML = it.children.length;
								}
							});
						}
					});
					// nResources
					dojo.xhrGet({
						url:gPage.baseURI+'/openView/Resources.php?format=json&searchdoc='+gDocument.id,
						load:function(res) {
							var js = eval('('+res+')'), children=0;
							var resourceList = [];
							dojo.forEach(js.items, function(it) {
								if(RegExp(","+gDocument.id+",").test(","+it.attachId+","))
								{
									children++;
								}
								resourceList.push(it.id);
							});
							dojo.byId('nResources').innerHTML = children;
							resourceList = "," + resourceList.join(",") + ",";
							dojo.query("tbody [id*='resource_']", dojo.byId('actionCol')).forEach(function(body) {
								if(!RegExp(","+body.id.replace(/.*_/, "")+",").test(resourceList))
									body.parentNode.removeChild(body);
							});
						}
					});
				
				}
				try {dojo.byId('onfly-attachId').innerHTML = gDocument.attachId;}catch(squelzch){}
				dojo.byId('onfly-isdraft').innerHTML = (gDocument.isdraft=="0"?"Publiceret":"Kladde");
				dojo.byId("onfly-lastmodified").innerHTML = gDocument.lastmodified.replace(/\ \+.*/, "");
				dojo.byId("onfly-creator").innerHTML = gDocument.creator;
				dojo.byId("onfly-created").innerHTML = gDocument.created.substring(0,gDocument.created.indexOf('+'));
			
			}
		});
	}catch(e) {
		console.log(e)
	}
}
function jumpStep(action) {
	switch(action) {
		case 'split_attach':
		case 'attach':
			var resources = /type=include/.test(document.location.search);
			var attachTo = null;
			var url = gPage.baseURI+"/openView/Documents.php?format=json"+(!resources?"":"&type=page")
			tree = new TreeBuilder()
			var dialog = openNotify("Tilknyt til en side", _ExtendHTML.notifyTemplates.selectSubPage, [ 
			{
				id:'canceloption',
				classes:'dijitEditorIcon dijitEditorIconUndo',
				cb : function() {
					tree.destroy();
				}
			},{
				id:'continueoption',
				classes:'dijitEditorIcon dijitEditorIconRedo',
				cb:function() {
					if(resources) {
						if(gDocument.attachId != "")
							for(var i = 0, ids = gDocument.attachId.split(","); i < ids.length; i++)
								if(ids[i] == attachTo)
									var exists = true;

						if(!exists) {
							if(typeof ids == "object")
								ids.push(attachTo)
							else
								ids = [attachTo];
							ids = ids.join(",")
						}
					}
					gDocument.attachId = (resources?ids:attachTo);
					document.forms[0].attach_id.value = (resources?ids:attachTo);
				}
			}
			]);
			tree.init(url,"selectDocumentTreeNode", {type:"page"})
			tree.load(
				function(){
				},
				function(item){
					attachTo = tree.self.store.getValue(item, "id")
				}
			);

			break;

		case 'cancel':
		case 'cancel_all':
			
			var when = gDocument.saveState;
			var replaceString = "";
			if(typeof(gDocument.saveState) == 'object') {
				replaceString = "Ved annullering mistes de midlertigt gemte ændringer. Dokumentet gemmes som det var, da denne editor-session startedes.  Der er lagret en revision " + when.asString() + " som vil blive slettet permanent!";
			}

			var szHtml = _ExtendHTML.notifyTemplates.confirmCancel.replace("{RESPONSE}", replaceString);
			openNotify("Annullér alt", szHtml,[ {
				id:'canceloption',
				classes:'dijitEditorIcon dijitEditorIconSave'
			},{
				id:'continueoption',
				classes:'dijitEditorIcon dijitEditorIconUndo',
				cb:function() {
					submitDocument("resetstate_form", true);
					var url = gPage.baseUrl, id = null;
					if(typeof gDocument.type != "undefined")  {
						id = (gDocument.type == "page" ? gDocument.id : (gDocument.attachId != "") ? gDocument.attachId.replace(",.*","") : id);
					}
					document.location.href = url + (id!=null) ? "?OpenDoc&id="+id:"";
				}
			}
			]);
			break;
		case 'save':
			submitDocument("document_form");
			break;
		case 'draft':
			dojo.byId('document_form').elements['isdraft'].value = '1';
			jumpStep('save');
			break;
		case 'publish':
			dojo.byId('document_form').elements['isdraft'].value = '';
			jumpStep('save');
			break;
		default:
			alert('jumpStep expects valid parameter...');


	}
}
function attachResource() {
	if(gDocument.id == ""){
		openNotify("Fejl!", _ExtendHTML.notifyTemplates.resourceErr, [ {
			id:'saveoption',
			cb:function() {
				submitDocument('document_form', true);
			},
			classes:'dijitEditorIcon dijitEditorIconSave'
		}, {
			id:'canceloption',
			classes:'dijitEditorIcon dijitEditorIconUndo'
		}
		]);
		addResource('script');
		addResource('style');
	}
	var treeMedia, treeResource;
	var attachTo = null;
	var url = gPage.baseURI+"/openView/Resources.php?format=json"

	var dialog = openNotify("Tilknyt ('link') medier / ressourcer", _ExtendHTML.notifyTemplates.selectResource, [ 
	{
		id:'canceloption',
		classes:'dijitEditorIcon dijitEditorIconUndo',
		cb : function() {
			treeMedia.destroy();
			treeResource.destroy();
		}
	},{
		id:'continueoption',
		classes:'dijitEditorIcon dijitEditorIconRedo',
		cb:function() {
				var ids = attachTo._S.getValue(attachTo, 'attachId').split(',');
				for(var i = 0; i < ids.length; i++)
						if(ids[i] == gDocument.id)
							var exists = true;
				if(!exists) {
					if(typeof ids == "object")
						ids.push(gDocument.id)
					else
						ids = [gDocument.id];
					ids = ids.join(",")
					submitPartial(attachTo._S.getValue(attachTo, 'id'), {
						form: ( /html/.test(attachTo._S.getValue(attachTo, 'relation')) ? 'media' : 'include'), 
						editResource:1,
						attachId:ids
					}, true);
				}
		}
	}
	]);
	treeMedia = new TreeBuilder();
	treeMedia.init(url,"selectMediaTreeNode", {type:"media"})
	treeMedia.load(
		function(){
			setTimeout(function() {
				treeMedia.self.tree.rootNode.getChildren().forEach(function(node) {
					node.labelNode.setAttribute("title", node.item.relation);
				});
			},1500);
		},
		function(item){
			treeMedia.self.tree.rootNode.setSelected();
			attachTo = item;
		}
	);
	treeResource = new TreeBuilder();
	treeResource.init(url,"selectResourceTreeNode", {type:"include"})
	treeResource.load(
		function(){
			setTimeout(function() {
				treeResource.self.tree.rootNode.getChildren().forEach(function(node) {
					node.labelNode.setAttribute("title", node.item.relation);
				});
			},1500);
		},
		function(item){
			treeMedia.self.tree.rootNode.setSelected();
			attachTo = treeResource.self.store.getValue(item, "id")
		}
	);

}

function detachResource(resourceId, form, currentAttachIds) {
	var dialog = openNotify("Frigør ('unlink') ressource", _ExtendHTML.notifyTemplates.detachResource, [ 
	{
		id:'canceloption',
		classes:'dijitEditorIcon dijitEditorIconUndo'
	},{
		id:'continueoption',
		classes:'dijitEditorIcon dijitEditorIconRedo',
		cb:function() {
			var newId = [];
			dojo.forEach(currentAttachIds.split(','), function(id) {
				if(id != gDocument.id) newId.push(id);
			});
			submitPartial(resourceId, {
				form: form, 
				editResource:1,
				attachId:newId.join(",")
			});
			var rem = dojo.byId('resource_'+resourceId);
			rem.parentNode.removeChild(rem);
		}
	}
	]);
}
function attachProductImage(imageTarget) {
	imageTarget = imageTarget.target;
	var resources = /type=include/.test(document.location.search);
	var selectedFile = null;
	var url = gPage.baseURI+"/openView/Files.php?format=json&dir=products";
	tree = new TreeBuilder();
	var imgUrl = "";

	var poller = null;
	var checkForUpload = function() {
	//	if(dojo.byId('selectImageFrame').contentWindow.document.getElementById('uploadresult'))
	//		imgUrl = dojo.byId('selectImageFrame').contentWindow.document.getElementById('uploadresult');
	}
	var dialog = openNotify("Upload / Vælg billede", _ExtendHTML.notifyTemplates.attachImage, [ 
	{
		id:'toggleoption',
		classes:'dijitEditorIcon dijitEditorIconRefresh',
		cb : function() {
			var treenode = dojo.byId('selectImageTreeNode');
			if(treenode.style.display=="none") {
				treenode.style.display="block";
				dojo.byId('selectImageFrame').style.display="none";
			} else {
				treenode.style.display="none";
				dojo.byId('selectImageFrame').style.display="block";
			}
			return true;
		}
	},{

		id:'continueoption',
		classes:'dijitEditorIcon dijitEditorIconRedo',
		cb:function() { // submitshit
			var current = dojo.byId('images').value.split('\n');
			while(current.length < 4) current.push('');
			if(dojo.byId('selectImageFrame').contentWindow.document.getElementById('uploadresult'))
				imgUrl = dojo.byId('selectImageFrame').contentWindow.document.getElementById('uploadresult').value;
			if(imgUrl=="")return;
			if(/majorImg/.test(imageTarget.id)) {
				current[0] = imgUrl;
				imageTarget.src = "../thumbnail.php?file="+imgUrl+"&w=75&h=80";
			} else {
				current[imageTarget.id.replace(/[^0-9]*/, "")] = imgUrl.replace(/.*\/fileadmin/, "/fileadmin");
				imageTarget.src = "../thumbnail.php?file="+imgUrl+"&w=60&h=50";
			}
			dojo.byId('images').value = current.join('\n');
			

		}
	},{
		id:'canceloption',
		classes:'dijitEditorIcon dijitEditorIconUndo',
		cb : function() { //cancel
			tree.destroy();
		}
	}
	]);
	tree.init(url,"selectImageTreeNode", {type:"file"},{
		showRoot:false,
		labelAttr:'filename'
	})
	tree.load(
		function(){
			setTimeout(function() {
				var w;
				for(var i = 0; i < 10; i++) {
					w = dijit.byId('OoCmS_Tree_'+i);
					if(w) {
						w.update();
						return;
					}
				}
			}, 750);

		},
		function(item){
			imgUrl = tree.self.store.getValue(item, "abspath")
		}
	);


}









if(typeof _ExtendHTML == "undefined") {
	var _ExtendHTML = {}
}
_ExtendHTML.notifyTemplates = {
		selectSubPage :"<div class=\"popup-selectpage\" ><h4>Vælg dokument fra listen</h4>" +
		"<p class=\"popup-selectpage\" id=\"selectDocumentTreeNode\">"+
		"</p><p>&nbsp;</p>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Annullér</span>"+
		"<span id=\"continueoption\">Tilknyt</span>"+
		"</div></div>",
		selectResource :"<div class=\"popup-selectpage\" ><h4>Vælg ressource</h4>" +
		"<table><tbody><tr><td valign=\"top\"><p style=\"font-size:12px;\">Medier</p>"+
		"<p class=\"popup-selectpage\" id=\"selectMediaTreeNode\"></p></td>"+
		"<td valign=\"top\"><p style=\"font-size:12px;\">Scripts/Stylesheets</p>"+
		"<p class=\"popup-selectpage\" id=\"selectResourceTreeNode\"></p></td></tr></table>"+
		"<p style=\"font-size:10px;\"><font color=\"red\">*NB*</font> Når 'Tilknyt' vælges, vil redigeringssiden<br/> genopfriskes - " +
		"og ikke gemte ændringer går tabt!</p><p>&nbsp;</p>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Annullér</span>"+
		"<span id=\"continueoption\">Tilknyt</span>"+
		"</div></div>",
		attachImage :"<div class=\"popup-attachimage\" style=\"min-height:400px; position:relative;\"><h4>Uploads:</h4>" +
		"<p class=\"popup-attachimage\" id=\"selectImageTreeNode\" style=\"min-height:300px;min-width:400px;border:1px solid\">a</p>"+
	//	"<p>&nbsp;</p>"+
	//	"<p><iframe id=\"selectImageFrame\" style=\"overflow:hidden;display:none\" src=\"subforms/fileuploads.php?type=product\" "+
	//	"  frameborder=\"0\" width=\"400\" height=\"160\"></iframe></p>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"toggleoption\">Upload / Eksisterende</span>"+
		"<span id=\"canceloption\">Annullér</span>"+
		"<span id=\"continueoption\">Tilknyt</span>"+
		"</div></div>",
		detachResource:"<div class=\"popup-selectpage\" ><h4>Vil du fjerne lænke til ressource?</h4>" +
		"<p style=\"font-size:10px;\"><font color=\"red\">*NB*</font> Når 'Frigør' vælges, vil redigeringssiden<br/> genopfriskes - " +
		"og ikke gemte ændringer går tabt!</p><p>&nbsp;</p>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Annullér</span>"+
		"<span id=\"continueoption\">Frigør</span>"+
		"</div></div>",
		resourceErr:"<div class=\"popup-notify\"><h4>Kan ikke tilføje ressourcer til 'tomt' dokument</h4>" +
		"<p class=\"popup-notify\">Dokumentet er endnu ikke oprettet i databasen."+
		"For at tilføje ressourcer dokumentet, er det nødvendigt for systemet at kende dets unikke id.<br/><br/>"+
		"Du skal derfor gemme dokumentet og vælge at tilføje en ressource efter at have opfrisket editoren<br /><br /><br />" +
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Rediger videre</span>"+
		"<span id=\"saveoption\">Gem og genindlæs</span>"+
		"</div></div>",
		saveErr : "<div class=\"popup-error\"><h4>Kan ikke gemme dokument</h4>" +
		"<p class=\"popup-notify\">Dokumentet er endnu ikke oprettet i databasen eller én af følgende parametre er ikke sat:<br/>"+
		"&nbsp;&nbsp;&nbsp;(<b>titel</b>,<b>indhold</b>,<b>id</b>)<br/><br/>"+
		"Systemet returnerede følgende fejl:<br><font color=red>{RESPONSE}</font>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Rediger videre</span>"+
		"<span id=\"saveoption\">Forsøg igen</span>"+
		"</div></div>",
		editErr : "<div class=\"popup-error\"><h4>Forespurgte ID ikke fundet</h4>" +
		"<p class=\"popup-notify\">Dokumentet er endnu ikke oprettet i databasen<br/>"+
		"<div class=\"popup-buttonbar\">"+
		"<span id=\"canceloption\">Gå tilbage</span>"+
		"</div></div>",
		confirmCancel : "<div class=\"popup-notify\"><h4>Ændringer vil gå tabt, sikker?</h4>" +
		"<p class=\"popup-notify\">De seneste ændringer vil gå tabt hvis du vælger at annullére, og kan på ingen måde genskabes.<br /><br />"+
		"Du kan vælge at <i>Rediger videre</i> og i stedet lagre dette som kladde<br><b>NB</b>, kladder er offentlig utilgængeligt<br /><br />{RESPONSE}<br />" +
		"</p><div class=\"popup-buttonbar\">"+
		"<span id=\"continueoption\">Afslut uden af gemme</span>"+
		"<span id=\"canceloption\">Rediger videre</span>"+
		"</div></div>",
		saveSuccess : "<div class=\"popup-notify\"><h4>Ændringer gemt</h4>" +
	"<p class=\"popup-notify\">Dine ændringer til dokumentet er udført!<br/>"+
	"<div class=\"popup-buttonbar\">"+
	"<span id=\"canceloption\">Gå tilbage</span>"+
	"</div></div>"
	}
//}
/*
var Resource = {
	addToPage : function() {

	},
	attachToPage : function() {

	},
	deleteResource : function() {

	},
	resourceStore : function() {

	},
	preview:  function() {

		dojo.require("dojox.highlight");
		dojo.require("dojox.widget.Dialog");
		dojo.require("dojox.highlight.languages."+
			dojo.byId('document_form').elements['mimetype'].value.replace(/.*\//, ""));
		var w = dijit.byId('hlPreview');
		var bufEl;
		if(w) {
			bufEl = w.containerNode;
			w.setContent(dojo.byId("bodyText").value);
		} else {
			bufEl = document.createElement('span');
			dojo.body().appendChild(bufEl);
			bufEl.innerHTML = dojo.byId("bodyText").value;
			bufEl.id = "hlPreview";

		}
		var d = dojo.coords(bufEl);
		console.log(d)
		dojox.highlight.init(bufEl);
		console.log(dojo.coords(bufEl))

		if(w) {
			w.dimensions=[d.w,d.h];
			w.layout()
		} else {
			w = new dojox.widget.Dialog({
				node:bufEl,
				title:"Preview",
				dimensions: [d.w,d.h],
				content: bufEl.innerHTML,
				id:'hlPreview'
			});
			w.startup();
		}
		
		w.show();
		setTimeout(function() {
			console.log(bufEl)
		}, 500)
	}
}
*/
function getQueryParam(ident)
{
	var s = document.location.search
	var b, e;
	b = s.indexOf(ident+"=");
	if(b == -1) return null;
	e = s.indexOf('&', b);
	if(e == -1)
		e = s.length;
	return s.substring(b + ident.length + 1, e);

}

/*
function loadHeadEditor(parentElement) {

	var headEdi = tinymce.EditorManager.get('DocumentHead');
	if(headEdi) return false;
	var textarea = document.createElement('textarea');
	textarea.id = "DocumentHead";
	var wrap = document.createElement('div');
	wrap.id ="DocumentHead_wrapper";
	wrap.className = "";
	wrap.appendChild(textarea);

	parentElement.appendChild(wrap);
	parentElement.appendChild(document.createElement('hr'));

	headEdi = loadInstance('DocumentHead');

}
function loadFootEditor(parentElement) {

	var footEdi = tinymce.EditorManager.get('DocumentFoot');
	if(footEdi) return false;

	var textarea = document.createElement('textarea');
	textarea.id = "DocumentFoot";
	var wrap = document.createElement('div');
	wrap.id ="DocumentFoot_wrapper";
	wrap.appendChild(textarea);

	parentElement.appendChild(document.createElement('hr'));
	parentElement.appendChild(wrap);

	footEdi = loadInstance('DocumentFoot');

}

function loadInstance(textareaId) {
	var instance = new tinymce.Editor(textareaId, {
		theme : "advanced",
		mode: "exact",
		skin : "o2k7",
		language : 'da',
		skin_variant : "black",
		extended_valid_elements : 'iframe[src|width|height|name|align]',
		plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		// Theme options
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,forecolor,backcolor,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "newdocument,save,template,preview|,code,fullscreen,|,undo,redo,|,cut,copy,paste,|,tablecontrols",
		theme_advanced_buttons3 : "charmap,nonbreaking,emotions,iespell,media,advhr,|,link,unlink,anchor,image,cleanup,|,insertdate,inserttime",
		//		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,,|,cite,abbr,",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : false,

		// Example content CSS (should be your site CSS)
		content_css : "css/standard.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/templates.php",
		external_link_list_url : "lists/links.php",
		external_image_list_url : "lists/images.php",
		media_external_list_url : "lists/medias.php",
		file_browser_callback : 'myFileBrowser',
		urlconverter_callback : 'customURLConverter',

		height:"300px",
		width:"100%"
		});

	tinymce.EditorManager.add(instance);
	instance.render();
	return instance;
}
*/
function customURLConverter (url, node, on_save) {
	return url;
}
function myFileBrowser (field_name, url, type, win) {
	var fileBrowserWindow = new Array();
	fileBrowserWindow.push('width=750');
	fileBrowserWindow.push('height=490');
	fileBrowserWindow.push('scrollable=true');
	fileBrowserWindow.push('statusbar=false');
	fileBrowserWindow.push('toolbar=false');
	fileBrowserWindow.push('resizeable=false');
	var remote = window.open((gPage ? gPage.baseURI : '') + '/admin/subforms/fileadmin.php' + "?type=" + type,'FileBrowser',fileBrowserWindow.join(","))
	var poll = function() {
		try {
		var el = remote.document.getElementById('uploadresult');
		} catch(isClosed) { }
		if(el && el.value != "") {
			win.document.forms[0][field_name].value = el.value;
			remote.close();
		} else
			setTimeout(poll, 500);
	}
	setTimeout(poll, 500);

	return false;
}


var forms_count = 0;
function openNotify(title, szHtml, oButtonCB) {

	var previewNode;
	dojo.body().appendChild(previewNode = document.createElement('div'));

	var dialog = dijit.byId('notifyDialog');
	if(dialog) dialog.destroy();
	dialog = new dijit.Dialog({
		node:previewNode,
		title:title,
		content: szHtml,
		id:'notifyDialog',
		onCancel : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		},
		onSubmit : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		},
		onExecute : function () {
			dojo.forEach(oButtonCB, function(b) {
				dijit.byId(b.id) && dijit.byId(b.id).destroy()
			})
		}
	});

	dialog.startup();
	dialog.show();

	// var o = [{ id:'s1', cb:'s2', classes:'cls' }]
	dojo.forEach(oButtonCB, function(button) {
		var node = dojo.byId(button.id);
		if(!node && console && console.debug) console.debug("Warning, button[id="+button.id+",cb="+button.cb+"] could not be initialized in notifyDialog");
		else {
			if(dijit.byId(button.id)) dijit.byId(button.id).destroy();
			new dijit.form.Button({
				iconClass: button.classes || null,
				onClick: function() {
					var dontClose = false;
					if(typeof(button.cb) == 'function') dontClose = button.cb();
					if(!dontClose) {
						dijit.byId('notifyDialog').destroy()
						dojo.forEach(oButtonCB, function(button) {
							dijit.byId(button.id).destroy();
						});
					}

				}
			},node).startup()
		}
	}) // each
	return dialog;
}


var TreeBuilder = function(){
	this.uri = "";
	this.type = -1;
	this.store = null;
	this.placeAt = "";
	this.tree = null;
	this.loaded = false;
	this.initialized = false;
	this.model = null;
	this.query = null;
	this.jsId = null;
	this.rootId  = null;
	this.rootLabel = null;
	this.childrenAttr = null;
	this.labelAttr = null;
	this.showRoot = null;



	//this.mixin = function(args) {
	//	for(var key in args) {
	//		this[key] = args[key];
	//	}

	//}
	this.fetch = function(query, completeCB) {
		if(!query) query = this.query || {type:'page'};
		var completed = function(items, request) {
			TreeBuilder.loaded = new Date();
			if(completeCB) completeCB(items, request);
		}
		this.store.fetch({
			query:query,
			onComplete:completed
		});
	};

	this.getModel = function() {
		if(this.model) return this.model;
		if(this.query.type=="page") {
			this.model = new OoCmS.TreeModel({
				jsId:this.jsId || 'CategoryModel',
				store:this.store,
				query:this.query,
				rootId:'catRoot',
				rootLabel:'Kategorier',
				childrenAttr:'children',
				labelAttr:'title',
				showRoot:true
			})

		} else {
console.log(this.jsId,this.rootId,this.rootLabel,this.showRoot,this.labelAttr);
			this.model = new OoCmS.TreeModel({
				jsId:this.jsId || 'resourceModel',
				store:this.store,
				query: this.query,
				rootId: this.rootId || 'resRoot',
				rootLabel: this.rootLabel || 'Resourcer',
				childrenAttr: this.childrenAttr || 'children',
				labelAttr: this.labelAttr || 'alias',
				showRoot: false//this.showRoot || true
			});

		}
		return this.model;
	};
	this.addMenu = function(tree, only) {
		dojo.require("dijit.Menu")

		this.menu = new dijit.Menu();
		var doNothing = function (e) {
			console.log(gMenuOpenedOnItem);
			console.log('not actually doing anything, just a test!');
		}
		var doSubPage = function() {
			console.log(gMenuOpenedOnItem.id);
			console.log(gTree.lastFocused.item.id)
		}
		this.menu.addChild(new dijit.MenuItem({
			label:"Inkludér ressource"
		}));
		this.menu.addChild(new dijit.MenuItem({
			label:"Tilføj underside",
			onClick:doSubPage
		}));
		this.menu.addChild(new dijit.MenuSeparator());
		this.menu.addChild(new dijit.MenuItem({
			label:"Cut",
			onClick:doNothing,
			iconClass:"dijitEditorIcon dijitEditorIconCut"
		}));
		this.menu.addChild(new dijit.MenuItem({
			label:"Copy",
			onClick:doNothing,
			iconClass:"dijitEditorIcon dijitEditorIconCopy"
		}));
		this.menu.addChild(new dijit.MenuItem({
			label:"Paste",
			onClick:doNothing,
			iconClass:"dijitEditorIcon dijitEditorIconPaste"
		}));
		this.menu.bindDomNode(tree.domNode);
		this.menu.startup();
		dojo.connect(this.menu, "_openMyself", tree, function(e) {
			var tn = dijit.getEnclosingWidget(e.target);
			gMenuOpenedOnItem = tn.item;
			if(only) {
				this.menu.getChildren().forEach(function(i){
					i.attr('disabled', !tn.item.children);
				});
			}

		});
	};
	this.writeTree = function(clickedCB) {
		dojo.require("dijit.Tree");
		if(!this.initialized) {
			console.error("TreeBuilder not initialized, forgot to call TreeBuilder.init('type')?");
		}
		var place = (typeof(this.placeAt) == 'string' ? dojo.byId(this.placeAt) : this.placeAt);
		if(!place) {
			console.error("TreeBuilder can't find DOMNode to write its contents to..");
		}


		if(!this.loaded) {
			this.fetch();
		}
console.log(this.jsId,this.rootId,this.rootLabel,this.showRoot);
		this.tree = new OoCmS.Tree({
			persist:this.persist || true,
			model:this.getModel(),
			store:this.store,
			onClick: clickedCB,
			showRoot : false//this.showRoot || true,

		});
		this.tree.placeAt(place).startup()
	}
	return {
		self : this,
		//mixin : this.mixin,
		addMenu : this.addMenu,
		init : function(url, node,query,args) {
			dojo.require("dojo.data.ItemFileReadStore");
			this.self.uri = url
			this.self.store = new dojo.data.ItemFileWriteStore({
				url: this.self.uri,
				query:query
			})
			if(args) 
				for(var i in args)
					this.self[i] = args[i];

			this.self.query = query;
			this.self.placeAt = node;
			this.self.initialized = true;
		},
	// load chain wrapper
		load : function(completeCB, clickCB) {
			this.self.fetch(this.self.query, completeCB);
			this.self.writeTree(clickCB)
		},
		destroy : function() {
			this.self.tree.destroyRecursive();
		}
	}
};


function addResource(type, pos) {
	try {
		if(gDocument.id == ""){
			openNotify("Tilføj ressource", _ExtendHTML.notifyTemplates.resourceErr, [ {
				id:'saveoption',
				cb:function() {
					submitDocument('document_form', true);
				},
				classes:'dijitEditorIcon dijitEditorIconSave'
			}, {
				id:'canceloption',
				classes:'dijitEditorIcon dijitEditorIconUndo'
			}
			]);

			return;
		}
		var szHtml = '';
		var wrapperid = "Document"+pos.charAt(0).toUpperCase() + pos.substring(1) + "_wrapper";
		var bodyDom = tinymce.EditorManager.get('DocumentBody').getElement();

		var wrap = dojo.byId(wrapperid)
		if(!wrap) {
			wrap = document.createElement('div');
			var ediWrap = bodyDom.parentNode;
			wrap.id = wrapperid;
			wrap.className = "";

			if(pos == 'head')
				ediWrap.insertBefore(wrap, bodyDom);
			else
				ediWrap.appendChild(wrap);
		}
		if(type == 'html') {
			if(pos == 'head') loadHeadEditor(wrap)
			else loadFootEditor(wrap)
			return;
		}
		szHtml = _ExtendHTML.resourceTemplates[type];
		szHtml = szHtml.replace(/{URL}/g, "").replace(/{COMMENT}/g, "").replace(/{WIDTH}/g,"").replace(/{HEIGHT}/g,"");
		szHtml = szHtml.replace(/{FORMID}/g, "resourceform_"+forms_count);
		var formDiv = document.createElement('div');
		formDiv.innerHTML += '<form style="margin:2px 0px;" id="resourceform_' + forms_count +
		'" onSubmit="submitResource(\'resourceform_' + forms_count +'\')" action="'+gPage.baseURI+'/lib/resource_api.php" method="post">' +
		szHtml +
		'<input type="hidden" value="'+pos+'" name="position" /></td>'+
		'<input type="hidden" value="'+gDocument.id+'" name="id" /></td>'+
		'<input type="hidden" value="true" name="addResource"/>' +
		'<input type="hidden" value="false" name="editResource"/>' +
		'<input type="hidden" value="'+type+'" name="type"/>' +
		'</form>';
		wrap.appendChild(formDiv);
		forms_count++;

	}catch(e) {
		console.log(e);
	}
}
function toggleResourceUI(formId) {
	//	#resourceAsTextBody, resourceAsReference {
	if(toggleState == 'href') {
		dojo.style(dojo.byId('resourceAsTextBody'), {
			display:'block'
		});
		dojo.style(dojo.byId('resourceAsReference'), {
			display:'none'
		});
		document.forms[formId]['uri'].value = "";
		toggleState = "body";
	} else {
		dojo.style(dojo.byId('resourceAsTextBody'), {
			display:'none'
		});
		document.forms[formId]['body'].value = "";
		dojo.style(dojo.byId('resourceAsReference'), {
			display:'block'
		});
		toggleState = "href";
	}

}
function toggleResourceMime(val) {
	if(val=='text/javascript') {
		dijit.byId('twinSel1').domNode.parentNode.style.display='none';
		dijit.byId('twinSel2').domNode.parentNode.style.display='none'
	} else {
		dijit.byId('twinSel1').domNode.parentNode.style.display='block';
		dijit.byId('twinSel2').domNode.parentNode.style.display='block'
	}

}
function editResource(type, pos, unid) {
	if(confirm("Det element du redigerer for øjeblikket lukkes, og ændringer bør gemmes før du går videre. Vil du fortsætte?")) 
		location = "edit.php?EditDoc&type="+type+"&id="+unid;

}
function setResourceOnChangeEvents(node) {
	console.log(node);
}
function submitResource(formId) {
	var theForm = dojo.byId(formId);
	dojo.xhrPost({
		url: theForm.action+"?id="+gDocument.id,
		content : dojo.formToObject(theForm),
		load : function(res) {
			if(res.indexOf("SAVED") == -1) alert(res);
			else {
				var unid = res.replace(/.*ID=\"/, "").replace(/\".*/, "");
				var resourceInfoNode = dojo.query("tbody[resourceid='"+unid+"']", dojo.byId('actionCol'))[0];
				dojo.xhrGet({
					
					url: gPage.baseURI+"/lib/resource_api.php?getResource&format=infohtml&unid="+unid,
					load : function(res) {
						var insert = res
						console.log(resourceInfoNode);
						if(resourceInfoNode) {
							dijit.byId('deleteResourceId_'+unid).destroy();
							dijit.byId('editResourceId_'+unid).destroy();
							resourceInfoNode.innerHTML = res.
							replace("<tbody[^>]*", "").
							replace("</tbody>", "");;
						} else {
							dojo.query(".resources-table",dojo.byId('actionCol'))[0].innerHTML += insert;
						}
					}
				});
				theForm.elements['addResource'].value = 'false';
				theForm.elements['editResource'].value = 'true';
			}
		}
	});
}
function deleteResource(unid) {
	if(!confirm("Sletter ressource, er du sikker?")) return;
	var ed = tinyMCE.get('DocumentBody');
	ed.setProgressState(1); // Show progress
	dojo.xhrPost({
		url: gPage.baseURI+"/admin/save.php",
		content : {
			delResource: 'true',
			partial: 'true',
			unid: unid,
			form:'include'
		},
		load : function(res) {
			if(res.indexOf("DELETED") == -1) alert(res);
			ed.setProgressState(0); // Show progress
			var resourceInfoNode = dojo.query("tbody[resourceid='"+unid+"']", dojo.byId('actionCol'))[0];
			resourceInfoNode.parentNode.removeChild(resourceInfoNode);

		}
	});
}





////////////////////////
// SUB Pages functions
////////////////////////

function openSelectSubpageDialog() {
	dojo.require("dojox.widget.Dialog");
	dojo.xhrGet({
		url : gPage.baseURI+'/openView/Documents.php?format=json&type=subpage',
		load:function(res) {
try{
			var szHtml = "<div class=\"popup-selectpage\">"+
			'<h4>Valg af subpage-id</h4>'+
			'<p class="popup-notify">Følgende er alle de elementer af undersider, som findes i databasen. Vælg det dokument du ønsker at behandle:<br /><span>'
			var json = eval('('+res+')');
			var buttons = [];
			dojo.forEach(json.items, function(entry) {
				console.log(entry);
				szHtml += '<div style="float:left;" class="selectpage"><span id="selectPage_'+entry.id+'">'+entry.title+'</span></div>';
				buttons.push({
					id:'selectPage_'+entry.id,
					cb:function() {
						document.location = document.location.href + "&id="+entry.id;
					}
				});
			})
			szHtml += "<div style=\"clear:both;\"></div></span></p><div class=\"popup-buttonbar\">"+
			"<span id=\"canceloption\">Annullér</span>"+
			"</div></div>"
			buttons.push({
				id:"canceloption",
				cb:function() {
					location:gPage.baseURI
				}
			});
			openNotify("Vælg", szHtml, buttons);
}catch(e){console.log(e)}
		}

	});
	
}
