const YES3 = {
    maxZ: 10000,
    Functions: {},
    contentLoaded: false,
    dirty: false,
    busy: false,
    yes3Url: "https://portal.redcap.yale.edu/resources/yes3",

    moduleObject: null,

    captions: {
        "yes" : "yes",
        "okay" : "okay",
        "save" : "save",
        "done" : "done",
        "no": "no",
        "cancel": "cancel",
        "close": "close",
        "update": "update",
        "add": "add",
        "proceed": "proceed",
        "restore": "restore",
        "wait": "PLEASE WAIT",
        "fielditem_placeholder_enabled": "start typing to autocomplete",
        "fielditem_placeholder_disabled": "select the event first"        
    },

    actionEnum: {
        add: 0,
        save: 1,
        restore: 2,
        download: 3
    },

    widthEnum: {
        narrow: 0,
        medium: 1,
        wide: 2
    },

    fieldTypeEnum: {
        any: "any",
        date: "date",
        indicator: "indicator",
        calculated: "calculated"
    },

    actionProps: [
        {
            "class": "fas fa-plus yes3-loaded yes3-action-icon",
            "action": "Add",
            "title": "Add an item"
        },
        {
            "class": "far fa-save yes3-loaded yes3-action-icon",
            "action": "Save",
            "title": "Save all changes"
        },
        {
            "class": "fas fa-undo yes3-loaded yes3-action-icon",
            "action": "Restore",
            "title": "Restore from backup"
        },
        {
            "class": "fas fa-download yes3-loaded yes3-action-icon",
            "action": "Download",
            "title": "Download"
        }
    ],

    panels: {
        PAGE_HELP: "yes3-page-help-panel",
        YESNO: "yes3-yesno-panel",
        HELLO: "yes3-hello-panel"
    },

    page: {
        name: ""
    }
};


