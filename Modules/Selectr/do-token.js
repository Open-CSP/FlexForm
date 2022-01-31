

function doToken() {

	$.when(
		$.getScript( "/extensions/WSForm/Modules/Selectr/dist/selectr.min.js" )
	).done( function () {
		doTokenSetup();
	} );
}

document.addEventListener("DOMContentLoaded", function() {
	wachtff(doToken);
});