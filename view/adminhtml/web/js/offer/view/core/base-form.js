// view/adminhtml/web/js/offer/view/core/base-form.js
define([
    'jquery',
    'mage/translate',
    'OneMoveTwo_Offers/js/offer/view/modules/event-manager',
    'OneMoveTwo_Offers/js/offer/view/modules/ajax-handler'
], function ($, $t, EventManager, AjaxHandler) {
    'use strict';

    /**
     * Базовый класс для всех форм
     * Предоставляет общую функциональность и интерфейс для расширения
     */
    return function(config) {
        return {
            // Настройки по умолчанию
            defaultOptions: {
                formSelector: 'form',
                saveButton: '.btn-primary',
                enableAutoSave: false,
                showLoader: true,
                enableEventSystem: true,
                enableModules: true
            },

            // Конфигурация
            config: {},
            options: {},

            // Основные компоненты
            eventManager: null,
            ajaxHandler: null,
            modules: {},

            // Состояние формы
            isInitialized: false,
            isSubmitting: false,
            formElement: null,

            /**
             * Инициализация базовой формы
             * @param {object} userConfig - пользовательская конфигурация
             * @param {object} userOptions - пользовательские опции
             */
            init: function(userConfig, userOptions) {
                // Объединяем конфигурации
                this.config = $.extend(true, {}, this.getDefaultConfig(), userConfig || {});
                this.options = $.extend(true, {}, this.defaultOptions, this.getFormSpecificOptions(), userOptions || {});

                // Находим элемент формы
                this.formElement = $(this.options.formSelector);
                if (!this.formElement.length) {
                    console.error('Form element not found:', this.options.formSelector);
                    return this;
                }

                // Инициализируем базовые компоненты
                this.initBaseComponents();

                // Инициализируем модули
                this.initModules();

                // Привязываем события
                this.bindEvents();

                // Специфичная инициализация
                this.initFormSpecific();

                // Генерируем событие завершения инициализации
                this.trigger('form:initialized', { form: this });

                this.isInitialized = true;
                return this;
            },

            /**
             * Получение конфигурации по умолчанию
             * Переопределяется в наследниках
             */
            getDefaultConfig: function() {
                return {
                    entityId: null,
                    ajaxUrl: null,
                    redirectUrl: null
                };
            },

            /**
             * Получение опций специфичных для формы
             * Переопределяется в наследниках
             */
            getFormSpecificOptions: function() {
                return {};
            },

            /**
             * Инициализация базовых компонентов
             */
            initBaseComponents: function() {
                if (this.options.enableEventSystem) {
                    this.eventManager = EventManager();
                }

                if (this.eventManager) {
                    this.ajaxHandler = AjaxHandler().init(this.eventManager);
                    this.bindAjaxEvents();
                }
            },

            /**
             * Привязка событий AJAX
             */
            bindAjaxEvents: function() {
                var self = this;

                this.eventManager.on('ajax:success', function(event) {
                    self.handleAjaxSuccess(event.data.response, event.data.parsedResponse);
                });

                this.eventManager.on('ajax:error', function(event) {
                    self.handleAjaxError(event.data);
                });
            },

            /**
             * Инициализация модулей
             */
            initModules: function() {
                if (!this.options.enableModules) {
                    return;
                }

                var moduleConfig = this.getModuleConfig();
                var self = this;

                Object.keys(moduleConfig).forEach(function(moduleName) {
                    var moduleSettings = moduleConfig[moduleName];

                    if (moduleSettings.enabled) {
                        self.initModule(moduleName, moduleSettings);
                    }
                });
            },

            /**
             * Получение конфигурации модулей
             * Переопределяется в наследниках
             */
            getModuleConfig: function() {
                return {};
            },

            /**
             * Инициализация отдельного модуля
             * @param {string} moduleName - название модуля
             * @param {object} moduleSettings - настройки модуля
             */
            initModule: function(moduleName, moduleSettings) {
                try {
                    require([moduleSettings.path], function(ModuleClass) {
                        this.modules[moduleName] = ModuleClass().init(
                            moduleSettings.config || {},
                            this.createModuleCallbacks(moduleName)
                        );

                        this.trigger('module:initialized', {
                            module: moduleName,
                            instance: this.modules[moduleName]
                        });
                    }.bind(this));
                } catch (error) {
                    console.error('Failed to initialize module ' + moduleName + ':', error);
                }
            },

            /**
             * Создание колбэков для модуля
             * @param {string} moduleName - название модуля
             * @returns {object} - объект с колбэками
             */
            createModuleCallbacks: function(moduleName) {
                var self = this;
                return {
                    showError: function(message) {
                        self.showError(message);
                    },
                    showSuccess: function(message) {
                        self.showSuccess(message);
                    },
                    trigger: function(eventName, data) {
                        self.trigger('module:' + moduleName + ':' + eventName, data);
                    }
                };
            },

            /**
             * Привязка событий
             */
            bindEvents: function() {
                this.bindBaseEvents();
                this.bindFormSpecificEvents();
            },

            /**
             * Привязка базовых событий
             */
            bindBaseEvents: function() {
                var self = this;

                // Отправка формы
                this.formElement.on('submit', function(e) {
                    e.preventDefault();
                    self.submitForm();
                });

                // Кнопки сохранения
                $(this.options.saveButton).on('click', function(e) {
                    e.preventDefault();
                    var continueEdit = $(this).data('continue') || false;
                    self.submitForm(continueEdit);
                });
            },

            /**
             * Привязка специфичных событий формы
             * Переопределяется в наследниках
             */
            bindFormSpecificEvents: function() {
                // Переопределить в наследнике
            },

            /**
             * Инициализация специфичная для формы
             * Переопределяется в наследниках
             */
            initFormSpecific: function() {
                // Переопределить в наследнике
            },

            /**
             * Отправка формы
             * @param {boolean} continueEdit - продолжить редактирование после сохранения
             */
            submitForm: function(continueEdit) {
                if (this.isSubmitting) {
                    return false;
                }

                // Генерируем событие перед отправкой
                var beforeSubmit = this.trigger('form:beforeSubmit', {
                    continueEdit: continueEdit
                });

                if (!beforeSubmit) {
                    return false;
                }

                this.isSubmitting = true;

                // Подготавливаем данные
                var formData = this.prepareFormData();
                var submitUrl = this.getSubmitUrl();

                // Добавляем служебные параметры
                this.addServiceParams(formData, continueEdit);

                // Отправляем через AJAX handler
                var self = this;
                this.ajaxHandler.submit(submitUrl, formData, {
                    showLoader: this.options.showLoader
                }).always(function() {
                    self.isSubmitting = false;
                });
            },

            /**
             * Подготовка данных формы
             * @returns {FormData} - подготовленные данные
             */
            prepareFormData: function() {
                var formData = new FormData();

                // Добавляем основные поля формы
                this.formElement.serializeArray().forEach(function(field) {
                    formData.append(field.name, field.value);
                });

                // Позволяем модулям добавить свои данные
                this.trigger('form:prepareData', { formData: formData });

                // Специфичная подготовка данных
                this.prepareFormSpecificData(formData);

                return formData;
            },

            /**
             * Подготовка специфичных данных формы
             * Переопределяется в наследниках
             * @param {FormData} formData - объект с данными формы
             */
            prepareFormSpecificData: function(formData) {
                // Переопределить в наследнике
            },

            /**
             * Получение URL для отправки
             * @returns {string} - URL для отправки
             */
            getSubmitUrl: function() {
                return this.config.ajaxUrl || this.formElement.attr('action');
            },

            /**
             * Добавление служебных параметров
             * @param {FormData} formData - данные формы
             * @param {boolean} continueEdit - продолжить редактирование
             */
            addServiceParams: function(formData, continueEdit) {
                this.ajaxHandler.prepareFormData(formData, {
                    continueEdit: continueEdit,
                    block: this.options.updateBlock,
                    responseVariable: this.options.responseVariable
                });
            },

            /**
             * Обработка успешного AJAX ответа
             * @param {string} rawResponse - сырой ответ
             * @param {object} parsedResponse - распарсенный ответ
             */
            handleAjaxSuccess: function(rawResponse, parsedResponse) {
                if (!parsedResponse) {
                    this.showError($t('Invalid response format'));
                    return;
                }

                // Проверяем специальные случаи
                if (parsedResponse.reload) {
                    location.reload();
                    return;
                }

                if (parsedResponse.error) {
                    this.showError(parsedResponse.message || $t('An error occurred'));
                    return;
                }

                if (parsedResponse.ajaxExpired && parsedResponse.ajaxRedirect) {
                    window.location.href = parsedResponse.ajaxRedirect;
                    return;
                }

                // Обновляем блоки на странице
                this.updatePageBlocks(parsedResponse);

                // Показываем сообщение об успехе
                var successMessage = parsedResponse.message || $t('Form saved successfully');
                this.showSuccess(successMessage);

                // Генерируем событие успешной отправки
                this.trigger('form:submitSuccess', {
                    response: rawResponse,
                    parsedResponse: parsedResponse
                });
            },

            /**
             * Обработка ошибки AJAX
             * @param {object} errorData - данные об ошибке
             */
            handleAjaxError: function(errorData) {
                this.showError(errorData.message);

                this.trigger('form:submitError', {
                    error: errorData
                });
            },

            /**
             * Обновление блоков на странице
             * @param {object} response - ответ с блоками
             */
            updatePageBlocks: function(response) {
                var self = this;

                // Определяем какие блоки нужно обновить
                var blocksToUpdate = this.getBlocksToUpdate(response);

                blocksToUpdate.forEach(function(blockName) {
                    if (response[blockName]) {
                        self.updatePageBlock(blockName, response[blockName]);
                    }
                });
            },

            /**
             * Получение списка блоков для обновления
             * @param {object} response - ответ сервера
             * @returns {Array} - список названий блоков
             */
            getBlocksToUpdate: function(response) {
                var systemKeys = ['error', 'reload', 'ajaxExpired', 'ajaxRedirect', 'message', 'header', 'redirect_url', 'success'];

                return Object.keys(response).filter(function(key) {
                    return systemKeys.indexOf(key) === -1;
                });
            },

            /**
             * Обновление отдельного блока
             * @param {string} blockName - название блока
             * @param {string} blockHtml - HTML содержимое блока
             */
            updatePageBlock: function(blockName, blockHtml) {
                var blockId = 'offer-' + blockName;
                var $block = $('#' + blockId);

                if ($block.length) {
                    $block.html(blockHtml);
                    $block.trigger('contentUpdated');

                    this.trigger('block:updated', {
                        blockName: blockName,
                        blockId: blockId,
                        element: $block
                    });
                }
            },

            /**
             * Показать сообщение об ошибке
             * @param {string} message - текст ошибки
             */
            showError: function(message) {
                console.error('Form Error:', message);
                // Базовая реализация - просто логирование
                // Наследники могут переопределить для показа уведомлений
            },

            /**
             * Показать сообщение об успехе
             * @param {string} message - текст сообщения
             */
            showSuccess: function(message) {
                console.log('Form Success:', message);
                // Базовая реализация - просто логирование
                // Наследники могут переопределить для показа уведомлений
            },

            /**
             * Генерация события
             * @param {string} eventName - название события
             * @param {*} data - данные события
             * @returns {boolean} - результат обработки события
             */
            trigger: function(eventName, data) {
                if (this.eventManager) {
                    return this.eventManager.trigger(eventName, data);
                }
                return true;
            },

            /**
             * Подписка на событие
             * @param {string} eventName - название события
             * @param {function} callback - обработчик
             * @param {object} context - контекст
             */
            on: function(eventName, callback, context) {
                if (this.eventManager) {
                    this.eventManager.on(eventName, callback, context);
                }
                return this;
            },

            /**
             * Получение экземпляра модуля
             * @param {string} moduleName - название модуля
             * @returns {object|null} - экземпляр модуля
             */
            getModule: function(moduleName) {
                return this.modules[moduleName] || null;
            },

            /**
             * Проверка доступности модуля
             * @param {string} moduleName - название модуля
             * @returns {boolean}
             */
            hasModule: function(moduleName) {
                return this.modules.hasOwnProperty(moduleName) && this.modules[moduleName] !== null;
            },

            /**
             * Уничтожение формы
             */
            destroy: function() {
                // Отвязываем события
                if (this.formElement) {
                    this.formElement.off();
                }

                // Уничтожаем модули
                Object.keys(this.modules).forEach(function(moduleName) {
                    var module = this.modules[moduleName];
                    if (module && typeof module.destroy === 'function') {
                        module.destroy();
                    }
                }, this);

                // Очищаем менеджер событий
                if (this.eventManager) {
                    this.eventManager.clear();
                }

                this.trigger('form:destroyed');

                // Очищаем ссылки
                this.modules = {};
                this.eventManager = null;
                this.ajaxHandler = null;
                this.formElement = null;
                this.isInitialized = false;
            }
        };
    };
});
