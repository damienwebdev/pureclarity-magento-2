/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'mage/validation',
        'Magento_Ui/js/modal/alert',
        'jquery/ui',
        'jquery/validate',
        'mage/translate'
    ],
    function ($, modal, validation, modalAlert) {
        'use strict';

        $.validator.addMethod(
            'validate-admin-password',
            function (value) {
                return value.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})/g);
            },
            $.mage.__('Password not strong enough, must contain 1 lowercase letter, 1 uppercase letter, 1 number and be 8 characters or longer')
        );

        let feedRunObject = {};
        let currentState = $('#pc-current-state').val();
        let signUpButton = $('#pc-sign-up-button');
        let signupContent = $('#pc-sign-up-form-content');
        let signupForm = $('#pc-sign-up-form');
        let saveDetailsForm = $('#pc-save-details-form');
        let saveDetailsButton = $('#pc-save-details-button');

        function submitSignUp()
        {
            let isValid = signupForm.validation('isValid');
            if (isValid) {
                signupContent.modal('closeModal');
                $.ajax({
                    showLoader: true,
                    url: signupForm.attr('action'),
                    data: signupForm.serialize(),
                    type: "POST",
                    dataType: 'json'
                }).done(function (data) {
                    if (data.success) {
                        $('#pc-welcome').fadeOut(200, function () {
                            $('#pc-waiting').fadeIn(200);
                        });

                        if (feedRunObject.selectStore.length) {
                            feedRunObject.selectStore.val($('#pc-sign-up-store-id').val());
                        } else {
                            feedRunObject.preselectStore.val($('#pc-sign-up-store-id').val());
                        }
                        feedRunObject.selectedStore = $('#pc-sign-up-store-id').val();
                        currentState = 'waiting';
                        setTimeout(checkStatus, 5000);
                    } else {
                        signupContent.modal('openModal');
                        $('#pc-sign-up-response-holder').html(data.error).addClass('error');
                    }
                }).fail(function(jqXHR, status, err) {
                    modalAlert({
                        title: $.mage.__('Error'),
                        content: $.mage.__('Please reload the page and try again'),
                        modalClass: 'alert',
                        buttons: [{
                            text: $.mage.__('Ok'),
                            class: 'action primary accept',
                            click: function () {
                                this.closeModal(true);
                            }
                        }]
                    });
                });
            }
        }

        function submitSaveDetails()
        {
            let isValid = saveDetailsForm.validation('isValid');
            if (isValid) {
                $.ajax({
                    showLoader: true,
                    url: saveDetailsForm.attr('action'),
                    data: saveDetailsForm.serialize(),
                    type: "POST",
                    dataType: 'json'
                }).done(function (data) {
                    if (data.success) {
                        $('#pc-welcome').fadeOut(200, function () {
                            $('#pc-content').fadeIn(200);
                        });
                        currentState = 'configured';
                        if (feedRunObject.selectStore.length) {
                            feedRunObject.selectStore.val($('#pc-details-store-id').val());
                        } else {
                            feedRunObject.preselectStore.val($('#pc-details-store-id').val());
                        }
                        feedRunObject.selectedStore = $('#pc-details-store-id').val();
                        pcFeedProgressCheck();
                    } else {
                        modalAlert({
                            title: $.mage.__('Error'),
                            content: $.mage.__('Please reload the page and try again'),
                            modalClass: 'alert',
                            buttons: [{
                                text: $.mage.__('Ok'),
                                class: 'action primary accept',
                                click: function () {
                                    this.closeModal(true);
                                }
                            }]
                        });
                    }
                }).fail(function(jqXHR, status, err) {
                    modalAlert({
                        title: $.mage.__('Error'),
                        content: $.mage.__('Please reload the page and try again'),
                        modalClass: 'alert',
                        buttons: [{
                            text: $.mage.__('Ok'),
                            class: 'action primary accept',
                            click: function () {
                                this.closeModal(true);
                            }
                        }]
                    });
                });
            }
        }

        function getStoreDetails()
        {
            $.ajax({
                showLoader: true,
                url: $('#pc-get-store-details-url').val(),
                data: { 'form_key': window.FORM_KEY, 'store_id': $('select#pc-sign-up-store-id').val() },
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                if (data.success && data.store_data) {
                    $('#pc-sign-up-store-currency').html(data.store_data.currency);
                    $('#pc-sign-up-store-timezone').html(data.store_data.timezone);
                    $('#pc-sign-up-store-url').val(data.store_data.url);
                } else {
                    modalAlert({
                        title: $.mage.__('Error'),
                        content: data.error,
                        modalClass: 'alert',
                        buttons: [{
                            text: $.mage.__('Ok'),
                            class: 'action primary accept',
                            click: function () {
                                this.closeModal(true);
                            }
                        }]
                    });
                }
            }).fail(function(jqXHR, status, err) {
                modalAlert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('Please reload the page and try again'),
                    modalClass: 'alert',
                    buttons: [{
                        text: $.mage.__('Ok'),
                        class: 'action primary accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
            });
        }

        function checkStatus()
        {
            $.ajax({
                showLoader: false,
                url: $('#pc-sign-up-waiting-call-url').val(),
                data: '',
                type: "GET",
                dataType: 'json'
            }).done(function (data) {
                if (data.success) {
                    $('#pc-waiting').fadeOut(200, function () {
                        $('#pc-content').fadeIn(200);
                    });
                    currentState = 'configured';
                    feedRunObject.statusLabelProducts.html($.mage.__('Waiting for feed run to start'));
                    feedRunObject.statusClassProducts.attr('class', 'pc-feed-status-icon pc-feed-waiting');
                    feedRunObject.statusLabelCategories.html($.mage.__('Waiting for feed run to start'));
                    feedRunObject.statusClassCategories.attr('class', 'pc-feed-status-icon pc-feed-waiting');
                    feedRunObject.statusLabelUsers.html($.mage.__('Waiting for feed run to start'));
                    feedRunObject.statusClassUsers.attr('class', 'pc-feed-status-icon pc-feed-waiting');
                    feedRunObject.statusLabelOrders.html($.mage.__('Waiting for feed run to start'));
                    feedRunObject.statusClassOrders.attr('class', 'pc-feed-status-icon pc-feed-waiting');
                    pcFeedProgressCheck();
                } else if (data.error !== '') {
                    modalAlert({
                        title: $.mage.__('Error'),
                        content: data.error,
                        modalClass: 'alert',
                        buttons: [{
                            text: $.mage.__('Ok'),
                            class: 'action primary accept',
                            click: function () {
                                this.closeModal(true);
                            }
                        }]
                    });
                } else {
                    setTimeout(checkStatus, 5000);
                }
            }).fail(function(jqXHR, status, err) {
                modalAlert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('Please reload the page and try again'),
                    modalClass: 'alert',
                    buttons: [{
                        text: $.mage.__('Ok'),
                        class: 'action primary accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
            });
        }

        if (signUpButton.length && currentState === 'not_configured') {
            let options = {
                'title': $.mage.__('Account Setup'),
                modalClass: 'pc-sign-up-modal',
                buttons: [{
                    text: $.mage.__('Sign up'),
                    class: 'primary',
                    click: submitSignUp
                }]
            };

            modal(options, signupContent);
            signUpButton.on('click', function () {
                signupContent.modal('openModal');
            });

            saveDetailsButton.on('click', submitSaveDetails);

            let selectStoreSignup = $('select#pc-sign-up-store-id');
            if (selectStoreSignup.length) {
                selectStoreSignup.on('change', getStoreDetails);
            }
        }

        if (currentState === 'waiting') {
            checkStatus();
        }

        let feedModalButton = $('#pc-feedpopupbutton');
        if (feedModalButton.length) {
            let options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                modalClass: 'pc-run-feeds pc-modal',
                title: $.mage.__('PureClarity Data Feed'),
                buttons: [{
                    text: $.mage.__('Run feeds now'),
                    class: 'primary',
                    click: pcFeedRun
                }]
            };

            modal(options, $('#pc-feeds-modal-popup'));

            feedModalButton.on('click', function () {
                if (feedModalButton.hasClass('pc-disabled') === false) {
                    pcFeedResetState();
                    $("#pc-feeds-modal-popup").modal('openModal');
                }
            });

            feedRunObject = {
                runFeedUrl: $("#pc-feed-run-url").val(),
                progressFeedUrl: $("#pc-feed-progress-url").val(),
                selectStore: $('select#pc-feed-info-store'),
                preselectStore: $('input#pc-feed-info-store'),
                messageContainer: $('#pc-statusMessage'),
                chkProducts: $('#pc-chkProducts'),
                chkCategories: $('#pc-chkCategories'),
                chkBrands: $('#pc-chkBrands'),
                chkUsers: $('#pc-chkUsers'),
                chkOrders: $('#pc-chkOrders'),
                statusLabelProducts: $('#pc-productFeedStatusLabel'),
                statusLabelCategories: $('#pc-categoryFeedStatusLabel'),
                statusLabelBrands: $('#pc-brandFeedStatusLabel'),
                statusLabelUsers: $('#pc-userFeedStatusLabel'),
                statusLabelOrders: $('#pc-ordersFeedStatusLabel'),
                statusClassProducts: $('#pc-productFeedStatusClass'),
                statusClassCategories: $('#pc-categoryFeedStatusClass'),
                statusClassBrands: $('#pc-brandFeedStatusClass'),
                statusClassUsers: $('#pc-userFeedStatusClass'),
                statusClassOrders: $('#pc-ordersFeedStatusClass'),
                selectedStore: 0,
            };

            if (currentState === 'configured' && $('#pc-feeds-in-progress').val() === '1') {
                pcFeedSetInfoStore();
            }

            let feedInfoStoreSelect = $('select#pc-feed-info-store');
            if (feedInfoStoreSelect.length) {
                feedInfoStoreSelect.on('change', pcFeedSetInfoStore);
            }
        }

        function pcFeedSetInfoStore()
        {
            feedRunObject.selectedStore = $('select#pc-feed-info-store').val();
            pcFeedProgressCheck();
        }

        function pcFeedRun()
        {
            if (!feedRunObject.chkProducts.is(':checked') &&
                !feedRunObject.chkCategories.is(':checked') &&
                (feedRunObject.chkBrands.length === 0 || !feedRunObject.chkBrands.is(':checked')) &&
                !feedRunObject.chkUsers.is(':checked') &&
                !feedRunObject.chkOrders.is(':checked')
            ) {
                return;
            }

            if (feedRunObject.selectStore.length) {
                feedRunObject.selectedStore = feedRunObject.selectStore.val();
            } else {
                feedRunObject.selectedStore = feedRunObject.preselectStore.val();
            }
            feedRunObject.chkProducts.prop("disabled", true);
            feedRunObject.chkCategories.prop("disabled", true);

            if (feedRunObject.chkBrands.length) {
                feedRunObject.chkBrands.prop("disabled", true);
            }

            feedRunObject.chkUsers.prop("disabled", true);
            feedRunObject.chkOrders.prop("disabled", true);
            feedRunObject.isComplete = false;

            var urlParts = [feedRunObject.runFeedUrl + '?storeid=' + feedRunObject.selectedStore];
            urlParts.push('product=' + feedRunObject.chkProducts.is(':checked'));
            urlParts.push('category=' + feedRunObject.chkCategories.is(':checked'));
            if (feedRunObject.chkBrands.length) {
                urlParts.push('brand=' + feedRunObject.chkBrands.is(':checked'));
            }
            urlParts.push('user=' + feedRunObject.chkUsers.is(':checked'));
            urlParts.push('orders=' + feedRunObject.chkOrders.is(':checked'));

            $.ajax({
                showLoader: true,
                url: urlParts.join('&'),
                data: { form_key: window.FORM_KEY, storeid: feedRunObject.selectedStore },
            }).done(function(response) {
                    $("#pc-feeds-modal-popup").modal('closeModal');
                    pcInitProgress();
                    setTimeout(pcFeedProgressCheck, 1000);
            }).fail(function(jqXHR, status, err) {
                modalAlert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('Please reload the page and try again'),
                    modalClass: 'alert',
                    buttons: [{
                        text: $.mage.__('Ok'),
                        class: 'action primary accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
            });
        }

        function pcInitProgress() {

            if (feedRunObject.chkProducts.is(':checked')) {
                feedRunObject.statusLabelProducts.html($.mage.__('Waiting for feed run to start'));
                feedRunObject.statusClassProducts.attr('class', 'pc-feed-status-icon pc-feed-waiting');
            }

            if (feedRunObject.chkCategories.is(':checked')) {
                feedRunObject.statusLabelCategories.html($.mage.__('Waiting for feed run to start'));
                feedRunObject.statusClassCategories.attr('class', 'pc-feed-status-icon pc-feed-waiting');
            }

            if (feedRunObject.chkBrands.length && feedRunObject.chkBrands.is(':checked')) {
                feedRunObject.statusLabelBrands.html($.mage.__('Waiting for feed run to start'));
                feedRunObject.statusClassBrands.attr('class', 'pc-feed-status-icon pc-feed-waiting');
            }

            if (feedRunObject.chkUsers.is(':checked')) {
                feedRunObject.statusLabelUsers.html($.mage.__('Waiting for feed run to start'));
                feedRunObject.statusClassUsers.attr('class', 'pc-feed-status-icon pc-feed-waiting');
            }

            if (feedRunObject.chkOrders.is(':checked')) {
                feedRunObject.statusLabelOrders.html($.mage.__('Waiting for feed run to start'));
                feedRunObject.statusClassOrders.attr('class', 'pc-feed-status-icon pc-feed-waiting');
            }
        }

        function pcFeedProgressCheck() {
            $.ajax({
                url: feedRunObject.progressFeedUrl,
                data: {form_key: window.FORM_KEY, storeid: feedRunObject.selectedStore},
            }).done(function (response){
                if (!response){
                    // session has ended, reload to force login
                    location.reload();
                } else {
                    feedRunObject.statusLabelProducts.html(response.product.label);
                    feedRunObject.statusLabelCategories.html(response.category.label);
                    feedRunObject.statusLabelBrands.html(response.brand.label);
                    feedRunObject.statusLabelUsers.html(response.user.label);
                    feedRunObject.statusLabelOrders.html(response.orders.label);
                    feedRunObject.statusClassProducts.attr('class', 'pc-feed-status-icon ' + response.product.class);
                    feedRunObject.statusClassCategories.attr('class', 'pc-feed-status-icon ' + response.category.class);
                    feedRunObject.statusClassBrands.attr('class', 'pc-feed-status-icon ' + response.brand.class);
                    feedRunObject.statusClassUsers.attr('class', 'pc-feed-status-icon ' + response.user.class);
                    feedRunObject.statusClassOrders.attr('class', 'pc-feed-status-icon ' + response.orders.class);

                    if (response.product.running ||
                        response.category.running ||
                        response.brand.running ||
                        response.user.running ||
                        response.orders.running
                    ) {
                        feedModalButton.addClass('pc-disabled');
                        feedModalButton.attr('title', $.mage.__('Feeds In Progress'));
                        feedModalButton.html($.mage.__('Feeds In Progress'));
                        setTimeout(pcFeedProgressCheck, 1000);
                    } else if (response.product.enabled === false &&
                        response.category.enabled === false &&
                        response.brand.enabled === false &&
                        response.user.enabled === false &&
                        response.orders.enabled === false
                    ) {
                        feedModalButton.addClass('pc-disabled');
                        feedModalButton.attr('title', $.mage.__('Feeds Not Enabled'));
                        feedModalButton.html($.mage.__('Feeds Not Enabled'));
                    } else {
                        feedModalButton.attr('title', $.mage.__('Run Feeds Manually'));
                        feedModalButton.html($.mage.__('Run Feeds Manually'));
                        feedModalButton.removeClass('pc-disabled');
                    }
                }
            }).fail(function(jqXHR, status, err) {
                modalAlert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('Please reload the page and try again'),
                    modalClass: 'alert',
                    buttons: [{
                        text: $.mage.__('Ok'),
                        class: 'action primary accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
            });
        }

        function pcFeedResetState() {
            feedRunObject.isComplete = true;
            feedRunObject.chkProducts.prop("disabled", false);
            feedRunObject.chkCategories.prop("disabled", false);
            if (feedRunObject.chkBrands.length) {
                feedRunObject.chkBrands.prop("disabled", false);
            }
            feedRunObject.chkUsers.prop("disabled", false);
            feedRunObject.chkOrders.prop("disabled", false);
        }
    }
);