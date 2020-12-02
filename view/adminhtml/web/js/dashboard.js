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
        'mage/translate',
        'slick'
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
        let signUpButton = $('#pc-sign-up-submit-button');
        let linkAccountContent = $('#pc-link-account-form-content');
        let signupForm = $('#pc-sign-up-form');
        let signupSubmitted = false;
        let linkSubmitted = false;

        function submitSignUp()
        {
            let isValid = signupForm.validation('isValid');
            if (isValid && !signupSubmitted) {
                signupSubmitted = true;
                $('#pc-sign-up').fadeOut(200, function () {
                    $('#pc-waiting').fadeIn(200);
                    $.ajax({
                        showLoader: false,
                        url: signupForm.attr('action'),
                        data: signupForm.serialize(),
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        if (data.success) {
                            currentState = 'waiting';
                            setTimeout(checkStatus, 5000);
                        } else {
                            signupSubmitted = false;
                            $('#pc-waiting').fadeOut(200, function () {
                                $('#pc-sign-up').fadeIn(200);
                                $('#pc-sign-up-response-holder').html(data.error).addClass('error');
                            });
                        }
                    }).fail(function(jqXHR, status, err) {
                        signupSubmitted = false;
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
                });
            }
        }

        function submitSaveDetails()
        {
            let saveDetailsForm = $('#pc-save-details-form');
            let isValid = saveDetailsForm.validation('isValid');
            if (isValid && !linkSubmitted) {
                linkSubmitted = true;
                linkAccountContent.modal('closeModal');
                $('#pc-sign-up').fadeOut(200, function () {
                    $('#pc-waiting').fadeIn(200);
                    $.ajax({
                        showLoader: false,
                        url: saveDetailsForm.attr('action'),
                        data: saveDetailsForm.serialize(),
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        if (data.success) {
                            if($('#pc-save-details-form input:radio[name=type]:checked').val() === 'add') {
                                currentState = 'waiting';
                                setTimeout(checkStatus, 5000);
                            } else {
                                location.reload();
                            }
                        } else {
                            linkSubmitted = false;
                            $('#pc-waiting').fadeOut(200, function () {
                                $('#pc-sign-up').fadeIn(200);
                                linkAccountContent.modal('openModal');
                                $('#pc-link-account-response-holder').html(data.error).addClass('error');
                            });
                        }
                    }).fail(function(jqXHR, status, err) {
                        linkSubmitted = false;
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
                });
            }
        }

        function checkStatus()
        {
            $.ajax({
                showLoader: false,
                url: $('#pc-sign-up-waiting-call-url').val(),
                data: { 'store': $('#pc-sign-up-store-id').val() },
                type: "GET",
                dataType: 'json'
            }).done(function (data) {
                if (data.success) {
                    location.reload();
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

        function initSlick() {
            $('#pc-features-list').slick({
                dots: true,
                infinite: false,
                arrows: true,
                autoplay: true,
                autoplaySpeed: 5000,
                speed: 300,
                slidesToShow: 1,
                slidesToScroll: 1
            });
        }

        function initLinkAccountToggle() {
            $('#pc-save-details-form input:radio[name=type]').change(function () {
                if($(this).val() === 'link') {
                    $('#pc-details-store-fields').hide();
                    $('#pc-details-add-info').hide();
                    $('#pc-details-link-info').show();
                    $('#pc-details-store-name').removeClass('required');
                    $('#pc-details-store-url').removeClass('required');
                } else {
                    $('#pc-details-store-fields').show();
                    $('#pc-details-add-info').show();
                    $('#pc-details-link-info').hide();
                    $('#pc-details-store-name').addClass('required');
                    $('#pc-details-store-url').addClass('required');
                }
            });
        }

        if (currentState === 'not_configured') {

            initSlick();

            let options = {
                'title': $.mage.__('Link Existing Account'),
                modalClass: 'pc-link-account-modal',
                buttons: [{
                    text: $.mage.__('Link Account'),
                    class: 'primary',
                    click: submitSaveDetails
                }]
            };

            initLinkAccountToggle();

            modal(options, linkAccountContent);
            $('#pc-link-account-button').on('click', function () {
                linkAccountContent.modal('openModal');
            });

            signUpButton.on('click', submitSignUp);

        }

        if (currentState === 'waiting') {
            initSlick();
            checkStatus();
        }

        if (currentState === 'configured') {
            function pcNextStepsAction(action) {
                if (action.hasClass('pureclarity-clicked') === false) {
                    var linkId = action.attr('id');
                    action.addClass('pureclarity-clicked');

                    $.ajax({
                        showLoader: true,
                        url: $('#pc-next-steps-track-call-url').val(),
                        data: { form_key: window.FORM_KEY, 'store': $('#pc-current-store-id').val(), 'next-step-id': linkId },
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        action.click();
                    }).error(function(jqXHR, status, err) {
                        action.click();
                    }).fail(function(jqXHR, status, err) {
                        action.click();
                    });
                    return false;
                }
            }

            $('.pc-action').on('click', function () {
                pcNextStepsAction($(this));
            });
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
                progressCheckRunning: 0,
            };

            if (currentState === 'configured' && $('#pc-feeds-in-progress').val() === '1') {
                pcFeedSetInfoStore();
            }

            if (currentState === 'configured') {
                $('.pureclarity-headline-stat-tab').on('click', function () {
                    $('.pureclarity-headline-stat-tab').each(function(){
                        $(this).removeClass('pureclarity-headline-stat-active');
                    })
                    $(this).addClass('pureclarity-headline-stat-active');
                    var pcStatContentId = $(this).attr('id');
                    $('.pureclarity-headline-stat').hide();
                    $('#' + pcStatContentId + '-content').show();
                });
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

            feedRunObject.selectedStore = feedRunObject.preselectStore.val();
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
                    if (feedRunObject.progressCheckRunning === 0) {
                        setTimeout(pcFeedProgressCheck, 1000);
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
            feedRunObject.progressCheckRunning = 1;
            feedRunObject.selectedStore = feedRunObject.preselectStore.val();
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
                        setTimeout(pcFeedProgressCheck, 1000);
                    } else if (response.product.enabled === false &&
                        response.category.enabled === false &&
                        response.brand.enabled === false &&
                        response.user.enabled === false &&
                        response.orders.enabled === false
                    ) {
                        feedRunObject.progressCheckRunning = 0;
                        feedModalButton.addClass('pc-disabled');
                        feedModalButton.attr('title', $.mage.__('Feeds Not Enabled'));
                        feedModalButton.html($.mage.__('Feeds Not Enabled'));
                    } else {
                        var welcomeBanner = $('#pc-banner-welcome');
                        if (welcomeBanner) {
                            welcomeBanner.hide(1000, function (){
                                $('#pc-banner-getting-started').show(1000);
                            });
                        }
                        feedRunObject.progressCheckRunning = 0;
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