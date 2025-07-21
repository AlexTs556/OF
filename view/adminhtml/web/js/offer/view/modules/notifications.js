define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, alert) {
    'use strict';

    return function() {
        return {
            /**
             * Initialize the notifications module
             */
            init: function() {
                return this;
            },

            /**
             * Show success message
             */
            showSuccess: function(message) {
                alert({
                    title: $t('Success'),
                    content: message,
                    modalClass: 'modal-success'
                });
            },

            /**
             * Show error message
             */
            showError: function(message) {
                // Create notification element
                var $notification = $('<div class="file-error-notification">' + message + '</div>');
                $('body').append($notification);

                setTimeout(function() {
                    $notification.remove();
                }, 5000);

                // Also show modal for critical errors
                alert({
                    title: $t('Error'),
                    content: message,
                    modalClass: 'modal-error'
                });
            },

            /**
             * Update page blocks with response data
             */
            updatePageBlocks: function(response) {
                // Get area ID function
                function getAreaId(area) {
                    return 'offer-' + area;
                }

                // Always add message to loading areas
                var loadingAreas = Object.keys(response);
                if (loadingAreas.indexOf('message') === -1) {
                    loadingAreas.push('message');
                }

                // Update each block
                loadingAreas.forEach(function(areaName) {
                    var blockId = getAreaId(areaName);
                    var $block = $('#' + blockId);

                    if ($block.length) {
                        // Only update if message area has content or it's not message area
                        if (areaName !== 'message' || response[areaName]) {
                            $block.html(response[areaName]);

                            // Trigger content updated event for reinitializing components
                            $block.trigger('contentUpdated');

                            // Execute callback if exists
                            if ($block[0].callback && typeof window[this.getCallbackName(areaName)] === 'function') {
                                window[this.getCallbackName(areaName)]();
                            }
                        }
                    }
                }, this);

                // Update page title if provided
                if (response.header) {
                    $('.page-actions-inner').attr('data-title', response.header);
                }
            },

            /**
             * Get callback name for area
             */
            getCallbackName: function(area) {
                return area + 'Loaded';
            },

            /**
             * Handle AJAX response
             */
            handleAjaxResponse: function(response, continueEdit, successCallback) {
                var self = this;
                var parsedResponse = null;


                console.log(response);

                // Handle mixed HTML/JS response
                if (typeof response === 'string') {
                    // Check if response starts with <script> tag (HTML response)
                    if (response.trim().indexOf('<script>') === 0) {
                        // Extract JSON directly from response
                        var jsonStart = response.indexOf('{');
                        var jsonEnd = response.lastIndexOf('}') + 1;

                        if (jsonStart !== -1 && jsonEnd !== -1) {
                            var jsonPart = response.substring(jsonStart, jsonEnd);
                            try {
                                parsedResponse = JSON.parse(jsonPart);
                            } catch (e) {
                                console.error('Failed to parse extracted JSON:', e);
                                self.showError($t('Invalid response format'));
                                return;
                            }
                        } else {
                            console.error('No JSON found in script response');
                            self.showError($t('Invalid response format'));
                            return;
                        }
                    } else {
                        // Try to parse as direct JSON
                        try {
                            parsedResponse = JSON.parse(response);
                        } catch (e) {
                            console.error('Failed to parse JSON response:', e);
                            self.showError($t('Invalid response format'));
                            return;
                        }
                    }
                } else {
                    parsedResponse = response;
                }

                if (!parsedResponse) {
                    self.showError($t('Empty response received'));
                    return;
                }

                // Handle different response scenarios
                if (parsedResponse.reload) {
                    location.reload();
                    return;
                }

                if (parsedResponse.error) {
                    self.showError(parsedResponse.message || $t('An error occurred'));
                    return;
                }

                if (parsedResponse.ajaxExpired && parsedResponse.ajaxRedirect) {
                    window.location.href = parsedResponse.ajaxRedirect;
                    return;
                }

                // Check if response has blocks to update (any key that's not system keys)
                var hasBlocks = false;
                var systemKeys = ['error', 'reload', 'ajaxExpired', 'ajaxRedirect', 'message', 'header', 'redirect_url', 'success'];

                for (var key in parsedResponse) {
                    if (parsedResponse.hasOwnProperty(key) && systemKeys.indexOf(key) === -1) {
                        hasBlocks = true;
                        break;
                    }
                }

                // Update blocks if response contains them
                if (hasBlocks) {
                    self.updatePageBlocks(parsedResponse);
                }

                // Show success message
                var successMessage = parsedResponse.message || $t('Offer saved successfully');
                self.showSuccess(successMessage);

                // Call success callback if provided
                if (typeof successCallback === 'function') {
                    successCallback(parsedResponse);
                }

                // Handle redirect for non-continue operations
                if (!continueEdit) {
                    if (parsedResponse.redirect_url) {
                        setTimeout(function() {
                            window.location.href = parsedResponse.redirect_url;
                        }, 1000);
                    } else {
                        // Default redirect behavior - but don't reload if blocks were updated
                        if (!hasBlocks) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                }
            },

            /**
             * Handle form submission error
             */
            handleError: function(xhr) {
                var errorMessage = $t('An error occurred while saving the offer');

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Use default message
                }

                this.showError(errorMessage);
            }
        };
    };
});
