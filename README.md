# Worldline Online Payments

## Hosted checkout

[![M2 Coding Standard](https://github.com/wl-online-payments-direct/plugin-magento-hostedcheckout/actions/workflows/coding-standard.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-hostedcheckout/actions/workflows/coding-standard.yml)
[![M2 Mess Detector](https://github.com/wl-online-payments-direct/plugin-magento-hostedcheckout/actions/workflows/mess-detector.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-hostedcheckout/actions/workflows/mess-detector.yml)

This is a module for the hosted checkout Worldline payment solution.

To install this solution, you may use
[adobe commerce marketplace](https://marketplace.magento.com/worldline-module-magento-payment.html)
or install it from the GitHub.

This solution is also included into:
- [main plugin for adobe commerce](https://github.com/wl-online-payments-direct/plugin-magento)
- [redirect payments (single payment buttons)](https://github.com/wl-online-payments-direct/plugin-magento-redirect-payments)

### Change log:

#### 1.8.2
- Add fix for Adobe Commerce cloud instances.

#### 1.8.1
- Add backend address validation before payments.
- General code improvements and bug fixes.

#### 1.8.0
- Add surcharge functionality (for the Australian market).
- Add Sepa Direct Debit payment method.
- Add the ability to save the Sepa Direct Debit mandate and use it through the Magento vault.
- Improvements of the Oney3x4x payment method.
- Extract GraphQl into a dedicated extension.
- General code improvements and bug fixes.

#### 1.7.1
- Support the 13.0.0 version of PWA.

#### 1.7.0
- Add Multibanco payment method.
- Add price restrictions for currencies having specific decimals rules (like JPY).
- Move 3-D Secure settings to the general tab.
- Change names and tooltips of the 3-D Secure settings.
- Add integration tests.
- General code improvements and bug fixes.

#### 1.6.1
- Rise core version.

#### 1.6.0
- Add the "Mealvouchers" payment method.
- Improve cancel and void actions logic.
- Add uninstall script.
- Update release notes.
- General code improvements and bug fixes.

#### 1.5.0
- Add "groupCards" functionality (for hosted checkout) : group all card under one single payment button.
- Improve Worldline payment box design: split in payment and fraud results.
- Add a feature to request 3DS exemption for transactions below 30 EUR.
- General code improvements and bug fixes.

#### 1.4.0
- Option added to enforce Strong Customer Authentication for every 3DS request.
- Improvements and support for 2.3.x magento versions.
- General code improvements and bug fix.

#### 1.3.1
- Improve work for multi website instances.

#### 1.3.0
- Improve the "waiting" page.
- Add the "pending" page.

#### 1.2.0
- General improvements and bug fixes.

#### 1.1.1
- PWA improvements and support.
- General code improvements.

#### 1.1.0
- Waiting page has been added after payment is done to correctly process webhooks and create the order.
- Asyncronic order creation through get calls when webhooks suffer delay.
- Refund flow is improved for multi-website instances.
- Bancontact payment method implementation has been improved.
- General improvements and bug fixes.

#### 1.0.0
- Initial MVP version.
