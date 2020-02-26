/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Customer/js/customer-data',
    'pcjs'
], function (Component, ko, $, customerData, pcjs) {
    'use strict';

    return Component.extend({
        initialize: function () {
            var self = this;
            this._super();
            this.data = customerData.get('customer-details');

            this.renderTrackingEvents = ko.computed(function () {
                if (pureclarityConfig.state.mode !== 'serverside' && self.data && pcjs.sectionUpdated(self.data()['data_id'], 'customer-details') && self.data()['isLoggedIn']) {
                    pcjs.push("customer_details", self.data()['customer']);
                    return true;
                }
                return false;
            });
        }
    });
});