

function doToken() {

	$.when(
		$.getScript( "/extensions/FlexForm/Modules/Selectr/dist/selectr.min.js" )
	).done( function () {
		doTokenSetup();
	} );
}

document.addEventListener("DOMContentLoaded", function() {
	wachtff(doToken);
});