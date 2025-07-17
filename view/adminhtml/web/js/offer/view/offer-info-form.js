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

            // Configuration from backend
            config: {
                existingFiles: config.existingFiles || [],
                mediaUrl: config.mediaUrl || '',
                offerId: config.offerId || null
            },

            // File management properties
            files: new Map(),
            existingFiles: new Map(),
            fileIdCounter: 0,
            filesToDelete: [],

            /**
             * Initialize the component
             */
            init: function() {
                this.loadExistingFiles();
                this.bindEvents();
                this.initValidation();
                this.initFileUpload();
                this.initAutoGenerate();
                this.updateFileListDisplay();
            },

            /**
             * Load existing files from backend
             */
            loadExistingFiles: function() {
                var self = this;

                // Debug: log what we received from backend
                console.log('Config data received:', this.config);
                console.log('Existing files from backend:', this.config.existingFiles);

                if (this.config.existingFiles && Object.keys(this.config.existingFiles).length > 0) {
                    console.log('Processing existing files, count:', Object.keys(this.config.existingFiles).length);

                    // Handle object format (your case) or array format
                    var filesArray = Array.isArray(this.config.existingFiles)
                        ? this.config.existingFiles
                        : Object.values(this.config.existingFiles);

                    filesArray.forEach(function(fileData) {
                        console.log('Processing file:', fileData);

                        var existingFile = {
                            id: 'existing_' + (fileData.attachment_id || fileData.id),
                            name: fileData.file_name || fileData.name || fileData.filename,
                            path: fileData.file_path || fileData.path,
                            size: parseInt(fileData.file_size || fileData.size || 0),
                            type: fileData.file_type || fileData.type || self.getFileTypeFromPath(fileData.file_path || fileData.path),
                            isExisting: true,
                            attachmentId: fileData.attachment_id || fileData.id
                        };

                        console.log('Created existing file object:', existingFile);
                        self.existingFiles.set(existingFile.id, existingFile);
                    });

                    console.log('Existing files map after loading:', self.existingFiles);
                } else {
                    console.log('No existing files found or empty array');
                }
            },

            /**
             * Get file type from path
             */
            getFileTypeFromPath: function(path) {
                var extension = path.toLowerCase().split('.').pop();
                switch (extension) {
                    case 'jpg':
                    case 'jpeg':
                        return 'image/jpeg';
                    case 'png':
                        return 'image/png';
                    case 'gif':
                        return 'image/gif';
                    case 'pdf':
                        return 'application/pdf';
                    default:
                        return 'application/octet-stream';
                }
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

                // Check for duplicates in both new and existing files
                var existingFile = Array.from(this.files.values()).find(function(f) {
                    return f.name === file.name;
                });
                var existingInLoaded = Array.from(this.existingFiles.values()).find(function(f) {
                    return f.name === file.name;
                });

                if (existingFile || existingInLoaded) {
                    this.showError($t('File with name "%1" already exists.').replace('%1', file.name));
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
                var totalFiles = this.files.size + this.existingFiles.size;

                console.log('Updating file list display');
                console.log('New files count:', this.files.size);
                console.log('Existing files count:', this.existingFiles.size);
                console.log('Total files:', totalFiles);

                if (totalFiles === 0) {
                    console.log('No files to display, showing drop zone');
                    $fileList.html('<div class="file-drop-zone">Drag and drop files here or click the button to select.</div>');
                    $fileList.removeClass('has-files');
                } else {
                    console.log('Files found, displaying file list');
                    $fileList.addClass('has-files');
                    $fileList.empty();

                    var self = this;

                    // Display existing files first
                    console.log('Adding existing files to display');
                    this.existingFiles.forEach(function(file, fileId) {
                        console.log('Adding existing file:', file);
                        var fileItem = self.createExistingFileItem(fileId, file);
                        $fileList.append(fileItem);
                    });

                    // Display new files
                    console.log('Adding new files to display');
                    this.files.forEach(function(file, fileId) {
                        console.log('Adding new file:', file);
                        var fileItem = self.createFileItem(fileId, file);
                        $fileList.append(fileItem);
                    });
                }
            },

            /**
             * Create existing file item element
             */
            createExistingFileItem: function(fileId, file) {
                var self = this;
                var $fileItem = $('<div class="file-item existing-file" data-file-id="' + fileId + '"></div>');

                var fileInfoHtml = '<div class="file-info">' +
                    '<span class="file-name" title="' + file.name + '">' + file.name + '</span>' +
                    '<span class="file-size">(' + this.formatFileSize(file.size) + ')</span>' +
                    '<span class="file-status">Saved</span>' +
                    '</div>';

                var actionsHtml = '<div class="file-actions">';
                if (this.isImageFile(file.name)) {
                    actionsHtml += '<button type="button" class="btn-preview" title="Preview">👁</button>';
                }
                actionsHtml += '<button type="button" class="btn-download" title="Download">📥</button>';
                actionsHtml += '<button type="button" class="btn-remove" title="Delete file">✕</button>';
                actionsHtml += '</div>';

                $fileItem.html(fileInfoHtml + actionsHtml);

                // Bind events
                $fileItem.find('.btn-remove').on('click', function() {
                    self.removeExistingFile(fileId);
                });

                $fileItem.find('.btn-preview').on('click', function() {
                    self.previewExistingFile(fileId);
                });

                $fileItem.find('.btn-download').on('click', function() {
                    self.downloadExistingFile(fileId);
                });

                return $fileItem;
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
             * Remove existing file from list (mark for deletion)
             */
            removeExistingFile: function(fileId) {
                var self = this;
                var file = this.existingFiles.get(fileId);

                if (!file) return;

                confirm({
                    title: $t('Delete File'),
                    content: $t('Are you sure you want to delete "%1"?').replace('%1', file.name),
                    actions: {
                        confirm: function() {
                            // Mark file for deletion using attachment_id
                            self.filesToDelete.push(file.attachmentId);
                            console.log('File marked for deletion:', file.attachmentId);
                            console.log('Files to delete:', self.filesToDelete);
                            // Remove from existing files
                            self.existingFiles.delete(fileId);
                            self.updateFileListDisplay();
                        }
                    }
                });
            },

            /**
             * Remove file from list
             */
            removeFile: function(fileId) {
                this.files.delete(fileId);
                this.updateFileListDisplay();
            },

            /**
             * Preview existing image file
             */
            previewExistingFile: function(fileId) {
                var file = this.existingFiles.get(fileId);
                if (!file || !this.isImageFile(file.name)) return;

                var fileUrl = this.config.mediaUrl + file.path;
                this.showImagePreview(file.name, fileUrl);
            },

            /**
             * Preview image file
             */
            previewFile: function(fileId) {
                var file = this.files.get(fileId);
                if (!file || !this.isImageFile(file.name)) return;

                var fileUrl = URL.createObjectURL(file);
                this.showImagePreview(file.name, fileUrl);
            },

            /**
             * Show image preview modal
             */
            showImagePreview: function(fileName, imageUrl) {
                var $modal = $('<div class="preview-modal">' +
                    '<div class="preview-content">' +
                    '<div class="preview-header">' +
                    '<span class="preview-title">' + fileName + '</span>' +
                    '<button class="preview-close">✕</button>' +
                    '</div>' +
                    '<div class="preview-body">' +
                    '<img src="' + imageUrl + '" alt="' + fileName + '" class="preview-image">' +
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
             * Download existing file
             */
            downloadExistingFile: function(fileId) {
                var file = this.existingFiles.get(fileId);
                if (!file) return;

                var fileUrl = this.config.mediaUrl + file.path;
                var link = document.createElement('a');
                link.href = fileUrl;
                link.download = file.name;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
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
                let self = this,
                    as_js_varname = 'iFrameResponse',
                    block = 'offer_tab_summary';

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

                // Add files to delete
                if (this.filesToDelete.length > 0) {
                    formData.append('delete_attachments', JSON.stringify(this.filesToDelete));
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
                        self.handleAjaxResponse(response, continueEdit);
                    },
                    error: function(xhr) {
                        self.handleError(xhr);
                    }
                });
            },

            /**
             * Handle AJAX response similar to first file logic
             */
            handleAjaxResponse: function(response, continueEdit) {
                var self = this;
                var parsedResponse = null;

                // Handle mixed HTML/JS response like in first file
                if (typeof response === 'string') {
                    // Check if response starts with <script> tag (HTML response)
                    if (response.trim().indexOf('<script>') === 0) {
                        // Extract JSON directly from response - simple and reliable approach
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

                // Handle different response scenarios like in first file
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
             * Update page blocks with response data
             */
            updatePageBlocks: function(response) {
                var self = this;

                // Get area ID function like in first file
                function getAreaId(area) {
                    return 'offer-' + area;
                }

                // Always add message to loading areas like in first file
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

                            // Execute callback if exists (like in first file)
                            if ($block[0].callback && typeof window[self.getCallbackName(areaName)] === 'function') {
                                window[self.getCallbackName(areaName)]();
                            }
                        }
                    }
                });

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
