define([], function () {
    'use strict';

    /**
     * Реестр типов форм
     * Централизованное хранение всех доступных типов форм
     */
    var FormRegistry = {
        // Хранилище зарегистрированных типов форм
        _registry: {},

        /**
         * Регистрация нового типа формы
         * @param {string} type - тип формы (например, 'offer-info')
         * @param {function} FormClass - конструктор класса формы
         * @param {object} defaultConfig - конфигурация по умолчанию для этого типа
         */
        register: function(type, FormClass, defaultConfig) {
            this._registry[type] = {
                FormClass: FormClass,
                defaultConfig: defaultConfig || {}
            };
        },

        /**
         * Получение зарегистрированного типа формы
         * @param {string} type - тип формы
         * @returns {object|null} - объект с FormClass и defaultConfig или null
         */
        get: function(type) {
            return this._registry[type] || null;
        },

        /**
         * Проверка существования типа формы
         * @param {string} type - тип формы
         * @returns {boolean}
         */
        has: function(type) {
            return this._registry.hasOwnProperty(type);
        },

        /**
         * Получение всех зарегистрированных типов
         * @returns {Array} - массив названий типов
         */
        getRegisteredTypes: function() {
            return Object.keys(this._registry);
        },

        /**
         * Удаление типа из реестра
         * @param {string} type - тип формы
         */
        unregister: function(type) {
            delete this._registry[type];
        }
    };

    return FormRegistry;
});
