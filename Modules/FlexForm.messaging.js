function createMessagesIfNeeded () {
	let alert = $('[class^="flexform alert-"]')
	if (alert !== null && alert.length > 0) {
		alert.each(function () {
			let type = $(this).attr('class').split('-')[1]
			if (type === 'danger') {
				type = 'error'
			}
			if (type === 'warning') {
				type = 'warn'
			}
			let title = $(this).data('title');
			let msg = $(this).text();
			let msgId = $(this).data('id');
			let persistent = $(this).data('persistent');
			if ( typeof mwMessageAttach !== 'undefined' ) {
				if ( typeof $.notify === 'undefined' ) {
					var u = mw.config.get('wgScriptPath')

					if (u === 'undefined') {
						u = ''
					}

					$.getScript(u + '/extensions/FlexForm/Modules/notify.js').done(function () {
						setTimeout(function () {
							showMessage(msg, type, $(mwMessageAttach), true, title)
							//console.log( alert.text(), type, $(mwMessageAttach) );
						}, 500)

					})
				}

			} else {
				var newHtml = '<div class="flexform alert-' + type + '">' + $(this).html() + '</div>';
				if ( title !== undefined || title !== '' ) {
					if ( type === 'html' || persistent == '1' ) {
						if ( persistent == '1' ) {
							mw.notify($(newHtml), { autoHide: false, type: "html", title: title })
						} else {
							mw.notify($($(this).html()), { autoHide: false, type: type, title: title })
						}
					} else {
						mw.notify($(this).text(), { autoHide: false, type: type, title: title })
					}
				} else {
					if (type === 'html' || persistent == '1' ) {
						if ( persistent == '1' ) {
							mw.notify($(newHtml), { autoHide: false, type: "html", title: title })
						} else {
							mw.notify($($(this).html()), { autoHide: false, type: type })
						}
					} else {
						mw.notify($(this).text(), { autoHide: false, type: type })
					}
				}
			}
		})

	}
}

function ffMsgAck( mId ) {
	new mw.Api().post({
		action: 'flexform',
		titleStartsWith: 'nop',
		what: 'acknowledge',
		mId: mId,
	})
		.then(function (data) {
			console.log ( "Message acknowledged", data );
		})
		.fail(function () {
			alert('error removing message, see console')
		})
}

waitASec(createMessagesIfNeeded, true)

/**
 * Holds further JavaScript execution intull jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function waitASec (method, both = false) {
	if (window.jQuery) {
		if (both === false) {
			method()
		} else {
			if (window.mw) {
				if (mw.notify) {
					method()
				} else {
					setTimeout(function () {
						waitASec(method, true)
					}, 250)
				}
			} else {
				setTimeout(function () {
					waitASec(method, true)
				}, 250)
			}
		}
	} else {
		setTimeout(function () {
			waitASec(method, true)
		}, 50)
	}
}