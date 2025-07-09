define([
    "jquery",
    "offerMainScript"
], function (jQuery) {
    'use strict';

    let $el = jQuery('#edit_form'),
        config,
        baseUrl,
        offer,
        payment;


    console.log('33333333333333333333333333333');
    console.log($el);

    if (!$el.length || !$el.data('offer-config')) {
        return;
    }

    config = $el.data('offer-config');
    baseUrl = $el.data('load-base-url');

    offer = new AdminOffer(config);

    console.log(offer);
    console.log('@@@@@@@@@@@@@@@@@@@@@@@@@');
    offer.setLoadBaseUrl(baseUrl);

    payment = {
        switchMethod: offer.switchPaymentMethod.bind(offer)
    };

    window.offer = offer;
    window.payment = payment;
});
