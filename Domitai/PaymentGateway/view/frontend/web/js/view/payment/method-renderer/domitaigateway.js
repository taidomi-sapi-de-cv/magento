define([
    "Magento_Checkout/js/view/payment/default",
    "Magento_Checkout/js/action/redirect-on-success",
    "mage/url",
], function (Component, redirectOnSuccessAction, url) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Domitai_PaymentGateway/payment/customtemplate",
            transactionResult: "",
        },
        afterPlaceOrder: function () {
            redirectOnSuccessAction.redirectUrl = url.build(
                "domitai/path/index"
            );
            this.redirectAfterPlaceOrder = true;
        },
        initObservable: function () {
            this._super().observe(["transactionResult"]);
            return this;
        },
        getData: function () {
            return {
                method: this.item.method,
                additional_data: {
                    transaction_result: this.transactionResult(),
                },
            };
        },
        getTransactionResults: function () {
            return _.map(
                window.checkoutConfig.payment.custompayment.transactionResults,
                function (value, key) {
                    return {
                        value: key,
                        transaction_result: value,
                    };
                }
            );
        },
    });
});
