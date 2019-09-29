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
        let currentState = $('#pureclarity_current_state').val();
        let signUpButton = $('#sign_up_button');
        let signupContent = $('#sign_up_form_content');
        let signupForm = $('#sign_up_form');
        let saveDetailsForm = $('#save_details_form');
        let saveDetailsButton = $('#save_details_button');

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
                        $('#pureclarity_welcome').fadeOut(200, function () {
                            $('#pureclarity_waiting').fadeIn(200);
                        });

                        feedRunObject.selectedStore = $('#pureclarity_signup_store_id').val();
                        currentState = 'waiting';
                        setTimeout(checkStatus, 5000);
                    } else {
                        signupContent.modal('openModal');
                        $('#response_holder').html(data.error).addClass('error');
                    }
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
                        $('#pureclarity_welcome').fadeOut(200, function () {
                            $('#pureclarity_content').fadeIn(200);
                        });
                        currentState = 'configured';
                        feedRunObject.selectedStore = $('#pureclarity_details_store_id').val();
                        pcFeedProgressCheck();
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
                });
            }
        }

        function getStoreDetails()
        {
            $.ajax({
                showLoader: true,
                url: $('#pureclarity_get_store_details_url').val(),
                data: { 'form_key': window.FORM_KEY, 'store_id': $('select#pureclarity_store_id').val() },
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                if (data.success && data.store_data) {
                    $('#pureclarity_store_currency').html(data.store_data.currency);
                    $('#pureclarity_store_timezone').html(data.store_data.timezone);
                    $('#pureclarity_store_url').val(data.store_data.url);
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
            });
        }

        function checkStatus()
        {
            $.ajax({
                showLoader: false,
                url: $('#sign_up_waiting_call_url').val(),
                data: '',
                type: "GET",
                dataType: 'json'
            }).done(function (data) {
                if (data.success) {
                    $('#pureclarity_waiting').fadeOut(200, function () {
                        $('#pureclarity_content').fadeIn(200);
                    });
                    currentState = 'configured';
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
            });
        }

        if (signUpButton.length && currentState === 'not_configured') {
            let options = {
                'title': 'Account Setup',
                buttons: [{
                    text: 'Sign Up',
                    class: 'primary',
                    click: submitSignUp
                }]
            };

            modal(options, signupContent);
            signUpButton.on('click', function () {
                signupContent.modal('openModal');
            });

            saveDetailsButton.on('click', submitSaveDetails);

            let selectStoreSignup = $('select#pureclarity_store_id');
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
                modalClass: 'pc-run-feeds',
                title: $.mage.__('PureClarity Data Feed'),
                buttons: [{
                    text: $.mage.__('Run feeds now'),
                    class: '',
                    click: pcFeedRun
                }]
            };

            modal(options, $('#pc-feeds-modal-popup'));

            feedModalButton.on('click', function () {
                pcFeedResetState();
                $("#pc-feeds-modal-popup").modal('openModal');
            });

            feedRunObject = {
                runFeedUrl: $("#pc-feed-run-url").val(),
                progressFeedUrl: $("#pc-feed-progress-url").val(),
                selectStore: $('select#pc-selectStore'),
                preselectStore: $('input#pc-selectStore'),
                messageContainer: $('#pc-statusMessage'),
                chkProducts: $('#pc-chkProducts'),
                chkCategories: $('#pc-chkCategories'),
                chkBrands: $('#pc-chkBrands'),
                chkUsers: $('#pc-chkUsers'),
                chkOrders: $('#pc-chkOrders'),
                statusProducts: $('#pc-productFeedStatus'),
                statusCategories: $('#pc-categoryFeedStatus'),
                statusBrands: $('#pc-brandFeedStatus'),
                statusUsers: $('#pc-userFeedStatus'),
                statusOrders: $('#pc-ordersFeedStatus'),
                selectedStore: 0,
            };

            if (currentState === 'configured' && $('#pc-feeds-in-progress').val() === '1') {
                feedRunObject.selectedStore = $('#pc-feeds-in-progress-store').val();
                pcFeedProgressCheck();
            }
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
                feedRunObject.selectedStore = feedRunObject.selectStore.find(":selected").val();
                feedRunObject.selectStore.prop("disabled", true);
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
                url: urlParts.join('&'),
                data: { form_key: window.FORM_KEY, storeid: feedRunObject.selectedStore },
            })
                .done(function(response) {
                    $("#pc-feeds-modal-popup").modal('closeModal');
                    pcInitProgress();
                    setTimeout(pcFeedProgressCheck, 1000);
                })
                .fail(function(jqXHR, status, err) {
                    feedRunObject.callError = jqXHR.responseText;
                });
        }

        function pcInitProgress() {
            if (feedRunObject.chkProducts.is(':checked')) {
                feedRunObject.statusProducts.html($.mage.__('Waiting for feed run to start'));
            }

            if (feedRunObject.chkCategories.is(':checked')) {
                feedRunObject.statusCategories.html($.mage.__('Waiting for feed run to start'));
            }

            if (feedRunObject.chkBrands.length && feedRunObject.chkBrands.is(':checked')) {
                feedRunObject.statusBrands.html($.mage.__('Waiting for feed run to start'));
            }

            if (feedRunObject.chkUsers.is(':checked')) {
                feedRunObject.statusUsers.html($.mage.__('Waiting for feed run to start'));
            }

            if (feedRunObject.chkOrders.is(':checked')) {
                feedRunObject.statusOrders.html($.mage.__('Waiting for feed run to start'));
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
                    feedRunObject.statusProducts.html(response.product.label);
                    feedRunObject.statusCategories.html(response.category.label);
                    feedRunObject.statusBrands.html(response.brand.label);
                    feedRunObject.statusUsers.html(response.user.label);
                    feedRunObject.statusOrders.html(response.orders.label);

                    if (response.product.running ||
                        response.category.running ||
                        response.brand.running ||
                        response.user.running ||
                        response.orders.running
                    ) {
                        setTimeout(pcFeedProgressCheck, 1000);
                    }
                }
            });
        }

        function pcFeedResetState() {
            feedRunObject.isComplete = true;
            if (feedRunObject.selectStore.length) {
                feedRunObject.selectStore.prop("disabled", false);
            }
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