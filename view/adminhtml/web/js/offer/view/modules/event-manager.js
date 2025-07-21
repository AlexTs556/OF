define(['jquery'], function ($) {
    'use strict';

    /**
     * Менеджер событий для форм
     * Предоставляет систему событий для взаимодействия между компонентами
     */
    return function() {
        return {
            // Хранилище обработчиков событий
            _listeners: {},

            /**
             * Подписка на событие
             * @param {string} eventName - название события
             * @param {function} callback - функция-обработчик
             * @param {object} context - контекст выполнения (this)
             */
            on: function(eventName, callback, context) {
                if (!this._listeners[eventName]) {
                    this._listeners[eventName] = [];
                }

                this._listeners[eventName].push({
                    callback: callback,
                    context: context || null
                });

                return this;
            },

            /**
             * Отписка от события
             * @param {string} eventName - название события
             * @param {function} callback - функция-обработчик для удаления
             */
            off: function(eventName, callback) {
                if (!this._listeners[eventName]) {
                    return this;
                }

                if (!callback) {
                    // Удаляем все обработчики для события
                    delete this._listeners[eventName];
                } else {
                    // Удаляем конкретный обработчик
                    this._listeners[eventName] = this._listeners[eventName].filter(function(listener) {
                        return listener.callback !== callback;
                    });
                }

                return this;
            },

            /**
             * Одноразовая подписка на событие
             * @param {string} eventName - название события
             * @param {function} callback - функция-обработчик
             * @param {object} context - контекст выполнения
             */
            once: function(eventName, callback, context) {
                var self = this;
                var onceCallback = function() {
                    callback.apply(context || this, arguments);
                    self.off(eventName, onceCallback);
                };

                return this.on(eventName, onceCallback, context);
            },

            /**
             * Генерация события
             * @param {string} eventName - название события
             * @param {*} data - данные события
             * @returns {boolean} - false если событие было отменено
             */
            trigger: function(eventName, data) {
                if (!this._listeners[eventName]) {
                    return true;
                }

                var event = {
                    type: eventName,
                    data: data || {},
                    preventDefault: false,
                    stopPropagation: false
                };

                // Добавляем методы для управления событием
                event.prevent = function() {
                    this.preventDefault = true;
                };

                event.stop = function() {
                    this.stopPropagation = true;
                };

                // Выполняем все обработчики
                for (var i = 0; i < this._listeners[eventName].length; i++) {
                    var listener = this._listeners[eventName][i];

                    try {
                        listener.callback.call(listener.context, event);
                    } catch (e) {
                        console.error('Error in event handler for "' + eventName + '":', e);
                    }

                    // Если вызван stopPropagation, прерываем выполнение
                    if (event.stopPropagation) {
                        break;
                    }
                }

                // Возвращаем true если событие не было отменено
                return !event.preventDefault;
            },

            /**
             * Получение списка всех событий с обработчиками
             * @returns {Array} - массив названий событий
             */
            getEventNames: function() {
                return Object.keys(this._listeners);
            },

            /**
             * Получение количества обработчиков для события
             * @param {string} eventName - название события
             * @returns {number} - количество обработчиков
             */
            getListenerCount: function(eventName) {
                return this._listeners[eventName] ? this._listeners[eventName].length : 0;
            },

            /**
             * Очистка всех обработчиков
             */
            clear: function() {
                this._listeners = {};
                return this;
            }
        };
    };
});
