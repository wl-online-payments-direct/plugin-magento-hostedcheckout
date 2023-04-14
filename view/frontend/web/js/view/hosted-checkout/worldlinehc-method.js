define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Worldline_HostedCheckout/js/view/hosted-checkout/redirect',
    'Magento_Checkout/js/model/full-screen-loader',
    'Worldline_PaymentCore/js/model/device-data',
    'Magento_Checkout/js/model/payment/additional-validators'
], function ($, Component, VaultEnabler, placeOrderAction, fullScreenLoader, deviceData, additionalValidators) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Worldline_HostedCheckout/payment/worldlinehc'
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());
            return this;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].hcVaultCode;
        },

        /**
         * Get list of available CC types
         *
         * @returns {Object}
         */
        getAvailableTypes: function () {
            let availableTypes = window.checkoutConfig.payment[this.getCode()].icons,
                applePayCode = 302;
            if (availableTypes && availableTypes instanceof Object) {
                if (availableTypes[applePayCode]) {
                    if (!window.ApplePaySession) {
                        delete availableTypes[applePayCode];
                    }
                }
                return Object.keys(availableTypes);
            }

            return [];
        },

        /**
         * Get payment icons.
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment[this.getCode()].icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment[this.getCode()].icons[type]
                : false;
        },

        placeOrder: function (data, event) {
            let self = this;

            if (event) {
                event.preventDefault();
            }

            if (!this.validate() ||
                (this.isPlaceOrderActionAllowed() !== true) ||
                !additionalValidators.validate()
            ) {
                return false;
            }

            fullScreenLoader.startLoader();

            this.isPlaceOrderActionAllowed(false);

            $.when(
                placeOrderAction(self.getData(), self.messageContainer)
            ).done(
                function (redirectUrl) {
                    if (redirectUrl) {
                        window.location.replace(redirectUrl);
                    }
                }
            ).fail(
                function () {
                    self.isPlaceOrderActionAllowed(true);
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                }
            );

            return true;
        },

        /**
         * @returns {Object}
         */
        getData: function () {
            let data = this._super();
            data.additional_data = deviceData.getData();

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        }
    });
});
