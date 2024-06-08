/**
 * Holds further JavaScript execution intull jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function ffHoldTillReady( method, both= true ) {
	if ( window.jQuery ) {
		if ( both === false ) {
			if ( window.wsform ) {
				method();
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250 )
			}
		} else {
			if ( window.mw ) {
				var scriptPath = mw.config.get( 'wgScript' )
				if ( scriptPath !== null && scriptPath !== false ) {
					if ( window.wsform ) {
						method();
					} else {
						$.getScript( scriptPath + '/extensions/FlexForm/Modules/FlexForm.general.js' ).done(function () {
							method()
						});
					}
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