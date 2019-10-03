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

function test1() {
    alert("callback when done");
}


function test2(btn,callback) {
	alert("callback before posting");
	wsform(btn,callback);
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
    var test = frm[0].checkValidity();
    if(test === false) {
        frm[0].reportValidity();
        $(btn).removeClass( "disabled" );
        return false;
    }



    var content = frm.contents();
    var dat = frm.serialize();
    var target = frm.attr('action');
    //form.empty();
    //form.addClass('wsform-show');

    $.ajax({
        url: target,
        type : 'POST',
        data : dat,
        dataType: "json",
        success: function(result) {
            $(btn).removeClass( "disabled" );
            //alert(result);
            if(result.status == 'ok') {
                showMessage('Saved succesfully', "success", $(btn));
                if (callback !== 0 && typeof callback !== 'undefined' ) {
                    callback(frm);
                }
                //$(btn).prop('value', val + ' (saved)');
            } else {
                $.notify('WSForm : ERROR: '+result.message, "error");
                //$(btn).prop('value', val + ' (ERROR: '+result.message+')');
            }
            //form.removeClass('wsform-show');
            //form.html(content);
        }
    });
}