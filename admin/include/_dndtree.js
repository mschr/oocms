define([
	"dojo/_base/declare",
	"dojo/_base/lang",
	"dijit/tree/dndSource",
	"OoCmS/_treebase"
], function(declare, dlang, djtreedndsource, ootreebase) {
	
		return declare("OoCmS._dndtree", [ootreebase],{
			observers: [],
			loaded: false,
			initialized: false,
			dndController: djtreedndsource,
			constructor: function constructor(args) {
				if(args.dndController) this.dndController = args.dndController;
				if(dlang.isString(this.dndController)){
					this.dndController = dlang.getObject(this.dndController);
				}
				traceLog(this,arguments)
			},
			__postCreate : function __postCreate() {
				this.inherited(arguments);
				traceLog(this,arguments)
				this.model.mDragController = this.dndController;
				if(typeof this.menu == "string")
				{
					switch(this.menu) {
						case "OoCmS.RelationsMenu":
							this.menu = new OoCmS.RelationsMenu({
								tree : this,
								toolbox: this.toolbox
							});
							break;
						case "OoCmS.ResourcesMenu":
							this.menu = new OoCmS.ResourcesMenu({
								tree : this,
								toolbox: this.toolbox
							});
							break;
						case "OoCmS.FileAdminMenu":
							this.menu = new OoCmS.FileAdminMenu({
								tree : this,
								toolbox: this.toolbox
							});
					}
				}
			},
			checkAcceptance : function(sourceTree, nodes)
			{
				return true;
			},
			checkItemAcceptance:function (targetDomNode, from_dndSource)
			{
				try {
					var rowNode = dijit.getEnclosingWidget(targetDomNode);
				} catch(err) {
					console.log(err, "cannot accept drag on unknown widget")
				}
				// assert that we're targeting a valid treenode
				if(!rowNode || !rowNode.isTreeNode ) return false;
				var to_dndSource = this,
				toTree = to_dndSource.tree,
				fromTree = from_dndSource.tree;
				if(toTree.id == "resourceselectorTree") {
					// resourcetree will not accept any drops at all
					return false;
				} else if(fromTree.id == "resourceselectorTree") {
					// pr definition; subpages cannot relate to resources, a page and 
					// all its contained sub's however, will 'inherit' the resource
					if(rowNode.item.root || (rowNode.item.type && rowNode.item.type[0] == "subpage")) return false;
					// we need to check if allready attached
					for(var i in from_dndSource.selection)
						if(new RegExp("(^{ID}$|^{ID},|,{ID},|,{ID}$)".replace(/\{ID\}/g, rowNode.item.id)).test(from_dndSource.selection[i].item.attachId[0]))
							return false;
				} else if(fromTree.id == "pageselectorTree") {
					// 
					if(toTree.id == "resourceselectorTree") return false
				}
				return true;
			}
		});
		
})