String.prototype.truncateAt = function( n ){
    if ( this.length > n-3 ) return this.substring(0, n-3) + "...";
    else if ( this.length > n ) return this.substring(0, n);
    else return this;
}
 
 /**
  * Escapes html elements within string, including quotation marks. 
  * Can be used to condition user input or data prior to display.
  * 
  * @returns string
  */
 String.prototype.escapeHTML = function() {
     return this.replace(
         /[&<>"]/g,
         function(chr){
             return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[chr] || chr;
        }
     )
}

String.prototype.isAlphaNumeric = function()
{
    const re = /^[0-9a-z]+$/i;

    return re.test(this);
}

String.prototype.isValidFieldname = function()
{
    const reSingle = /^[a-z]$/i;
    const reMultiple = /^[a-z][0-9_a-z]*[0-9a-z]$/i;

    if ( this.length===0 ) return false;

    if ( this.length===1 ) return reSingle.test(this);

    return reMultiple.test(this);
}

String.prototype.isValidFilename = function()
{
    const reSingle = /^[a-z]$/i;
    const reMultiple = /^[a-z][0-9_ -a-z]*[0-9a-z]$/i;

    if ( this.length===0 ) return false;

    if ( this.length===1 ) return reSingle.test(this);

    return reMultiple.test(this);
}
  
 // formats date as mm-dd-yyyy
 Date.prototype.mdy = function() {
    var mm = this.getMonth() + 1; // getMonth() is zero-based(!)
    var dd = this.getDate();
    return [
       (mm>9 ? '' : '0') + mm,
       (dd>9 ? '' : '0') + dd,
       this.getFullYear()
    ].join('-');
};
 
 // formats date as ISO (yyyy-mm-dd)
 Date.prototype.ymd = function() {
    var mm = this.getMonth() + 1; // getMonth() is zero-based(!)
    var dd = this.getDate();
    return [
       this.getFullYear(),
       (mm>9 ? '' : '0') + mm,
       (dd>9 ? '' : '0') + dd
    ].join('-');
};
 
 // formats date as ISO (yyyy-mm-dd hh:mm:ss)
 Date.prototype.ymdhms = function() {
    var mm = this.getMonth() + 1; // getMonth() is zero-based(!)
    var dd = this.getDate();
    var hh = this.getHours();
    var nn = this.getMinutes();
    var ss = this.getSeconds();
    var ymd = [
       this.getFullYear(),
       (mm>9 ? '' : '0') + mm,
       (dd>9 ? '' : '0') + dd
    ].join('-');
    var hms = [
       (hh>9 ? '' : '0') + hh,
       (nn>9 ? '' : '0') + nn,
       (ss>9 ? '' : '0') + ss
    ].join(':');
 
    return [ymd, hms].join(' ');
};
 
 // formats date as m/d/y hh:mm:ss
 Date.prototype.mdyhms = function() {
 
    return [this.getMonth()+1,
            this.getDate(),
            this.getFullYear()].join('/')+' '+
            [this.getHours(),
                this.getMinutes(),
                this.getSeconds()
            ].join(':')
    ;
};

// the IIFE is just to scope out the days and months variables
(function() {
    var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    Date.prototype.getMonthName = function() {
        return months[ this.getMonth() ];
    };
    Date.prototype.getDayName = function() {
        return days[ this.getDay() ];
    };
     
    // formats date as m/d/y hh:mm:ss
    Date.prototype.verboseDatetime = function() {

        let m = this.getMinutes();
        let mm = ( m > 10 ) ? ''+m : '0'+m;

        return this.getDayName()
            + ", " + this.getMonthName()
            + " " + this.getDate()
            + " " + this.getFullYear()
            + " " + ( this.getHours() >= 12 ? [(this.getHours()-12), mm].join(":") + "pm" : [this.getHours(), mm].join(":") + "am" )
        ;
    };

})();

 
 /*
  * replaces REDCap's escapeHtml which crashes (in this context anyway)
  * probably deprecated, since we added this function to the string prototype
  */
 const escapeHTML = str =>
    str.replace(
       /[&<>'"]/g,
       tag =>
          ({
             '&': '&amp;',
             '<': '&lt;',
             '>': '&gt;',
             "'": '&#39;',
             '"': '&quot;'
         }[tag] || tag)
    );
 
 // centers an element on screen
 jQuery.fn.center = function (dx, dy, atTheTop, toTheLeft) 
 {
    atTheTop = atTheTop || false;
    toTheLeft = toTheLeft || false;

    let x = ( toTheLeft ) ? 10 : Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + $(window).scrollLeft() - dx/2);

    // we can't let the panel be located too high up, lest the close button be obscured by the bootstrap menu overlay
    let y = ( atTheTop ) ? 30 : Math.max(30, (($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop() - dy/2);

    this.css("position","absolute");

    this.css("top", y + "px");

    this.css("left", x + "px");

    return this;
};
 
 // positions an element on screen
 jQuery.fn.situate = function (x, y) {
    this.css("position","absolute");
    this.css("top", Math.max(0, y +
       $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, x +
       $(window).scrollLeft()) + "px");
    return this;
};

jQuery.fn.markAsError = function() {

    this.addClass("yes3-error");
    return this;
}

jQuery.ui.autocomplete.prototype._resizeMenu = function () {
    var ul = this.menu.element;
    ul.outerWidth(this.element.outerWidth());
}

/* === ACTION FUNCTIONS === */

YES3.Functions.setThemeDark = function()
{
    YES3.setTheme('dark');
}

YES3.Functions.setThemeLight = function()
{
    YES3.setTheme('light');
}

YES3.Functions.showPageHelp = function( onPageLoad )
{
    onPageLoad = onPageLoad || false;

    YES3.openPanel('yes3-page-help-panel', true);

    // hide the 'got it' checkbox if help was not called by page loader
    if ( !onPageLoad ){

        $("div#yes3-page-help-panel-not-got-it").hide();
    }
}

YES3.Functions.closePageHelp= function()
{
    YES3.closePanel('yes3-page-help-panel');
}


YES3.isEmpty = function( x )
{
    if ( typeof x === "undefined" ) return true;
    if ( x===null ) return true;
    if ( typeof x === "object" ) return YES3.isEmptyObject( x );
    if ( typeof x === "string" ) return ( x.length === 0 );
    return false;
}

YES3.isEmptyArray = function( x )
{
    return YES3.isEmptyObject( x );
}

YES3.isEmptyObject = function( x )
{
    if ( typeof x !== "object" ) return true;
    if ( typeof x.length !== "undefined" ) return ( x.length === 0 );
    if ( Array.isArray(x) ) return ( !x.length );
    return ( !Object.keys(x).length );
}

YES3.isNonEmptyObject = function( o )
{
    return ( typeof o === "object" && Object.keys(o).length > 0 );
}

YES3.isNonEmptyArray = function( o )
{
    return ( typeof o === "object" && Array.isArray(o) && o.length > 0 );
}

YES3.getYes3ContainerElement = function()
{
    return $("div#yes3-container");
}

YES3.getYes3ContainerParentElement = function()
{
    return $("div#yes3-container").parent();
}

YES3.setCaptions = function()
{
    $("input[type=button].yes3-button-caption-yes").val(YES3.captions.yes);
    $("input[type=button].yes3-button-caption-okay").val(YES3.captions.okay);
    $("input[type=button].yes3-button-caption-restore").val(YES3.captions.restore);
    $("input[type=button].yes3-button-caption-save").val(YES3.captions.save);
    $("input[type=button].yes3-button-caption-done").val(YES3.captions.done);
    $("input[type=button].yes3-button-caption-no").val(YES3.captions.no);
    $("input[type=button].yes3-button-caption-cancel").val(YES3.captions.cancel);
    $("input[type=button].yes3-button-caption-close").val(YES3.captions.close);
    $("input[type=button].yes3-button-caption-proceed").val(YES3.captions.proceed);
    $("input[type=button].yes3-button-caption-update").val(YES3.captions.update);
}

/*** DEBUGGING ***/
/*
window.onerror = function(message, source, lineno, error)
{ 
    if ( !YES3_DEBUG_MESSAGES ) return false;

    // make sure the damn cursor is not spinning
    YES3.notBusy();

    // de-modalize
    YES3.endModalState();

    let msg = "A Javascript error was encountered! Please take a screen shot and write down exactly what preceded this sorry state."
        + "<br>-------"
        + "<br><br>message: " + message
        + "<br><br>source: " + source
        + "<br><br>lineno: " + lineno
        + "<br><br>error: " + error
    ;

    YES3.hello( msg );

    return true;
}
*/

YES3.debugMessage = function()
{
    if ( !YES3_DEBUG_MESSAGES ) return false;

    console.log.apply(null, arguments);
}

/* === ACTION ICON SUPPORT === */

YES3.displayActionIcons = function( listenersLater )
{
    listenersLater = listenersLater || false;

    $('i.yes3-action-disabled').removeClass('yes3-action-disabled');

    if ( localStorage.getItem('theme')==='dark'){

        $('.yes3-light-theme-only').hide();
        $('.yes3-dark-theme-only').show();
    }
    else {

        $('.yes3-light-theme-only').show();
        $('.yes3-dark-theme-only').hide();
    }

    if ( !listenersLater ) {

        YES3.setActionIconListeners( YES3.getContainer() );
    }
}

YES3.setActionIconListeners = function(parentElement)
{
    $("i.yes3-action-icon").off();

    $("i.yes3-action-icon:not(.yes3-action-disabled):not(.yes3-nohandler)").on("click", function(){

        let action = $(this).attr("action");

        if ( typeof YES3.Functions[action] === "function" ) {
            YES3.Functions[action].call(this);
        }
        else {
            YES3.YesNo(`No can do: the feature 'YES3.Functions.${action}' has not been implemented yet.`);
        }    
    })
}

/* === ELEMENT GETTERS === */

YES3.getContainer = function(){

    return $('div#yes3-container');
}

YES3.page.getContentWrapper = function(){

    return $('div#yes3-page-content-wrapper');
}

YES3.page.getHeader = function(){

    return $('div#yes3-page-header');
}

YES3.page.getFooter = function(){

    return $('div#yes3-page-footer');
}

YES3.page.getHelpContentWrapper = function(){

    return $("div#yes3-page-help-content");
}

/* === THEME === */

YES3.applyThemeBackgroundToParent = function()
{
    YES3.getYes3ContainerParentElement().css('background-color', YES3.getYes3ContainerElement().css('background-color') );
}

YES3.setTheme = function(theme)
{
    theme = theme || "light";

    localStorage.setItem('theme', theme);

    if ( theme !== YES3.getYes3ContainerParentElement().attr('data-theme') ) {
        YES3.getYes3ContainerParentElement().attr('data-theme', theme);
    }

    YES3.onTheme();
}

YES3.onTheme = function()
{
    YES3.applyThemeBackgroundToParent();

    YES3.displayActionIcons();

    //YES3.setThemeObjects();
}

YES3.setThemeObjects = function()
{
    let theme = localStorage.getItem('theme');

    $("img.yes3-square-logo").attr('src', YES3.moduleProperties.imageUrl[theme].logo_square);

    $("img.yes3-horizontal-logo").attr('src', YES3.moduleProperties.imageUrl[theme].logo_horizontal);

    $("img.yes3-logo").off().on("click", function(){

        window.open(YES3.yes3Url, "popup=yes");
    })
}

YES3.getTheme = function()
{
    return localStorage.getItem('theme') || YES3.getYes3ContainerParentElement().attr('data-theme');
}

YES3.populateProjectPropertyElements = function(){

    $(".yes3-project_id").html( YES3.moduleProperties.project_id );
    $(".yes3-projectTitle").html( YES3.moduleProperties.projectTitle );
}

/* === DIALOGS === */

YES3.YesNo = function(question, fnYes, fnNo) {
    YES3.yesFunction = fnYes;
    YES3.noFunction = fnNo;
    $('#yes3-yesno-message').html(question);
    YES3.openPanel(YES3.panels.YESNO);
};
 
YES3.Yes = function() {
    YES3.closePanel(YES3.panels.YESNO);
    if ( typeof YES3.yesFunction == "function" ) {
        YES3.yesFunction();
    } else {
        window[YES3.yesFunction]();
    }   
};
 
 YES3.No = function() {
    YES3.closePanel(YES3.panels.YESNO);
    if ( typeof YES3.noFunction == "function" ) {
       YES3.noFunction();
   }
};

YES3.hello = function(msg, fn, nonmodal) {
    if ( fn ) {
        YES3.helloFunction = fn;
    } else {
        YES3.helloFunction = null;
    }
    $('#yes3-hello-message').html(msg);
    YES3.openPanel(YES3.panels.HELLO, nonmodal);
};
 
YES3.helloClose = function() {
    YES3.closePanel(YES3.panels.HELLO);
    if ( typeof YES3.helloFunction == "function" ) {
        YES3.helloFunction();
    }
};

YES3.isBusy = function(message) 
{
    YES3.busy = true; 
    
    YES3.getYes3ContainerParentElement().css({'cursor': 'wait'});
    
    YES3.openPanel("yes3-busy").html(message).css("z-index", "20000");
}

YES3.notBusy = function() 
{
    YES3.busy = false;  

    YES3.closePanel("yes3-busy");

    YES3.getYes3ContainerParentElement().css({'cursor': 'default'});
}

/* === PANELS === */
 
 /**
  * 
  * @param {*} panelName 
  * @param {*} nonmodal 
  * @param {*} x 
  * @param {*} y 
  */
YES3.openPanel = function(panelName, nonmodal, x, y, atTheTop, toTheLeft, maxWidth) 
{
    atTheTop = atTheTop || false;
    toTheLeft = toTheLeft || false;
    maxWidth = maxWidth || 950;
    
    nonmodal = nonmodal || false;
    x = x || 0;
    y = y || 0;
    
    let panel = $(`#${panelName}`);

    let theParent = panel.parent();

    if ( !nonmodal ) {
        
        YES3.startModalState(); // places the full-screen overlay just below the panel -->
    }

    if ( $(window).width() > maxWidth ){

        panel.css("width", maxWidth+"px");
    }
    else {

        panel.css("width", $(window).width()+"px");
    }

    if ( x || y ) {
        panel.situate( x, y );
    } else {
        panel.center(theParent.offset().left, theParent.offset().top, atTheTop, toTheLeft);
    }
    
    YES3.maxZ++;

    panel.show().css('z-index', YES3.maxZ);

    return panel;
};
 
YES3.closePanel = function(panelName) {
    let panel = $(`#${panelName}`);
    panel.hide();
    YES3.endModalState();
};

YES3.startModalState = function(){

    $('#yes3-screen-cover').show();
}

YES3.endModalState = function(){

    $('#yes3-screen-cover').hide();
}

YES3.getPanel = function(panel_id){

    return $(`div#${panel_id}`);
}

YES3.setJQueryStuff = function(){

    $(".yes3-draggable").draggable({
        "handle": ".yes3-panel-header-row, .yes3-panel-handle, .yes3-drag-handle"
    });

    $(".yes3-sortable").sortable({
        "handle": ".yes3-handle"
    });
}

YES3.whackREDCapUI = function(){

    //$('div#subheader').remove();
    $('div#south').remove();
}

/** 
 * AJAX call using the v13+ module.ajax function
 */
YES3.ajax = ( action, parms, callBackFn )=>{

    YES3.moduleObject
    .ajax(action, parms)
    .then(function(response){
        
        callBackFn(response);
    })
    .catch(function(err){

        console.error('YES3 AJAX ERROR REPORT', err);

        /**
         * if this is the hacked version, log the response
         */
        if ( typeof err === 'object' && typeof err.responsetext === 'string' ){

            YES3.moduleObject.log(err.responsetext, {'log_type':'AJAX RESPONSE TEXT'});
        }
    });
}


/*** ONLOAD ***/
/*
* the approved alternative to $(document).ready()
*/
$( function () {
    
    YES3.whackREDCapUI();

    YES3.setCaptions();

    YES3.setJQueryStuff();

    YES3.setTheme( YES3.getTheme() );
})



