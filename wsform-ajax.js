

var wsAjax = true;


function test1() {
    alert("callback when done");
}


function test2(btn,callback) {
	alert("callback before posting");
	wsform(btn,callback);
}



function wsAutoSaveInitAjax() {
    var autosaveForms = $('form.ws-autosave');
    autosaveForms.each(function(){
        var form = this;
        var id = $(this).attr('id');
        if( typeof id === 'undefined' ) {
            return;
        }
        //observer[id] = new MutationObserver(function(){

        $(form).find("input[type=button]").each(function(){
            if( typeof $(this).attr('onclick') !== 'undefined' && $(this).attr('onclick') !== false ) {
                setGlobalAutoSave( this, id );
            }
        });
        $(this).on('input paste change', 'input, select, textarea, div', function(){
        // $(this).bind("DOMSubtreeModified", function(){
            $(form).find("input[type=button]").each(function(){
                if( typeof $(this).attr('onclick') !== 'undefined' && $(this).attr('onclick') !== false ) {
                    var btn = this;
                    clearTimeout(wsFormTimeOutId[id + '_general']);
                    if( typeof wsFormTimeOutId !== 'undefined' ) {
                        if( wsFormTimeOutId[id] !== undefined ) {
                            clearTimeout(wsFormTimeOutId[id]);
                        }
                    }
                    wsFormTimeOutId[id] = setTimeout( function() {
                        wsAutoSave(btn);
                    }, wsAutoSaveOnChangeInterval );
                    setGlobalAutoSave( btn, id );
                }

            });
        });
        //observer[id].observe(this, { childList: true, subtree: true } );
    });
}




wachtff( wsAutoSaveInitAjax );