var server = 'http://dev.clientreviews.info/jp-poweredby/';
/**
 * @check JQuery IS defined Or not
 */
if( typeof jQuery === 'undefined' ) {
    appendScript(server + 'js/jquery.js');
};

function appendScript(pathToScript) {
    var head = document.getElementsByTagName("head")[0];
    var js = document.createElement("script");
    js.type = "text/javascript";
    js.src = pathToScript;
    head.appendChild(js);
};

function appendHtml(element, pathToHtml) {
    jQuery(element).append('<iframe src=' + pathToHtml + ' height="85px" width="270px" name="frame1" id="frame1" style="border:none;"></iframe>');   

    return  jQuery(element);
};

function autoResize(id){
    var newheight;
    var newwidth;

    if(document.getElementById){
        newheight = document.getElementById(id).contentWindow.document .body.scrollHeight;
        newwidth = document.getElementById(id).contentWindow.document .body.scrollWidth;
    }

    document.getElementById(id).height = (newheight) + "px";
    document.getElementById(id).width = (newwidth) + "px";
}

function poweredByJobProgress(element, config){
    var themes = ['grey', 'white'];
    var theme = 'grey';
    if( themes.indexOf(config.theme) > -1 ) {
        theme = config.theme; 
    }

    return appendHtml(element, server + 'include/'+theme+'.html');

}

function appendLinkStyle(pathToStyle) {
    var head  = document.getElementsByTagName("head")[0];
    var style = document.createElement("link");
    style.rel = "stylesheet";
    style.href = pathToStyle;
    head.appendChild(style);
};