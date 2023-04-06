
function renderMobileShot( videoId, canvasId, buttonId ) {
	const player = document.getElementById( videoId );
	const canvas = document.getElementById( canvasId );
	const context = canvas.getContext( '2d' );
	const captureButton = document.getElementById( buttonId );
	const constraints = {
		video: true,
	};
	captureButton.addEventListener('click', () => {
		if ( typeof player.width !== 'undefined' && typeof player.height !== 'undefined') {
			context.drawImage(player, 0, 0, player.width, player.height);
		} else {
			context.drawImage(player, 0, 0);
		}

// Stop all video streams.
		//player.srcObject.getVideoTracks().forEach(track => track.stop());
	});

	navigator.mediaDevices.getUserMedia( constraints )
		.then( ( stream ) => {
			// Attach the video stream to the video element and autoplay.
			player.srcObject = stream;
		});
}