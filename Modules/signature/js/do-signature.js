
function doWSformActions() {
	if ( typeof ff_signature === 'undefined' ) {
		return;
	}
	console.log( ff_signature.length );
	for( let i = 0; i < ff_signature.length; ++i ) {
		let name = ff_signature[i].name;
		let func = "signature_" + name;
		window[func]();
	}

}

function holdOnSignature(method) {
	//console.log('wacht ff op jQuery..');
	if (window.jQuery) {
		//console.log('ok JQuery active.. lets go!');
		method();
	} else {
		setTimeout(function() { wachtff(method) }, 50);
	}
}



function doSignature() {
	var path = mw.config.get('wgScriptPath');
	if( path === null || !path ) {
		path = '';
	}
	$.when(
		$.getScript( "https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" )
	).done( function () {
		$.when(

			$.getScript( path + "/extensions/FlexForm/Modules/signature/js/touch-punch.js" ),
			$.getScript( path + "/extensions/FlexForm/Modules/signature/js/jquery.signature.js" )
		).done( function () {
			doWSformActions();
		} )
	} );
}



document.addEventListener("DOMContentLoaded", function() {
	holdOnSignature(doSignature);
});