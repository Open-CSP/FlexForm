

function doToken() {

	$.when(
		$.getScript( "/extensions/WSForm/modules/Selectr/dist/selectr.min.js" )
	).done( function () {
		doTokenSetup();
	} );
}

document.addEventListener("DOMContentLoaded", function() {
	wachtff(doToken);
});