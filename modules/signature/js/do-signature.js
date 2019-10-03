


function wachtff(method) {
	console.log('wacht ff op jQuery..');
	if (window.jQuery) {
		console.log('ok JQuery active.. lets go!');
		method();
	} else {
		setTimeout(function() { wachtff(method) }, 50);
	}
}



function doSignature() {
	$.when(
		$.getScript( "https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" )
	).done( function () {
		$.when(
			$.getScript( "/extensions/WSForm/modules/signature/js/touch-punch.js" ),
			$.getScript( "/extensions/WSForm/modules/signature/js/jquery.signature.js" )
		).done( function () {
			doWSformActions();
		} )
	} );
}



document.addEventListener("DOMContentLoaded", function() {
	wachtff(doSignature);
});