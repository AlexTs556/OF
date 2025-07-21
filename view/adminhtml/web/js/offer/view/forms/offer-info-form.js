define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/core/base-form',
    'OneMoveTwo_Offers/js/offer/view/modules/notifications'
], function ($, BaseForm, Notifications) {
    'use strict';

    /**
     * Форма информации об оффере
     * Наследует BaseForm и добавляет специфичную логику для offer info
     */
    return function(config) {
        // Создаем экземпляр базовой формы
        var form = BaseForm(config);

        // Расширяем базовую функциональность
        return $.extend(form, {

            /**
             * Получение опций специфичных для формы offer info
             */
            getFormSpecificOptions: function() {
                return {
                    formSelector: '#offer_info',
                    saveButton: '.btn-primary:contains("Save and Continue")',
                    updateBlock: 'offer_tab_summary',
                    responseVariable: 'iFrameResponse',

                    // Специфичные селекторы для offer info
                    offerEmailCheckbox: '#offer_email',
                    autoGenerateCheckbox: '#auto_generate',
                    offerNumberInput: '#offer_number'
                };
            },

            /**
             * Получение конфигурации модулей
             */
            getModuleConfig: function() {
                return {
                    fileHandler: {
                        enabled: true,
                        path: 'OneMoveTwo_Offers/js/offer/view/modules/file-handler',
                        config: this.config
                    },
                    notifications: {
                        enabled: true,
                        path: 'OneMoveTwo_Offers/js/offer/view/modules/notifications',
                        config: {}
                    }
                };
            },

            /**
             * Инициализация специфичная для offer info формы
             */
            initFormSpecific: function() {
                this.resetAutoGenerateState();
                this.initNotificationsIntegration();
            },

            /**
             * Интеграция с модулем уведомлений
             */
            initNotificationsIntegration: function() {
                var self = this;

                // Переопределяем методы показа сообщений для использования модуля notifications
                this.on('module:notifications:initialized', function(event) {
                    var notificationsModule = event.data.instance;

                    // Переопределяем методы для использования модуля уведомлений
                    self.showError = function(message) {
                        notificationsModule.showError(message);
                    };

                    self.showSuccess = function(message) {
                        notificationsModule.showSuccess(message);
                    };
                });
            },

            /**
             * Привязка специфичных событий формы
             */
            bindFormSpecificEvents: function() {
                var self = this;

                // Auto generate checkbox - toggle input field when checkbox state changes
                $(this.options.autoGenerateCheckbox).on('change', function() {
                    self.toggleOfferNumberInput(this.checked);
                });
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
             * Обработка успешной отправки (хук)
             */
            onSubmitSuccess: function(response, parsedResponse) {
                // Специфичная логика для offer info формы после успешной отправки
                console.log('Offer info form submitted successfully');

                // Можно добавить специфичную логику, например:
                // - обновление каких-то полей
                // - показ специфичных уведомлений
                // - логирование
            },

            /**
             * Обработка ошибки отправки (хук)
             */
            onSubmitError: function(xhr) {
                // Специфичная логика для offer info формы при ошибке
                console.error('Offer info form submission failed');
            },

            /**
             * Обработка обновления блоков страницы
             */
            updatePageBlocks: function(response) {
                // Вызываем базовую логику
                var result = this.constructor.prototype.updatePageBlocks.call(this, response);

                // Добавляем специфичную обработку для offer info
                this.handleOfferInfoSpecificUpdates(response);

                return result;
            },

            /**
             * Специфичная обработка обновлений для offer info
             */
            handleOfferInfoSpecificUpdates: function(response) {
                // Если обновился блок summary, может потребоваться реинициализация JS
                if (response.offer_tab_summary) {
                    this.trigger('offer:summary:updated', {
                        content: response.offer_tab_summary
                    });
                }

                // Другие специфичные обновления...
            }
        });
    };
});
