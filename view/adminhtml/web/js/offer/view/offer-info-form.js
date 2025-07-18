define([
    'jquery',
    'mage/url',
    'mage/translate',
    'OneMoveTwo_Offers/js/offer/view/modules/file-handler',
    'OneMoveTwo_Offers/js/offer/view/modules/form-validation',
    'OneMoveTwo_Offers/js/offer/view/modules/notifications'
], function ($, urlBuilder, $t, FileHandler, FormValidation, Notifications) {
    'use strict';

    return function(config, element) {
        var OfferInfoForm = {
            // Configuration options
            options: {
                formSelector: '#offer_info',
                saveAndContinueButton: '.btn-primary:contains("Save and Continue")',
                offerEmailCheckbox: '#offer_email',
                autoGenerateCheckbox: '#auto_generate',
                offerNumberInput: '#offer_number'
            },

            // Configuration from backend
            config: {
                existingFiles: config.existingFiles || [],
                mediaUrl: config.mediaUrl || '',
                offerId: config.offerId || null
            },

            // Module instances
            modules: {
                fileHandler: null,
                formValidation: null,
                notifications: null
            },

            /**
             * Initialize the component
             */
            init: function() {
                this.initModules();
                this.bindEvents();
                this.resetAutoGenerateState();
                return this;
            },

            /**
             * Initialize all modules
             */
            initModules: function() {
                // Initialize file handler
                this.modules.fileHandler = FileHandler().init(this.config, this.showError.bind(this));

                // Initialize form validation
                this.modules.formValidation = FormValidation().init({
                    formSelector: this.options.formSelector
                });

                // Initialize notifications
                this.modules.notifications = Notifications().init();
            },

            /**
             * Bind all events
             */
            bindEvents: function() {
                var self = this;

                // Form submission
                $(this.options.formSelector).on('submit', function(e) {
                    e.preventDefault();
                    self.submitForm(false);
                });

                // Save and Continue button
                $(this.options.saveAndContinueButton).on('click', function(e) {
                    e.preventDefault();
                    self.submitForm(true);
                });

                // Auto generate checkbox - toggle input field when checkbox state changes
                $(this.options.autoGenerateCheckbox).on('change', function() {
                    self.toggleOfferNumberInput(this.checked);
                });

                // Initialize the offer number input state based on checkbox
                // Always start with the checkbox checked and input disabled
                this.resetAutoGenerateState();
            },

            /**
             * Reset auto generate checkbox to checked state
             * This ensures the checkbox is always checked after page reload
             */
            resetAutoGenerateState: function() {
                $(this.options.autoGenerateCheckbox).prop('checked', true);
                this.toggleOfferNumberInput(true);
            },

            /**
             * Toggle offer number input based on auto generate checkbox
             */
            toggleOfferNumberInput: function(isChecked) {
                var $offerNumberInput = $(this.options.offerNumberInput);

                if (isChecked) {
                    // Disable the offer number input when auto-generate is checked
                    $offerNumberInput.addClass('disabled').prop('readonly', true);
                    $offerNumberInput.attr('disabled', 'disabled');
                } else {
                    // Enable the offer number input when auto-generate is unchecked
                    $offerNumberInput.removeClass('disabled').prop('readonly', false);
                    $offerNumberInput.removeAttr('disabled');
                }
            },

            /**
             * Show error message (proxy to notifications module)
             */
            showError: function(message) {
                this.modules.notifications.showError(message);
            },

            /**
             * Show success message (proxy to notifications module)
             */
            showSuccess: function(message) {
                this.modules.notifications.showSuccess(message);
            },

            /**
             * Submit form with all data
             */
            submitForm: function(continueEdit) {
                let self = this,
                    as_js_varname = 'iFrameResponse',
                    block = 'offer_tab_summary';

                // Validate form
                if (!this.modules.formValidation.validateForm()) {
                    return false;
                }

                var $form = $(this.options.formSelector);
                var formData = new FormData();

                // Add form fields
                $form.serializeArray().forEach(function(field) {
                    formData.append(field.name, field.value);
                });

                // Get files data from file handler
                var filesData = this.modules.fileHandler.getFilesData();

                // Add files as attachments array
                filesData.attachments.forEach(function(attachment) {
                    // Add file to FormData for upload
                    formData.append('attachments[]', attachment.file);
                });

                // Add attachments info as JSON for backend processing
                formData.append('attachments_info', JSON.stringify(filesData.attachments.map(function(att) {
                    return {
                        name: att.name,
                        size: att.size,
                        type: att.type,
                        id: att.id
                    };
                })));

                // Add files to delete
                if (filesData.filesToDelete.length > 0) {
                    formData.append('delete_attachments', JSON.stringify(filesData.filesToDelete));
                }

                // Add json flag to get JSON response
                formData.append('json', '1');

                if (block) {
                    formData.append('block', block);
                }

                if (as_js_varname) {
                    formData.append('as_js_varname', as_js_varname);
                }

                // Add continue flag
                if (continueEdit) {
                    formData.append('back', 'continue');
                }

                // Submit
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'text',
                    showLoader: true,
                    success: function(response) {
                        self.modules.notifications.handleAjaxResponse(response, continueEdit);
                    },
                    error: function(xhr) {
                        self.modules.notifications.handleError(xhr);
                    }
                });
            }
        };

        // Initialize the component
        OfferInfoForm.init();

        // Return component instance for external access
        return OfferInfoForm;
    };
});
