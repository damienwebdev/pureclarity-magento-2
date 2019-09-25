require(
    ['jquery', 'Magento_Ui/js/modal/modal', 'mage/validation', 'Magento_Ui/js/modal/alert'],
    function ($, modal, validation, modalAlert) {
        'use strict';

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
                    console.log(data);
                    if (data.success) {
                        $('#pureclarity_welcome').fadeOut(200, function () {
                            $('#pureclarity_waiting').fadeIn(200);
                        });

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
        }

        if (currentState === 'waiting') {
            checkStatus();
        }
    }
);