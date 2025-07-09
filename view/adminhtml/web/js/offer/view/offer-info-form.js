define([
    'jquery',
    'mage/validation',
    'mage/url',
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'jquery/ui'
], function ($, validation, urlBuilder, $t, mageTemplate, alert, confirm) {
    'use strict';

    return function(config, element) {
        let options = {
            formSelector: '#offer_info',
            saveButton: 'button[type="submit"]',
            saveAndContinueButton: '.btn:contains("Save and Continue")',
            cancelButton: '.btn:contains("Cancel")',
            fileUploadButton: '.btn-secondary:contains("Upload Files")',
            fileDropZone: '.file-drop-zone',
            autoGenerateCheckbox: '#auto_generate',
            offerNumberInput: '#offer_number',
            offerEmailCheckbox: '#offer_email'
        };

        var component = {
            /**
             * Инициализация компонента
             */
            init: function() {
                this.bindEvents();
                this.initValidation();
                this.initFileUpload();
                this.initAutoGenerate();
            },

            /**
             * Привязка событий
             */
            bindEvents: function() {
                var self = this;

                // Обработка отправки формы
                $(options.formSelector).on('submit', function(e) {
                    e.preventDefault();
                    self.submitForm(false);
                });

                // Кнопка "Save and Continue"
                $(options.saveAndContinueButton).on('click', function(e) {
                    e.preventDefault();
                    self.submitForm(true);
                });

                // Кнопка "Cancel"
                $(options.cancelButton).on('click', function(e) {
                    e.preventDefault();
                    self.cancelForm();
                });

                // Кнопка загрузки файлов
                $(options.fileUploadButton).on('click', function(e) {
                    e.preventDefault();
                    self.openFileDialog();
                });

                // Drag & Drop для файлов
                $(options.fileDropZone).on('dragover', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                }).on('dragleave', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                }).on('drop', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    self.handleFilesDrop(e.originalEvent.dataTransfer.files);
                });
            },

            /**
             * Инициализация валидации формы
             */
            initValidation: function() {
                $(options.formSelector).validation({
                    rules: {
                        'offer_name': {
                            required: true,
                            minlength: 3
                        },
                        'offer_number': {
                            required: function() {
                                return !$(options.autoGenerateCheckbox).is(':checked');
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
             * Инициализация автогенерации номера предложения
             */
            initAutoGenerate: function() {
                var self = this;

                $(options.autoGenerateCheckbox).on('change', function() {
                    var $offerNumber = $(options.offerNumberInput);

                    if ($(this).is(':checked')) {
                        $offerNumber.prop('readonly', true).addClass('disabled');
                        self.generateOfferNumber();
                    } else {
                        $offerNumber.prop('readonly', false).removeClass('disabled');
                    }
                });
            },

            /**
             * Генерация номера предложения
             */
            generateOfferNumber: function() {
                var self = this;

                $.ajax({
                    url: urlBuilder.build('admin/offer/generateNumber'),
                    type: 'POST',
                    dataType: 'json',
                    showLoader: true,
                    data: {
                        form_key: $('input[name="form_key"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $(options.offerNumberInput).val(response.offer_number);
                        } else {
                            self.showError(response.message || $t('Error generating offer number'));
                        }
                    },
                    error: function() {
                        self.showError($t('Error generating offer number'));
                    }
                });
            },

            /**
             * Инициализация загрузки файлов
             */
            initFileUpload: function() {
                // Создаем скрытый input для файлов
                if (!$('#file-input-hidden').length) {
                    $('<input type="file" id="file-input-hidden" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="display:none;">').appendTo('body');
                }
            },

            /**
             * Открытие диалога выбора файлов
             */
            openFileDialog: function() {
                var self = this;

                $('#file-input-hidden').off('change').on('change', function() {
                    self.handleFiles(this.files);
                }).click();
            },

            /**
             * Обработка выбранных файлов
             */
            handleFiles: function(files) {
                this.handleFilesDrop(files);
            },

            /**
             * Обработка файлов из drag&drop
             */
            handleFilesDrop: function(files) {
                var self = this;

                Array.from(files).forEach(function(file) {
                    // Проверка типа файла
                    var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

                    if (!allowedTypes.includes(file.type)) {
                        self.showError($t('File type not allowed: ') + file.name);
                        return;
                    }

                    // Проверка размера файла (10MB)
                    if (file.size > 10 * 1024 * 1024) {
                        self.showError($t('File too large: ') + file.name);
                        return;
                    }

                    // Добавляем файл в список
                    self.addFileToList(file);
                });
            },

            /**
             * Добавление файла в список
             */
            addFileToList: function(file) {
                var fileTemplate = '<div class="file-item" data-file-name="' + file.name + '">' +
                    '<span class="file-name">' + file.name + '</span>' +
                    '<span class="file-size">(' + this.formatFileSize(file.size) + ')</span>' +
                    '<button type="button" class="remove-file btn btn-link">&times;</button>' +
                    '</div>';

                $('.file-drop-zone').before(fileTemplate);

                // Обработчик удаления файла
                $('.file-item:last .remove-file').on('click', function() {
                    $(this).closest('.file-item').remove();
                });
            },

            /**
             * Форматирование размера файла
             */
            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                var k = 1024;
                var sizes = ['Bytes', 'KB', 'MB', 'GB'];
                var i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            /**
             * Отправка формы
             */
            submitForm: function(continueEdit) {
                var self = this;
                var $form = $(options.formSelector);

                if (!$form.validation('isValid')) {
                    return false;
                }

                var formData = new FormData();

                // Собираем данные формы
                $form.serializeArray().forEach(function(field) {
                    formData.append(field.name, field.value);
                });

                // Добавляем файлы
                $('.file-item').each(function() {
                    var fileName = $(this).data('file-name');
                    var fileInput = $('#file-input-hidden')[0];

                    if (fileInput.files) {
                        Array.from(fileInput.files).forEach(function(file) {
                            if (file.name === fileName) {
                                formData.append('sketches[]', file);
                            }
                        });
                    }
                });

                // Добавляем флаг "продолжить редактирование"
                if (continueEdit) {
                    formData.append('back', 'continue');
                }

                // Отправка данных
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
                        console.log(xhr);
                        self.handleError(xhr);
                    }
                });
            },

            /**
             * Обработка успешного ответа
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
             * Обработка ошибки
             */
            handleError: function(xhr) {
                var errorMessage = $t('An error occurred while saving the offer');

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Используем сообщение по умолчанию
                }

                this.showError(errorMessage);
            },

            /**
             * Отмена формы
             */
            cancelForm: function() {
                confirm({
                    content: $t('Are you sure you want to cancel? All unsaved changes will be lost.'),
                    actions: {
                        confirm: function() {
                            // Переход к списку предложений или закрытие формы
                            window.location.href = urlBuilder.build('admin/offer/index');
                        }
                    }
                });
            },

            /**
             * Показать сообщение об успехе
             */
            showSuccess: function(message) {
                alert({
                    title: $t('Success'),
                    content: message,
                    modalClass: 'modal-success'
                });
            },

            /**
             * Показать сообщение об ошибке
             */
            showError: function(message) {
                alert({
                    title: $t('Error'),
                    content: message,
                    modalClass: 'modal-error'
                });
            }
        };

        // Инициализируем компонент
        component.init();

        return component;
    };
});
