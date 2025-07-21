define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/core/form-registry'
], function ($, FormRegistry) {
    'use strict';

    /**
     * Фабрика для создания форм
     * Централизованное создание экземпляров форм на основе типа
     */
    var FormFactory = {

        /**
         * Создание формы по типу
         * @param {string} formType - тип формы
         * @param {object} config - конфигурация формы
         * @param {object} options - дополнительные опции
         * @returns {object|null} - экземпляр формы или null
         */
        create: function(formType, config, options) {
            // Получаем информацию о типе формы из реестра
            var formInfo = FormRegistry.get(formType);

            if (!formInfo) {
                console.error('Unknown form type:', formType);
                return null;
            }

            try {
                // Объединяем конфигурацию по умолчанию с пользовательской
                var mergedConfig = this.mergeConfigs(formInfo.defaultConfig, config || {});

                // Создаем экземпляр формы
                var formInstance = formInfo.FormClass(mergedConfig);

                // Инициализируем форму
                formInstance.init(mergedConfig, options);

                console.log('Created form instance:', formType, formInstance);

                return formInstance;

            } catch (error) {
                console.error('Failed to create form instance for type "' + formType + '":', error);
                return null;
            }
        },

        /**
         * Создание формы из HTML элемента
         * @param {jQuery|HTMLElement} element - элемент формы
         * @returns {object|null} - экземпляр формы или null
         */
        createFromElement: function(element) {
            var $element = $(element);

            // Получаем тип формы из data-атрибута
            var formType = $element.data('form-type');
            if (!formType) {
                console.warn('Form element missing data-form-type attribute');
                return null;
            }

            // Получаем конфигурацию из data-атрибута
            var config = $element.data('form-config') || {};

            // Дополнительные опции на основе элемента
            var options = this.extractOptionsFromElement($element);

            return this.create(formType, config, options);
        },

        /**
         * Извлечение опций из HTML элемента
         * @param {jQuery} $element - jQuery элемент
         * @returns {object} - извлеченные опции
         */
        extractOptionsFromElement: function($element) {
            var options = {};

            // Извлекаем селектор формы
            if ($element.is('form')) {
                options.formSelector = '#' + $element.attr('id');
            } else {
                // Ищем форму внутри элемента
                var $form = $element.find('form').first();
                if ($form.length) {
                    options.formSelector = '#' + $form.attr('id');
                }
            }

            // Извлекаем другие data-атрибуты как опции
            var dataAttrs = $element.data();
            Object.keys(dataAttrs).forEach(function(key) {
                if (key.startsWith('form-option-')) {
                    var optionKey = key.replace('form-option-', '').replace(/-/g, '');
                    options[optionKey] = dataAttrs[key];
                }
            });

            return options;
        },

        /**
         * Глубокое объединение конфигураций
         * @param {object} defaultConfig - конфигурация по умолчанию
         * @param {object} userConfig - пользовательская конфигурация
         * @returns {object} - объединенная конфигурация
         */
        mergeConfigs: function(defaultConfig, userConfig) {
            // Простое глубокое объединение объектов
            return this.deepMerge({}, defaultConfig, userConfig);
        },

        /**
         * Глубокое объединение объектов
         * @param {object} target - целевой объект
         * @param {...object} sources - исходные объекты
         * @returns {object} - объединенный объект
         */
        deepMerge: function(target) {
            var sources = Array.prototype.slice.call(arguments, 1);

            sources.forEach(function(source) {
                if (source && typeof source === 'object') {
                    Object.keys(source).forEach(function(key) {
                        var sourceValue = source[key];
                        var targetValue = target[key];

                        if (sourceValue && typeof sourceValue === 'object' && !Array.isArray(sourceValue)) {
                            // Рекурсивно объединяем объекты
                            target[key] = this.deepMerge(targetValue || {}, sourceValue);
                        } else {
                            // Просто копируем значение
                            target[key] = sourceValue;
                        }
                    }, this);
                }
            }, this);

            return target;
        },

        /**
         * Валидация конфигурации формы
         * @param {string} formType - тип формы
         * @param {object} config - конфигурация для валидации
         * @returns {boolean} - результат валидации
         */
        validateConfig: function(formType, config) {
            var formInfo = FormRegistry.get(formType);

            if (!formInfo) {
                return false;
            }

            // Здесь можно добавить специфичную валидацию для каждого типа формы
            // Пока просто проверяем что config это объект
            return typeof config === 'object' && config !== null;
        },

        /**
         * Получение списка доступных типов форм
         * @returns {Array} - массив доступных типов
         */
        getAvailableTypes: function() {
            return FormRegistry.getRegisteredTypes();
        },

        /**
         * Проверка поддержки типа формы
         * @param {string} formType - тип формы
         * @returns {boolean} - поддерживается ли тип
         */
        supportsType: function(formType) {
            return FormRegistry.has(formType);
        }
    };

    return FormFactory;
});
