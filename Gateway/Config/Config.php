<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use Magento\Store\Model\ScopeInterface;
use Worldline\HostedCheckout\Ui\ConfigProvider;

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
    public const ONEY_PAYMENT_OPTION = 'oney3x4x_payment_option';
    public const ALLOW_ATTEMPTS = 'allow_attempts';
    public const SESSION_TIMEOUT = 'session_timeout';

    public const DIRECT_DEBIT_RECURRENCE_TYPE = 'direct_debit_recurrence_type';
    public const DIRECT_DEBIT_SIGNATURE_TYPE = 'direct_debit_signature_type';

    public const BANK_TRANSFER_MODE = 'bank_transfer_mode';

    public const VAULT_PATH = 'payment/worldline_hosted_checkout_vault/active';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = ConfigProvider::HC_CODE,
        $pathPattern = PaymentGatewayConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->scopeConfig = $scopeConfig;
    }

    public function getAuthorizationMode(?int $storeId = null): string
    {
        if ($this->getValue(self::PAYMENT_ACTION, $storeId) === self::AUTHORIZE_CAPTURE) {
            return self::AUTHORIZATION_MODE_SALE;
        }

        $authorizationMode = (string) $this->getValue(self::AUTHORIZATION_MODE, $storeId);
        if ($authorizationMode === 'pre') {
            return self::AUTHORIZATION_MODE_PRE;
        }

        return self::AUTHORIZATION_MODE_FINAL;
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

    public function getOneyPaymentOption(?int $storeId = null): string
    {
        return (string) $this->getValue(self::ONEY_PAYMENT_OPTION, $storeId);
    }

    public function getAllowedAttempts(?int $storeId = null): int
    {
        return (int) $this->getValue(self::ALLOW_ATTEMPTS, $storeId);
    }

    public function getSessionTimeout(?int $storeId = null): int
    {
        return (int) $this->getValue(self::SESSION_TIMEOUT, $storeId);
    }

    public function getBankTransferMode(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::BANK_TRANSFER_MODE, $storeId);
    }

    public function isVaultActive(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::VAULT_PATH, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
