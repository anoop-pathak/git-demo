(function() {
	'use strict';

	var dir = function ($injector, $timeout, JPEvent ) {
		return {
			restrict: 'A',
			link: function (scope, iElement, iAttrs) {

				var self = {};

				var init = function() {
					
					/**
					 *
					 * @classes
					 */
					self.containerClass 	= 'enable-customer-selection';
					self.subClass 			= 'selection-sub-container';
					self.subBtnCntrClass 	= 'selection-sub-container-btn';
					self.multiSelectionClass = 'multi-selection';


					/**
					 *
					 * @use after data load
					 */
					$timeout(function() {
						/**
						 * @set Main Container
						 */
						self.main = $(iElement).find(getAsRef('pc'));

						/**
						 * @bind [bindEvents]
						 */
						bindEvents();
					});
				};

				/**
				 *
				 * @get ref as Class 
				 */
				var getAsRef = function(type) {

					// Parent Container
					if( type == 'pc' ) {
						return '.'+self.containerClass;
					}

					// SUB Container
					if( type == 'sc' ) {
						return '.'+self.subClass;
					}

					// multi Selection Class
					if( type == 'msc' ) {
						return '.'+self.multiSelectionClass;
					}

					// Sub Container > Btn Container
					if( type == 'scb' ) {
						return '.'+self.subBtnCntrClass;
					}
				};

				/**
				 * @method [bindEvents]
				 * @use manage all click and Custom Events
				 **/
				var bindEvents = function(){

					if( self.main.length == 0 ) { return; }
					
					/**
					 *
					 * @bind click btns
					 */
					bindBtnClick(self.main)
				};

				/**
				 * @method [isMultiSelect]
				 * @return [Bool]	
				 * @use check the container option have ability to multi select
				 */
				var containerHaveSub = function(ele) {

					var ref = $(self.main);
					if( JP.haveValue(ele) ) { ref = ele; }

					return ($(ref).find(getAsRef('sc')));
				};

				/**
				 * @method [isMultiSelect]
				 * @return [Bool]	
				 * @use check the container option have ability to multi select
				 */
				var isMultiSelect = function(ele) {

					var ref = $(self.main);
					if( JP.haveValue(ele) ) { ref = ele; }

					return JP.isTrue($(ref).hasClass(self.multiSelectionClass));
				};

				var getUniqueBtn  = function() {
					return '<div contenteditable="false" class="'+self.subBtnCntrClass+'"> <a href="javascript:void(0)" class="btn btn-default" id="'+JP.getUniqueVal()+'" >Click to Select</a> </div>';
				};

				/**
				 * @method [appendBtnToSubContainer]
				 * @use Add button on Sub Containers
				 */
				var appendBtnToSubContainer = function(container){


					var subs = containerHaveSub(container);
					if( subs.length == 0 ) { return; }
					
					$.each( $(subs), function(i, sub) {
						if( $(sub).find( getAsRef('sc') ).length == 0 ) {
							$(sub).append( getUniqueBtn() );
						}
					});
				};

				/**
				 *
				 * @bind Click Events
				 */
				var bindBtnClick = function(container) {

					$(container).on('click', getAsRef('scb')+' a', function(e) {
						e.preventDefault();

						/**
						 * @get Sub & Parent Container
						 */
						var sub =  $(this).closest( getAsRef('sc') );
						var parent =  $(this).closest( getAsRef('pc') );

						/**
						 * @bind Multi Selection Events
						 */
						if( isMultiSelect(parent) ) {
							manageMultiSelection(this, sub, parent); return;
						}

						/**
						 * @bind Single Selection Events
						 */
						manageSingleSelection(this, sub, parent);
					});
				};

				/**
				 * @method Multi Selection Events
				 * @use multi Selection
				 * @params [btn refrence]
				 * @params [sub SubContainer refrence]
				 * @params [parent Container refrence]
				 */
				var manageMultiSelection  = function(btn, sub, parent) {


					
					if( $(btn).hasClass('btn-primary') ) { 
						
						$(btn)
							.removeClass('btn-primary')
							.addClass('btn-default')
							.text('Click to Select');

						/**
						* @tag html
						**/
						JPEvent.fire('a', {
							id: $(btn).attr('id'),
							val: $(btn).attr('class')
						});

						/**
						* @tag attribute
						**/
						JPEvent.fire('tags', {
							id: $(btn).attr('id'),
							val: ($(btn).text()),
							tag: 'a',
						});
						return; 
					}

					console.log( 'A' );


					$(btn)
						.removeClass('btn-default')
						.addClass('btn-primary')
						.text('Selected');


					/**
					* @tag html
					**/
					JPEvent.fire('a', {
						id: $(btn).attr('id'),
						val: $(btn).attr('class')
					});

					/**
					* @tag attribute
					**/
					JPEvent.fire('tags', {
						id: $(btn).attr('id'),
						val: ($(btn).text()),
						tag: 'a',
					});
				};

				/**
				 * @method Single Selection Events
				 * @use single Selection
				 * @params [btn refrence]
				 * @params [sub SubContainer refrence]
				 * @params [parent Container refrence]
				 */
				var manageSingleSelection = function(btn, sub, parent) {

					var selected = $(btn).hasClass('btn-primary');

					$(parent)
						.find( getAsRef('sc') )
						.find( getAsRef('scb') + '  a' )
						.removeClass('btn-primary')
						.addClass('btn-default')
						.text('Click to Select');

					
					if( selected ) { return; }

					$(btn)
						.removeClass('btn-default')
						.addClass('btn-primary')
						.text('Selected');


					$.each($(parent).find( getAsRef('sc') ), function(i, sub){
						var a = $(sub).find( getAsRef('scb') + '  a' );

						/**
						* @tag html
						**/
						JPEvent.fire('a', {
							id: $(a).attr('id'),
							val: $(a).attr('class')
						});

						/**
						* @tag attribute
						**/
						JPEvent.fire('tags', {
							id: $(a).attr('id'),
							val: ($(a).text()),
							tag: 'a'
						});

					})
				};


				$injector.invoke(init);
			}
		};
	};

	dir.$inject = ['$injector', '$timeout', 'JPEvent'];

	/**
	* jobProgress Module
	*
	* Description
	*/
	angular
		.module('jobProgress')
		.directive('seclectionContainer', dir);
})();