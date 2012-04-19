define(["dojo/_base/declare",
	"dojo/_base/lang",
	"dijit/_WidgetBase",
	"dijit/_TemplatedMixin",
	"dojo/text!./resources/SimpleInput.html"
], function(declare, dlang, djwidgetbase,djtemplated,szTemplateString) {
	
	return declare("OoCmS.formdialog.SimpleInput",[djwidgetbase, djtemplated],{
		templateString: szTemplateString,
		label: '',
		defaultType:'dijit.form.TextBox',
		type:'',
		typeProps: {},
		value: '',
		name: '',
		_setLabelAttr: {
			node: 'labelNode', 
			type: 'innerHTML'
		},
		_setValueAttr: function(val) {
			this._input.set("value", val);
		},
		_getValueAttr: function() {
			return this._input.get("value");
		},
		_setNameAttr: function(newName) {
			this._input.set("name", newName);
			this.labelNode.setAttribute("for", newName);
		},
		_getNameAttr: function() {
			return this._input.get("name");
		},
		_setPropertiesAttr: function(props) {
			for(var i in props) if(props.hasOwnProperty(i)) this._input.set(i, props[i]);
		},
		_setDisabledAttr: function(disabled) {
			this._input.set("disabled", disabled)
		},
		_getDisabledAttr: function(disabled) {
			return this._input.get("disabled")
		},
		getInput: function() {
			return this._input;
		},
		constructor: function(args) {
			dlang.mixin(this, args);
			console.log(this.type);
			this._input = new (eval(!this.type?this.defaultType:this.type))(this.properties, document.createElement('div'));
			this.inherited(arguments);
		},
		postCreate:function() {
			this._input.placeAt(this.inputNode);
			this.inherited(arguments);
		}
	});
});