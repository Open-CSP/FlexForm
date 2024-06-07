/**
 * Holds further JavaScript execution intull jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function ffHoldTillReady( method, both = false ) {
	//console.log('wacht ff op jQuery..: ' + method.name );
	if ( window.jQuery ) {
		if (both === false) {
			//console.log( 'ok JQuery active.. lets go!' );
			method()
		} else {
			if ( window.mw ) {
				var scriptPath = mw.config.get( 'wgScript' )
				if ( scriptPath !== null && scriptPath !== false ) {
					method()
				} else {
					setTimeout( function () {
						ffHoldTillReady( method, true )
					}, 250)
				}
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250)
			}
		}
	} else {
		setTimeout( function () {
			ffHoldTillReady(method)
		}, 50)
	}
}