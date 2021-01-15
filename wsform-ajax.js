/**
 * Show popup message. Initiated and loaded bu wsform function
 * @param  {[string]}  msg           [Text message]
 * @param  {[string]}  type          [what kind of alert (success, alert, warning, etc..)]
 * @param  {Boolean} [where=false] [where to show]
 * @param  {Boolean} [stick=false] [wether popup must be sticky or not]
 * @return {[type]}                []
 */
function showMessage(msg,type,where = false, stick=false) {
    if ( typeof $.notify === "undefined" ) return;
    if (where !== false) {
        if(stick) {
            where.notify(msg,type, {clickToHide: true, autoHide: false });
        } else {
            where.notify(msg,type);
        }
    } else {
        if(stick) {
            $.notify(msg, type, {clickToHide: true, autoHide: false });
        } else {
            $.notify(msg, type);
        }
    }
}

(function($) {
    $.fn.autoSave = function(callback, ms) {
        return this.each(function() {
            var timer = 0,
                $this = $(this),
                delay = ms || 1000;
            $this.keyup(function() {
                clearTimeout(timer);
                var $context = $this.val();
                if(localStorage) {
                    localStorage.setItem("autoSave", $context);
                }
                timer = setTimeout(function() {
                    callback();
                }, delay);
            });
        });
    };
})(jQuery);

var WSFormEditorsUpdates = false;

function test1() {
    alert("callback when done");
}


function test2(btn,callback) {
	alert("callback before posting");
	wsform(btn,callback);
}

function wsAutoSave() {
    var autosaveForms = $('form.ws-autosave');
    autosaveForms.each(function(){
        $(this).autoSave(function(){
            //$(this).
        }, 5000);
    });
}

function updateVE( btn, callback, preCallback, pform ){
   // console.log("updating..");
    var VEditors = $(pform).find("span.ve-area-wrapper");
    var numberofEditors = VEditors.length;
    var tAreasFieldNames = [];
    var tAreas = $(pform).find("textarea").each(function(){
        tAreasFieldNames.push( $(this).attr('name') );
    });
   // console.log(tAreasFieldNames);
    var veInstances = VEditors.getVEInstances();
    $(veInstances).each(function () {
        var instanceName = $(this)[0].$node[0].name;
        //console.log("updating.. " + instanceName);
        if( $.inArray( instanceName, tAreasFieldNames ) !== -1 ) {
            //console.log("updating.. ..");
            new mw.Api().post({
                action: 'veforall-parsoid-utils',
                from: 'html',
                to: 'wikitext',
                content: $(this)[0].target.getSurface().getHtml(),
                title: mw.config.get( 'wgPageName' ).split( /(\\|\/)/g ).pop()
            } )
                .then( function ( data ) {
                    //console.log("updating.. then");
                    var text = data[ 'veforall-parsoid-utils' ].content;
                    var esc = text.replace(/(?<!{{[^}]+)\|(?!=[^]+}})/gmi, "{{!}}");
                    var area = pform.find("textarea[name='" + instanceName + "']")[0];
                    $(area).val(esc);
                    numberofEditors--;
                    if( numberofEditors === 0 ) {
                        WSFormEditorsUpdates = true;
                        //console.log("updating.. done");
                        wsform( btn, callback, preCallback );
                    }
                } )
                .fail( function () {
                    //console.log("updating.. fail");
                    alert('Could not initialize ve4all, see console for error');
                    console.log(result);
                } );
        }

    });
}

/**
 * WSform Ajax handler
 * @param  {[object]}  btn              [btn that was clicked]
 * @param  {Boolean or boolean} [callback=false] [either function to callback or false if none]
 * @return {[none]}                   [run given callback]
 */
function wsform(btn,callback = 0, preCallback = 0) {

    if(preCallback !== 0 && typeof preCallback !== 'undefined') {
        preCallback(btn,callback);
        return;
    }
    $(btn).addClass( "disabled" );

    if (typeof $.notify === "undefined" ) {
        var u = mw.config.values.wgScriptPath;

        if( u === "undefined" ) {
            u = "";
        }

        $.getScript(u + '/extensions/WSForm/modules/notify.js');
    }



    //console.log(callback);
    var val = $(btn).prop('value');
    var frm = $(btn).closest('form');
    frm.addClass( "wsform-submitting" );

    if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE' && WSFormEditorsUpdates === false ) {
        updateVE( btn, callback, preCallback, frm );
    } else {

        if ( typeof window.mwonsuccess === 'undefined' ) {
            var mwonsuccess = 'Saved successfully';
        } else mwonsuccess = window.mwonsuccess;
        // Added posting as user for Ajax v0.8.0.5.8
        var res = $(frm).find('input[name="wsuid"]');
        if ($(res) && $(res).length === 0) {
            var uid = getUid();
            //console.log( uid );
            if (uid !== false) {
                $('<input />')
                    .attr('type', 'hidden')
                    .attr('name', 'wsuid')
                    .attr('value', uid)
                    .appendTo(frm);
            }
        }

        var test = frm[0].checkValidity();
        if (test === false) {
            frm[0].reportValidity();
            $(btn).removeClass("disabled");
            frm.removeClass("wsform-submitting");
            return false;
        }

        var content = frm.contents();
        var dat = frm.serialize();
        var target = frm.attr('action');
        //form.empty();
        //form.addClass('wsform-show');

        $.ajax({
            url: target,
            type: 'POST',
            data: dat,
            dataType: "json",
            success: function (result) {
                $(btn).removeClass("disabled");
                frm.removeClass("wsform-submitting");
                frm.addClass("wsform-submitted");
                //alert(result);
                if (result.status === 'ok') {
                    showMessage( mwonsuccess, "success", $(btn));
                    if (callback !== 0 && typeof callback !== 'undefined') {
                        callback(frm);
                    }
                    //$(btn).prop('value', val + ' (saved)');
                } else {
                    $.notify('WSForm : ERROR: ' + result.message, "error");
                    //$(btn).prop('value', val + ' (ERROR: '+result.message+')');
                }
                //form.removeClass('wsform-show');
                //form.html(content);
            }
        });
        if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE' ){
            WSFormEditorsUpdates = false;
        }
    }
}