function isFFInitiated() {
	return typeof window.FlexFormInitiated !== 'undefined';
}

function FFInitiated() {
	if ( typeof window.FlexFormInitiated === 'undefined' ) {
		window.FlexFormInitiated = true;
	}
}

/**
 * Holds further JavaScript execution until jQuery is loaded
 * @param method
 * @param both
 * @param maxRetries
 */
function ffHoldTillReady( method, both = true, maxRetries = 100 ) {

	if ( maxRetries <= 0 ) {
		console.warn( "ffHoldTillReady timeout: jQuery, MW or FlexForm not loaded" );
		return;
	}

	const isJQueryReady = typeof window.jQuery !== 'undefined';
	const isMwReady = !both || typeof window.mw !== 'undefined';

	if ( isJQueryReady && isMwReady && typeof isFFInitiated === 'function' && isFFInitiated() ) {
		method();
	} else {
		const delay = isJQueryReady ? 250 : 50;
		setTimeout( function () {
			ffHoldTillReady( method, both, maxRetries - 1 );
		}, delay );
	}
}

function ffDummyInit(){
}

ffHoldTillReady( ffDummyInit );