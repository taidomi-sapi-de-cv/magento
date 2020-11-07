define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
    "use strict";
    rendererList.push({
        type: "domitaigateway",
        component:
            "Domitai_PaymentGateway/js/view/payment/method-renderer/domitaigateway",
    });
    return Component.extend({});
});
