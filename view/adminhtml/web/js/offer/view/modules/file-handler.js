define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, confirm) {
    'use strict';

    return function() {
        return {
            // Configuration options
            options: {
                fileUploadButton: '.btn-secondary',
                fileDropZone: '.file-list',
                fileListContainer: '.file-list',
                maxFileSize: 10 * 1024 * 1024, // 10MB
                allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf']
            },

            // File management properties
            files: new Map(),
            existingFiles: new Map(),
            fileIdCounter: 0,
            filesToDelete: [],
            config: {},
            callbacks: {},

            /**
             * Initialize the file handler
             */
            init: function(config, callbacks) {
                this.config = config || {};
                this.callbacks = callbacks || {};

                this.loadExistingFiles();
                this.initFileUpload();
                this.bindEvents();
                this.updateFileListDisplay();

                // Подписываемся на событие подготовки данных формы
                this.bindFormEvents();

                return this;
            },

            /**
             * Привязка к событиям формы
             */
            bindFormEvents: function() {
                var self = this;

                // Подписываемся на событие подготовки данных
                if (this.callbacks.on) {
                    this.callbacks.on('form:prepareData', function(event) {
                        self.addFilesToFormData(event.data.formData);
                    });
                }
            },

            /**
             * Добавление файлов в FormData (сохраняем текущую структуру)
             */
            addFilesToFormData: function(formData) {
                var filesData = this.getFilesData();

                // Добавляем файлы как attachments[] (текущая структура)
                filesData.attachments.forEach(function(attachment) {
                    formData.append('attachments[]', attachment.file);
                });

                // Добавляем информацию о вложениях как attachments_info (текущая структура)
                if (filesData.attachments.length > 0) {
                    formData.append('attachments_info', JSON.stringify(
                        filesData.attachments.map(function(att) {
                            return {
                                name: att.name,
                                size: att.size,
                                type: att.type,
                                id: att.id
                            };
                        })
                    ));
                }

                // Добавляем файлы для удаления как delete_attachments (текущая структура)
                if (filesData.filesToDelete.length > 0) {
                    formData.append('delete_attachments', JSON.stringify(filesData.filesToDelete));
                }
            },

            /**
             * Load existing files from backend
             */
            loadExistingFiles: function() {
                var self = this;

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
             * Bind all file-related events
             */
            bindEvents: function() {
                var self = this;

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
             * Get files data for form submission (backward compatibility)
             */
            getFilesData: function() {
                var attachments = [];
                this.files.forEach(function(file, fileId) {
                    attachments.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        id: fileId
                    });
                });

                return {
                    files: this.files,
                    existingFiles: this.existingFiles,
                    filesToDelete: this.filesToDelete,
                    attachments: attachments
                };
            },

            /**
             * Show error message
             */
            showError: function(message) {
                if (this.callbacks.showError) {
                    this.callbacks.showError(message);
                } else {
                    console.error(message);
                }
            },

            /**
             * Show success message
             */
            showSuccess: function(message) {
                if (this.callbacks.showSuccess) {
                    this.callbacks.showSuccess(message);
                } else {
                    console.log(message);
                }
            },

            /**
             * Trigger event
             */
            trigger: function(eventName, data) {
                if (this.callbacks.trigger) {
                    this.callbacks.trigger(eventName, data);
                }
            },

            /**
             * Subscribe to event
             */
            on: function(eventName, callback) {
                if (this.callbacks.on) {
                    this.callbacks.on(eventName, callback);
                }
            },

            /**
             * Cleanup when destroying
             */
            destroy: function() {
                // Remove file input
                if (this.fileInput && this.fileInput.parentNode) {
                    this.fileInput.parentNode.removeChild(this.fileInput);
                }

                // Clear maps
                this.files.clear();
                this.existingFiles.clear();
                this.filesToDelete = [];

                // Remove event listeners
                $(this.options.fileListContainer).off();
                $(this.options.fileUploadButton).off();
            }
        };
    };
});
