

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
        var type = $(this).attr('data-autosave');
        var id = $(this).attr('id');
        if( typeof id === 'undefined' ) {
            return;
        }
        //observer[id] = new MutationObserver(function(){

        if( type === 'auto' || type === 'oninterval' ) {
            $('<button onClick="wsToggleIntervalSave(this)" class="btn btn-primary ws-interval-on" id="btn-' + id + '">Autosave is On</button>').insertBefore(form);
            $(form).find("input[type=button]").each(function () {
                setGlobalAutoSave(this, id);
            });
        }

        if( type === 'auto' || type === 'onchange' ) {
            $(this).on('input paste change', 'input, select, textarea, div', function(){
                //console.log("setting wsSetEventsAutoSave");
                wsSetEventsAutoSave(form);
            });
        }


        //observer[id].observe(this, { childList: true, subtree: true } );
    });
}




wachtff( wsAutoSaveInitAjax );