
function createMessagesIfNeeded () {
    let alert = $('[class^="flexform alert-"]')
    if (alert !== null && alert.length > 0) {
        alert.each( function () {
            let type = $(this).attr('class').split('-')[1]
            if (type === 'danger') type = 'error'
            if (type === 'warning') type = 'warn'
            let title = $(this).data('title');
            let msg = $(this).text();
            if ( typeof mwMessageAttach !== 'undefined' ) {
                if (typeof $.notify === 'undefined') {
                    var u = mw.config.get('wgScriptPath')

                    if (u === 'undefined') {
                        u = ''
                    }

                    $.getScript(u + '/extensions/FlexForm/Modules/notify.js').done( function() {
                        setTimeout(function(){
                            showMessage( msg, type, $(mwMessageAttach), true, title );
                            //console.log( alert.text(), type, $(mwMessageAttach) );
                        }, 500);

                    })
                }

            } else {
                console.log ( "type: " + type );
                if ( title !== undefined || title !== '' ) {
                    if ( type === 'html' ) {
                        mw.notify( $($(this).html()), { autoHide: false, type: type, title: title })
                    } else {
                        mw.notify($(this).text(), { autoHide: false, type: type, title: title })
                    }
                } else {
                    if ( type === 'html' ) {
                        mw.notify( $($(this).html()), { autoHide: false, type: type })
                    } else {
                        mw.notify($(this).text(), { autoHide: false, type: type })
                    }
                }
            }
        });

    }
}

waitASec(createMessagesIfNeeded, true );
/**
 * Holds further JavaScript execution intull jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function waitASec (method, both = false ) {
    //console.log('wacht ff op jQuery..: ' + method.name );
    if (window.jQuery) {
        if (both === false) {
            //console.log( 'ok JQuery active.. lets go!' );
            method()
        } else {
            if (window.mw) {
                var scriptPath = mw.config.get('wgScript')
                if (scriptPath !== null && scriptPath !== false) {
                    method()
                } else {
                    setTimeout(function () {
                        waitASec(method, true)
                    }, 250)
                }
            } else {
                setTimeout(function () {
                    waitASec(method, true)
                }, 250)
            }
        }
    } else {
        setTimeout(function () {
            waitASec(method)
        }, 50)
    }
}