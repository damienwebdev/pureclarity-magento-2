
define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Customer/js/customer-data',
    'pcjs'
], function (Component, ko, $, customerData, pcjs) {

    return Component.extend({
        initialize: function () {
            var self = this;
            this._super();
            this.data = customerData.get('cart-update');

            this.renderTrackingEvents = ko.computed(function () {
                if (pcjs.sectionUpdated(self.data()['data_id'], 'cart-update')) {
                    if (self.data().items.length == 0) {
                        pcjs.push("set_basket", {cart_empty: true});
                    } else {
                        pcjs.push("set_basket", self.data().items); 
                    }
                    return true;
                }
                return false;
            });
        }
    });
});