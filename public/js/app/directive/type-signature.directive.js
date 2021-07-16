(function() {
	'use strict';

	/**
	*
	* @directive function
	*/
	var textSignatureDirective = function($injector, $timeout, $rootScope, API_PREFIX) {
		return {
			restrict: 'E',
			templateUrl: API_PREFIX()+'/js/app/views/signature-directive.html',
			replace: true,
			scope: {
				opt:'=options'
			},
			link: function (scope, iElement, iAttrs) {
				
				if( angular.isUndefined(scope.opt) || !angular.isObject(scope.opt)) {
					scope.opt = {};
				}

				var viewData  = this;

				/* fonts */
				scope.familyList  = [
					'Satisfy',
					'Arial',
					'Sans Serif',
					'Serif'
					// 'Comic Sans MS',
					// 'Garamond',
					// 'Georgia',
					// 'Tahoma',
					// 'Trebuchet MS',
					// 'Verdana'
				];

				/* style */
				scope.styleList = [
					'Bold',
					'Normal', 
					'Italic'
				];

				scope.baseUrl = API_PREFIX() ; 
				scope.userEnterFamily = 'Satisfy';
				scope.userEnterStyle = 'Bold';
				scope.userEnterSize = '58';

				scope.textInputObj = {
					maxLength: '30'
				};

				// GRAPHICS TO CANVAS /////
				var CVS = document.createElement('canvas');
				CVS.width  = 350;
				CVS.height = 220;
				jQuery(iElement[0]).find('.canvas-section').append(CVS);

				/**
				*
				* @onload set 
				*/
				var cvsObj = {
				    image      : API_PREFIX() + "/proposal_theme/images/signature-background.png",
				    text       : "",
				    fontFamily : angular.copy(scope.userEnterFamily),
				    fontStyle  : angular.copy(scope.userEnterStyle),
				    fontSize   : (angular.copy(scope.userEnterSize) || 0)+'px',
				    color      : "rgba(0, 0, 0, 0.1)"
				};
				

				/**
				*
				* @draw image
				*/
				function sendToCanvas( ob ){

					scope.linkForNewTab = '';

					// iElement.find('.canvas-section').appendChild(CVS); // Add canvas to DOM
					var ctx = CVS.getContext('2d');
				  	var img = new Image();
				  	img.onload = function(){
					  	ctx.clearRect(0, 0, CVS.width, CVS.height);
					    ctx.drawImage(img, 0, 0);
					    ctx.font = ob.fontStyle+' '+ob.fontSize+' '+ob.fontFamily;
					    ctx.textAlign = 'center';
					    ctx.fillStyle = ob.color;
					    ctx.fontStyle = 'Bold';					    
					    ctx.fillText(ob.text, CVS.width/2, CVS.height/2);

					};
				  	img.src = ob.image;
				    
				    // $timeout(function() {
				    // 	console.log('asds');
				    // 	scope.imageImgaeUrl = CVS.toDataURL();
				    // }, 800, false);
				};


				scope.getLink = function() {
					scope.linkForNewTab = CVS.toDataURL();
				};

				scope.$on('Sign:UpdateFormat', function(event, arg) {
					scope.updateImage(arg.type, arg.val);
				});

				/**
				*
				* @update Image
				*/
				scope.updateImage = function (type, val){
				
					switch(type) {
						case 'size':
							scope.userEnterSize = val;
							cvsObj.fontSize = scope.userEnterSize+'px';
						break;

						case 'font-family':
							scope.userEnterFamily = val;
							cvsObj.fontFamily = scope.userEnterFamily;
						break;

						case 'style':
							scope.userEnterStyle = val;
							cvsObj.fontStyle = scope.userEnterStyle;
						break;

						case 'text':
							cvsObj.text     = scope.userEntertext;
							cvsObj.color    = 'rgba(0, 0, 0, 0.7)';
						break; 
					}

					setCSVFontSize();

					$timeout(function() {

						sendToCanvas( cvsObj );
					});
				};

				/**
				 * Set font size of text according to length
				 */
				var setCSVFontSize = function() {
					var textLenghtBreakups = [40, 35, 30, 25, 20, 15];
					var fontSize = scope.userEnterSize;
					var index = textLenghtBreakups.findIndex(function(val) { return val <= cvsObj.text.length });
					var lowestFontSize = 14;
					var fontSizeDiff = 4; // font size diff based on index

					if (cvsObj.fontFamily != 'Satisfy') {
						fontSize = 54
						lowestFontSize = 12;
						fontSizeDiff = 3;
					}

					// diffrent settings for Sans srif font family
					if (cvsObj.fontFamily == 'Sans Serif') {
						fontSize = 48
						lowestFontSize = 10;
						if (cvsObj.fontStyle == 'Bold') {
							fontSize = 44
							fontSizeDiff = 2;
						}
					}

					if (index != -1) {
						fontSize = lowestFontSize + ((index + 1) * fontSizeDiff);
					}

					cvsObj.fontSize = fontSize + 'px';
				}
				
				/**
				*
				* !is Valid image
				*/
				scope.opt.isValidImage  = function() {
					return ( !scope.userEntertext || parseInt(scope.userEnterSize) < 40 ) ? false : true;
				};

				/**
				*
				* !is Valid image
				*/
				scope.opt.getImage  = function() {
					return CVS.toDataURL();
				};



				/**
				*
				* @onload set Temp
				*/
				var init  = function() {
 					
					/**
					 *
					 * @set Data onload
					 */
					sendToCanvas( cvsObj ); 
				};


				$injector.invoke(init);
			}
		};
	};

	/**
	*
	* @dependency
	*/
	textSignatureDirective.$inject = ['$injector', '$timeout', '$rootScope', 'API_PREFIX'];

	/**
	*
	* @module
	*/
	angular
		.module('jobProgress')
		.directive('textSignature', textSignatureDirective);
})();