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
            this.data = customerData.get('cart-update');

            this.renderTrackingEvents = ko.computed(function () {
                if (pureclarityConfig.state.mode !== 'serverside' && pcjs.sectionUpdated(self.data()['data_id'], 'cart-update')) {
                    pcjs.push("set_basket", self.data().items);
                    return true;
                }
                return false;
            });
        }
    });
});