define([
    'jquery',
    'mage/validation',
    'mage/translate'
], function ($, validation, $t) {
    'use strict';

    return function() {
        return {
            // Configuration options
            options: {
                formSelector: '#offer_info'
            },

            /**
             * Initialize the validation module
             */
            init: function(options) {
                $.extend(this.options, options || {});
                this.initValidation();
                return this;
            },

            /**
             * Initialize form validation
             */
            initValidation: function() {
                var self = this;
                $(this.options.formSelector).validation({
                    rules: {
                        'offer_name': {
                            required: true,
                            minlength: 3
                        },
                        'offer_number': {
                            required: function() {
                                return !$('#auto_generate').is(':checked');
                            }
                        },
                        'expiry_date': {
                            required: true
                        }
                    },
                    messages: {
                        'offer_name': {
                            required: $t('Offer name is required'),
                            minlength: $t('Offer name must be at least 3 characters')
                        },
                        'offer_number': {
                            required: $t('Offer number is required when auto-generate is disabled')
                        },
                        'expiry_date': {
                            required: $t('Expiry date is required')
                        }
                    }
                });
            },

            /**
             * Validate form
             */
            validateForm: function() {
                var $form = $(this.options.formSelector);
                return $form.validation('isValid');
            }
        };
    };
});
