define([
    'jquery',
    'OneMoveTwo_Offers/js/offer/view/form-initializer'
], function ($, FormInitializer) {
    'use strict';

    /**
     * Magento виджет для инициализации offer info формы
     * Используется через data-mage-init="offerInfoForm"
     */
    return function(config, element) {
        // Добавляем тип формы в конфигурацию
        config = $.extend({
            formType: 'offer-info'
        }, config || {});

        // Делегируем инициализацию Form Initializer
        return FormInitializer(config, element);
    };
});
