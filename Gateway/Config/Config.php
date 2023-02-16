<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;

class Config extends PaymentGatewayConfig
{
    public const AUTHORIZATION_MODE = 'authorization_mode';
    public const PAYMENT_ACTION = 'payment_action';
    public const AUTHORIZATION_MODE_FINAL = 'FINAL_AUTHORIZATION';
    public const AUTHORIZATION_MODE_PRE = 'PRE_AUTHORIZATION';
    public const AUTHORIZATION_MODE_SALE = 'SALE';
    public const AUTHORIZE_CAPTURE = 'authorize_capture';
    public const TEMPLATE_ID = 'template_id';
    public const KEY_ACTIVE = 'active';
    public const KEY_CART_LINES = 'cart_lines';
    public const ENABLE_GROUP_CARDS = 'enable_group_cards';
    public const PROCESS_MEALVOUCHERS = 'process_mealvouchers';

    public const DIRECT_DEBIT_RECURRENCE_TYPE = 'direct_debit_recurrence_type';
    public const DIRECT_DEBIT_SIGNATURE_TYPE = 'direct_debit_signature_type';

    public function getAuthorizationMode(?int $storeId = null): string
    {
        if ($this->getValue(self::PAYMENT_ACTION, $storeId) === self::AUTHORIZE_CAPTURE) {
            return self::AUTHORIZATION_MODE_SALE;
        }

        $authorizationMode = (string) $this->getValue(self::AUTHORIZATION_MODE, $storeId);
        switch ($authorizationMode) {
            case 'pre':
                return self::AUTHORIZATION_MODE_PRE;
            default:
                return self::AUTHORIZATION_MODE_FINAL;
        }
    }

    public function getTemplateId(?int $storeId = null): string
    {
        return (string) $this->getValue(self::TEMPLATE_ID, $storeId);
    }

    public function isActive(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    public function isCartLines(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_CART_LINES, $storeId);
    }

    public function getDirectDebitRecurrenceType(?int $storeId = null): string
    {
        return (string) $this->getValue(self::DIRECT_DEBIT_RECURRENCE_TYPE, $storeId);
    }

    public function getDirectDebitSignatureType(?int $storeId = null): string
    {
        return (string) $this->getValue(self::DIRECT_DEBIT_SIGNATURE_TYPE, $storeId);
    }

    public function isGroupCardsEnabled(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::ENABLE_GROUP_CARDS, $storeId);
    }

    public function isProcessMealvouchers(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::PROCESS_MEALVOUCHERS, $storeId);
    }
}
