(function() {

    'use strict';

	/**
	*
	* Config 
	*/
	angular.module('jobProgress')


	/**
	*
	*@App Constranits
	**/
	.constant('API_PREFIX', function() {
		// return 'http://localhost/jp/jp_web_api/public';
		// return window.location.origin;  // local
		if( (window.location.host).indexOf('jobprog.net') > -1 ) {
			return window.location.origin + '/mobile/public'; // Mobile
		}
		return window.location.origin + '/api5/public'; // Local
	})
	.constant('APP_URL', function() {

		if( (window.location.host).indexOf('jobprog.net') > -1 ) {
			return window.location.origin + "/ma";
		}

		return window.location.origin + "/app" // Live & Staging
	})
	.constant('WEB_APP', function() {

		if( !(/^www./.test(window.location.hostname)) ) {
			return window.location.protocol+'//www.'+window.location.hostname+'/app5';
		}

		// return 'http://localhost/jp/jp_web_api/public';
		// return window.location.origin;  // local
		return window.location.origin + '/app5'; // Live & Staging
	});

})();



