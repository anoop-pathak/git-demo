(function() {
    'use strict';

    /**
    *
    * @hide console.
    */
    // window['console']['log'] = function() { };


    /**
    *
    * Main module of the application.
    */
    angular.module('jobProgress',[
        'signature',
        'ui.bootstrap',
        'angular-loading-bar',
        'aside',
        'ui.mask',
        'validation',
        'validation.rule',
        'jobProgress.scroll'
    ])

    /**
    *
    * Config block
    */
    .config(['$interpolateProvider', 'cfpLoadingBarProvider', '$validationProvider',
        function($interpolateProvider, cfpLoadingBarProvider, $validationProvider) {

        /**
        *
        * @show/hide Message
        */
        $validationProvider.showSuccessMessage = false; // or true(default)
        // $validationProvider.showErrorMessage = true; // or true(default)

        /**
        *
        * @set delimaters
        */
        $interpolateProvider.startSymbol('<%');
        $interpolateProvider.endSymbol('%>');

        /**
        *
        * @set spinner
        */
        cfpLoadingBarProvider.includeSpinner = true;
    }]);


    /************************************************************
                            JP CLASS
    ************************************************************/
    String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.replace(new RegExp(search, 'g'), replacement);
    };
    // var JobProgress = function() {};

    var JobProgress = {
        ng: function(){
            return angular.injector(['ng', 'jobProgress']);
        },
        haveValue: function(text) {

            if( !angular.isDefined(text) ) {
                return false;
            }

            if( text === '0' || text === 0){
                return true;
            }

            if( text != '' && text != null  ) {
                return true;
            }

            return false;
        },
        isFunction: function(fun) {
            if( JobProgress.haveValue(fun) && angular.isFunction(fun) ) {
                return true;
            }

            return false;
        },
        isTrue: function(bool, returnNumeric) {
            if( !angular.isDefined(bool)) {
                return (returnNumeric === true) ? 0 :false;
            }

            if( bool == '1' || bool == 1 || bool == 'true' || bool == true ) {
                return true;
            }

            return (returnNumeric === true) ? 0 : false;
        },
        getTableCellText: function(td) {

            if( $(td).hasClass('cell-with-dropdown') ) {
                return $(td).find('.cell-text').text().trim();
            }

            return $(td).text().trim();
        },
        getObject: function(object, secondTime) {

            if( angular.isObject(object) ) {
                return object;
            }

            if( !JP.haveValue(object) ) {
                return {};
            }

            if( !JobProgress.isTrue(secondTime) && angular.isString(object) ) {
                return JobProgress.getObject( angular.fromJson(object), true);
            }

            return {};
        },
        getArray: function(arr) {
            if( JobProgress.arrayHaveValues(arr) ) {
                return arr;
            }

            return [];
        },
        getString: function(str) {

            if( JobProgress.haveValue(str) && angular.isString(str)) {
                return (str).toString();
            }

            return '';
        },
        arrayHaveValues: function(array) {

            if( angular.isDefined(array) && angular.isArray(array) && array.length > 0) {
                return true;
            }

            return false;
        },
        isArray: function(arr) {
            if( angular.isDefined(arr) && angular.isArray(arr) && arr.length > 0 ) {
                return true;
            }

            return false;
        },
        setTableCellText: function(td, text) {
            
            if( $(td).hasClass('cell-with-dropdown') ) {
                $(td).find('.cell-text').text(text);
            } else {
                $(td).text(text);
            }
        },
        int: function(val) {
            return parseInt(val);
        },
        replaceText: function(text, withoutSign) {
    
            if( angular.isUndefined(text) || text == null || text == '') {
                return '';
            }

            var val = angular.copy(text.toString().trim())
            val = val.replaceAll('\\$', '');
            val = val.replaceAll("[a-zA-Z]", '');
                
            if( JobProgress.isTrue(withoutSign) ) {
                val = val.replace('-', '');
                val = val.replace('+','');
            }

            return  val.replaceAll(',', '');
        },
        isObject : function(obj) {
             if( angular.isDefined(obj) && angular.isObject(obj) ) {
                return true;
            }

            return false;
        },
        isValidVal: function(val) {
             return ( JP.haveValue(val) && !isNaN(val) );
        },
        numberWithCurrency: function(val) {
            var filter  = JP.ng().get('$filter');
            return filter('currency')(val)
        },
        removeDollorCommaForCalculation: function(text, withoutSign) {

            if( angular.isUndefined(text) || text == null || text == '') {
                return '';
            }

            var val = angular.copy(text.toString().trim())
            val = val.replaceAll('\\$', '');
            val = val.replaceAll("[a-zA-Z]", '');

            if( JP.isTrue(withoutSign) ) {
                val = val.replace('-', '');
                val = val.replace('+','');
            }

            return  val.replaceAll(',', '');
        },
        isMobile: function() {
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ||
               (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.platform))) {

                return true;
            }

            return false;
        },
        getAPPUrl: function() {
            var func = JP.ng().get('WEB_APP');
            return func();
        }
    };

    // window.Enviroment = Enviroment;

    window.JP = JobProgress;
})();

