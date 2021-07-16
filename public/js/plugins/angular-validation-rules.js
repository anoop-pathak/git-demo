'use strict';

/**
* JobProgress Plus Module
* @angular validation Rules
*/

angular.module('validation.rule', ['validation'])

.directive('onlyDigits', function () {

    return {
        restrict: 'A',
        require: '?ngModel',
        link: function (scope, element, attrs, modelCtrl) {
            modelCtrl.$parsers.push(function (inputValue) {
                
                if (angular.isUndefined(inputValue) || inputValue == '' || inputValue == null ) {
                    return '';
                }
                if (angular.isFunction(inputValue.replace)) {
                    var transformedInput = inputValue.replace(/[^0-9.]/g, '');
                    if (transformedInput !== inputValue) {
                        modelCtrl.$setViewValue(transformedInput);
                        modelCtrl.$render();
                    }
                    return transformedInput;    
                }
                return inputValue;
            });
        }
    };
})

/**
 * @config
 */
.config(['$validationProvider', function($validationProvider) {

	/* Validation Rules */
    var expression = {
        required: function(value, scope, ele) {
            return !!value;
        },
        url: /((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/,
        email: /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,63}|[0-9]{1,3})(\]?)$/,
        number: /^\d+$/,
        decimal:/^[0-9]{1,6}(\.[0-9]{0,3})?$/,
        decimal2:/^[0-9]{1,6}(\.[0-9]{0,2})?$/,
        floatLimit: /^\d*(?:\.\d{0,2})?$/,
        alphaNumeric: /[A-Za-z0-9._-]+$/,
        equal: function(value, scope, element, attr) {
        	return attr.validatorEqual === value;
        },
        minlength: function(value, scope, element, attr) {
            var outString = angular.copy(value);

            if( JobProgress.isTrue(attr.validatorIsphone) ) {

                outString = outString.replace(/[`~!@#$%^&*()_|+\-=?;:'",.<>\{\}\[\]\\\/]/gi, '');
            }
            
            return attr.validatorLength <= outString.length;
        },
        notGreater: function(value, scope, element, attr) {
            return parseInt(attr.validationNotGreator) > parseInt(value);
        },
        maxlength: function(value, scope, element, attr) {
            console.log( value.length );
            return attr.validatorLength == value.length;
        },
        equalNotGreater: function(value, scope, element, attr) {
            
            if( !value ) {
                return true;
            }

            return parseFloat(attr.validationEqualNotGreater) >= parseFloat(value);
        },
        decimalNumber: function(value, scope, element, attr) {

            if( angular.isUndefined(value) ) {
                return true;
            }

            value = value.toString();
            
            if(value.indexOf('.') == -1) {
                return true;
            }
            if(value.indexOf('.') > -1) {
                return true;
            }

            return false;
        },
        noDecimal: function(value, scope, element, attr) {
            console.log('value', value);
            if( angular.isUndefined(value) 
                || value == '' 
                || value == null ) {
                return true;
            }

            value = value.toString();
            
            if( value != '' ) {

                if(value.indexOf('.') > -1) {
                    return false;
                }
            }
            return true;
        },
        validEmail: function(value) {
            if( !value || value == '' || value.length == 0 || value == null) {
                return true;
            }

            if(value.match(/^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,63}|[0-9]{1,3})(\]?)$/) ){
                return true;
            } else {
                return false;
            }
        },
        validUrl: function(value) {

            if( !value || value == '' || value.length == 0 || value == null) {
                return true;
            }

            if(value.match(/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/) ){
                return true;
            } else {
                return false;
            }
        },
        validMonth: function(value) {
           // var value = value.replace(new RegExp(/\\/g),”/”);
            if( !value || value == '' || value.length == 0 || value == null) {
                return true;
            }
            
            var month = value.substring(0, 2);
            var isSlash = value.substring(2, 5);

            if (isSlash == ' / ') {
                var year = value.substring(5, 7);
            }else{
                 var year = value.substring(2, 4);
            }
            console.log('year',year);
            if(parseInt(month) > 0 
                && parseInt(month) <= 12 
                && parseInt(year) > 0){
                return true;
            } else {
                return false;
            }
        }
    };

    /* Validation Default Messages */
    var defaultMsg = {
        required: {
            error: 'This field is Required.',
            success: 'Done'
        },
        url: {
            error: 'Please enter valid url.',
            success: 'Done'
        },
        validUrl: {
            error: 'Please enter valid url.',
            success: 'Done'
        },
        alphaNumeric: {
            error: 'Enter only alphabets and numeric.',
            success: 'Done'
        },
        decimalWithTwo: {
            error: 'Enter only numbers or decimal numbers (xx.xx).',
            success: 'Done'  
        },
        email: {
            error: 'Please enter valid email address.',
            success: 'Done'
        },
        validEmail: {
            error: 'Please enter valid email address.',
            success: 'Done'
        },
        number: {
            error: 'Please enter only number.',
            success: 'Done'
        },
        decimal:{
            error: 'Please enter a valid format (xx.xxx).',
            success: 'Done'
        },
        decimal2:{
            error: 'Please enter a valid format (xx.xx).',
            success: 'Done'
        },
        floatLimit:{
            error: 'Please enter a valid format (xx.xx).',
            success: 'Done'
        },
        equal: {
        	error: 'Confirm password not matched.',
            success: 'Done'
        },
        minlength: {
            error: 'Min Length',
            success: 'Done' 
        },
        maxlength: {
            error: 'Max Length',
            success: 'Done' 
        },
        notGreater: {
            error: 'Limit Range',
            success: 'Done' 
        },
        equalNotGreater: {
            error: 'Need less than and equal to',
            success: 'Done'   
        },
        decimalNumber: {
            error: 'Need less than and equal to',
            success: 'Done'
        },
        noDecimal: {
            error:'No Desimal Value.',
            success: 'Done'
        },
        validMonth: {
            error:'Please enter valid Month and Year.',
            success: 'Done'
        }
    };

    /* Validation Setup */
    $validationProvider.setExpression(expression).setDefaultMsg(defaultMsg);

}]);

