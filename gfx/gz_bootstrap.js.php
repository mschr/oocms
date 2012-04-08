/*  EventCache library for Quickr theme implementation framework
 *            2008-2009 Morten Sigsgaard Christensen
 * EventCache, top layer event handler to keep track of non-anonymous mem-neat
 * If not using Safari browser, functionpointers are stored,
 * making hooks and unhooking easily accessible with correct handling
*/
var EventCache={
    M_NAME:0,
    M_FUNC:1,
    registers:[],
    getName:function cache_getName(_20){
        if(sBrowserData().m_browserName=="Netscape"){
            return _20.name;
        }else{
            return /\W*function\s+([\w\$]+)\(/.exec(_20)[1];
        }
    },
    register:function cache_register(_21){
        var _22=this.getName(_21);
        if(!_22){
            if(h_ClientBrowser.m_isSafari==false){
                logit("EventCache : Unable to register anonymous function");
            }
        }else{
            for(i=0;i<this.registers.length;i++){
                if(this.registers[i][this.M_NAME].indexOf(_22)!=-1){
                    return i;
                }
            }
            this.registers.push([_22,_21]);
            return (this.registers.length-1);
        }
    },
    hook:function cache_hook(_23,_24,_25){
        if(h_ClientBrowser.m_isSafari==true){
            Event.observe(_23,_24,_25);
            return true;
        }
        var _26,_27=-1;
        if(typeof (_25)=="function"){
            _26=this.getName(_25);
        }else{
            _26=_25.replace(".","_");
        }
        if(_26==""){
            if(h_ClientBrowser.m_isSafari==false){
                logit("EventCache : Unable to hook anonymous function");
            }
            return false;
        }
        for(i=0;i<this.registers.length;i++){
            if(this.registers[i][this.M_NAME].indexOf(_26)!=-1){
                _27=i;
            }
        }
        if(_27!=-1){
            Event.observe(_23,_24,this.registers[_27][this.M_FUNC]);
        }else{
            if(typeof (_25)=="function"){
                _27=this.register(_25);
                Event.observe(_23,_24,this.registers[_27][this.M_FUNC]);
            }else{
                if(h_ClientBrowser.m_isSafari==false){
                    logit("EventCache : Eventhandler must be registered first  ["+_23.id+": "+_24+" >> "+_26+"]");
                }
            }
        }
    },
    unhook:function cache_unhook(_28,_29,_2a){
        if(h_ClientBrowser.m_isSafari==true){
            Event.stopObserving(_28,_29,_2a);
            return;
        }
        var _2b;
        if(typeof (_2a)=="function"){
            _2b=this.getName(_2a);
        }else{
            _2b=_2a.replace(".","_");
        }
        var _2c=-1;
        for(i=0;i<this.registers.length;i++){
            if(this.registers[i][this.M_NAME].indexOf(_2b)!=-1){
                _2c=i;
            }
        }
        if(_2c!=-1){
            Event.stopObserving(_28,_29,this.registers[_2c][this.M_FUNC]);
        }else{
            if(h_ClientBrowser.m_isSafari==false){
                logit("EventCache : Trying to unhook an unregistered event.. ["+_28.id+": "+_29+" >> "+_2b+"]");
            }
        }
    },
    toggleHookFunction:function cache_toggleHook(el,_2e,_2f,_30){
        this.unhook(el,_2e,_2f);
        this.hook(el,_2e,_30);
    }
};

/*  WidgetFactory library for Quickr theme implementation framework
*            2008-2009 Morten Sigsgaard Christensen
*
* Widgets are showing information page context, e.g. lists files if any attachments etc.
* HTML injection and most eval is pulled from 'ExtendHTML'.
* The Factory is the controller whereas ExtendHTML may be the model
*--------------------------------------------------------------------------*/

var WidgetFactory={
    /**
*	Base-structure for widgets , init and end functions
*/
    returnHTML:null,
    activatedWidgets:[],
    minimizedWidgets: (getCookie('MiniWidgets') == "" ? '' : getCookie('MiniWidgets') ),
    //enabledWidgets: getCooki...
    /**
* @param id - of a widget control-name
* @returns true if widgetid is found withing WidgetFactory.enabledWidgets - hash. false, if not.
*/
    isEnabled:function object_WidgetFactory_isEnabled(id)
    {
        if(this.enabledWidgets.indexOf(id) != -1)
            return true;
        else return false;
    },
    /**
* When a widget is created, it's id is pushed in the activeWidgets hash.
* This function makes sure a widget wont appear more than once
* @param id - of a widget control-name
* @returns true if widgetid is found within WidgetFactory.activeWidgets - hash.
*/
    isActive:function object_WidgetFactory_isActive(id)
    {
        if(!this.activatedWidgets)
            return false;
        return (this.activatedWidgets.grep("^"+id+"$").length > 0);
    },
    /**
* When user enters a setup, upon creation the factory checks for preference
* If minimized is enabled, widget show up as a boxed widgetTitle
* @param id - of a widget control-name
* @returns true if widgetid is preferred minimized
*/
    isMinimized : function(id)
    {
        return this.minimizedWidgets.split(',').member(id);
    },

    /* enables a widget
* @param which a widget-id, found in a createWidget switch case
* Widget will be enabled upon calling this function
*/
    enableWidget:function (which)
    {
        if(this.isEnabled(which))
            return;
        else this.enabledWidgets += ','+which;
        setCookie('EnabledWidgets', this.enabledWidgets, null, "/");
    },
    /* disables a widget
* @param which a widget-id, found in a createWidget switch case
* Widget will be disabled upon calling this function
*/
    disableWidget:function (which)
    {
        var ptr=this.enabledWidgets.indexOf(which);
        if(ptr == 0) {
            this.enabledWidgets=this.enabledWidgets.substring(which.length+1);
        } else {
            this.enabledWidgets=this.enabledWidgets.substring(0, ptr-1)+this.enabledWidgets.substring((which.length+ptr));
        }
        setCookie('EnabledWidgets', this.enabledWidgets, null, "/");
    },
    /**
* For the ELSA crew (set a member-array in theme)
* Specific functionality can be applied during the scripts,
* @param id username variable from a haiku-object
* @returns a boolean if id is matched in the roles-hash
*/
    userCheckIfManager:function(id)
    {
        return ( $(this.managerRoles).grep(id).length > 0 );
    },
    /**
* createWidget is the main evaluation..
* Consists of a switch case, directing output(preformatted html) correctly
* A widget is created, based on the parameters, basically the magic is in the CSS,
* though borders are controlled here
*
* @param id , e.g. 'revision', 'attachments' or 'plugins' etc..
* @param bg due to upper right cornor hack (ekstra td..) white as default if null is passed
* @param border set this boolean in order to encapsulate with the borders
* @see WidgetFactory.enabledWidgets
* @returns preformatted WidgetsHTML
* TODO: Create widget Hash array with pointers to booleans, in ExtendHTML
*/
    createWidget:function object_WidgetFactory_createWidget(id, bg, border)
    {
        var szReturn;
        var headline;
        try {
            // check if user/we wants it shown
            if(	!this.isEnabled(id)
                || 	this.isActive(id)
                || 	fieldNames.h_Name == 'gCalendar')
                return "";
            if(id.indexOf('plugins') != -1) {
                if(ExtendHTML.sPlugins()) {
                    headline='<div style="line-height: 5px;">&nbsp;</div>';
                    this.returnHTML=null;
                } else return "";
            } else if(id.indexOf('revision') != -1)	{
                if(this.sRevisionFork())
                {
                    headline="Dokument detail";
                    if(ExtendHTML.sDraft)
                        this.returnHTML=null;
                    if(ExtendHTML.sRevision)
                        this.returnHTML=null;
                    if(ExtendHTML.sResponse){
                        this.returnHTML=null;
                    }
                } else return "";
            } else if(id.indexOf('attachments') != -1) {
                if(ExtendHTML.sAttach())
                {
                    headline="Filer";
                    this.returnHTML=null;

                } else return "";
            } else if(id.indexOf('grouproom') != -1) {
                if(ExtendHTML.sGroupRoom())
                {
                    headline="Grupperum";
                    this.returnHTML=null;
                } else return "";

            } else if(id.indexOf('calendar') != -1) {
                if(ExtendHTML.sTaskView() && fieldNames.h_TaskDueDate != "") {
                    headline='';
                    // use ExtendHTML html
                    this.returnHTML=null;
                    WidgetFactory.pre_processCalWidget();
                } else return "";
            } else
                return "";
            // return what has been evaluated

            this.activatedWidgets.push(id);

            szReturn="";
            if(border)
                szReturn=generateBorderTop(id+'BoxFrame', 'widgetFrame', bg);
            szReturn += this.widgetTableInit(id, headline);
            if(this.returnHTML != null)
                szReturn += this.returnHTML;
            else szReturn += ExtendHTML.returnHTML;
            szReturn += this.widgetTableEnd();
            if(border)
                szReturn += generateBorderBottom();
            return szReturn;
        } catch(e) {
            logit(sExceptionOut(e));
        }
    },
    /**
* The CalWidget (original qp-calendar popup) object i pulled in from an iframe
* This function polls if latency occurs in the io - though only as fall-back
*  - the iframe is instantly prepped upon activation of widget
* When contents have been injected, the duration of the current task is injected
*/
    pre_processCalWidget:function()
    {
        /* override qp-func - new 'IO' */
        var io=dojo.byId('calendarIFrame');
        if(!io) {
            setTimeout(WidgetFactory.pre_processCalWidget, 500);
            return;
        }
        var fakeURL=ConstructFakeBaseURL(self),colon=fakeURL.indexOf(':');
        var fixedURL=window.location.protocol+fakeURL.substring(( colon+1));
        io.src=fixedURL;
        gCalWidgetWin=io.contentWindow;
        if(!gCalWidgetWin.document || !gCalWidgetWin.document.open)
            setTimeout(WidgetFactory.pre_processCalWidget, 500);
        gCalWidgetWin.document.open( );
        gCalWidgetWin.document.write( GetCalWidgetHTML(fieldNames.h_TaskDueDate) );
        gCalWidgetWin.document.close( );
        var body=io.contentWindow.document.getElementsByTagName('body')[0];

        body.style['padding']="0";
        body.style['margin']="0";

    //ExtendHTML.sCalWidgetHighlight();
    },
    sVerifyWidgets:function object_WidgetFactory_sVerifyWidgets()
    {
        var widgettable=document.getElementById('controlBox');
        var rows=widgettable.getElementsByTagName('tbody')[0].childNodes;
        for(var i=0; i < rows.length; i++)
        {
            if(typeof(rows[i]) == 'undefined' || rows[i].nodeType != 1)
                continue;
            if(rows[i].getElementsByTagName('td').length < 2)
                rows[i].parentNode.removeChild(rows[i]);
        }
    },
    /**
*	Fork for revision, response and draft pages
*/

    sRevisionFork:function object_WidgetFactory_sRevisionFork()
    {
        var somethingSet=false;
        /* Check if 'this haiku' is a revision of another page? */
        if(h_PageType === "h_Revision")
        {
            if(ExtendHTML.sPageRevision(document.forms['h_PageUI']))
                somethingSet=true;
        }
        if(h_PageType === "h_Response")
        {
            if(ExtendHTML.sPageResponse(document.forms['h_PageUI']))
                somethingSet=true;
        }
        /* Check for existing draft? */
        if(h_DraftVersionTimestamp != "")
            if(ExtendHTML.sPageDraft(document.forms['h_PageUI']))
                somethingSet=true;
        /* Otherwise all this is for naught, well ( bigO=n | n=3 ) ..*/
        return somethingSet;
    },
    scaleability:[ "14", "18", "22", "28", "32", "40", "48" ],
    getScaleableIcon:function object_WidgetFactory_getScaleableIcon(file, dim)
    {
        var catchit= document.createElement('img');
        var len=this.scaleability.length;
        if(dim < this.scaleability[0])
            dim=this.scaleability[0];
        else if ( dim > this.scaleability[len-1] )
            dim=this.scaleability[len-1];
        else
        {
            for(var i=0; i < len; i++) {
                if( dim >= this.scaleability[i] && dim < this.scaleability[i+1] ) {
                    dim=this.scaleability[i];
                }
            }
        }
        catchit.border="0";
        catchit.src='https://admin.hum.aau.dk/qp/quickrdist/images/scaled/'+file.replace(/\.svg/, "-"+dim+"x"+dim+".gif");
        return catchit;
    },
    hideWidget:function object_WidgetFactory_hideWidget(evt){},
    showWidget:function object_WidgetFactory_showWidget(evt){},
    deleteWidget:function object_WidgetFactory_deleteWidget(evt){}

}; // WidgetFactory Stage 1

// controls not available in this version, outcommented
// Initializing event callback functions in register
//EventCache.register(WidgetFactory.hideWidget);
//EventCache.register(WidgetFactory.showWidget);
//EventCache.register(WidgetFactory.deleteWidget);


/*  ExtendHTML library for Quickr theme implementation framework
 *            2008-2009 Morten Sigsgaard Christensen
 * Stub for the ExtendHTML, in order for events to have a function reference
 * Most HTML for injection is found in theme files,
 * e.g. var attachmentInitTagStr = "<tr><th>Files</th>...etc."
 *
 * Evaluation of page has a number of functions, introduced in later loaded script
 */

var ExtendHTML = {
    returnHTML:null,
    sRevision:false,
    sDraft:null,
    sResponse:false,
    generateHTML:function(_2){
        var _3=document.createElement("span");
        _3.appendChild(_2);
        return _3.innerHTML;
    },
    sPlugins:function(){
        if(!h_ClientBrowser.isPlatformWin()){
            return false;
        }
        this.returnHTML=this.pluginIconRow+this.pluginInfoRowA+"href=\"javascript:modal('"+this.pluginAnchors+"<br /><img src="+this.pluginImg[0]+" />', 770, 500, null);\">"+this.pluginInfoRowB;
        return true;
    },
    sPluginSwapDocu:function(el,id){
        el.parentNode.lastChild.src=ExtendHTML.pluginImg[id];
    },
    sSignin:function(_34){
        var _35=document.getElementById("my-signin");
        if(_35){
            var _36=document.createElement("A");
            _36.className="signin-text";
            _36.href=document.location.href.replace(/(\w+)\/\?(\w+)/,"$1/?Logout");
            _35.innerHTML+="&nbsp;&nbsp;";
            _35.appendChild(_36);
        }
    },
    sHelp:function(_37){
        if(G_ShowHelp){
            return this.helpTag+_37+"</a>";
        }else{
            return "";
        }

}};// ExtendHTML Stage 1


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// dynamic_toc.js
// twist implemented with tables
// arranges icon<>anchors better
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
var TWISTY_CLASS="h-img-twisty";
var TWISTY_CLASS_AJAX="h-img-ajaxTwisty";
var COLLAPSED=0;
var EXPANDED=1;
var NONE=2;
var LOADING=3;
var SELECTED=4;
var RESTRICTED=5;
var ICON_WIDTH=14;
var twistImgAlt=new Array();
twistImgAlt[COLLAPSED]="Expand";
twistImgAlt[EXPANDED]="Collapse";
twistImgAlt[NONE]="";
var folderImgAlt=new Array();
folderImgAlt[COLLAPSED]="Folder";
folderImgAlt[EXPANDED]="Folder";
folderImgAlt[NONE]="";
folderImgAlt[SELECTED]="Folder selected";
folderImgAlt[RESTRICTED]="Folder restricted to Managers";
var twistImg=new Array();
twistImg[COLLAPSED]="/qphtml/html/common/folder_twist_open.gif";
twistImg[EXPANDED]="/qphtml/html/common/folder_twist_close.gif";
twistImg[NONE]="/qphtml/html/common/transparent.gif";
twistImg[LOADING]="/qphtml/html/common/ajax_loader.gif";
var twistImgTag=new Array();
twistImgTag[COLLAPSED]="<img style='z-index: 10; padding-left:%PLpx;' class='"+TWISTY_CLASS_AJAX+"' src='"+twistImg[COLLAPSED]+"' width='"+ICON_WIDTH+"' border='0' align='middle' title='"+twistImgAlt[COLLAPSED]+"' alt='"+twistImgAlt[COLLAPSED]+"' onclick='javascript:twist(this,%d);cancelBubble(event);'/>";
twistImgTag[EXPANDED]="<img style='z-index: 10; padding-left:%PLpx;' class='"+TWISTY_CLASS+"' src='"+twistImg[EXPANDED]+"' width='"+ICON_WIDTH+"' border='0' align='middle' title='"+twistImgAlt[EXPANDED]+"' alt='"+twistImgAlt[EXPANDED]+"' onclick='javascript:twist(this,%d);cancelBubble(event);'/>";
twistImgTag[NONE]="<img style='z-index: 10; padding-left:%PLpx;' src='"+twistImg[NONE]+"' width='"+ICON_WIDTH+"' border='0' height='1' align='middle' title='"+twistImgAlt[NONE]+"' alt='"+twistImgAlt[NONE]+"' />";
var folderImg=new Array();
folderImg[COLLAPSED]="/qphtml/html/common/folder_close.gif";
folderImg[EXPANDED]="/qphtml/html/common/folder_open.gif";
folderImg[NONE]="/qphtml/html/common/transparent.gif";
folderImg[SELECTED]="/qphtml/html/common/folder_selected.gif";
folderImg[RESTRICTED]="/qphtml/html/common/folder_closed_grayed.gif";
var folderImgTag=new Array();
folderImgTag[COLLAPSED]="<img src='"+folderImg[COLLAPSED]+"' border='0' align='middle' valign='middle' title='"+folderImgAlt[COLLAPSED]+"' alt='"+folderImgAlt[COLLAPSED]+"' ";
folderImgTag[EXPANDED]="<img src='"+folderImg[EXPANDED]+"' border='0' align='middle' valign='middle' title='"+folderImgAlt[EXPANDED]+"' alt='"+folderImgAlt[EXPANDED]+"' ";
folderImgTag[NONE]="<img src='"+folderImg[NONE]+"' border='0' align='middle' width='"+ICON_WIDTH+"' height='1' title='"+folderImgAlt[NONE]+"' alt='"+folderImgAlt[NONE]+"' ";
folderImgTag[SELECTED]="<img src='"+folderImg[SELECTED]+"' border='0' align='middle' valign='middle' title='"+folderImgAlt[SELECTED]+"' alt='"+folderImgAlt[SELECTED]+"' ";
folderImgTag[RESTRICTED]="<img src='"+folderImg[RESTRICTED]+"' border='0' align='middle' valign='middle' title='"+folderImgAlt[RESTRICTED]+"' alt='"+folderImgAlt[RESTRICTED]+"' ";
var goUpIconTag="<img src=\"/qphtml/html/common/goup.gif\" align=\"absbottom\" width=12 height=16 alt=\"\" border=\"0\" />";
var imgFolder=encodeURIComponent(GetDocTypeIconImgTag(D_QPTypeFolder));
var g_parentUnid=null;
var g_parentTwisty=null;
var g_parentDiv=null;
var g_parentLevel=0;
var twistExpFunc=new Array();
twistExpFunc[0]=addFoldersToToc;
twistExpFunc[1]=addFoldersToSelector;
// Truncation lengths, TOC titlesï¿½
var maxLengths=[[17,15,14],[14,13,12]];

function dotDaName(_1,_2,_3,_4){
//truncate
var _5=_2.split(" ");
var _6=(_4)?1:0;
var _7=false;
for(var i=0;i<_5.length;i++){
if(_5[i].length>maxLengths[_6][_3]){
_5[i]=_5[i].substring(0,maxLengths[_6][_3]-2)+"..";
_7=true;
}
}
if(_7){
_1=_1.replace(/>/,"title=\""+_2+"\">");
_2=_5.join(" ");
_1=_1.substring(0,_1.indexOf(">")+1)+_2+"</a>";
}
return _1;
}
function cancelBubble(_9){
if(_9.stopPropagation){
_9.stopPropagation();
}else{
if(window.event){
window.event.cancelBubble=true;
}
}
}
function addTocItemObj(_a,_b,_c,_d,_e,_f,_10,_11,_12,_13){
var toc=document.getElementById("toc");
var _15=getTocItemNode(_a,_b,_c,_d,_e,_f,_10,_11,_12,_13);
if(toc&&_15){
toc.appendChild(_15);
}
}
function getTocItemNode(_16,_17,url,_19,_1a,_1b,_1c,_1d,_1e,_1f){
var iEF=0;
var _21=_1d||false;
var _22=(typeof _1f=="undefined"?false:_1f);
_19=decodeURIComponent(_19);
_16=decodeURIComponent(_16);
if(_16=="Go Up"){
_19=goUpIconTag;
}
var _23=twistImgTag[NONE];
if(_1b==EXPANDED||_1b==COLLAPSED){
_23=twistImgTag[_1b].replace(/%d/g,iEF.toString());
_19=folderImgTag[_1b]+"onmousedown=\"document.location.href='"+url+"';\"  />";
}
var _24=(_1c=="1"&&_19.indexOf("folder_")>-1);
if(_24){
if(!_22&&currentUserAccess>2){
_19=folderImgTag[RESTRICTED]+"onmousedown=\"document.location.href='"+url+"';\"  />";
}else{
if(_21){
_19=folderImgTag[EXPANDED]+"onmousedown=\"document.location.href='"+url+"';\"  />";
}
}
}
var _25=_1a*10;
_23=_23.replace(/%PL/,_25);
var _26=" href=\""+url+"\" ";
var _27=_21?"\" class=\"tocSelected-text\" ":"";
var _28="<a"+_26+_27+">"+_16+"</a>";
_28=dotDaName(_28,_16,_1a,_21);
var _29=(_21?"tocSelected-bg":"toc-bg")+(_24?" dropTarget":"");
var _2a=(_24?"folder":"");
var _2b=document.createElement("div");
_2b.id=(_24?_1e:"");
_2b.className=_29;
_2b.style.display="block";
_2b.title=_2a;
_2b.setAttribute("unid",_17);
_2b.setAttribute("level",_1a);
_2b.innerHTML="<table cellspacing=\"1\" cellpadding=\"0\" style=\"padding: 1.5px 0px;\" class=\"hiddenTable\"><tbody>"+"<tr onclick=\"document.location.href='"+url+"';\">"+"<td style=\"z-index: 5; line-height: 13px;\" align=\"center\" valign=\"top\">"+_23+"</td>"+"<td align=\"left\" style=\"line-height:13px;\" valign=\"top\" style=\"width: 20px;\">"+_19+"</td>"+"<td align=\"left\" style=\"line-height:13px; padding-top:2px;\" valign=\"top\" width=\"100%\">"+_28+"</td>"+"</tr><tbody></table>";
if(!_21){
_2b.onmouseover=function(){
_2b.style.backgroundColor="#F0F0F0";
};
_2b.onmouseout=function(){
_2b.style.backgroundColor="#F9F8F4";
};
}
if(_24&&_22&&currentUserAccess>2){
QP_DragAndDrop_initDropTarget(_2b);
}
return _2b;
}
var G_greenFolder=null;
function addFolderSelectorObj(_2c,_2d,_2e,url,_30,_31,_32){
var toc=document.getElementById("folderSelector");
var _34=getFolderSelectorNode(_2c,_2d,_2e,url,_30,_31,_32);
if(toc&&_34){
toc.appendChild(_34);
}
}
function getFolderSelectorNode(_35,_36,_37,url,_39,_3a,_3b){
var _3c=twistImgTag[NONE];
var _3d=decodeURIComponent(imgFolder);
if(_3a==EXPANDED||_3a==COLLAPSED){
_3c=twistImgTag[_3a].replace(/%d/g,"1");
_3d=folderImgTag[_3a]+"onmousedown=\"document.location.href='"+url+"';\"  />";
}
var _3e=_39*10;
_3c=_3c.replace(/%PL/,_3e);
if(_3b){
var _3f="<a onclick=\""+url+"makeMeGreen(this);\" href=\"javascript:void();\">"+_35+"</a>";
}else{
_3d=folderImgTag[RESTRICTED]+"onmousedown=\"document.location.href='"+url+"';\"  />";
_3f="<a onclick=\""+G_FolderSectionAction_CantPublish+"\" href=\"javascript:void();\">"+_35+"</a>";
}
_3f=dotDaName(_3f,_35,_39,bIsSelected);
var _40=document.createElement("div");
_40.id=_36;
_40.className="toc-bg";
_40.style.display="block";
_40.title=_35;
_40.setAttribute("unid",_37);
_40.setAttribute("level",_39);
_40.innerHTML="<table cellspacing=\"1\" cellpadding=\"0\" style=\"padding: 1.5px 0px;\" class=\"hiddenTable\"><tbody>"+"<tr onclick=\"return captureBubbleOrJumpTo(event, '"+url+"');\" style=\"z-index: -1;\">"+"<td style=\"z-index: 5;\" align=\"center\" valign=\"top\">"+_3c+"</td>"+"<td align=\"left\" valign=\"top\" style=\"width: 20px;\">"+_3d+"</td>"+"<td align=\"left\" valign=\"bottom\" width=\"100%\" style=\"line-height:13px; padding-top: 2px;\">"+_3f+"</td>"+"</tr><tbody></table>";
if(!bIsSelected){
_40.onmouseover=function(){
_40.style.backgroundColor="#F0F0F0";
};
_40.onmouseout=function(){
_40.style.backgroundColor="#F9F8F4";
};
}
return _40;
}
function hiliteFolderSelectorByUnid(_41){
var _42=document.getElementById("folderSelector");
var _43=_42.getElementsByTagName("div");
for(var i=0;i<_43.length;i++){
if(_43[i].getAttribute("unid")==_41){
var a=_43[i].getElementsByTagName("a");
if(a[0]){
makeMeGreen(a[0]);
}
}
}
}
function makeMeGreen(me){
var _47=me.getElementsByTagName("img");
if(_47[0]){
if(G_greenFolder!=null){
G_greenFolder.src=folderImg[COLLAPSED];
}
_47[0].src=folderImg[SELECTED];
G_greenFolder=_47[0];
}
}
function twist(obj,i){
if(obj.alt==twistImgAlt[EXPANDED]){
showHideAllSubF(obj,false);
}else{
expSubF(obj,i);
}
}
function expSubF(_4a,_4b){
if(_4a.className==TWISTY_CLASS_AJAX){
for(var cur=_4a;cur!=null&&!(/DIV/.test(cur.tagName));cur=cur.parentNode){
}
if((unid=cur.getAttribute("unid"))){
g_parentTwisty=_4a;
g_parentDiv=cur;
g_parentLevel=parseInt(g_parentDiv.getAttribute("level"));
g_parentUnid=g_parentDiv.getAttribute("unid");
ajax=new QPAjax();
ajax.RequestJS((location.href+"&Form=h_FolderJSON&nowebcaching&PreSetFields=h_ParentUnid;"+unid),twistExpFunc[_4b],"get",expSubFError);
_4a.src=twistImg[LOADING];
}
}else{
showHideAllSubF(_4a,true);
}
}
function expSubFError(_4d){
setTwistyObj(g_parentTwisty,COLLAPSED);
}
function addFoldersToToc(_4e){
g_parentTwisty.className=TWISTY_CLASS;
var _4f=_4e.items;
for(var i=0;i<_4f.length;i++){
var _51=_4f[i].item;
var _52=getTocItemNode(_51.title,_51.unid,"../../h_Toc/"+_51.unid+"/?OpenDocument",imgFolder,g_parentLevel+1,(_51.hasSubfolder=="1"?COLLAPSED:NONE),_51.type,false,_51.SystemName,(_51.canAddDocs=="1"||currentUserAccess>=6));
var _53=document.getElementById("toc");
if(_52&&_53&&g_parentDiv){
if((div=getNextSiblingByTag(g_parentDiv,"div"))){
_53.insertBefore(_52,div);
}else{
_53.appendChild(_52);
}
setTwistyObj(g_parentTwisty,EXPANDED);
}
}
}
function addFoldersToSelector(_54){
g_parentTwisty.className=TWISTY_CLASS;
var _55=_54.items;
for(var i=0;i<_55.length;i++){
var _57=_55[i].item;
var _58=((h_PublishedVersionUNID=="")?h_PageUnid:h_PublishedVersionUNID);
var url="javascript:setFolderName("+"'"+_57.SystemName+"',"+"'"+_57.unid+"',"+"'"+_57.title+"',"+"'"+_57.style+"',"+"'"+_57.sortOrder+"',"+"'"+_57.sortColumn+"',"+"'"+_57.ancestry+"~"+_58+"',"+"'"+g_parentUnid+"',"+true+","+false+");";
var _5a=getFolderSelectorNode(_57.title,_57.SystemName,_57.unid,url,g_parentLevel+1,(_57.hasSubfolder=="1"?COLLAPSED:NONE),(_57.canAddDocs=="1"||currentUserAccess>=6));
var _5b=document.getElementById("folderSelector");
if(_5a&&_5b&&g_parentDiv){
if((div=getNextSiblingByTag(g_parentDiv,"div"))){
_5b.insertBefore(_5a,div);
}else{
_5b.appendChild(_5a);
}
setTwistyObj(g_parentTwisty,EXPANDED);
}
}
}
function showHideAllSubF(_5c,_5d){
for(var cur=_5c;cur!=null&&!(/DIV/.test(cur.tagName));cur=cur.parentNode){
}
g_parentDiv=cur;
g_parentLevel=parseInt(g_parentDiv.getAttribute("level"));
var div=getNextSiblingByTag(cur,"div");
while(div!=null&&(lev=div.getAttribute("level"))!=null&&parseInt(lev)>g_parentLevel){
if(_5d){
if((ch0=div.childNodes[0])&&ch0.tagName=="IMG"&&ch0.className==TWISTY_CLASS){
setTwistyObj(ch0,EXPANDED);
}
div.style.display="block";
}else{
div.style.display="none";
}
div=getNextSiblingByTag(div,"div");
}
setTwistyObj(_5c,(_5d?EXPANDED:COLLAPSED));
}
function setTwistyObj(obj,_61){
obj.src=twistImg[_61];
obj.alt=twistImgAlt[_61];
obj.title=obj.alt;
}
function getNextSiblingByTag(obj,tag){
var _64=tag.toUpperCase();
for(sib=obj.nextSibling;sib!=null;sib=sib.nextSibling){
if(sib.nodeName.toUpperCase()==_64){
return sib;
}
}
return null;
}
////// dynamic_toc.js



////
// sBase.js
function modal(_46,w,h){
if(!dojo.require){
return false;
}
dojo.require("dojo.widget.FloatingPane");
if(!dojo.widget.byId("TextFieldDialog")){
var div=document.createElement("div");
if(_46.length>0){
div.innerHTML="<p id=\"TextFieldDialog_feed\">"+_46+"</p>";
}
div.style.width=w+"px";
div.style.height=h+"px";
dojo.body().appendChild(div);
var _4a=dojo.widget.createWidget("dojo:ModalFloatingPane",{id:"TextFieldDialog",title:"Info",toggle:"fade",resizeable:"true",windowState:(_46.length>0)?"normal":"minimized",hasShadow:"false"},div);
}else{
if(_46.length>0){
dojo.byId("TextFieldDialog_container").innerHTML=_46;
dojo.widget.byId("TextFieldDialog").show();
}
}
return true
}
function eModal(sHTML, title, w, h)
{
dojo.require("dojowidgets.widget.ModalInput");
 //Open up new modal
 var infoParams = {
  widgetId: "TextFieldDialog",
  displayCloseAction: false,
  title: title,
  resizable: false,
  height: h+"px",
  width: w+"px",
  //submitFunction: "",
  formText: sHTML
 };
 var modal = new dojowidgets.widget.ModalInput(infoParams);
}
function setCookie(_17,_18,_19,_1a){
var _1b=new Date();
_1b.setDate(_1b.getDate()+_19);
if(_19==null||typeof (_19)=="undefined"){
_1b=new Date(_1b.getTime()+15768000000);
}
document.cookie=_17+"="+escape(_18)+";expires="+_1b.toGMTString()+((_1a)?";path="+_1a:"");
}
function getCookie(_1c){
if(document.cookie.length>0){
c_start=document.cookie.indexOf(_1c+"=");
if(c_start!=-1){
c_start=c_start+_1c.length+1;
c_end=document.cookie.indexOf(";",c_start);
if(c_end==-1){
c_end=document.cookie.length;
}
return unescape(document.cookie.substring(c_start,c_end));
}
}
return "";
}
function deleteCookie(_1d,_1e){
if(getCookie(_1d)!=""){
var _1f=_1d+"="+((_1e)?";path="+_1e:"");
_1f+=";expires=Thu, 01-Jan-1970 00:00:01 GMT";
document.cookie=_1f;
}
}
document.write("<style type=text/css>.a-Room { font-size: 10pt; !important; }</style>");
function sBrowserData(){
return h_ClientBrowser;
}
/* Ok's lets not use this global key-event unless nescesarry :)
if(navigator.userAgent.indexOf('Gecko') != -1)
	window.addEventListener('keypress', loaddebug, false); 
else document.body.attachEvent('onkeypress', loaddebug);
*/
var debugwindow = null;
function loaddebug(event)
{
	event = (event) ? event : ((window.event) ? event : null); var myChar = String.fromCharCode((event.charCode)?event.charCode:((event.which)?event.which:event.keyCode)).toLowerCase()
	if(!event.shiftKey || myChar != 'd')
		return null;
	if(debugwindow != null) {
        	return debugwindow;
        }
	debugwindow = window.open("about:blank", "debug", "menubar=0,status=0,scrollbars=1,location=0,resizable=1,width=600,height=290");
        if(debugwindow == null) 
        	return null;
try {
	debugwindow.document.open();
} catch(e) { return debugwindow; }
	debugwindow.document.write("<head><title>Quickr/dojo commandline</title></head><form><textarea cols=80 rows=10 style=font-size:8pt; name=DEBUG></textarea>");
	debugwindow.document.write("<textarea cols=45 rows=1 onkeyup=\"watchKeyHit(this, event)\" name=EVAL></textarea></form><button onclick=\"opener.eval(document.forms[0].EVAL.value);\" />Run</button>");
	debugwindow.eval("window.console = new Object();window.console.window = document.forms[0].DEBUG;window.console.log=function(txt){console.window.value += txt+'\\n'}")
        debugwindow.eval("function watchKeyHit(el, _1) {var _3=_1.keyCode; if(_3==0){ _3=_1.charCode; } ; if(_3 == 13) { var e = window.opener.eval(document.forms[0].EVAL.value);document.forms[0].DEBUG.value+=e+'\\n';} }");
	debugwindow.document.close();
        window.console = debugwindow.console;
        
	return debugwindow;
}
function logit(_1){

	if(h_ClientBrowser.isIE()){
		if(WidgetFactory.userCheckIfManager && WidgetFactory.userCheckIfManager(currentMember.m_userName))
                {
			if(! window.console) return ; // activate by 'SHIFT+D' : loaddebug();
			console.log(_1);
		}else{ // ifIsManager
			return;
		} //isNotManager
	} else {
		if(typeof (window.console)=="object" && console.debug){
			console.debug(_1);
		}else if(WidgetFactory.userCheckIfManager && WidgetFactory.userCheckIfManager(currentMember.m_userName))
		{
			if(loadFirebugConsole) {
				window.console=loadFirebugConsole();
				window.console.debug(_1);
			} //canLoad
//	                else {
			if(! window.console)loaddebug();
                        if(!debugwindow) return;
			console.log(_1);
//	                }
		}
	}//ifnoconsole
}
function require(_2)
{
document.write('<script type="text/javascript" src="'+_2+'"><\/script>');
}
function sExceptionOut(ex){
	var warn = "FixMe: ";
	if (ex instanceof RangeError)		warn+="Number out of range!"
        else if (ex instanceof SyntaxError) 	warn+="Syntax error in code!"
        else if (ex instanceof EvalError) 	warn+= "eval() function was used in an incorrect manner";
        else if (ex instanceof ReferenceError) 	warn+= "Variable references are invalid";
        else if (ex instanceof TypeError) 	warn+= "Variable type problem, invalid use of object/method/parameter " + ex.message.substring(1, ex.message.indexOf("'", 2));
        else if (ex instanceof URIError) 	warn+= "encodeURI() or decodeURI() function used in an incorrect manner";
       	else warn+="An unspecified error occurred!"
        if(ex.fileName && ex.stack)
		return "[" + ex.name + "] : " + ex.message + "\n   " + warn
                	+ "\n ##"+ex.fileName.substring(ex.fileName.lastIndexOf("/"))+"# line: "+ex.lineNumber+" #\n";
        return "[" + ex.name + "] : " + ex.message + "\n   " + warn; 

try{
var _8=ex.name+" : "+ex.message+" ## "+ex.fileName.substring(ex.fileName.lastIndexOf("/"))+"# line: "+ex.lineNumber+" #\n";
_8+=ex.stack;
}
catch(e){
logit(e);
}
return _8;
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//
// INTERFACE FOR REMOTEROOMMANAGER
// sets up remotewindow, and configures its links
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
var MEMBER=0;var DBOPEN=1;var BASICS=3;var SETTING=4;
var roomManageButtons=new Array();
roomManageButtons[MEMBER]="<button style=\"width:100px;\" onClick=\"roomManage('EnterMemberManage');\" />Members</button>";
roomManageButtons[DBOPEN]="<button style=\"width:100px;\" onClick=\"roomManage('EnterIndex');\">RoomIndex</button>";
roomManageButtons[BASICS]="<button style=\"width:100px;\" onClick=\"roomManage('EnterBasics');\" />Basics</button>";
roomManageButtons[SETTING]="<button style=\"width:100px;\" onClick=\"roomManage('EnterCustomize');\" />Customize</button>";

var closeButton="<br /><p align=right><button style=\"width:60px;\" onClick=\"dojo.widget.byId('TextFieldDialog').hide();\">Luk</button></p>";

var remoteWindow;
var remoteAnchors;
var remoteProgress;
var remoteWhat;

function openRemoteRoomManager(){
if(currentUserRoles.indexOf("h_Managers")==-1){
return false;
}
remoteUrl=getAbsoluteHaikuPath(self)+"Main.nsf"+"/?OpenDatabase&Form=h_SiteMapUI&NoWebCaching";
windowWidth=210;
windowHeight=240;
modal("<div id=\"stzTextArea\">SiteMap indlæser, vent...</div><div align=\"center\" id=\"stzWidgetArea\"></div><div align=\"right\" id=\"stzButtonArea\">"+closeButton+"</div>",300,200);
dojo.require("dojo.widget.ProgressBar");
var _31=document.createElement("div");
_31.setAttribute("dojoType","ProgressBar");
_31.setAttribute("widgetId","settingProgress");
var _32=dojo.widget.byId("TextFieldDialog");
dojo.byId("stzWidgetArea").appendChild(_31);
var _33=dojo.widget.createWidget("dojo:ProgressBar",{id:"settingProgress",progressValue:"1",numboxes:"70",multiplier:"1",width:"240",height:"15"},_31);
_33.render();
_33.startAnimation();
remoteWindow=window.open(remoteUrl,"Remote","resizable=yes,width=210,height=260,top=120,left=25,toolbar=no,scrollbars=yes,menubar=no,status=no");
if(remoteWindow!=null){
remoteWindow.focus();
}
window.setTimeout("pollRemote()",300);
}
function pollRemote(){
remoteAnchors=remoteWindow.document.getElementsByTagName("a");
if(remoteAnchors.length==0){
setTimeout("pollRemote()",100);
}else{
var _34=dojo.widget.byId("settingProgress");
if(_34){
_34.stopAnimation();
_34.setProgressValue(1);
_34.hide();
}
dojo.byId("stzTextArea").innerHTML="Vælg mellem:";
dojo.byId("stzButtonArea").innerHTML="<br />"+roomManageButtons[DBOPEN]+roomManageButtons[MEMBER]+roomManageButtons[BASICS]+roomManageButtons[SETTING];
}
}
function pollProgress(){
var _35=dojo.widget.byId("settingProgress");
var Cur=remoteProgress++;
var Max=parseInt(_35.getMaxProgressValue());
remoteAnchors=remoteWindow.document.getElementsByTagName("a");
var buf=null;
if(_35){
try{
buf=remoteAnchors[Cur].href.replace("EnterRoom",remoteWhat);
remoteAnchors[Cur].href=buf;
_35.setProgressValue(Cur+1);
}
catch(e){
roomManage(remoteWhat);
}
}
if(Cur==(Max-1)){
dojo.widget.byId("TextFieldDialog").closeWindow();
setTimeout("remoteWindow.focus()",30);
eval(remoteWhat+"('Main.nsf')");
}else{
setTimeout("pollProgress()",25);
}
}
function roomManage(_39){
remoteWhat=_39;
remoteProgress=0;
var _3a=dojo.widget.byId("settingProgress");
var _3b;
if(_3a){
_3a.progressValue=0;
_3a.setMaxProgressValue(remoteWindow.document.getElementsByTagName("a").length);
_3a.show();
}
pollProgress();
}
function EnterMemberManage(nsf){
document.location=areaNsf.substring(0, areaNsf.lastIndexOf('/'))+"/"+nsf+"/h_Toc/406AC6104BD821D30525670800167200/?EditDocument";
}
function EnterCustomize(nsf){
document.location=areaNsf.substring(0, areaNsf.lastIndexOf('/'))+"/"+nsf+"/h_Toc/A6090949E584BB1105256708001671FE/?OpenDocument";
}
function EnterBasics(nsf){
document.location=areaNsf.substring(0, areaNsf.lastIndexOf('/'))+"/"+nsf+"/$defaultview/h_RoomSettings/?EditDocument&Form=h_PageUI&PreSetFields=h_SetEditScene;h_TailorRoomEdit,h_SetEditNextScene;h_TailorRoomEdit,h_EditAction;h_Edit,h_ReturnToPage;A6090949E584BB1105256708001671FE,h_SetEditCurrentScene;h_TailorRoomEdit";
}
function EnterIndex(nsf){
document.location=areaNsf.substring(0, areaNsf.lastIndexOf('/'))+"/"+nsf+"/h_Toc/CE6A3D6B1F546C9405256708001671FF/?OpenDocument";
}
function extractLinks(_40){
modal("<TEXTAREA rows=5 cols=30 id=TextField></TEXTAREA><br />",400,220);
feedmodal(_40);
};
function feedmodal(_41){
var _42;
var _43=dojo.byId("toc").getElementsByTagName("table");
set=dojo.byId("TextField");
set.value="<font size=\"3\" face=\"Times New Roman\">";
set.value+="<p><font style=\"font-family: Verdana;\" size=\"4\">Kursusrum</font></p></br>";
var t="";
for(var i=0;i<_43.length;i++){
_42=_43[i].firstChild.getElementsByTagName("a")[0];
switch(_41){
case "room":
if(/OpenDatabase/.test(_42.href)){
t=_42.getAttribute("title");
if(t==null){
t=_42.innerHTML;
}
set.value+="<a style=\"padding-left:15px;\" class=\"a-Room\" href=\""+_42.href+"\"><span style=\"font-family:arial;\">"+t.replace(/:$/,"")+"</span></a><br />";
}
break;
case "folder":
if(/OpenDocument/.test(_42.href)){
t=_42.getAttribute("title");
if(t==null){
t=_42.innerHTML;
}
set.value+="<a style=\"padding-left:15px;\" class=\"a-Room\" href=\""+_42.href+"\"><span style=\"font-family:arial;\">"+t.replace(/:$/,"")+"</span></a><br />";
}
break;
default:
}
}
set.value+="</font>";
};
document.write("<style type=text/css>.a-Room { font-size: 10pt; !important; }</style>");
