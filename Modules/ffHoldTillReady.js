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
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function ffHoldTillReady( method, both = true ) {
	if ( window.jQuery ) {
		if ( both === false ) {
			if ( isFFInitiated() ) {
				method();
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250 )
			}
		} else {
			if ( window.mw ) {
				if ( isFFInitiated() ) {
					method();
				} else {
					setTimeout( function () {
						ffHoldTillReady( method, true )
					}, 250 )
				}
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250 )
			}
		}
	} else {
		setTimeout( function () {
			ffHoldTillReady( method, both )
		}, 50 )
	}
}
