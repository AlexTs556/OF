define([
    'jquery',
    'mage/validation',
    'mage/url',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'jquery/ui'
], function ($, validation, urlBuilder, $t, alert, confirm) {
    'use strict';

    return function(config, element) {
        var OfferInfoForm = {
            // Configuration options
            options: {
                formSelector: '#offer_info',
                saveAndContinueButton: '.btn-primary:contains("Save and Continue")',
                autoGenerateCheckbox: '#auto_generate',
                offerNumberInput: '#offer_number',
                offerEmailCheckbox: '#offer_email',
                fileUploadButton: '.btn-secondary',
                fileDropZone: '.file-list',
                fileListContainer: '.file-list',
                maxFileSize: 10 * 1024 * 1024, // 10MB
                allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf']
            },

            // File management properties
            files: new Map(),
            fileIdCounter: 0,

            /**
             * Initialize the component
             */
            init: function() {
                this.bindEvents();
                this.initValidation();
                this.initFileUpload();
                this.initAutoGenerate();
                this.updateFileListDisplay();
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

                // Auto-generate checkbox
                $(this.options.autoGenerateCheckbox).on('change', function() {
                    self.updateOfferNumberInputState();
                });

                // File upload button
                $(this.options.fileUploadButton).on('click', function(e) {
                    e.preventDefault();
                    self.openFileDialog();
                });

                // File drop zone events
                var $fileList = $(this.options.fileListContainer);
                $fileList
                    .on('dragover', function(e) { self.handleDragOver(e); })
                    .on('dragenter', function(e) { self.handleDragEnter(e); })
                    .on('dragleave', function(e) { self.handleDragLeave(e); })
                    .on('drop', function(e) { self.handleDrop(e); })
                    .on('click', '.file-drop-zone', function() { self.openFileDialog(); });
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
                                return !$(self.options.autoGenerateCheckbox).is(':checked');
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
             * Initialize file upload functionality
             */
            initFileUpload: function() {
                this.createFileInput();
            },

            /**
             * Create hidden file input
             */
            createFileInput: function() {
                if (!this.fileInput) {
                    this.fileInput = document.createElement('input');
                    this.fileInput.type = 'file';
                    this.fileInput.multiple = true;
                    this.fileInput.accept = 'image/*,.pdf';
                    this.fileInput.style.display = 'none';
                    document.body.appendChild(this.fileInput);

                    var self = this;
                    this.fileInput.addEventListener('change', function(e) {
                        self.handleFileSelect(e);
                    });
                }
            },

            /**
             * Initialize auto-generate functionality
             */
            initAutoGenerate: function() {
                // Set checkbox state based on existing offer number value
                var $input = $(this.options.offerNumberInput);
                var $checkbox = $(this.options.autoGenerateCheckbox);

                var hasExistingNumber = $input.val() && $input.val().trim() !== '';

                if (hasExistingNumber) {
                    // If there's already a number, disable auto-generate
                    $checkbox.prop('checked', false);
                } else {
                    // If no number exists, enable auto-generate
                    $checkbox.prop('checked', true);
                }

                this.updateOfferNumberInputState();
            },

            /**
             * Update offer number input state based on auto-generate checkbox
             */
            updateOfferNumberInputState: function() {
                var $checkbox = $(this.options.autoGenerateCheckbox);
                var $input = $(this.options.offerNumberInput);

                if ($checkbox.is(':checked')) {
                    $input.prop('readonly', true).addClass('disabled');
                    // Clear the input when auto-generate is enabled
                    // The number will be generated on backend during form submission
                    $input.val('');
                } else {
                    $input.prop('readonly', false).removeClass('disabled');
                }
            },

            /**
             * Open file selection dialog
             */
            openFileDialog: function() {
                this.fileInput.click();
            },

            /**
             * Handle file selection from input
             */
            handleFileSelect: function(event) {
                var selectedFiles = Array.from(event.target.files);
                this.processFiles(selectedFiles);
                this.fileInput.value = '';
            },

            /**
             * Handle drag over event
             */
            handleDragOver: function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(e.currentTarget).addClass('drag-over');
            },

            /**
             * Handle drag enter event
             */
            handleDragEnter: function(e) {
                e.preventDefault();
                $(e.currentTarget).addClass('drag-over');
            },

            /**
             * Handle drag leave event
             */
            handleDragLeave: function(e) {
                e.preventDefault();
                if (!e.currentTarget.contains(e.originalEvent.relatedTarget)) {
                    $(e.currentTarget).removeClass('drag-over');
                }
            },

            /**
             * Handle drop event
             */
            handleDrop: function(e) {
                e.preventDefault();
                $(e.currentTarget).removeClass('drag-over');
                var droppedFiles = Array.from(e.originalEvent.dataTransfer.files);
                this.processFiles(droppedFiles);
            },

            /**
             * Process selected files
             */
            processFiles: function(files) {
                var self = this;
                files.forEach(function(file) {
                    if (self.validateFile(file)) {
                        self.addFile(file);
                    }
                });
                this.updateFileListDisplay();
            },

            /**
             * Validate file before adding
             */
            validateFile: function(file) {
                if (!this.options.allowedTypes.includes(file.type)) {
                    this.showError($t('File "%1" has unsupported type. Allowed: images and PDF.').replace('%1', file.name));
                    return false;
                }

                if (file.size > this.options.maxFileSize) {
                    var maxSizeMB = this.options.maxFileSize / 1024 / 1024;
                    this.showError($t('File "%1" is too large. Maximum size: %2MB.').replace('%1', file.name).replace('%2', maxSizeMB));
                    return false;
                }

                var existingFile = Array.from(this.files.values()).find(function(f) {
                    return f.name === file.name;
                });
                if (existingFile) {
                    this.showError($t('File with name "%1" already added.').replace('%1', file.name));
                    return false;
                }

                return true;
            },

            /**
             * Add file to the files map
             */
            addFile: function(file) {
                var fileId = this.generateFileId();
                this.files.set(fileId, file);
            },

            /**
             * Update file list display
             */
            updateFileListDisplay: function() {
                var $fileList = $(this.options.fileListContainer);

                if (this.files.size === 0) {
                    $fileList.html('<div class="file-drop-zone">Drag and drop files here or click the button to select.</div>');
                    $fileList.removeClass('has-files');
                } else {
                    $fileList.addClass('has-files');
                    $fileList.empty();

                    var self = this;
                    this.files.forEach(function(file, fileId) {
                        var fileItem = self.createFileItem(fileId, file);
                        $fileList.append(fileItem);
                    });
                }
            },

            /**
             * Create file item element
             */
            createFileItem: function(fileId, file) {
                var self = this;
                var $fileItem = $('<div class="file-item" data-file-id="' + fileId + '"></div>');

                var fileInfoHtml = '<div class="file-info">' +
                    '<span class="file-name" title="' + file.name + '">' + file.name + '</span>' +
                    '<span class="file-size">(' + this.formatFileSize(file.size) + ')</span>' +
                    '</div>';

                var actionsHtml = '<div class="file-actions">';
                if (this.isImageFile(file.name)) {
                    actionsHtml += '<button type="button" class="btn-preview" title="Preview">👁</button>';
                }
                actionsHtml += '<button type="button" class="btn-remove" title="Delete file">✕</button>';
                actionsHtml += '</div>';

                $fileItem.html(fileInfoHtml + actionsHtml);

                // Bind events
                $fileItem.find('.btn-remove').on('click', function() {
                    self.removeFile(fileId);
                });

                $fileItem.find('.btn-preview').on('click', function() {
                    self.previewFile(fileId);
                });

                return $fileItem;
            },

            /**
             * Remove file from list
             */
            removeFile: function(fileId) {
                this.files.delete(fileId);
                this.updateFileListDisplay();
            },

            /**
             * Preview image file
             */
            previewFile: function(fileId) {
                var file = this.files.get(fileId);
                if (!file || !this.isImageFile(file.name)) return;

                var self = this;
                var $modal = $('<div class="preview-modal">' +
                    '<div class="preview-content">' +
                    '<div class="preview-header">' +
                    '<span class="preview-title">' + file.name + '</span>' +
                    '<button class="preview-close">✕</button>' +
                    '</div>' +
                    '<div class="preview-body">' +
                    '<img src="' + URL.createObjectURL(file) + '" alt="' + file.name + '" class="preview-image">' +
                    '</div>' +
                    '</div>' +
                    '</div>');

                $('body').append($modal);

                $modal.find('.preview-close').on('click', function() {
                    $modal.remove();
                });

                $modal.on('click', function(e) {
                    if (e.target === this) {
                        $modal.remove();
                    }
                });
            },

            /**
             * Generate unique file ID
             */
            generateFileId: function() {
                return 'file_' + (++this.fileIdCounter) + '_' + Date.now();
            },

            /**
             * Format file size for display
             */
            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                var k = 1024;
                var sizes = ['Bytes', 'KB', 'MB', 'GB'];
                var i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            /**
             * Check if file is an image
             */
            isImageFile: function(fileName) {
                var imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp'];
                var extension = fileName.toLowerCase().substring(fileName.lastIndexOf('.'));
                return imageExtensions.includes(extension);
            },

            /**
             * Get all uploaded files
             */
            getAllFiles: function() {
                return Array.from(this.files.values());
            },

            /**
             * Get FormData with all files and attachments info
             */
            getFormData: function() {
                var formData = new FormData();
                var attachments = [];

                this.files.forEach(function(file, fileId) {
                    attachments.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        id: fileId
                    });
                    formData.append('attachments[]', file);
                });

                // Add attachments info as JSON
                formData.append('attachments_info', JSON.stringify(attachments.map(function(att) {
                    return {
                        name: att.name,
                        size: att.size,
                        type: att.type,
                        id: att.id
                    };
                })));

                return formData;
            },

            /**
             * Validate form including files
             */
            validateForm: function() {
                var $form = $(this.options.formSelector);

                if (!$form.validation('isValid')) {
                    return false;
                }

                return true;
            },

            /**
             * Submit form with all data
             */
            submitForm: function(continueEdit) {
                var self = this;

                if (!this.validateForm()) {
                    return false;
                }

                var $form = $(this.options.formSelector);
                var formData = new FormData();

                // Add form fields
                $form.serializeArray().forEach(function(field) {
                    formData.append(field.name, field.value);
                });

                // Add files as attachments array
                var attachments = [];
                this.files.forEach(function(file, fileId) {
                    attachments.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        id: fileId
                    });
                    // Also add file to FormData for upload
                    formData.append('attachments[]', file);
                });

                // Add attachments info as JSON for backend processing
                formData.append('attachments_info', JSON.stringify(attachments.map(function(att) {
                    return {
                        name: att.name,
                        size: att.size,
                        type: att.type,
                        id: att.id
                    };
                })));

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
                    dataType: 'json',
                    showLoader: true,
                    success: function(response) {
                        self.handleSuccess(response, continueEdit);
                    },
                    error: function(xhr) {
                        self.handleError(xhr);
                    }
                });
            },

            /**
             * Handle successful form submission
             */
            handleSuccess: function(response, continueEdit) {
                if (response.success) {
                    this.showSuccess(response.message || $t('Offer saved successfully'));

                    if (!continueEdit && response.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    }
                } else {
                    this.showError(response.message || $t('Error saving offer'));
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
            }
        };

        // Initialize the component
        OfferInfoForm.init();

        // Return component instance for external access
        return OfferInfoForm;
    };
});
