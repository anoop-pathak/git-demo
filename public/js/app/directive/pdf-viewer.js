(function() {
	'use strict';

	/**
	*
	* @directive function
	*/
	var directive = function($injector, $timeout, $rootScope, API_PREFIX) {
		return {
			restrict: 'E',
			templateUrl: API_PREFIX()+'/js/app/views/pdf-viewer.html',
			replace: true,
			link: function (scope, iElement, iAttrs) {
				
				/**
				*
				* @default ( init )
				*/
				var init  = function() {
 					
 					/* options for pdf viewer */
					var options = {
						// height: '1200px',
						pdfOpenParams: {
							navpanes: 0,
							toolbar: 0,
							statusbar: 0,
							view: "FitV",
							pagemode: "thumbs"
						},
						forcePDFJS: true,
						PDFJS_URL: API_PREFIX()+"/js/plugins/pdfjs/web/viewer.html"
					};

					/* load viewer */
					$timeout(function() {

						// $(iElement).find('#pdf').height(1100);
						// $(iElement).find('#pdf').height(window.innerHeight - 85);
						if( typeof iAttrs.pageType == 'string' && iAttrs.pageType == 'legal-page'  ) {
							// $(iElement).find('#pdf').height(1285);
						}
						PDFObject.embed(pdfUrl, "#pdf", options);
						
						$timeout(function() {

							getIframe(function(iframe) {

								/* get height */
								var height = iframe.getElementsByClassName("page")[0].offsetHeight;

								console.log(height);
								// $('.pdfobject-container').css({
								// 	'height' : height - 210 + 'px'
								// });
							});
						}, 2000);
					});
				};

				var getIframe = function(callback) {
					var iframe = iElement.find("iframe")[0].contentDocument;

					$timeout(function() {

						if( JP.haveValue(iframe) ) {
							callback(iframe);
						}
					}, 3000);
				};
				/**
				*
				* @call on load
				*/
				$injector.invoke(init);
			}
		};
	};

	/**
	*
	* @dependency
	*/
	directive.$inject = ['$injector', '$timeout', '$rootScope', 'API_PREFIX'];

	/**
	*
	* @module
	*/
	angular
		.module('jobProgress')
		.directive('pdfViewer', directive);
})();