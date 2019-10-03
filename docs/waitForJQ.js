
function wachtff(method, msg=false) {
	if(msg !== false) {
		console.log(msg);
	}
	console.log('wacht ff op jQuery..');
	if (window.jQuery) {
		console.log('ok JQuery active.. lets go!');
		method();
	} else {
		setTimeout(function() { wachtff(method) }, 50);
	}
}