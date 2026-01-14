<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Ui\PaymentIconsProviderInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    public const HC_CODE = 'worldline_hosted_checkout';

    /**
     * @var string
     */
    public const HC_VAULT_CODE = 'worldline_hosted_checkout_vault';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentIconsProviderInterface
     */
    private $iconProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        PaymentIconsProviderInterface $iconProvider
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->iconProvider = $iconProvider;
        $this->storeManager = $storeManager;
    }

    public function getConfig(): array
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getStoreId();

            $result = [
                'payment' => [
                    self::HC_CODE => [
                        'isActive' => $this->config->isActive($storeId),
                        'icons' => $this->getIcons($storeId)
                    ]
                ]
            ];

            if ($this->config->isVaultActive($storeId)) {
                $result['payment'][self::HC_CODE]['hcVaultCode'] = self::HC_VAULT_CODE;
            }

            return $result;
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return [];
        }
    }

    public function getIcons(int $storeId): array
    {
        $quote = $this->checkoutSession->getQuote();
        $icons = $this->iconProvider->getIcons($storeId);

        $this->unsetUnavailableTypes($icons, $quote);

        return $icons;
    }

    private function unsetUnavailableTypes(array &$icons, Quote $quote): void
    {
        unset($icons[PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID]);

        if ($quote->getGrandTotal() < 0.00001) {
            unset($icons[PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID]);
        }
    }
}
