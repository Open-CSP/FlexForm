

function doToken() {
	$.when(
		$.getScript( "/extensions/WSForm/modules/tokens3/jquery.inputpicker.js" )
	).done( function () {
		doTokenSetup();
	} );
}

document.addEventListener("DOMContentLoaded", function() {
	wachtff(doToken, true);
});