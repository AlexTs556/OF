define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/core/form-factory',
    'OneMoveTwo_Offers/js/offer/view/core/form-registry',
    'OneMoveTwo_Offers/js/offer/view/forms/offer-info-form'
], function ($, FormFactory, FormRegistry, OfferInfoForm) {
    'use strict';

    /**
     * Инициализатор форм
     * Регистрирует доступные типы форм и предоставляет методы создания
     * Используется как Magento widget через data-mage-init
     */
    return function(config, element) {

        var FormInitializer = {

            // Конфигурация
            config: config || {},
            element: element,
            formInstance: null,

            /**
             * Инициализация
             */
            init: function() {
                // Регистрируем доступные типы форм
                this.registerFormTypes();

                // Определяем тип формы из конфигурации
                var formType = this.determineFormType();

                if (!formType) {
                    console.error('Cannot determine form type from config:', this.config);
                    return this;
                }

                // Создаем и инициализируем форму
                this.createForm(formType);

                return this;
            },

            /**
             * Регистрация всех доступных типов форм
             */
            registerFormTypes: function() {
                // Подгружаем дополнительные типы форм
                var self = this;

                // Регистрируем форму информации об оффере
                FormRegistry.register('offer-info', OfferInfoForm, {
                    existingFiles: [],
                    mediaUrl: '',
                    offerId: null
                });

                // Регистрируем форму конфигурации продукта
                require(['OneMoveTwo_Offers/js/offer/view/forms/product-configure-form'], function(ProductConfigureForm) {
                    FormRegistry.register('product-configure', ProductConfigureForm, {
                        existingFiles: [],
                        mediaUrl: '',
                        itemId: null,
                        offerId: null,
                        itemData: {},
                        saveUrl: 'offers/item/save_item_configure'
                    });
                });

                // Здесь можно зарегистрировать другие типы форм:
                // FormRegistry.register('customer-info', CustomerInfoForm, defaultConfig);
            },

            /**
             * Определение типа формы
             */
            determineFormType: function() {
                // Если тип явно указан в конфигурации
                if (this.config.formType) {
                    return this.config.formType;
                }

                // Определяем тип по наличию определенных полей в конфигурации
                if (this.config.hasOwnProperty('offerId') ||
                    this.config.hasOwnProperty('existingFiles')) {
                    return 'offer-info';
                }

                // Определяем тип для конфигурации продукта
                if (this.config.hasOwnProperty('itemId') ||
                    this.config.hasOwnProperty('itemData')) {
                    return 'product-configure';
                }

                // Можно добавить другие способы определения типа
                // if (this.config.hasOwnProperty('customerId')) {
                //     return 'customer-info';
                // }

                return null;
            },

            /**
             * Создание экземпляра формы
             */
            createForm: function(formType) {
                try {
                    // Подготавливаем конфигурацию для формы
                    var formConfig = this.prepareFormConfig();

                    // Подготавливаем опции для формы
                    var formOptions = this.prepareFormOptions();

                    // Создаем форму через фабрику
                    this.formInstance = FormFactory.create(formType, formConfig, formOptions);

                    if (this.formInstance) {
                        console.log('Form created successfully:', formType);
                        this.bindFormEvents();
                    } else {
                        console.error('Failed to create form:', formType);
                    }

                } catch (error) {
                    console.error('Error creating form:', error);
                }
            },

            /**
             * Подготовка конфигурации для формы
             */
            prepareFormConfig: function() {
                var formConfig = $.extend({}, this.config);

                // Убираем служебные поля которые не нужны форме
                delete formConfig.formType;

                return formConfig;
            },

            /**
             * Подготовка опций для формы
             */
            prepareFormOptions: function() {
                var options = {};

                // Если element это форма, используем её ID как селектор
                if (this.element && $(this.element).is('form')) {
                    var formId = $(this.element).attr('id');
                    if (formId) {
                        options.formSelector = '#' + formId;
                    }
                }

                return options;
            },

            /**
             * Привязка событий формы
             */
            bindFormEvents: function() {
                var self = this;

                if (!this.formInstance) {
                    return;
                }

                // Подписываемся на ключевые события формы
                this.formInstance.on('form:submitSuccess', function(event) {
                    self.handleFormSuccess(event.data);
                });

                this.formInstance.on('form:submitError', function(event) {
                    self.handleFormError(event.data);
                });

                this.formInstance.on('block:updated', function(event) {
                    self.handleBlockUpdate(event.data);
                });
            },

            /**
             * Обработка успешной отправки формы
             */
            handleFormSuccess: function(data) {
                console.log('Form submitted successfully:', data);

                // Здесь можно добавить общую логику для всех форм
                // например, логирование, аналитику и т.д.
            },

            /**
             * Обработка ошибки отправки формы
             */
            handleFormError: function(data) {
                console.error('Form submission failed:', data);

                // Общая обработка ошибок
            },

            /**
             * Обработка обновления блоков
             */
            handleBlockUpdate: function(data) {
                console.log('Block updated:', data.blockName);

                // Переинициализация JS компонентов в обновленном блоке
                this.reinitializeBlockComponents(data.element);
            },

            /**
             * Переинициализация JS компонентов в обновленном блоке
             */
            reinitializeBlockComponents: function($block) {
                // Ищем элементы с data-mage-init в обновленном блоке
                $block.find('[data-mage-init]').each(function() {
                    var $element = $(this);
                    var mageInit = $element.data('mage-init');

                    if (mageInit) {
                        // Переинициализируем Magento компоненты
                        $element.mage(mageInit);
                    }
                });

                // Можно добавить переинициализацию других компонентов
                // например, jQuery UI виджетов и т.д.
            },

            /**
             * Получение экземпляра формы
             */
            getFormInstance: function() {
                return this.formInstance;
            },

            /**
             * Уничтожение инициализатора
             */
            destroy: function() {
                if (this.formInstance && typeof this.formInstance.destroy === 'function') {
                    this.formInstance.destroy();
                }

                this.formInstance = null;
                this.element = null;
                this.config = {};
            }
        };

        // Инициализируем и возвращаем экземпляр
        return FormInitializer.init();
    };
});
