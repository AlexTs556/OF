// view/adminhtml/web/js/offer/view/forms/product-configure-form.js
define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/core/base-form',
    'OneMoveTwo_Offers/js/offer/view/modules/notifications'
], function ($, BaseForm, Notifications) {
    'use strict';

    /**
     * Форма конфигурации продукта
     * Наследует BaseForm и добавляет специфичную логику для configure popup
     */
    return function(config) {
        // Создаем экземпляр базовой формы
        var form = BaseForm(config);

        // Расширяем базовую функциональность
        return $.extend(form, {

            /**
             * Получение опций специфичных для формы product configure
             */
            getFormSpecificOptions: function() {
                return {
                    formSelector: '#product_configure_form',
                    saveButton: '.modal-footer .action-primary',
                    updateBlock: null, // Попап не обновляет блоки страницы
                    responseVariable: null,
                    showLoader: true,

                    // Специфичные селекторы для product configure
                    itemIdInput: '#configure_item_id',
                    offerIdInput: '#configure_offer_id',
                    productNameInput: '#configure_item_name',
                    productSkuInput: '#configure_item_sku',
                    productQtyInput: '#configure_item_qty',
                    productPriceInput: '#configure_item_price',
                    productImageContainer: '#product-image-container',
                    stockInfo: '#stock-info',
                    stockQuantity: '#stock-quantity'
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
             * Получение URL для отправки
             */
            getSubmitUrl: function() {
                return this.config.saveUrl || 'offers/item/save_item_configure';
            },

            /**
             * Инициализация специфичная для product configure формы
             */
            initFormSpecific: function() {
                this.initNotificationsIntegration();

                // Если есть данные айтема, заполняем форму
                if (this.config.itemData) {
                    this.populateForm(this.config.itemData);
                }
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
             * Заполнение формы данными айтема
             */
            populateForm: function(itemData) {
                console.log('Populating form with item data:', itemData);

                // Заполняем основные поля
                if (itemData.id) {
                    $(this.options.itemIdInput).val(itemData.id);
                }

                if (itemData.offer_id) {
                    $(this.options.offerIdInput).val(itemData.offer_id);
                }

                if (itemData.name) {
                    $(this.options.productNameInput).val(itemData.name);
                    // Обновляем заголовок попапа
                    $('.popup-title').text('Configure: ' + itemData.name);
                }

                if (itemData.sku) {
                    $(this.options.productSkuInput).val(itemData.sku);
                }

                if (itemData.qty) {
                    $(this.options.productQtyInput).val(itemData.qty);
                }

                if (itemData.price) {
                    $(this.options.productPriceInput).val(itemData.price);
                }

                // Обновляем изображение продукта
                this.updateProductImage(itemData);

                // Обновляем информацию о стоке
                this.updateStockInfo(itemData);

                // Заполняем заметки если есть
                if (itemData.customer_note) {
                    $('#customer_note').val(itemData.customer_note);
                }

                if (itemData.internal_note) {
                    $('#internal_note').val(itemData.internal_note);
                }

                // Заполняем кастомизацию если есть
                if (itemData.customization) {
                    this.populateCustomization(itemData.customization);
                }

                // Заполняем чекбоксы
                if (itemData.optional_item !== undefined) {
                    $('#optional_item').prop('checked', !!itemData.optional_item);
                }

                if (itemData.ignore_inventory !== undefined) {
                    $('#ignore_inventory').prop('checked', !!itemData.ignore_inventory);
                }
            },

            /**
             * Обновление изображения продукта
             */
            updateProductImage: function(itemData) {
                var $imageContainer = $(this.options.productImageContainer);

                if (itemData.image_url) {
                    $imageContainer.html('<img src="' + itemData.image_url + '" alt="' + (itemData.name || 'Product') + '" class="product-image">');
                } else {
                    $imageContainer.html('<span>No Image</span>');
                }
            },

            /**
             * Обновление информации о стоке
             */
            updateStockInfo: function(itemData) {
                var stockQuantity = itemData.stock_quantity || itemData.qty || 0;
                $(this.options.stockQuantity).text(stockQuantity);
            },

            /**
             * Заполнение таблицы кастомизации
             */
            populateCustomization: function(customizationData) {
                if (!Array.isArray(customizationData) || customizationData.length === 0) {
                    return;
                }

                var $tbody = $('#customization-tbody');
                $tbody.empty(); // Очищаем существующие строки

                customizationData.forEach(function(item, index) {
                    var row = $('<tr></tr>');
                    row.html(`
                        <td><input type="text" name="customization[${index}][attribute]" value="${item.attribute || ''}" placeholder="Attribute name" /></td>
                        <td><input type="text" name="customization[${index}][standard]" value="${item.standard || ''}" placeholder="Standard value" /></td>
                        <td><input type="text" name="customization[${index}][custom]" value="${item.custom || ''}" placeholder="Custom value" /></td>
                        <td><button type="button" class="remove-row-btn" onclick="removeCustomizationRow(this)">×</button></td>
                    `);
                    $tbody.append(row);
                });

                // Если нет строк, добавляем одну пустую
                if (customizationData.length === 0) {
                    window.addCustomizationRow();
                }
            },

            /**
             * Подготовка специфичных данных формы
             */
            prepareFormSpecificData: function(formData) {
                // Добавляем ID айтема и оффера для бэкенда
                var itemId = $(this.options.itemIdInput).val();
                var offerId = $(this.options.offerIdInput).val();

                if (itemId) {
                    formData.append('item_id', itemId);
                }

                if (offerId) {
                    formData.append('offer_id', offerId);
                }

                // Собираем все данные конфигурации в единую структуру
                var itemConfiguration = this.collectItemConfiguration();
                formData.append('item_configuration', JSON.stringify(itemConfiguration));
            },

            /**
             * Сбор всех данных конфигурации айтема
             */
            collectItemConfiguration: function() {
                var itemConfiguration = {
                    // Основные поля продукта
                    product_name: $(this.options.productNameInput).val(),
                    product_sku: $(this.options.productSkuInput).val(),
                    product_qty: parseInt($(this.options.productQtyInput).val()) || 1,
                    product_price: parseFloat($(this.options.productPriceInput).val()) || 0,

                    // Комментарии
                    customer_note: $('#customer_note').val() || '',
                    internal_note: $('#internal_note').val() || '',

                    // Настройки
                    optional_item: $('#optional_item').is(':checked'),
                    ignore_inventory: $('#ignore_inventory').is(':checked'),

                    // Кастомизация
                    customization: this.collectCustomizationData(),

                    // Файлы будут добавлены автоматически через file handler
                    attachments: []
                };

                // Добавляем информацию о файлах если есть file handler
                if (this.hasModule('fileHandler')) {
                    var filesData = this.getModule('fileHandler').getFilesData();

                    // Добавляем существующие файлы
                    if (filesData.existingFiles && filesData.existingFiles.size > 0) {
                        filesData.existingFiles.forEach(function(file) {
                            if (file.isExisting) {
                                itemConfiguration.attachments.push({
                                    attachment_id: file.attachmentId,
                                    file_name: file.name,
                                    file_path: file.path,
                                    file_size: file.size,
                                    file_type: file.type
                                });
                            }
                        });
                    }

                    // Новые файлы будут обработаны через стандартный механизм attachments[]
                }

                return itemConfiguration;
            },

            /**
             * Сбор данных кастомизации
             */
            collectCustomizationData: function() {
                var customizationData = [];
                $('#customization-tbody tr').each(function() {
                    var $row = $(this);
                    var attribute = $row.find('input[name*="[attribute]"]').val();
                    var standard = $row.find('input[name*="[standard]"]').val();
                    var custom = $row.find('input[name*="[custom]"]').val();

                    // Добавляем только строки где заполнен хотя бы атрибут
                    if (attribute && attribute.trim()) {
                        customizationData.push({
                            attribute: attribute.trim(),
                            standard: standard.trim(),
                            custom: custom.trim()
                        });
                    }
                });

                return customizationData;
            },

            /**
             * Обработка успешной отправки (хук)
             */
            onSubmitSuccess: function(response, parsedResponse) {
                console.log('Product configure form submitted successfully');

                // Закрываем попап после успешного сохранения
                this.closePopup();

                // Можно добавить обновление грида или других элементов
                this.trigger('item:configured', {
                    itemId: $(this.options.itemIdInput).val(),
                    response: parsedResponse
                });
            },

            /**
             * Обработка ошибки отправки (хук)
             */
            onSubmitError: function(xhr) {
                console.error('Product configure form submission failed');
                // Попап остается открытым для исправления ошибок
            },

            /**
             * Закрытие попапа
             */
            closePopup: function() {
                // Если используется jQuery UI modal
                if ($('#custom-options-popup').hasClass('ui-dialog-content')) {
                    $('#custom-options-popup').dialog('close');
                }
                // Если используется Magento modal
                else if ($('#custom-options-popup').data('mage-modal')) {
                    $('#custom-options-popup').modal('closeModal');
                }
                // Простое скрытие
                else {
                    $('#custom-options-popup').hide();
                }
            },

            /**
             * Обновление данных айтема (для вызова извне)
             */
            updateItemData: function(newItemData) {
                this.config.itemData = $.extend({}, this.config.itemData, newItemData);
                this.populateForm(this.config.itemData);
            },

            /**
             * Получение текущих данных формы
             */
            getFormData: function() {
                var data = {};

                // Основные поля
                data.item_id = $(this.options.itemIdInput).val();
                data.offer_id = $(this.options.offerIdInput).val();
                data.name = $(this.options.productNameInput).val();
                data.sku = $(this.options.productSkuInput).val();
                data.qty = $(this.options.productQtyInput).val();
                data.price = $(this.options.productPriceInput).val();
                data.customer_note = $('#customer_note').val();
                data.internal_note = $('#internal_note').val();
                data.optional_item = $('#optional_item').is(':checked') ? 1 : 0;
                data.ignore_inventory = $('#ignore_inventory').is(':checked') ? 1 : 0;

                // Кастомизация
                data.customization = this.collectCustomizationData();

                // Файлы
                if (this.hasModule('fileHandler')) {
                    data.files = this.getModule('fileHandler').getFilesData();
                }

                return data;
            }
        });
    };
});
