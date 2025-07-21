let config = {
    map: {
        '*': {
            // Основные виджеты (для обратной совместимости и новой системы)
            offerForm: 'OneMoveTwo_Offers/js/offer/view/form',
            offerInfoForm: 'OneMoveTwo_Offers/js/offer/view/offer-info-form',
            offerMainScript: 'OneMoveTwo_Offers/js/offer/view/scripts',
            productConfigureForm: 'OneMoveTwo_Offers/js/offer/view/product-configure-form',

            // Новая архитектура - базовые компоненты
            baseForm: 'OneMoveTwo_Offers/js/offer/view/core/base-form',
            formFactory: 'OneMoveTwo_Offers/js/offer/view/core/form-factory',
            formRegistry: 'OneMoveTwo_Offers/js/offer/view/core/form-registry',
            formInitializer: 'OneMoveTwo_Offers/js/offer/view/form-initializer',

            // Модули
            fileHandler: 'OneMoveTwo_Offers/js/offer/view/modules/file-handler',
            eventManager: 'OneMoveTwo_Offers/js/offer/view/modules/event-manager',
            ajaxHandler: 'OneMoveTwo_Offers/js/offer/view/modules/ajax-handler',
            notifications: 'OneMoveTwo_Offers/js/offer/view/modules/notifications',
            formValidation: 'OneMoveTwo_Offers/js/offer/view/modules/form-validation',

            // Конкретные формы
            offerInfoFormNew: 'OneMoveTwo_Offers/js/offer/view/forms/offer-info-form',
            productConfigureFormNew: 'OneMoveTwo_Offers/js/offer/view/forms/product-configure-form'
        }
    },

    // Настройки для путей модулей
    paths: {
        'OneMoveTwo_Offers/core': 'OneMoveTwo_Offers/js/offer/view/core',
        'OneMoveTwo_Offers/modules': 'OneMoveTwo_Offers/js/offer/view/modules',
        'OneMoveTwo_Offers/forms': 'OneMoveTwo_Offers/js/offer/view/forms'
    },

    // Зависимости для модулей
    shim: {
        'OneMoveTwo_Offers/js/offer/view/core/base-form': {
            deps: ['jquery', 'mage/translate']
        },
        'OneMoveTwo_Offers/js/offer/view/modules/file-handler': {
            deps: ['jquery', 'mage/translate', 'Magento_Ui/js/modal/confirm']
        },
        'OneMoveTwo_Offers/js/offer/view/modules/notifications': {
            deps: ['jquery', 'mage/translate', 'Magento_Ui/js/modal/alert']
        }
    }
};
