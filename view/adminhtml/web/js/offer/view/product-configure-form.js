define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/form-initializer'
], function ($, FormInitializer) {
    'use strict';

    /**
     * Magento виджет для инициализации product configure формы
     * Используется через data-mage-init="productConfigureForm"
     */
    return function(config, element) {
        // Добавляем тип формы в конфигурацию
        config = $.extend({
            formType: 'product-configure'
        }, config || {});

        // Делегируем инициализацию Form Initializer
        return FormInitializer(config, element);
    };
});
