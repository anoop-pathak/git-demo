(function() {
    'use strict';

     /**
    *
    * @controller function
    */
    var Ctrl = function ($modalInstance, $injector, Customer, JobData) {

         var viewData = this;

         /**
        * 
        * @default (init)
        */
        var init = function() {

             viewData.job = JP.getObject(JobData);
            console.log('viewData.job', viewData.job);
            getList();
        };

         /**
        * 
        * @list
        */
        var getList = function() {
            viewData.loading = true;

             Customer.getGreenskyList({job_id: viewData.job.id, limit: 0}, viewData.job.share_token).then(function(success) {

                 viewData.list = JP.getArray(success.data.data);

                 viewData.list.map(function(val) {
                    val.dateTime = moment(val.created_at).format('MM/DD/YYYY');
                });
            }).finally(function() {

                 viewData.loading = false;
            });
        };

         /**
        *
        * @close btn
        */
        viewData.close = function() {
            $modalInstance.dismiss();
        };

         /**
        *
        * @call on load
        */
        $injector.invoke(init);
    };

     /**
    *
    * @dependency
    */
    Ctrl.$inject = ['$modalInstance', '$injector', 'Customer', 'JobData'];

     /**
    * Job Progress Plus Module
    *
    * @controller jobProgress:(ConfirmCtrl)
    */
    angular
        .module('jobProgress')
        .controller('GreenSkyListCtrl', Ctrl);
})();