define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    /**
     * Обработчик AJAX запросов
     * Стандартизированная обработка запросов для всех форм
     */
    return function() {
        return {
            // Настройки по умолчанию
            defaultOptions: {
                method: 'POST',
                dataType: 'text',
                processData: false,
                contentType: false,
                showLoader: true,
                timeout: 30000
            },

            // Экземпляр событийного менеджера (будет передан извне)
            eventManager: null,

            /**
             * Инициализация с менеджером событий
             * @param {object} eventManager - экземпляр менеджера событий
             */
            init: function(eventManager) {
                this.eventManager = eventManager;
                return this;
            },

            /**
             * Выполнение AJAX запроса
             * @param {string} url - URL для запроса
             * @param {FormData|object} data - данные для отправки
             * @param {object} options - дополнительные опции запроса
             * @returns {Promise} - промис с результатом запроса
             */
            submit: function(url, data, options) {
                var self = this;
                options = $.extend({}, this.defaultOptions, options || {});

                // Генерируем событие перед отправкой
                if (this.eventManager) {
                    var beforeEvent = this.eventManager.trigger('ajax:beforeSend', {
                        url: url,
                        data: data,
                        options: options
                    });

                    if (!beforeEvent) {
                        return $.Deferred().reject('Request cancelled by beforeSend event').promise();
                    }
                }

                return $.ajax({
                    url: url,
                    type: options.method,
                    data: data,
                    dataType: options.dataType,
                    processData: options.processData,
                    contentType: options.contentType,
                    showLoader: options.showLoader,
                    timeout: options.timeout
                }).done(function(response) {
                    self._handleSuccess(response, options);
                }).fail(function(xhr, status, error) {
                    self._handleError(xhr, status, error, options);
                });
            },

            /**
             * Обработка успешного ответа
             * @param {*} response - ответ сервера
             * @param {object} options - опции запроса
             * @private
             */
            _handleSuccess: function(response, options) {
                var parsedResponse = this._parseResponse(response);

                if (this.eventManager) {
                    this.eventManager.trigger('ajax:success', {
                        response: response,
                        parsedResponse: parsedResponse,
                        options: options
                    });
                }
            },

            /**
             * Обработка ошибки запроса
             * @param {object} xhr - объект XMLHttpRequest
             * @param {string} status - статус ошибки
             * @param {string} error - текст ошибки
             * @param {object} options - опции запроса
             * @private
             */
            _handleError: function(xhr, status, error, options) {
                var errorData = {
                    xhr: xhr,
                    status: status,
                    error: error,
                    message: this._extractErrorMessage(xhr),
                    options: options
                };

                if (this.eventManager) {
                    this.eventManager.trigger('ajax:error', errorData);
                }
            },

            /**
             * Парсинг ответа сервера
             * @param {*} response - сырой ответ
             * @returns {object|null} - распарсенный ответ
             * @private
             */
            _parseResponse: function(response) {
                if (typeof response === 'object') {
                    return response;
                }

                if (typeof response === 'string') {
                    // Проверяем, начинается ли ответ с <script> (HTML ответ)
                    if (response.trim().indexOf('<script>') === 0) {
                        return this._extractJsonFromScript(response);
                    } else {
                        // Пытаемся парсить как прямой JSON
                        try {
                            return JSON.parse(response);
                        } catch (e) {
                            console.warn('Failed to parse response as JSON:', e);
                            return null;
                        }
                    }
                }

                return null;
            },

            /**
             * Извлечение JSON из script-тега
             * @param {string} response - HTML ответ со script
             * @returns {object|null} - распарсенный JSON
             * @private
             */
            _extractJsonFromScript: function(response) {
                var jsonStart = response.indexOf('{');
                var jsonEnd = response.lastIndexOf('}') + 1;

                if (jsonStart !== -1 && jsonEnd !== -1) {
                    var jsonPart = response.substring(jsonStart, jsonEnd);
                    try {
                        return JSON.parse(jsonPart);
                    } catch (e) {
                        console.error('Failed to parse extracted JSON:', e);
                        return null;
                    }
                }

                return null;
            },

            /**
             * Извлечение сообщения об ошибке из xhr
             * @param {object} xhr - объект XMLHttpRequest
             * @returns {string} - сообщение об ошибке
             * @private
             */
            _extractErrorMessage: function(xhr) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    return response.message || $t('An error occurred while processing the request');
                } catch (e) {
                    return $t('An error occurred while processing the request');
                }
            },

            /**
             * Подготовка FormData с базовыми параметрами
             * @param {FormData} formData - существующий FormData
             * @param {object} params - дополнительные параметры
             * @returns {FormData} - дополненный FormData
             */
            prepareFormData: function(formData, params) {
                params = params || {};

                // Добавляем стандартные параметры
                if (params.json !== false) {
                    formData.append('json', '1');
                }

                if (params.continueEdit) {
                    formData.append('back', 'continue');
                }

                if (params.block) {
                    formData.append('block', params.block);
                }

                if (params.responseVariable) {
                    formData.append('as_js_varname', params.responseVariable);
                }

                return formData;
            }
        };
    };
});
